<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
   protected $fillable = [
    'customer_name',
    'customer_id',
    'bike_model',
    'issue',
    'status',
    'cost',
    'is_paid',
    'started_at',
    'estimated_minutes',
    'completed_at',
    'reservation_id',
];

protected $casts = [
    'started_at' => 'datetime',
    'completed_at' => 'datetime',
];

public function customer()
{
    return $this->belongsTo(Customer::class);
}

/** RD2-R3 Phase 3: the online reservation this repair was started from (null for a plain walk-in). */
public function reservation()
{
    return $this->belongsTo(RepairReservation::class, 'reservation_id');
}

/** Derived, never stored: started_at + estimate (Phase 2 default when the estimate is missing). */
public function expectedEndAt(): ?\Illuminate\Support\Carbon
{
    if (! $this->started_at) {
        return null;
    }

    return $this->started_at->copy()->addMinutes((int) ($this->estimated_minutes ?: config('repair_reservations.walk_in_minutes', 60)));
}

public function isOngoing(): bool
{
    return $this->status === 'ongoing';
}

public function isFinished(): bool
{
    return in_array($this->status, ['done', 'completed'], true);
}

/** Ongoing past its estimated finish: it keeps holding a bay until the owner marks it done. */
public function isOverdue(): bool
{
    $end = $this->expectedEndAt();

    return $this->isOngoing() && $end !== null && $end->lte(now());
}

/** Ongoing record from before Phase 2: no start timestamp, so its bay time is only an assumption. */
public function isLegacyOngoing(): bool
{
    return $this->isOngoing() && $this->started_at === null;
}
}
