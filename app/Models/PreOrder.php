<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\InventorySearchService;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Builder;

class PreOrder extends Model
{
    use HasAuditTrail;

    /** Owner-facing stages (see stage()), key => label, in lifecycle order. */
    public const STAGES = [
        'pending_sourcing' => 'Pending Sourcing',
        'awaiting_arrival' => 'Sourced / Awaiting Arrival',
        'ready' => 'Ready for Pickup',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'expired' => 'Pickup expired',
    ];

    protected $fillable = [
        'customer_name',
        'customer_id',
        'inventory_item_id',
        'part_name',
        'model',
        'quantity',
        'down_payment',
        'status',
        'price',
        'total',
        'is_paid',
        'is_stocked',
        'sourced_at',
        'picked_up_at',
        'ready_notified_at',
        'pickup_expires_at',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_stocked' => 'boolean',
        'sourced_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'ready_notified_at' => 'datetime',
        'pickup_expires_at' => 'datetime',
    ];

    // Audit #12: no `protected $with` — views render the denormalized
    // `customer_name` column, not the relation, so this eager load was unused.

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Audit #10: the catalog item this pre-order was placed against (null for
    // custom/sourced parts). Used to restock accurately on completion.
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** Customer notification deep link: Pre-orders tab, this record. */
    public function customerLink(): string
    {
        return route('customer.dashboard', ['tab' => 'preorders']) . '#preorder-' . $this->id;
    }

    /** Owner notification deep link: Process Pre-orders, this record. */
    public function ownerLink(): string
    {
        return route('preorders.index') . '#preorder-' . $this->id;
    }

    /**
     * SYS4-03 B2b: owner search. Every term must match at least one of: pre-order number
     * (digits, optional leading "#" / zeros: "#0012" and "12" both mean id 12), customer name,
     * part name, motorcycle model. Text matching is literal and case-insensitive (Stage B1 helper).
     *
     * @param  array<int,string>  $terms
     */
    public function scopeAdminSearch(Builder $query, array $terms): Builder
    {
        foreach ($terms as $term) {
            $query->where(function (Builder $w) use ($term) {
                if (preg_match('/^#?0*(\d{1,9})$/', $term, $m)) {
                    $w->orWhere('pre_orders.id', (int) $m[1]);
                }
                foreach (['pre_orders.customer_name', 'pre_orders.part_name', 'pre_orders.model'] as $column) {
                    InventorySearchService::whereLike($w, $column, $term, 'contains', 'or');
                }
            });
        }

        return $query;
    }

    /**
     * Restrict to a displayed stage, mirroring stage() exactly (the stage is derived from
     * status + is_stocked + sourced_at, not stored). Unknown keys leave the query unchanged.
     */
    public function scopeInStage(Builder $query, ?string $stage): Builder
    {
        $terminal = ['cancelled', 'completed', 'expired'];
        $open = fn (Builder $q) => $q->where(fn (Builder $o) => $o->whereNull('pre_orders.status')->orWhereNotIn('pre_orders.status', $terminal));

        return match ($stage) {
            'cancelled', 'completed', 'expired' => $query->where('pre_orders.status', $stage),
            'ready' => $query->where($open)->where('pre_orders.is_stocked', true),
            'awaiting_arrival' => $query->where($open)->where(fn (Builder $q) => $q->whereNull('pre_orders.is_stocked')->orWhere('pre_orders.is_stocked', false))
                ->whereNotNull('pre_orders.sourced_at'),
            'pending_sourcing' => $query->where($open)->where(fn (Builder $q) => $q->whereNull('pre_orders.is_stocked')->orWhere('pre_orders.is_stocked', false))
                ->whereNull('pre_orders.sourced_at'),
            default => $query,
        };
    }

    /**
     * Owner-facing lifecycle stage derived from existing columns (statuses are
     * never rewritten). Legacy rows with NULL sourced_at are "pending_sourcing".
     *
     * @return array{key: string, label: string}
     */
    public function stage(): array
    {
        if ($this->status === 'cancelled') {
            return ['key' => 'cancelled', 'label' => 'Cancelled'];
        }
        if ($this->status === 'completed') {
            return ['key' => 'completed', 'label' => 'Completed'];
        }
        if ($this->status === 'expired') {
            return ['key' => 'expired', 'label' => 'Pickup expired'];
        }
        if ($this->is_stocked) {
            return ['key' => 'ready', 'label' => 'Ready for Pickup'];
        }
        if ($this->sourced_at) {
            return ['key' => 'awaiting_arrival', 'label' => 'Sourced / Awaiting Arrival'];
        }

        return ['key' => 'pending_sourcing', 'label' => 'Pending Sourcing'];
    }
}
