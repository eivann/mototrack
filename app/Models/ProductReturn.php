<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasAuditTrail;

class ProductReturn extends Model
{
    use HasAuditTrail;

    protected $fillable = [
        'sale_id',
        'inventory_item_id',
        'customer_id',
        'quantity',
        'refund_amount',
        'action_taken',
        'notes',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
