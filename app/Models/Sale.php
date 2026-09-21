<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditTrail;

class Sale extends Model
{
    use SoftDeletes, HasAuditTrail;

    // Audit #12: no `protected $with` — every view-facing query eager-loads
    // its relations explicitly (SaleController, CheckoutController, dashboards),
    // so auto-loading only wasted joins on aggregate/update/notification queries.

public function item()
{
    return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
}

public function customer()
{
    return $this->belongsTo(Customer::class);
}

public function refundRequests()
{
    return $this->hasMany(RefundRequest::class);
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
    'status',
    'cancellation_reason',
];

protected $casts = [
    'payment_confirmed_at' => 'datetime',
];
}
