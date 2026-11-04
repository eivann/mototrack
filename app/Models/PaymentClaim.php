<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RD2-R2: a customer's claim that they paid a reservation / pre-order by GCash QR.
 * It records the claim and the owner's decision only; it never changes stock or fulfilment.
 * Not audited through HasAuditTrail on purpose, so payment references are never copied into audit rows.
 */
class PaymentClaim extends Model
{
    public const TYPE_RESERVATION = 'reservation';
    public const TYPE_PREORDER = 'preorder';
    public const TYPES = [self::TYPE_RESERVATION, self::TYPE_PREORDER];

    public const METHOD_GCASH_QR = 'gcash_qr';

    public const AWAITING = 'awaiting_payment';
    public const PENDING = 'pending_verification';
    public const VERIFIED = 'verified';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    /** States in which the claim still occupies the payable (unique "active" markers are set). */
    public const ACTIVE = [self::AWAITING, self::PENDING, self::VERIFIED];

    public const LABELS = [
        self::AWAITING => 'Awaiting payment',
        self::PENDING => 'Pending verification',
        self::VERIFIED => 'Payment verified',
        self::REJECTED => 'Payment rejected',
        self::CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'payable_type', 'payable_key', 'customer_id', 'method', 'status', 'expected_amount', 'currency',
        'declared_amount', 'reference_number', 'proof_path', 'active_reference_fingerprint',
        'active_proof_hash', 'active_payable_key', 'submitted_at', 'decided_at', 'decided_by',
        'verified_amount', 'decision_note', 'needs_owner_review', 'review_reason',
    ];

    /** Never serialised by accident (JSON, notifications, logs). */
    protected $hidden = ['reference_number', 'proof_path', 'active_reference_fingerprint', 'active_proof_hash'];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'declared_amount' => 'decimal:2',
        'verified_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
        'needs_owner_review' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function label(): string
    {
        return self::LABELS[$this->status] ?? $this->status;
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE, true);
    }

    /** Human reference for owner screens: "Reservation TRX-…" / "Pre-order #12". */
    public function payableLabel(): string
    {
        return $this->payable_type === self::TYPE_PREORDER
            ? 'Pre-order #'.$this->payable_key
            : 'Reservation '.$this->payable_key;
    }

    public function customerUrl(): string
    {
        return route('payments.show', ['type' => $this->payable_type, 'key' => $this->payable_key]);
    }
}
