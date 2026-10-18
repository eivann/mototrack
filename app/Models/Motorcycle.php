<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Motorcycle extends Model
{
    protected $fillable = [
        'brand',
        'model',
        'year',
        'engine_type',
    ];

    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'part_compatibility')
                    ->withPivot('notes')
                    ->withTimestamps();
    }
}
