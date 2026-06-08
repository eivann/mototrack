<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasAuditTrail;

class DamagedItem extends Model
{
    use HasAuditTrail;

    protected $fillable = ['inventory_item_id', 'quantity', 'notes'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
