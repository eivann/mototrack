<?php

namespace App\Services;

use App\Models\Repair;
use App\Models\RepairReservation;
use App\Models\RepairReservationEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * RD2-R3 Phase 2: shop-wide SIMULTANEOUS repair capacity.
 *
 *  - Occupancy = ACCEPTED online reservations ([scheduled_at, +appointment_minutes)) plus ONGOING walk-in
 *    repairs ([started_at, started_at + estimate)), counted together against one configurable limit (3).
 *  - Intervals are half-open, so back-to-back jobs (one ends exactly when the next starts) never overlap.
 *  - A new job fits only if, at EVERY instant of its own interval, existing occupancy + 1 <= capacity
 *    (a real interval sweep, not a head-count per day or per slot).
 *  - An ongoing walk-in that has passed its estimated finish is treated as still occupying a bay,
 *    open-ended, until the owner marks it done. A legacy ongoing walk-in without started_at is treated
 *    the same way (from its last update, or now if that is in the future).
 *  - Walk-in start/done and reservation acceptance take the same per-day lock row
 *    (repair_reservation_days), so concurrent decisions cannot overbook.
 *
 * Intervals are [Carbon start, ?Carbon end]; a null end means "until the owner marks it done".
 *
 * Phase 3: ONE physical job is counted ONCE. An accepted reservation stays `accepted` while its linked repair
 * is ongoing; the reservation row then contributes the merged interval
 *     [min(scheduled_at, repair started_at), repair expected end)    (open-ended once overdue)
 * and the linked repair is skipped as a walk-in. Starting early re-checks capacity; starting late keeps the
 * reserved capacity from scheduled_at; a checked-in reservation that has not started holds its bay open-ended.
 * Completion sets repairs.completed_at and the reservation's ended_at/`completed` status in ONE transaction.
 */
class RepairCapacityService
{
    public function tz(): string
    {
        return (string) config('repair_reservations.timezone', 'Asia/Manila');
    }

    public function now(): Carbon
    {
        return Carbon::now($this->tz());
    }

    /** Same clamped limit the reservation service uses. */
    public function capacity(): int
    {
        return max(1, min(5, (int) config('repair_reservations.capacity', 3)));
    }

    public function appointmentMinutes(): int
    {
        return max(5, (int) config('repair_reservations.appointment_minutes', 60));
    }

    public function walkInMinutes(?int $requested = null): int
    {
        $default = (int) config('repair_reservations.walk_in_minutes', $this->appointmentMinutes());

        return max(5, min(1440, $requested ?: $default));
    }

    // ------------------------------------------------------------------ occupancy

    /**
     * Current occupancy for one service date, in ONE query: accepted reservations of that date (with their
     * linked repair, if any) plus every ongoing WALK-IN repair (ongoing repairs hold a bay whatever day they began).
     *
     * @return array<int, array{0: Carbon, 1: ?Carbon}>
     */
    public function occupancy(Carbon $date, ?int $exceptReservationId = null, ?int $exceptRepairId = null): array
    {
        $appTz = config('app.timezone');
        $now = $this->now();

        $reservations = DB::table('repair_reservations')
            ->leftJoin('repairs', 'repairs.reservation_id', '=', 'repair_reservations.id')
            ->where('repair_reservations.status', 'accepted')
            ->where('repair_reservations.service_date', $date->copy()->timezone($this->tz())->format('Y-m-d'))
            ->when($exceptReservationId, fn ($q) => $q->where('repair_reservations.id', '!=', $exceptReservationId))
            ->selectRaw("'r' as kind, repair_reservations.scheduled_at as s, repair_reservations.estimated_minutes as m, "
                .'repair_reservations.checked_in_at as u, repairs.started_at as rs, repairs.estimated_minutes as rm, repairs.status as rstatus');

        // Walk-ins only: a repair linked to a reservation is counted through that reservation (never twice).
        $repairs = DB::table('repairs')
            ->where('status', 'ongoing')
            ->whereNull('reservation_id')
            ->when($exceptRepairId, fn ($q) => $q->where('id', '!=', $exceptRepairId))
            ->selectRaw("'w' as kind, started_at as s, estimated_minutes as m, updated_at as u, NULL as rs, NULL as rm, NULL as rstatus");

        $out = [];
        foreach ($reservations->unionAll($repairs)->get() as $row) {
            if ($row->kind === 'r') {
                $scheduled = Carbon::parse($row->s, $appTz)->timezone($this->tz());
                // The RESERVED window is always the booked length; the repair's own estimate never shrinks it.
                $reservedEnd = $scheduled->copy()->addMinutes($this->appointmentMinutes());

                if ($row->rstatus === 'ongoing') {
                    // One job: from the earlier of booked/actual start. It holds the bay until the later of the booked
                    // window and the repair estimate; once the estimate is exceeded it is open-ended until marked done.
                    // (An early start therefore never releases the booked window while the job is still running.)
                    $rStart = $row->rs !== null ? Carbon::parse($row->rs, $appTz)->timezone($this->tz()) : $scheduled->copy();
                    $rEnd = $rStart->copy()->addMinutes($row->rm ? (int) $row->rm : $this->appointmentMinutes());
                    $out[] = [$scheduled->min($rStart), $rEnd->lte($now) ? null : $rEnd->max($reservedEnd)];
                } elseif ($row->u !== null && $reservedEnd->lte($now)) {
                    // Checked in but the repair has not started: keep the bay until it starts or is cancelled.
                    $out[] = [$scheduled, null];
                } else {
                    $out[] = [$scheduled, $reservedEnd];
                }
                continue;
            }

            // Walk-in. Legacy ongoing rows have no started_at: assume they began at their last update.
            $start = $row->s !== null
                ? Carbon::parse($row->s, $appTz)->timezone($this->tz())
                : Carbon::parse($row->u ?? $now, $appTz)->timezone($this->tz());
            if ($start->gt($now)) {
                $start = $now->copy();
            }
            $end = $start->copy()->addMinutes($this->walkInMinutes($row->m ? (int) $row->m : null));
            // Overran its estimate: keep holding the bay until the owner marks it done.
            $out[] = [$start, $end->lte($now) ? null : $end];
        }

        return $out;
    }

    /**
     * Pure interval check. True when a job on [$start, $end) can be added without ever exceeding
     * capacity, given existing [start, ?end) occupancy (null end = open-ended).
     *
     * @param  array<int, array{0: Carbon, 1: ?Carbon}>  $occupied
     */
    public function fits(Carbon $start, Carbon $end, array $occupied, ?int $capacity = null): bool
    {
        return $this->peak($start, $end, $occupied) + 1 <= ($capacity ?? $this->capacity());
    }

    /**
     * Highest number of existing jobs active at any instant of [$start, $end).
     *
     * @param  array<int, array{0: Carbon, 1: ?Carbon}>  $occupied
     */
    public function peak(Carbon $start, Carbon $end, array $occupied): int
    {
        $overlapping = array_values(array_filter($occupied,
            fn ($o) => $o[0]->lt($end) && ($o[1] === null || $o[1]->gt($start))));

        // Occupancy only increases at an interval start, so the peak within [start, end) is reached at
        // $start or at the start of one of the overlapping intervals.
        $points = [$start->copy()];
        foreach ($overlapping as $o) {
            if ($o[0]->gt($start)) {
                $points[] = $o[0];
            }
        }
        $max = 0;
        foreach ($points as $p) {
            $active = 0;
            foreach ($overlapping as $o) {
                if ($o[0]->lte($p) && ($o[1] === null || $o[1]->gt($p))) {
                    $active++;
                }
            }
            $max = max($max, $active);
        }

        return $max;
    }

    /** True when more jobs overlap right now (or within the next appointment length) than the limit allows. */
    public function overCapacity(): bool
    {
        $now = $this->now();

        return $this->peak($now, $now->copy()->addMinutes($this->appointmentMinutes()), $this->occupancy($now)) > $this->capacity();
    }

    /**
     * Accepted reservations that have NOT started a repair yet but no longer fit because jobs ahead of them
     * overran (or were started into their window). For a visible warning only; nothing is cancelled.
     *
     * @param  iterable<RepairReservation>  $reservations
     * @return array<int, true> reservation id => conflict
     */
    public function reservationConflicts(iterable $reservations): array
    {
        $out = [];
        foreach ($reservations as $r) {
            if ($r->status === RepairReservation::ACCEPTED && $r->repair === null && ! $this->reservationFits($r->scheduled_at, $r->id)) {
                $out[$r->id] = true;
            }
        }

        return $out;
    }

    /** Would an online reservation starting at $start fit (excluding itself, if already stored)? */
    public function reservationFits(Carbon $start, ?int $exceptReservationId = null): bool
    {
        $start = $start->copy()->timezone($this->tz());

        return $this->fits($start, $start->copy()->addMinutes($this->appointmentMinutes()),
            $this->occupancy($start, $exceptReservationId));
    }

    /**
     * Which of today's slots still have room, from ONE occupancy query.
     *
     * @param  array<int, Carbon>  $slots
     * @return array<string, bool> 'H:i' => has room
     */
    public function slotRoom(array $slots, Carbon $day): array
    {
        $occupied = $this->occupancy($day);
        $out = [];
        foreach ($slots as $slot) {
            $out[$slot->format('H:i')] = $this->fits($slot, $slot->copy()->addMinutes($this->appointmentMinutes()), $occupied);
        }

        return $out;
    }

    // ------------------------------------------------------------------ walk-in start / done

    /** Run $work inside a transaction that holds the per-day lock shared with reservation acceptance. */
    public function withDayLock(Carbon $date, callable $work): mixed
    {
        $day = $date->copy()->timezone($this->tz())->format('Y-m-d');

        return DB::transaction(function () use ($day, $work) {
            DB::table('repair_reservation_days')->insertOrIgnore(['service_date' => $day]);
            DB::table('repair_reservation_days')->where('service_date', $day)->lockForUpdate()->first();

            return $work();
        });
    }

    /**
     * Mark a walk-in repair ongoing from now, or refuse when it would be the 4th overlapping job
     * (including a conflict with an accepted booking later in its estimated window).
     * Already ongoing: nothing changes. A repair linked to a reservation is never started from here.
     *
     * @throws RepairCapacityException
     */
    public function startWalkIn(Repair $repair, ?int $estimatedMinutes = null): Repair
    {
        return $this->withDayLock($this->now(), function () use ($repair, $estimatedMinutes) {
            $locked = Repair::query()->lockForUpdate()->findOrFail($repair->id);
            if ($locked->status === 'ongoing') {
                return $locked;
            }
            if ($locked->reservation_id) {
                throw new RepairCapacityException($locked->isFinished()
                    ? 'This repair is completed and linked to an online reservation, so it cannot be reopened.'
                    : 'This repair belongs to an online reservation. Use Start repair on the reservation.');
            }

            $now = $this->now();
            $minutes = $this->walkInMinutes($estimatedMinutes);
            if (! $this->fits($now, $now->copy()->addMinutes($minutes), $this->occupancy($now, null, $locked->id))) {
                throw new RepairCapacityException(
                    'The shop already has '.$this->capacity().' repairs overlapping that time (online bookings and ongoing jobs together). '
                    .'Finish one first, or start this job later.');
            }

            $locked->forceFill([
                'status' => 'ongoing',
                'started_at' => $now->copy()->timezone(config('app.timezone')),
                'estimated_minutes' => $minutes,
                'completed_at' => null,
            ])->save();

            return $locked;
        });
    }

    /**
     * Mark a repair done (frees its bay) under the same day lock. Idempotent. A repair linked to a reservation
     * completes that reservation in the SAME transaction; the reservation is then available as the
     * `justCompletedReservation` relation so the caller can notify after commit.
     */
    public function finishWalkIn(Repair $repair, string $status = 'done'): Repair
    {
        return $this->withDayLock($this->now(), function () use ($repair, $status) {
            $locked = Repair::query()->lockForUpdate()->findOrFail($repair->id);
            $wasDone = $locked->isFinished();
            if (! $wasDone) {
                $locked->status = $status;
            }
            if ($locked->completed_at === null) {
                $locked->completed_at = now();
            }
            $locked->save();

            if ($locked->reservation_id) {
                $reservation = RepairReservation::query()->lockForUpdate()->find($locked->reservation_id);
                if ($reservation && $reservation->status === RepairReservation::ACCEPTED) {
                    $reservation->update(['status' => RepairReservation::COMPLETED, 'ended_at' => $locked->completed_at, 'active_day_key' => null]);
                    $this->event($reservation, RepairReservation::ACCEPTED, RepairReservation::COMPLETED, 'owner', auth()->id(), 'Repair completed');
                    $locked->setRelation('justCompletedReservation', $reservation);
                }
            }

            return $locked;
        });
    }

    /** Move a repair back to pending (releases any bay it held). Refused for a repair linked to a reservation. */
    public function releaseWalkIn(Repair $repair): Repair
    {
        return $this->withDayLock($this->now(), function () use ($repair) {
            $locked = Repair::query()->lockForUpdate()->findOrFail($repair->id);
            if ($locked->reservation_id) {
                throw new RepairCapacityException('This repair belongs to an online reservation, so it cannot be returned to pending. Mark it Done when finished, or extend the estimate.');
            }
            $locked->forceFill(['status' => 'pending', 'started_at' => null, 'estimated_minutes' => null, 'completed_at' => null])->save();

            return $locked;
        });
    }

    // ------------------------------------------------------------------ Phase 3: reservation -> repair, extension

    /**
     * Owner "Start repair" for a CHECKED-IN accepted reservation: creates the single linked repair (ongoing) in
     * the same transaction and day lock as every other capacity decision. The reservation stays `accepted`.
     * Early start (before scheduled_at) is re-checked against capacity and refused if it would be a 4th job;
     * a late start needs no check (the reserved capacity was never released). Repeats change nothing.
     *
     * @return array{repair: Repair, changed: bool}
     *
     * @throws RepairReservationException|RepairCapacityException
     */
    public function startReservationRepair(RepairReservation $reservation, User $owner, ?int $minutes = null): array
    {
        if ($owner->role !== 'owner') {
            throw new RepairReservationException('Only the owner can start a repair.', 403);
        }

        return $this->withDayLock($reservation->service_date, function () use ($reservation, $owner, $minutes) {
            $locked = RepairReservation::query()->lockForUpdate()->findOrFail($reservation->id);

            $existing = Repair::query()->where('reservation_id', $locked->id)->first();
            if ($existing) {
                return ['repair' => $existing, 'changed' => false];
            }
            if ($locked->status !== RepairReservation::ACCEPTED) {
                throw new RepairReservationException("Only an accepted appointment can be started. This one is ".strtolower($locked->label()).'.', 409);
            }
            if ($locked->checked_in_at === null) {
                throw new RepairReservationException('Record the check-in of this customer before starting the repair.', 409);
            }

            $now = $this->now();
            $scheduled = $locked->scheduled_at->copy()->timezone($this->tz());
            $length = max(5, min(1440, (int) ($minutes ?: $locked->estimated_minutes ?: $this->appointmentMinutes())));

            // Early start: the job will hold [now, later of its estimate and the booked window), so check all of it.
            $holdEnd = $now->copy()->addMinutes($length)->max($scheduled->copy()->addMinutes($this->appointmentMinutes()));
            if ($now->lt($scheduled) && ! $this->fits($now, $holdEnd, $this->occupancy($now, $locked->id))) {
                throw new RepairCapacityException('Starting now would put '.($this->capacity() + 1).' repairs in the shop at once. Wait for a bay to free up or until the booked time.');
            }

            $repair = Repair::create([
                'customer_id' => $locked->customer_id,
                'customer_name' => $locked->customer?->name ?? 'Customer',
                'bike_model' => $locked->bike_model,
                'issue' => trim($locked->serviceLabel().': '.$locked->description),
                'cost' => null,
                'status' => 'ongoing',
                'is_paid' => false,
                'started_at' => $now->copy()->timezone(config('app.timezone')),
                'estimated_minutes' => $length,
                'reservation_id' => $locked->id,
            ]);
            $locked->update(['estimated_minutes' => $length, 'expected_end_at' => $now->copy()->addMinutes($length)->timezone(config('app.timezone'))]);
            $this->event($locked, RepairReservation::ACCEPTED, RepairReservation::ACCEPTED, 'owner', $owner->id, 'Repair started');

            return ['repair' => $repair, 'changed' => true];
        });
    }

    /**
     * Add time to an ongoing repair. A physically unfinished job keeps its bay, so extending is never refused;
     * if the added time overlaps other jobs beyond the limit a WARNING is returned. An overdue job is extended
     * from now; a legacy job without a start time is stamped as starting now.
     *
     * @return array{repair: Repair, warning: ?string}
     *
     * @throws RepairCapacityException
     */
    public function extend(Repair $repair, int $extraMinutes): array
    {
        if ($extraMinutes < 5 || $extraMinutes > 480) {
            throw new RepairCapacityException('Choose between 5 and 480 extra minutes.');
        }

        return $this->withDayLock($this->now(), function () use ($repair, $extraMinutes) {
            $locked = Repair::query()->lockForUpdate()->findOrFail($repair->id);
            if (! $locked->isOngoing()) {
                throw new RepairCapacityException('Only a repair that is in progress can be extended.');
            }

            $now = $this->now();
            $legacy = $locked->started_at === null;
            if ($legacy) {
                $locked->started_at = $now->copy()->timezone(config('app.timezone'));   // legacy: the owner confirms it is still running
            }
            $started = $locked->started_at->copy()->timezone($this->tz());
            $oldEnd = $legacy ? $now->copy() : $started->copy()->addMinutes((int) ($locked->estimated_minutes ?: $this->walkInMinutes()));
            $base = $oldEnd->gt($now) ? $oldEnd : $now->copy();
            $newEnd = $base->copy()->addMinutes($extraMinutes);
            $locked->estimated_minutes = max(5, (int) $started->diffInMinutes($newEnd));
            $locked->save();

            if ($locked->reservation_id) {
                RepairReservation::query()->whereKey($locked->reservation_id)->update([
                    'estimated_minutes' => $locked->estimated_minutes,
                    'expected_end_at' => $newEnd->copy()->timezone(config('app.timezone')),
                ]);
            }

            $others = $this->occupancy($now, $locked->reservation_id, $locked->reservation_id ? null : $locked->id);
            $warning = $this->peak($base, $newEnd, $others) + 1 > $this->capacity()
                ? 'Extended to '.$newEnd->format('g:i A').', but that overlaps other repairs or bookings beyond the limit of '.$this->capacity().'. Review the schedule.'
                : null;

            return ['repair' => $locked, 'warning' => $warning];
        });
    }

    private function event(RepairReservation $r, ?string $from, string $to, string $role, ?int $actorId, ?string $note): void
    {
        RepairReservationEvent::create([
            'repair_reservation_id' => $r->id, 'from_status' => $from, 'to_status' => $to,
            'actor_role' => $role, 'actor_id' => $actorId, 'note' => $note, 'created_at' => now(),
        ]);
    }
}
