<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** RD2-R3: a customer's same-day repair / service appointment request. Pending is NOT an appointment. */
class RepairReservation extends Model
{
    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';
    // Terminal statuses introduced by R3 Revision 4 (logic lands in later phases; defined here so the
    // set is complete). ACTIVE — the statuses that hold a day slot — remains pending + accepted only.
    public const EXPIRED = 'expired';
    public const COMPLETED = 'completed';
    public const ACTIVE = [self::PENDING, self::ACCEPTED];

    public const LABELS = [
        self::PENDING => 'Awaiting owner approval',
        self::ACCEPTED => 'Accepted',
        self::REJECTED => 'Rejected',
        self::CANCELLED => 'Cancelled',
        self::EXPIRED => 'Expired',
        self::COMPLETED => 'Completed',
    ];

    protected $fillable = [
        'reference', 'customer_id', 'service_date', 'scheduled_at', 'check_in_deadline_at', 'approval_deadline_at',
        'service_type', 'bike_model', 'description',
        'status', 'active_day_key', 'terms_accepted_at', 'decided_by', 'decided_at', 'decision_reason',
        'cancelled_at', 'cancelled_by',
        'checked_in_at', 'checked_in_by', 'received_by', 'estimated_minutes', 'expected_end_at', 'ended_at',
    ];

    protected $casts = [
        'service_date' => 'date:Y-m-d',
        'scheduled_at' => 'datetime',
        'check_in_deadline_at' => 'datetime',
        'approval_deadline_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'decided_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'expected_end_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** At most one repair per reservation (unique repairs.reservation_id). */
    public function repair(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Repair::class, 'reservation_id');
    }

    /**
     * What the customer/owner should SEE. The stored status stays `accepted` through check-in and repair;
     * the stage comes from checked_in_at and the linked repair.
     */
    public function stageLabel(): string
    {
        if ($this->status === self::ACCEPTED) {
            $repair = $this->relationLoaded('repair') ? $this->repair : $this->repair()->first();
            if ($repair && $repair->isOngoing()) {
                return 'Repair in progress';
            }
            if ($this->checked_in_at) {
                return 'Checked in';
            }
        }

        return $this->label();
    }

    public function events(): HasMany
    {
        return $this->hasMany(RepairReservationEvent::class)->orderBy('id');
    }

    public function label(): string
    {
        return self::LABELS[$this->status] ?? $this->status;
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE, true);
    }

    public function serviceLabel(): string
    {
        return config('repair_reservations.service_types.'.$this->service_type, $this->service_type);
    }

    /** "Today, 9:30 AM" style time in the business timezone. */
    public function timeLabel(): string
    {
        return $this->scheduled_at->copy()->timezone(config('repair_reservations.timezone'))->format('g:i A');
    }

    public function dateLabel(): string
    {
        return $this->scheduled_at->copy()->timezone(config('repair_reservations.timezone'))->format('M d, Y');
    }
}
