<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditTrail;
use App\Enums\OrderStatus;

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
    'picked_up_at',
];

protected $casts = [
    'payment_confirmed_at' => 'datetime',
    'picked_up_at' => 'datetime',
];

// ── Pickup & Refund Eligibility ─────────────────────────

/**
 * Whether the customer has physically picked up this item.
 */
public function isPickedUp(): bool
{
    return !is_null($this->picked_up_at);
}

/**
 * Whether this sale is eligible for a refund request.
 * Audit Item #39: Refunds only allowed if NOT picked up yet.
 */
public function canBeRefunded(): bool
{
    return !$this->isPickedUp()
        && in_array($this->status, [
            OrderStatus::SALE_PENDING,
            OrderStatus::SALE_COMPLETED,
        ])
        && $this->refundRequests()
            ->whereNotIn('status', [OrderStatus::REFUND_REJECTED, OrderStatus::REFUND_CANCELLED])
            ->doesntExist();
}

/**
 * Mark this sale's item as physically picked up by the customer.
 */
public function markAsPickedUp(): self
{
    $this->update(['picked_up_at' => now()]);
    return $this;
}

/**
 * Get the human-readable status label.
 */
public function getStatusLabelAttribute(): string
{
    return OrderStatus::label($this->status);
}

/**
 * Get the CSS color for the status badge.
 */
public function getStatusColorAttribute(): string
{
    return OrderStatus::color($this->status);
}
}
