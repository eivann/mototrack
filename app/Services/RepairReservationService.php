<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Repair;
use App\Models\RepairReservation;
use App\Models\RepairReservationEvent;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * RD2-R3: same-day repair / service appointment requests.
 *
 *  - A request starts PENDING and is never an appointment until the owner accepts it.
 *  - Capacity (configurable, see config/repair_reservations.php) is consumed by ACCEPTED requests only and
 *    is re-checked under a per-day lock when the owner accepts, so concurrent acceptances cannot overbook.
 *  - Every transition runs in a transaction on a locked row, is idempotent for a repeated identical
 *    decision, refuses conflicting ones, and writes one history event.
 *  - Notifications are sent only when a transition really happened (no duplicates on repeats).
 *  - Automatic cancellation is infrastructure only and stays inert until the owner confirms its rule.
 */
class RepairReservationService
{
    public function __construct(private NotificationDispatcher $notifier)
    {
    }

    // ------------------------------------------------------------------ configuration helpers

    public function tz(): string
    {
        return (string) config('repair_reservations.timezone', 'Asia/Manila');
    }

    public function now(): Carbon
    {
        return Carbon::now($this->tz());
    }

    /** Configured capacity, clamped to the 1–5 range the shop described. */
    public function capacity(): int
    {
        return max(1, min(5, (int) config('repair_reservations.capacity', 3)));
    }

    public function capacityUnit(): string
    {
        $unit = config('repair_reservations.capacity_unit', 'per_day');

        return in_array($unit, ['per_day', 'per_slot', 'simultaneous'], true) ? $unit : 'per_day';
    }

    /** @return array<int, Carbon> start times (business timezone) from opening until, not including, closing */
    public function slotStarts(?Carbon $day = null): array
    {
        $day = ($day ?? $this->now())->copy()->timezone($this->tz());
        $date = $day->format('Y-m-d');
        $step = max(5, (int) config('repair_reservations.slot_minutes', 30));
        $start = Carbon::createFromFormat('Y-m-d H:i', $date.' '.config('repair_reservations.open', '07:30'), $this->tz());
        $lastStart = Carbon::createFromFormat('Y-m-d H:i', $date.' '.config('repair_reservations.last_start', '17:00'), $this->tz());

        // The last start is INCLUSIVE: 07:30 … 17:00 gives 20 possible starts at a 30-minute step.
        $slots = [];
        for ($t = $start->copy(); $t->lte($lastStart); $t->addMinutes($step)) {
            $slots[] = $t->copy();
        }

        return $slots;
    }

    // ------------------------------------------------------------------ deadlines (check-in & approval)

    /** Minutes before the start by which the customer must physically check in (default 30). */
    public function checkInMinutes(): int
    {
        return max(0, (int) config('repair_reservations.check_in.minutes_before', 30));
    }

    /** Buffer (minutes) before the check-in deadline by which acceptance/requests must happen (default 0, provisional). */
    public function approvalBufferMinutes(): int
    {
        return max(0, (int) config('repair_reservations.approval.buffer_minutes', 0));
    }

    /** Check-in deadline for a start time: start − check-in minutes. Check-in AT this instant is valid. */
    public function checkInDeadlineFor(Carbon $scheduled): Carbon
    {
        return $scheduled->copy()->subMinutes($this->checkInMinutes());
    }

    /** Approval deadline for a start time: check-in deadline − buffer. Requests/acceptance must be STRICTLY before it. */
    public function approvalDeadlineFor(Carbon $scheduled): Carbon
    {
        return $this->checkInDeadlineFor($scheduled)->subMinutes($this->approvalBufferMinutes());
    }

    /** Slots the customer can pick right now: today only, strictly in the future, with capacity left. */
    public function availableSlots(): array
    {
        $now = $this->now();
        $slots = $this->slotStarts($now);

        // One query for the whole day instead of one capacity query per slot (RD2-R4 measured 17 queries here).
        $unit = $this->capacityUnit();
        $accepted = $unit === 'simultaneous' ? [] : RepairReservation::query()->where('status', RepairReservation::ACCEPTED)
            ->where('service_date', $now->format('Y-m-d'))->pluck('scheduled_at')
            ->map(fn ($t) => Carbon::parse($t)->timestamp)->all();
        $capacity = $this->capacity();
        $duration = $this->duration() * 60;

        // Simultaneous mode also counts ongoing walk-ins, via the shared capacity service (still one query).
        $room = $unit === 'simultaneous' ? app(RepairCapacityService::class)->slotRoom($slots, $now) : [];

        // Owner blackouts (Phase 4): one query for the day, the same overlap rule as create() and accept().
        $blackouts = app(RepairBlackoutService::class);
        $dayBlackouts = $blackouts->forDay($now);

        $out = [];
        foreach ($slots as $slot) {
            // Bookable only while strictly before the approval deadline (start − check-in − buffer).
            $bookable = $now->lt($this->approvalDeadlineFor($slot));
            $blocked = $blackouts->blockingIn($dayBlackouts, $slot) !== null;
            $ts = $slot->timestamp;
            $taken = match ($unit) {
                'per_slot' => count(array_filter($accepted, fn ($a) => $a === $ts)),
                'simultaneous' => count(array_filter($accepted, fn ($a) => $a > $ts - $duration && $a < $ts + $duration)),
                default => count($accepted),
            };
            $out[] = [
                'value' => $slot->format('H:i'),
                'label' => $slot->format('g:i A'),
                'available' => $bookable && ! $blocked && ($unit === 'simultaneous' ? ($room[$slot->format('H:i')] ?? false) : $taken < $capacity),
                'past' => ! $bookable,
                'blocked' => $blocked,
            ];
        }

        return $out;
    }

    /** Accepted requests that compete with a request starting at $at under the configured capacity unit. */
    public function acceptedCount(Carbon $at, ?int $exceptId = null): int
    {
        $q = RepairReservation::query()->where('status', RepairReservation::ACCEPTED)
            ->when($exceptId, fn ($w) => $w->where('id', '!=', $exceptId));

        $q = match ($this->capacityUnit()) {
            'per_slot' => $q->where('scheduled_at', $at->copy()->timezone(config('app.timezone'))),
            'simultaneous' => $q->where('scheduled_at', '>', $at->copy()->subMinutes($this->duration())->timezone(config('app.timezone')))
                ->where('scheduled_at', '<', $at->copy()->addMinutes($this->duration())->timezone(config('app.timezone'))),
            default => $q->where('service_date', $at->copy()->timezone($this->tz())->format('Y-m-d')),
        };

        return $q->count();
    }

    public function isFull(Carbon $at, ?int $exceptId = null): bool
    {
        // RD2-R3 Phase 2: shop-wide simultaneous capacity (accepted bookings + ongoing walk-ins, interval overlap).
        if ($this->capacityUnit() === 'simultaneous') {
            return ! app(RepairCapacityService::class)->reservationFits($at, $exceptId);
        }

        return $this->acceptedCount($at, $exceptId) >= $this->capacity();
    }

    private function duration(): int
    {
        return max(5, (int) config('repair_reservations.appointment_minutes', 60));
    }

    // ------------------------------------------------------------------ customer: create

    /**
     * @param  array{appointment_date:string, appointment_time:string, service_type:string, bike_model:string, description:string, accept_terms:mixed}  $data
     */
    public function create(Customer $customer, array $data): RepairReservation
    {
        $now = $this->now();
        if (($data['appointment_date'] ?? null) !== $now->format('Y-m-d')) {
            throw new RepairReservationException('Appointments can only be requested for today.');
        }
        $slot = collect($this->slotStarts($now))->first(fn (Carbon $s) => $s->format('H:i') === ($data['appointment_time'] ?? null));
        if (! $slot) {
            throw new RepairReservationException('Choose a time between '.Carbon::createFromFormat('H:i', config('repair_reservations.open'))->format('g:i A')
                .' and '.Carbon::createFromFormat('H:i', config('repair_reservations.last_start'))->format('g:i A').'.');
        }
        if ($now->gte($this->approvalDeadlineFor($slot))) {
            $lead = $this->checkInMinutes() + $this->approvalBufferMinutes();
            throw new RepairReservationException('That time can no longer be requested today. Requests must be sent more than '.$lead.' minutes before the start time. Please choose a later time.');
        }
        if (! array_key_exists($data['service_type'] ?? '', config('repair_reservations.service_types'))) {
            throw new RepairReservationException('Choose a valid service type.');
        }
        if (empty($data['accept_terms'])) {
            throw new RepairReservationException('Please confirm that you understand this is a request subject to owner approval.');
        }
        if (app(RepairBlackoutService::class)->firstBlocking($slot)) {
            // Customer-facing wording: the owner's reason is private.
            throw new RepairReservationException('The shop is not taking appointments at that time. Please choose another time.');
        }
        if ($this->isFull($slot)) {
            throw new RepairReservationException('The shop is fully booked for that time. Please choose another time or visit the shop.');
        }

        $reservation = null;
        try {
            $reservation = DB::transaction(function () use ($customer, $data, $slot, $now) {
                $r = RepairReservation::create([
                    'reference' => $this->newReference(),
                    'customer_id' => $customer->id,
                    'service_date' => $now->format('Y-m-d'),
                    'scheduled_at' => $slot->copy()->timezone(config('app.timezone')),
                    // Both deadlines are stored once, at request creation, so expiry and the acceptance
                    // cutoff use a fixed boundary that a later config change can never move.
                    'check_in_deadline_at' => $this->checkInDeadlineFor($slot)->timezone(config('app.timezone')),
                    'approval_deadline_at' => $this->approvalDeadlineFor($slot)->timezone(config('app.timezone')),
                    'service_type' => $data['service_type'],
                    'bike_model' => trim($data['bike_model']),
                    'description' => trim($data['description']),
                    'status' => RepairReservation::PENDING,
                    'active_day_key' => $customer->id.':'.$now->format('Y-m-d'),
                    'terms_accepted_at' => now(),
                ]);
                $this->event($r, null, RepairReservation::PENDING, 'customer', $customer->user_id, 'Request submitted');

                return $r;
            });
        } catch (UniqueConstraintViolationException) {
            throw new RepairReservationException('You already have an active service request for today. Cancel it first if you want to change the time.', 409);
        }

        $this->tell($customer->user, 'Service request received',
            "Request {$reservation->reference} for today at {$reservation->timeLabel()} was received. It is NOT confirmed until the shop owner accepts it.",
            route('repair-reservations.show', $reservation->reference));
        $this->tell(User::owners(), 'New service request',
            "{$reservation->reference} — {$reservation->serviceLabel()} today at {$reservation->timeLabel()} is waiting for your decision.",
            route('owner.repair-reservations.index'));

        return $reservation;
    }

    // ------------------------------------------------------------------ owner: decisions

    /** @return array{reservation: RepairReservation, changed: bool} */
    public function accept(RepairReservation $reservation, User $owner): array
    {
        $this->assertOwner($owner);
        $date = $reservation->service_date->format('Y-m-d');

        $result = DB::transaction(function () use ($reservation, $owner, $date) {
            // Serialise every acceptance for this service date, then re-read the row.
            DB::table('repair_reservation_days')->insertOrIgnore(['service_date' => $date]);
            DB::table('repair_reservation_days')->where('service_date', $date)->lockForUpdate()->first();
            $locked = RepairReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === RepairReservation::ACCEPTED) {
                return [$locked, false];
            }
            if ($locked->status !== RepairReservation::PENDING) {
                throw new RepairReservationException('This request is already '.strtolower($locked->label()).' and cannot be accepted.', 409);
            }
            // Acceptance must be STRICTLY before the stored approval deadline (start − check-in − buffer).
            $approvalDeadline = $locked->approval_deadline_at
                ?? $this->approvalDeadlineFor($locked->scheduled_at->copy()->timezone($this->tz()));
            if (now()->gte($approvalDeadline)) {
                throw new RepairReservationException('The approval deadline for this request has passed. Reject this request instead.', 409);
            }
            // Phase 4: rechecked here, under the day lock, so a stale page cannot accept into a blackout created since.
            if ($blackout = app(RepairBlackoutService::class)->firstBlocking($locked->scheduled_at)) {
                throw new RepairReservationException('This time is blacked out ('.$blackout->label().($blackout->reason ? ': '.$blackout->reason : '')
                    .'). It cannot be accepted. Reject the request with a reason instead.', 409);
            }
            if ($this->isFull($locked->scheduled_at, $locked->id)) {
                throw new RepairReservationException('Capacity is full ('.$this->capacity().' accepted, '.str_replace('_', ' ', $this->capacityUnit()).'). Reject this request or cancel another appointment first.', 409);
            }

            $from = $locked->status;
            $locked->update(['status' => RepairReservation::ACCEPTED, 'decided_by' => $owner->id, 'decided_at' => now(), 'decision_reason' => null]);
            $this->event($locked, $from, RepairReservation::ACCEPTED, 'owner', $owner->id, null);

            return [$locked, true];
        });

        [$locked, $changed] = $result;
        if ($changed) {
            $this->tell($locked->customer?->user, 'Service appointment accepted',
                "{$locked->reference}: your appointment today at {$locked->timeLabel()} is accepted.",
                route('repair-reservations.show', $locked->reference));
        }

        return ['reservation' => $locked, 'changed' => $changed];
    }

    /** @return array{reservation: RepairReservation, changed: bool} */
    public function reject(RepairReservation $reservation, User $owner, string $reason): array
    {
        $this->assertOwner($owner);
        $reason = trim($reason);
        if ($reason === '') {
            throw new RepairReservationException('Give the customer a short reason for rejecting.');
        }

        [$locked, $changed] = DB::transaction(function () use ($reservation, $owner, $reason) {
            $locked = RepairReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === RepairReservation::REJECTED) {
                return [$locked, false];
            }
            if ($locked->status !== RepairReservation::PENDING) {
                throw new RepairReservationException('Only a pending request can be rejected. This one is '.strtolower($locked->label()).'.', 409);
            }
            $locked->update(['status' => RepairReservation::REJECTED, 'active_day_key' => null, 'decided_by' => $owner->id,
                'decided_at' => now(), 'decision_reason' => Str::limit($reason, 250, '')]);
            $this->event($locked, RepairReservation::PENDING, RepairReservation::REJECTED, 'owner', $owner->id, Str::limit($reason, 250, ''));

            return [$locked, true];
        });

        if ($changed) {
            $this->tell($locked->customer?->user, 'Service request not accepted',
                "{$locked->reference}: the shop could not accept your request. Reason: {$locked->decision_reason}",
                route('repair-reservations.show', $locked->reference));
        }

        return ['reservation' => $locked, 'changed' => $changed];
    }

    /**
     * Cancel an active request. The customer may cancel their own; the owner must give a reason.
     *
     * @return array{reservation: RepairReservation, changed: bool}
     */
    public function cancel(RepairReservation $reservation, User $actor, ?string $reason = null): array
    {
        $role = $actor->role === 'owner' ? 'owner' : 'customer';
        $reason = $reason !== null ? trim($reason) : null;
        if ($role === 'owner' && ($reason === null || $reason === '')) {
            throw new RepairReservationException('Give the customer a short reason for cancelling.');
        }

        [$locked, $changed] = DB::transaction(function () use ($reservation, $actor, $role, $reason) {
            $locked = RepairReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            if ($role === 'customer' && $locked->customer?->user_id !== $actor->id) {
                throw new RepairReservationException('Request not found.', 404);
            }
            if ($locked->status === RepairReservation::CANCELLED) {
                return [$locked, false];
            }
            if (Repair::query()->where('reservation_id', $locked->id)->exists()) {
                throw new RepairReservationException('A repair has already been started for this appointment, so it can no longer be cancelled here.', 409);
            }
            if (! $locked->isActive()) {
                throw new RepairReservationException('This request is already '.strtolower($locked->label()).' and cannot be cancelled.', 409);
            }
            if ($role === 'customer' && $locked->scheduled_at->lte(now())) {
                throw new RepairReservationException('The appointment time has passed. Please contact the shop.', 409);
            }
            $this->applyCancel($locked, $role, $actor->id, $reason);

            return [$locked, true];
        });

        if ($changed) {
            $this->notifyCancelled($locked, $role, $reason);
        }

        return ['reservation' => $locked, 'changed' => $changed];
    }

    // ------------------------------------------------------------------ owner: physical check-in (Phase 3)

    /**
     * Record that the customer physically arrived. Owner only; staff receive customers and tell the owner, who
     * records it. Valid at or before the stored check-in deadline (to the second: 7:00:00 for a 7:30 start is
     * valid, 7:00:01 is not). The status stays `accepted`. A repeat changes nothing and sends nothing.
     *
     * @return array{reservation: RepairReservation, changed: bool}
     */
    public function checkIn(RepairReservation $reservation, User $owner, ?string $receivedBy = null): array
    {
        $this->assertOwner($owner);
        $receivedBy = $receivedBy !== null ? Str::limit(trim(strip_tags($receivedBy)), 80, '') : null;

        [$locked, $changed] = app(RepairCapacityService::class)->withDayLock($reservation->service_date, function () use ($reservation, $owner, $receivedBy) {
            $locked = RepairReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if ($locked->checked_in_at !== null) {
                return [$locked, false];
            }
            if ($locked->status !== RepairReservation::ACCEPTED) {
                throw new RepairReservationException('Only an accepted appointment can be checked in. This one is '.strtolower($locked->label()).'.', 409);
            }
            $deadline = $locked->check_in_deadline_at
                ?? $this->checkInDeadlineFor($locked->scheduled_at->copy()->timezone($this->tz()));
            if (now()->startOfSecond()->gt($deadline)) {
                throw new RepairReservationException('The check-in deadline ('.$deadline->copy()->timezone($this->tz())->format('g:i:s A').') has passed for this appointment.', 409);
            }

            $locked->update(['checked_in_at' => now(), 'checked_in_by' => $owner->id, 'received_by' => $receivedBy ?: null]);
            // The history is customer-visible: keep it generic. "Received by" stays on the reservation row (owner-only views).
            $this->event($locked, $locked->status, $locked->status, 'owner', $owner->id, 'Checked in');

            return [$locked, true];
        });

        if ($changed) {
            $this->tell($locked->customer?->user, 'Checked in',
                "{$locked->reference}: you are checked in for your {$locked->timeLabel()} appointment.",
                route('repair-reservations.show', $locked->reference));
        }

        return ['reservation' => $locked, 'changed' => $changed];
    }

    /** Notify after "Start repair" committed (never inside the transaction). */
    public function notifyRepairStarted(RepairReservation $r): void
    {
        $this->tell($r->customer?->user, 'Repair started', "{$r->reference}: work on your bike has started.", route('repair-reservations.show', $r->reference));
    }

    /** Notify after the linked repair was completed and the reservation became `completed`. */
    public function notifyCompleted(RepairReservation $r): void
    {
        $this->tell($r->customer?->user, 'Service completed', "{$r->reference}: your service is complete.", route('repair-reservations.show', $r->reference));
    }

    // ------------------------------------------------------------------ Phase 5: deadline processes (flag-gated)

    public function autoCancelEnabled(): bool
    {
        return (bool) config('repair_reservations.auto_cancel.enabled', false);
    }

    public function pendingExpiryEnabled(): bool
    {
        return (bool) config('repair_reservations.pending_expiry.enabled', false);
    }

    private function batchSize(): int
    {
        return max(1, min(500, (int) config('repair_reservations.batch_size', 100)));
    }

    /** Truncated to whole seconds and expressed in the storage timezone, for comparing with stored timestamps. */
    private function dbNow(Carbon $now): string
    {
        return $now->copy()->startOfSecond()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }

    /**
     * Automatic no-check-in cancellation. Eligible ONLY when ALL hold: status accepted, not checked in, no linked
     * repair, and the STORED check-in deadline is strictly earlier than now (exactly at the deadline is still valid).
     * Does nothing unless RR_AUTO_CANCEL_ENABLED is true — in every mode, including dry-run. Rows without a stored
     * deadline are skipped and counted, never given an invented one. Dry-run (the default) performs no writes.
     *
     * @return array{enabled: bool, dry_run: bool, eligible: int, processed: int, skipped: int, failed: int, would: array<int,string>}
     */
    public function runAutoCancel(?Carbon $now = null, bool $dryRun = true): array
    {
        $result = ['enabled' => $this->autoCancelEnabled(), 'dry_run' => $dryRun, 'eligible' => 0, 'processed' => 0, 'skipped' => 0, 'failed' => 0, 'would' => []];
        if (! $result['enabled']) {
            return $result;
        }

        $now = ($now ?? $this->now())->copy()->timezone($this->tz());
        $base = RepairReservation::query()->where('status', RepairReservation::ACCEPTED)->whereNull('checked_in_at')
            ->whereDoesntHave('repair');

        $result['skipped'] = (clone $base)->whereNull('check_in_deadline_at')->count();   // legacy rows: reported, never guessed
        $rows = (clone $base)->whereNotNull('check_in_deadline_at')->where('check_in_deadline_at', '<', $this->dbNow($now))
            ->orderBy('id')->limit($this->batchSize())->get(['id', 'reference']);
        $result['eligible'] = $rows->count();

        foreach ($rows as $row) {
            if ($dryRun) {
                $result['would'][] = $row->reference;
                continue;
            }
            try {
                $done = $this->autoCancelOne($row->id, $now);
                $done ? $result['processed']++ : $result['skipped']++;
            } catch (\Throwable $e) {
                $result['failed']++;
                Log::warning('Repair reservation auto-cancel failed: '.get_class($e));
            }
        }

        return $result;
    }

    /**
     * Cancel ONE reservation if it is still eligible, rechecking everything under the per-day lock and the row lock.
     * Returns the cancelled reservation, or null when it is no longer eligible (idempotent: a repeat does nothing).
     */
    public function autoCancelOne(int $id, ?Carbon $now = null): ?RepairReservation
    {
        if (! $this->autoCancelEnabled()) {
            return null;
        }
        $now = ($now ?? $this->now())->copy()->timezone($this->tz());
        $service = RepairReservation::query()->whereKey($id)->value('service_date');
        if ($service === null) {
            return null;
        }

        $cancelled = app(RepairCapacityService::class)->withDayLock(Carbon::parse($service, $this->tz()), function () use ($id, $now) {
            $r = RepairReservation::query()->whereKey($id)->lockForUpdate()->first();
            if (! $r || $r->status !== RepairReservation::ACCEPTED || $r->checked_in_at !== null || $r->check_in_deadline_at === null
                || ! $r->check_in_deadline_at->copy()->startOfSecond()->lt($now->copy()->startOfSecond())
                || Repair::query()->where('reservation_id', $r->id)->exists()) {
                return null;
            }
            $deadline = $r->check_in_deadline_at->copy()->timezone($this->tz())->format('g:i:s A');
            $this->applyCancel($r, 'system', null, "Automatically cancelled: no check-in was recorded by the {$deadline} deadline.");

            return $r;
        });

        if ($cancelled) {
            $this->notifyCancelled($cancelled, 'system', null);
        }

        return $cancelled;
    }

    /**
     * Expire unanswered PENDING requests. Eligible ONLY when status is pending and the STORED approval deadline is at
     * or before now (exactly at the deadline is eligible). Gated by RR_PENDING_EXPIRY_ENABLED in every mode;
     * blackout conflicts are never a reason to expire. Dry-run (the default) performs no writes.
     *
     * @return array{enabled: bool, dry_run: bool, eligible: int, processed: int, skipped: int, failed: int, would: array<int,string>}
     */
    public function runPendingExpiry(?Carbon $now = null, bool $dryRun = true): array
    {
        $result = ['enabled' => $this->pendingExpiryEnabled(), 'dry_run' => $dryRun, 'eligible' => 0, 'processed' => 0, 'skipped' => 0, 'failed' => 0, 'would' => []];
        if (! $result['enabled']) {
            return $result;
        }

        $now = ($now ?? $this->now())->copy()->timezone($this->tz());
        $base = RepairReservation::query()->where('status', RepairReservation::PENDING);

        $result['skipped'] = (clone $base)->whereNull('approval_deadline_at')->count();
        $rows = (clone $base)->whereNotNull('approval_deadline_at')->where('approval_deadline_at', '<=', $this->dbNow($now))
            ->orderBy('id')->limit($this->batchSize())->get(['id', 'reference']);
        $result['eligible'] = $rows->count();

        foreach ($rows as $row) {
            if ($dryRun) {
                $result['would'][] = $row->reference;
                continue;
            }
            try {
                $done = $this->expireOne($row->id, $now);
                $done ? $result['processed']++ : $result['skipped']++;
            } catch (\Throwable $e) {
                $result['failed']++;
                Log::warning('Repair reservation expiry failed: '.get_class($e));
            }
        }

        return $result;
    }

    /** Expire ONE pending reservation if it is still eligible (rechecked under locks). Idempotent. */
    public function expireOne(int $id, ?Carbon $now = null): ?RepairReservation
    {
        if (! $this->pendingExpiryEnabled()) {
            return null;
        }
        $now = ($now ?? $this->now())->copy()->timezone($this->tz());
        $service = RepairReservation::query()->whereKey($id)->value('service_date');
        if ($service === null) {
            return null;
        }

        $expired = app(RepairCapacityService::class)->withDayLock(Carbon::parse($service, $this->tz()), function () use ($id, $now) {
            $r = RepairReservation::query()->whereKey($id)->lockForUpdate()->first();
            if (! $r || $r->status !== RepairReservation::PENDING || $r->approval_deadline_at === null
                || $r->approval_deadline_at->copy()->startOfSecond()->gt($now->copy()->startOfSecond())) {
                return null;
            }
            $note = 'Expired: the shop did not answer before the approval deadline.';
            $r->update(['status' => RepairReservation::EXPIRED, 'active_day_key' => null, 'decision_reason' => $note]);
            $this->event($r, RepairReservation::PENDING, RepairReservation::EXPIRED, 'system', null, $note);

            return $r;
        });

        if ($expired) {
            $this->tell($expired->customer?->user, 'Service request expired',
                "{$expired->reference}: your request for {$expired->timeLabel()} was not answered in time and has expired. You can submit a new request if there is still time today.",
                route('repair-reservations.show', $expired->reference));
        }

        return $expired;
    }

    // ------------------------------------------------------------------ internals

    private function applyCancel(RepairReservation $r, string $role, ?int $actorId, ?string $reason): void
    {
        $from = $r->status;
        $r->update(['status' => RepairReservation::CANCELLED, 'active_day_key' => null, 'cancelled_at' => now(),
            'cancelled_by' => $role, 'decision_reason' => $reason !== null ? Str::limit($reason, 250, '') : $r->decision_reason]);
        $this->event($r, $from, RepairReservation::CANCELLED, $role, $actorId, $reason !== null ? Str::limit($reason, 250, '') : null);
    }

    private function notifyCancelled(RepairReservation $r, string $role, ?string $reason): void
    {
        $customerUrl = route('repair-reservations.show', $r->reference);
        if ($role === 'customer') {
            $this->tell(User::owners(), 'Service request cancelled',
                "{$r->reference} (today at {$r->timeLabel()}) was cancelled by the customer.", route('owner.repair-reservations.index'));
            $this->tell($r->customer?->user, 'Service request cancelled', "{$r->reference} was cancelled.", $customerUrl);
        } elseif ($role === 'owner') {
            $this->tell($r->customer?->user, 'Service appointment cancelled',
                "{$r->reference}: the shop cancelled your appointment. Reason: {$reason}", $customerUrl);
        } else {
            $this->tell($r->customer?->user, 'Service appointment cancelled',
                "{$r->reference}: your {$r->timeLabel()} appointment was automatically cancelled because no check-in was recorded by the deadline.", $customerUrl);
            $this->tell(User::owners(), 'Service request auto-cancelled',
                "{$r->reference} (today at {$r->timeLabel()}) was automatically cancelled: no check-in by the deadline.", route('owner.repair-reservations.index'));
        }
    }

    private function assertOwner(User $owner): void
    {
        if ($owner->role !== 'owner') {
            throw new RepairReservationException('Only the owner can decide service requests.', 403);
        }
    }

    private function event(RepairReservation $r, ?string $from, string $to, string $role, ?int $actorId, ?string $note): void
    {
        RepairReservationEvent::create([
            'repair_reservation_id' => $r->id, 'from_status' => $from, 'to_status' => $to,
            'actor_role' => $role, 'actor_id' => $actorId, 'note' => $note, 'created_at' => now(),
        ]);
    }

    private function newReference(): string
    {
        do {
            $ref = 'RR-'.now($this->tz())->format('ymd').'-'.Str::upper(Str::random(5));
        } while (RepairReservation::where('reference', $ref)->exists());

        return $ref;
    }

    /** Notification failures never roll back or block a booking decision. */
    private function tell($recipients, string $title, string $message, string $link): void
    {
        try {
            $this->notifier->send($recipients, new StatusUpdated($title, $message, $link));
        } catch (\Throwable $e) {
            Log::warning('Repair reservation notification failed: '.get_class($e));
        }
    }
}
