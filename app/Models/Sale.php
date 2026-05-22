<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $with = ['item', 'customer'];

public function item()
{
    return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
}

public function customer()
{
    return $this->belongsTo(Customer::class);
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
];

protected $casts = [
    'payment_confirmed_at' => 'datetime',
];
}
