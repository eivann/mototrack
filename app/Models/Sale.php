<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
public function item()
{
    return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
}

protected $fillable = [
    'inventory_item_id',
    'quantity',
    'price',
    'total_price'
];
}
