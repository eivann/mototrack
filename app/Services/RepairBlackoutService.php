<?php

namespace App\Services;

use App\Models\RepairBlackout;
use App\Models\RepairReservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * RD2-R3 Phase 4: owner-managed blackouts. ONE overlap rule is used by every check (customer slot list, new
 * request, owner acceptance, conflict flags on the owner page):
 *
 *   an appointment starting at S occupies the window [S, S + appointment_minutes)
 *   a blackout is the half-open interval [starts_at, ends_at)
 *   they conflict  <=>  S < ends_at  AND  S + appointment_minutes > starts_at
 *
 * So a booking that STARTS before a blackout but runs into it is blocked, one that ends exactly when the blackout
 * starts is not, and one that starts exactly when it ends is not. Existing reservations are never changed by a
 * blackout; they are only flagged.
 */
class RepairBlackoutService
{
    public function tz(): string
    {
        return (string) config('repair_reservations.timezone', 'Asia/Manila');
    }

    public function appointmentMinutes(): int
    {
        return max(5, (int) config('repair_reservations.appointment_minutes', 60));
    }

    private function db(Carbon $t): Carbon
    {
        return $t->copy()->timezone(config('app.timezone'));
    }

    /** The window an appointment starting at $start occupies. */
    public function windowFor(Carbon $start): array
    {
        $start = $start->copy()->timezone($this->tz());

        return [$start, $start->copy()->addMinutes($this->appointmentMinutes())];
    }

    /** @return Collection<int, RepairBlackout> blackouts overlapping [$from, $to) — one query. */
    public function overlapping(Carbon $from, Carbon $to): Collection
    {
        return RepairBlackout::query()
            ->where('starts_at', '<', $this->db($to))
            ->where('ends_at', '>', $this->db($from))
            ->orderBy('starts_at')->get();
    }

    /** The blackout that blocks an appointment starting at $scheduled, if any. */
    public function firstBlocking(Carbon $scheduled): ?RepairBlackout
    {
        [$s, $e] = $this->windowFor($scheduled);

        return $this->overlapping($s, $e)->first();
    }

    /** In-memory form of the same rule, for lists. */
    public function blockingIn(Collection $blackouts, Carbon $scheduled): ?RepairBlackout
    {
        [$s, $e] = $this->windowFor($scheduled);

        return $blackouts->first(fn (RepairBlackout $b) => $b->starts_at->lt($e) && $b->ends_at->gt($s));
    }

    /** All blackouts that touch one calendar day (a single query, for the slot list). */
    public function forDay(Carbon $day): Collection
    {
        $start = $day->copy()->timezone($this->tz())->startOfDay();

        return $this->overlapping($start, $start->copy()->addDay()->addMinutes($this->appointmentMinutes()));
    }

    /**
     * Blackout conflicts for a page of reservations, one query.
     *
     * @param  iterable<RepairReservation>  $reservations
     * @return array<int, RepairBlackout> reservation id => blocking blackout
     */
    public function conflictsFor(iterable $reservations): array
    {
        if ($reservations instanceof \Illuminate\Contracts\Pagination\Paginator) {
            $reservations = $reservations->getCollection();
        }
        $reservations = collect($reservations)->filter(fn ($r) => in_array($r->status, [RepairReservation::PENDING, RepairReservation::ACCEPTED], true));
        if ($reservations->isEmpty()) {
            return [];
        }
        $min = $reservations->min(fn ($r) => $r->scheduled_at->timestamp);
        $max = $reservations->max(fn ($r) => $r->scheduled_at->timestamp);
        $blackouts = $this->overlapping(Carbon::createFromTimestamp($min, $this->tz()), Carbon::createFromTimestamp($max, $this->tz())->addMinutes($this->appointmentMinutes()));

        $out = [];
        foreach ($reservations as $r) {
            if ($b = $this->blockingIn($blackouts, $r->scheduled_at)) {
                $out[$r->id] = $b;
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------ owner management

    /**
     * Create a blackout from owner input already shape-validated by the controller.
     *
     * @param  array{type:string, date:string, time?:?string, start_time?:?string, end_time?:?string, reason?:?string}  $data
     *
     * @throws RepairReservationException
     */
    public function create(User $owner, array $data): RepairBlackout
    {
        $this->assertOwner($owner);
        $tz = $this->tz();
        $day = Carbon::createFromFormat('Y-m-d', $data['date'], $tz)->startOfDay();
        $type = $data['type'];

        if ($type === RepairBlackout::DAY) {
            $start = $day->copy();
            $end = $day->copy()->addDay();
        } elseif ($type === RepairBlackout::SLOT) {
            $time = $data['time'] ?? null;
            $slot = collect(app(RepairReservationService::class)->slotStarts($day))->first(fn (Carbon $s) => $s->format('H:i') === $time);
            if (! $slot) {
                throw new RepairReservationException('Choose one of the real appointment times (between '
                    .Carbon::createFromFormat('H:i', config('repair_reservations.open'))->format('g:i A').' and '
                    .Carbon::createFromFormat('H:i', config('repair_reservations.last_start'))->format('g:i A').').');
            }
            $start = $slot->copy();
            $end = $slot->copy()->addMinutes(max(5, (int) config('repair_reservations.slot_minutes', 30)));
        } elseif ($type === RepairBlackout::RANGE) {
            $start = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.($data['start_time'] ?? ''), $tz);
            $end = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.($data['end_time'] ?? ''), $tz);
            if ($end->lte($start)) {
                throw new RepairReservationException('The end time must be after the start time.');
            }
        } else {
            throw new RepairReservationException('Choose slot, range or whole day.');
        }

        if ($end->lte(Carbon::now($tz))) {
            throw new RepairReservationException('That period is already over. Choose a current or future time.');
        }

        // Same per-day lock as acceptance, so a blackout and an acceptance for that day never interleave.
        return app(RepairCapacityService::class)->withDayLock($day, function () use ($owner, $type, $start, $end, $data) {
            return RepairBlackout::create([
                'starts_at' => $this->db($start),
                'ends_at' => $this->db($end),
                'kind' => $type,
                'reason' => isset($data['reason']) && trim((string) $data['reason']) !== '' ? Str::limit(trim(strip_tags($data['reason'])), 250, '') : null,
                'created_by' => $owner->id,
            ]);
        });
    }

    /**
     * Remove a blackout that is current or upcoming. A finished one is kept as history.
     *
     * @throws RepairReservationException
     */
    public function remove(User $owner, RepairBlackout $blackout): void
    {
        $this->assertOwner($owner);
        if ($blackout->ends_at->lte(now())) {
            throw new RepairReservationException('This blackout is already over and is kept as history.', 409);
        }

        app(RepairCapacityService::class)->withDayLock($blackout->starts_at->copy()->timezone($this->tz()), function () use ($blackout) {
            RepairBlackout::query()->whereKey($blackout->id)->delete();
        });
    }

    private function assertOwner(User $owner): void
    {
        if ($owner->role !== 'owner') {
            throw new RepairReservationException('Only the owner can manage blackouts.', 403);
        }
    }
}
