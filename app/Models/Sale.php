<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditTrail;
use App\Enums\OrderStatus;
use App\Services\InventorySearchService;
use Illuminate\Database\Eloquent\Builder;

class Sale extends Model
{
    use SoftDeletes, HasAuditTrail;

    // Audit #12: no `protected $with` — every view-facing query eager-loads
    // its relations explicitly (SaleController, CheckoutController, dashboards),
    // so auto-loading only wasted joins on aggregate/update/notification queries.

public function item()
{
    return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
}

public function customer()
{
    return $this->belongsTo(Customer::class);
}

public function header()
{
    return $this->belongsTo(SaleHeader::class, 'sale_header_id');
}

public function refundRequests()
{
    return $this->hasMany(RefundRequest::class);
}

public function productReturns()
{
    return $this->hasMany(ProductReturn::class);
}

protected $fillable = [
    'sale_header_id',
    'inventory_item_id',
    'customer_id',
    'quantity',
    'price',
    'total_price',
    'transaction_reference',
    'amount_tendered',
    'change',
    'status',
    'cancellation_reason',
    'picked_up_at',
    'payment_confirmed_at',
    'reserved_at',
    'expires_at',
];

protected $casts = [
    'payment_confirmed_at' => 'datetime',
    'picked_up_at' => 'datetime',
    'reserved_at' => 'datetime',
    'expires_at' => 'datetime',
];

// ── Owner reservation search (SYS4-03 B2a) ──────────────────

/**
 * Does ONE search term match this reservation line? Fields: transaction reference,
 * customer name / phone / email, reserved part name. Literal, case-insensitive
 * substring (Stage B1 escaping). Missing customer / item never errors: the
 * relation simply does not match.
 */
public function scopeMatchesReservationTerm(Builder $query, string $term): Builder
{
    return $query->where(function (Builder $w) use ($term) {
        InventorySearchService::whereLike($w, 'sales.transaction_reference', $term, 'contains', 'or');
        $w->orWhereHas('customer', function (Builder $c) use ($term) {
            $c->where(function (Builder $cc) use ($term) {
                foreach (['customers.name', 'customers.phone', 'customers.email'] as $column) {
                    InventorySearchService::whereLike($cc, $column, $term, 'contains', 'or');
                }
            });
        });
        $w->orWhereHas('item', function (Builder $i) use ($term) {
            // Grouped: the relation's join constraint must stay ANDed, never OR-ed with the LIKE.
            $i->where(function (Builder $ii) use ($term) {
                InventorySearchService::whereLike($ii, 'inventory_items.part_name', $term);
            });
        });
    });
}

/**
 * Reservation lines (restricted to $statuses) belonging to a reservation that matches
 * EVERY term. A term may match on any line of the reservation (or its customer), but
 * the lines returned are always ALL eligible lines of each matching reservation, never
 * only the matching line, so group totals and pickup lines stay complete.
 * Lines without a reference are their own one-line reservation.
 *
 * @param  array<int,string>  $terms
 * @param  array<int,string>  $statuses
 */
public function scopeReservationSearch(Builder $query, array $terms, array $statuses): Builder
{
    $query->whereIn('sales.status', $statuses);

    foreach ($terms as $term) {
        $query->where(function (Builder $w) use ($term, $statuses) {
            $w->whereIn(
                'sales.transaction_reference',
                static::query()->select('sales.transaction_reference')
                    ->whereNotNull('sales.transaction_reference')
                    ->whereIn('sales.status', $statuses)
                    ->matchesReservationTerm($term)
            )->orWhere(function (Builder $n) use ($term) {
                $n->whereNull('sales.transaction_reference')->matchesReservationTerm($term);
            });
        });
    }

    return $query;
}

// ── Receipt payment line ────────────────────────────────

/**
 * The line of a multi-line transaction that holds the cash tendered / change.
 *
 * Only one line carries the tender, but historically the pickup query was unordered, so
 * it is not always the lowest id. Pick the lowest-id line with a positive tender; with no
 * tender anywhere (pending, cancelled, single legacy line) fall back to the lowest-id line.
 * Pure: reads the given collection only, ignores status, runs no queries.
 *
 * @param  iterable<Sale>  $sales
 */
public static function paymentLine(iterable $sales): ?self
{
    $first = null;
    $holder = null;

    foreach ($sales as $sale) {
        if ($first === null || $sale->id < $first->id) {
            $first = $sale;
        }
        if ((float) $sale->amount_tendered > 0 && ($holder === null || $sale->id < $holder->id)) {
            $holder = $sale;
        }
    }

    return $holder ?? $first;
}

/**
 * SYS4-06 D4: tender / change to print for a transaction. A fully header-linked transaction uses its
 * SaleHeader (the transaction-level record) when it carries a positive tender; anything else
 * (legacy rows, an unpaid or zero-tender header, lines spread over several headers) falls back to
 * paymentLine(), so historical behaviour is unchanged. Returns an object exposing
 * `amount_tendered` and `change`, or null for no lines. Eager-load `header` to avoid an extra query.
 *
 * @param  iterable<Sale>  $sales
 */
public static function paymentFigures(iterable $sales): ?object
{
    $headerIds = [];
    $anchor = null;
    foreach ($sales as $sale) {
        $anchor ??= $sale;
        $headerIds[$sale->sale_header_id ?? 'none'] = true;
    }

    if ($anchor !== null && count($headerIds) === 1 && $anchor->sale_header_id !== null) {
        $header = $anchor->header;
        if ($header && (float) $header->amount_tendered > 0) {
            return (object) ['amount_tendered' => $header->amount_tendered, 'change' => $header->change_amount];
        }
    }

    return self::paymentLine($sales);
}

// ── Transaction resolution (header-aware, legacy-safe) ──────────────────

/** Same safeguard as sales:backfill-headers: lines of one checkout are created within seconds. */
public const MAX_LEGACY_CREATED_SPREAD_SECONDS = 120;

/**
 * Whether header-less lines sharing one reference can safely be shown as ONE transaction: a single
 * line always can; several need one consistent customer_id and shop_id and valid created_at values within the allowed spread
 * (fail closed otherwise).
 */
private static function legacyGroupIsCoherent(iterable $rows): bool
{
    $times = [];
    $customers = [];
    $shops = [];
    foreach ($rows as $r) {
        // NULL and non-NULL are distinct values here, so mixing them is inconsistent too.
        $customers[var_export($r->customer_id === null ? null : (int) $r->customer_id, true)] = true;
        $shops[var_export($r->shop_id === null ? null : (int) $r->shop_id, true)] = true;
        if (empty($r->created_at)) {
            return false;
        }
        try {
            $times[] = \Illuminate\Support\Carbon::parse($r->created_at)->getTimestamp();
        } catch (\Throwable) {
            return false;
        }
    }

    if (count($times) < 2) {
        return true;
    }

    return count($customers) === 1 && count($shops) === 1
        && (max($times) - min($times)) <= self::MAX_LEGACY_CREATED_SPREAD_SECONDS;
}

/**
 * All lines of the transaction the given anchor line belongs to, in id order, `item` and `header`
 * eager-loaded. A header-linked anchor resolves through sale_header_id (immune to reference reuse);
 * a legacy anchor with a reference resolves through that reference but only among UNLINKED rows, and
 * only if those rows are temporally coherent (otherwise an EMPTY collection: never merge unrelated
 * purchases); a legacy anchor without a (non-blank) reference is its own transaction.
 * $scope narrows every query (e.g. customer ownership).
 */
public static function transactionLines(self $anchor, ?callable $scope = null): \Illuminate\Database\Eloquent\Collection
{
    $query = static::with('item', 'header')->orderBy('id');
    $legacyGroup = false;

    if ($anchor->sale_header_id !== null) {
        $query->where('sale_header_id', $anchor->sale_header_id);
    } elseif (trim((string) $anchor->transaction_reference) !== '') {
        $query->where('transaction_reference', $anchor->transaction_reference)->whereNull('sale_header_id');
        $legacyGroup = true;
    } else {
        $query->whereKey($anchor->getKey());
    }

    if ($scope) {
        $scope($query);
    }

    $lines = $query->get();

    if ($legacyGroup && ! self::legacyGroupIsCoherent($lines)) {
        return static::newModelInstance()->newCollection();
    }

    return $lines;
}

/**
 * Resolve a transaction from the key shown to users: a sale transaction_reference, a header reference
 * (e.g. LEGACY-<id> for backfilled reference-less sales), or SALE-<id> for a reference-less sale not yet
 * backfilled. Unknown OR AMBIGUOUS keys return an empty collection: a key matching more than one linked
 * header, or both linked and unlinked rows, is never resolved by picking the lowest id. Ambiguity is
 * judged only among rows visible through $scope, so nothing about other customers is revealed.
 */
public static function transactionByKey(string $key, ?callable $scope = null): \Illuminate\Database\Eloquent\Collection
{
    $empty = fn () => static::newModelInstance()->newCollection();

    $candidates = static::query()->select(['id', 'sale_header_id', 'transaction_reference'])->orderBy('id')
        ->where(fn ($w) => $w->where('transaction_reference', $key)
            ->orWhereIn('sale_header_id', SaleHeader::query()->select('id')->where('transaction_reference', $key)));
    if ($scope) {
        $scope($candidates);
    }
    $candidates = $candidates->get();

    if ($candidates->isNotEmpty()) {
        $headerIds = $candidates->pluck('sale_header_id')->filter()->unique();
        $hasUnlinked = $candidates->contains(fn ($c) => $c->sale_header_id === null);

        if ($headerIds->count() > 1 || ($headerIds->isNotEmpty() && $hasUnlinked)) {
            return $empty();
        }

        return static::transactionLines($candidates->first(), $scope);
    }

    if (preg_match('/^SALE-(\d+)$/', $key, $m)) {
        $q = static::query()->select(['id', 'sale_header_id', 'transaction_reference'])
            ->whereKey((int) $m[1])->whereNull('sale_header_id')
            ->where(fn ($w) => $w->whereNull('transaction_reference')->orWhere('transaction_reference', ''));
        if ($scope) {
            $scope($q);
        }
        $anchor = $q->first();

        return $anchor ? static::transactionLines($anchor, $scope) : $empty();
    }

    return $empty();
}

// ── Pickup & Refund Eligibility ─────────────────────────

/**
 * Whether the customer has physically picked up this item.
 */
public function isPickedUp(): bool
{
    return !is_null($this->picked_up_at);
}

/**
 * Whether this sale is eligible for a refund request.
 * Audit Item #39: Refunds only allowed if NOT picked up yet.
 */
public function canBeRefunded(): bool
{
    return $this->status === OrderStatus::SALE_COMPLETED
        && $this->amount_tendered > 0
        && $this->picked_up_at === null
        && $this->refundRequests()
            ->whereNotIn('status', [OrderStatus::REFUND_REJECTED, OrderStatus::REFUND_CANCELLED])
            ->doesntExist();
}

/**
 * Mark this sale's item as physically picked up by the customer.
 */
public function markAsPickedUp(): self
{
    $this->update(['picked_up_at' => now()]);
    return $this;
}

/**
 * Get the human-readable status label.
 */
public function getStatusLabelAttribute(): string
{
    return OrderStatus::label($this->status);
}

/**
 * Get the CSS color for the status badge.
 */
public function getStatusColorAttribute(): string
{
    return OrderStatus::color($this->status);
}
}
