<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SYS4-06 Stage D1: transaction header. One row per transaction; the `sales` rows are its
 * detail lines. Line status stays authoritative on `sales` (no header status here), and
 * total_amount is the ORIGINAL transaction total, never reduced by refunds.
 */
class SaleHeader extends Model
{
    public const TYPE_POS = 'pos';
    public const TYPE_ONLINE_RESERVATION = 'online_reservation';
    public const TYPE_REPAIR = 'repair';
    public const TYPE_PREORDER = 'preorder';
    public const TYPE_LEGACY = 'legacy';

    protected $fillable = [
        'transaction_reference',
        'customer_id',
        'shop_id',
        'transaction_type',
        'total_amount',
        'amount_tendered',
        'change_amount',
        'reserved_at',
        'expires_at',
        'payment_confirmed_at',
        'picked_up_at',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'payment_confirmed_at' => 'datetime',
        'picked_up_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(Sale::class, 'sale_header_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
