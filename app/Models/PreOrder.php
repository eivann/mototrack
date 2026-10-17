<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditTrail;

class PreOrder extends Model
{
    use HasAuditTrail;
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
