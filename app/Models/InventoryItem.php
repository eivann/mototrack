<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $table = 'inventory_items';

    public $timestamps = false; // IMPORTANT (no created_at, updated_at)

    protected $fillable = [
        'category',
        'part_name',
        'brand',
        'model',
        'quantity',
        'notes',
        'price',
        'low_stock_threshold',
        'low_stock_notified'
    ];

    public function sales()
{
    return $this->hasMany(Sale::class);
}
}