<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\OrderStatus;

class RefundRequest extends Model
{
    protected $fillable = [
        'sale_id',
        'customer_id',
        'reason',
        'details',
        'photo_path',
        'status',
        'admin_notes',
        'refund_method',   // 'digital' or 'on_site'
        'qr_code_path',    // QR code image for digital refunds
        'pickup_time',     // Scheduled time for on-site refund pickups
    ];

    protected $casts = [
        'pickup_time' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // ── Helpers ──────────────────────────────────────────────

    /**
     * Whether this is a digital refund (customer uploaded QR code).
     */
    public function isDigital(): bool
    {
        return $this->refund_method === OrderStatus::METHOD_DIGITAL;
    }

    /**
     * Whether this is an on-site refund (customer picks up in person).
     */
    public function isOnSite(): bool
    {
        return $this->refund_method === OrderStatus::METHOD_ON_SITE;
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

