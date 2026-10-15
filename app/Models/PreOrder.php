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
        'picked_up_at',
        'ready_notified_at',
        'pickup_expires_at',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_stocked' => 'boolean',
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
}
