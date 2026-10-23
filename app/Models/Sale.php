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

public function refundRequests()
{
    return $this->hasMany(RefundRequest::class);
}

public function productReturns()
{
    return $this->hasMany(ProductReturn::class);
}

protected $fillable = [
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
