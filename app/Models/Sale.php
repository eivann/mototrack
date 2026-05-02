<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

public function item()
{
    return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
}

protected $fillable = [
    'inventory_item_id',
    'quantity',
    'price',
    'total_price',
    'transaction_reference',
    'amount_tendered',
    'change'
];
}
