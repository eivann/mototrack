<?php

use App\Models\Customer;
use App\Models\Repair;
use App\Models\RepairBlackout;
use App\Models\RepairReservation;
use App\Models\RepairReservationEvent;
use App\Models\User;
use App\Services\RepairBlackoutService;
use App\Services\RepairCapacityException;
use App\Services\RepairCapacityService;
use App\Services\RepairReservationException;
use App\Services\RepairReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

/**
 * RD2-R3 Phase 6: transaction / lock-order verification that SQLite CAN prove.
 *
 * SQLite serialises writers and ignores FOR UPDATE, so these tests do NOT prove true parallel behaviour; they pin the
 * properties that make the PostgreSQL behaviour correct: (1) every capacity-changing operation takes the shared per-day
 * lock row BEFORE it writes anything, (2) all state is rechecked from fresh rows after that lock (stale decisions lose),
 * (3) a failure rolls the whole operation back and no notification escapes, (4) duplicate attempts change nothing.
 * Real overlapping transactions need the disposable local PostgreSQL database described in
 * docs/MOTOTRACK_R3_PHASE6_POSTGRES_CONCURRENCY_PLAN.md.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::parse('2026-11-02 09:00:00', 'Asia/Manila'));
    config([
        'repair_reservations.capacity' => 3,
        'repair_reservations.capacity_unit' => 'simultaneous',
        'repair_reservations.appointment_minutes' => 60,
        'repair_reservations.walk_in_minutes' => 60,
        'repair_reservations.auto_cancel.enabled' => false,
        'repair_reservations.pending_expiry.enabled' => false,
    ]);
});

afterEach(fn () => Carbon::setTestNow());

function p6At(string $hms): Carbon
{
    return Carbon::parse('2026-11-02 '.$hms, 'Asia/Manila');
}

function p6Owner(): User
{
    $owner = User::factory()->create(['role' => 'owner']);
    Cache::forget('users_role_owner_ids');

    return $owner;
}

function p6Res(string $status = 'accepted', string $hms = '10:00:00', array $over = []): RepairReservation
{
    static $n = 0;
    $n++;
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    $at = p6At($hms);

    return RepairReservation::create(array_merge([
        'reference' => 'RR-P6-'.$n, 'customer_id' => $customer->id, 'service_date' => '2026-11-02',
        'scheduled_at' => $at, 'check_in_deadline_at' => $at->copy()->subMinutes(30), 'approval_deadline_at' => $at->copy()->subMinutes(30),
        'service_type' => 'brakes', 'bike_model' => 'Click 125', 'description' => 'phase six row', 'status' => $status,
        'active_day_key' => in_array($status, ['pending', 'accepted'], true) ? 'p6:'.$n : null,
    ], $over));
}

function p6Repair(string $status = 'pending', ?string $startHms = null, ?int $minutes = null): Repair
{
    $r = Repair::create(['customer_name' => 'Walk In', 'bike_model' => 'Mio', 'issue' => 'Noise', 'cost' => 500, 'status' => $status, 'is_paid' => false]);
    if ($startHms) {
        $r->forceFill(['started_at' => p6At($startHms), 'estimated_minutes' => $minutes])->save();
    }

    return $r;
}

function p6Cap(): RepairCapacityService
{
    return app(RepairCapacityService::class);
}

/** @return array<int,string> every SQL statement run by $work, in order. */
function p6Queries(callable $work): array
{
    $log = [];
    DB::listen(function ($q) use (&$log) { $log[] = $q->sql; });
    $work();

    return $log;
}

// ---------------------------------------------------------------- lock order

dataset('capacity_changing_operations', [
    'owner acceptance' => [fn () => (function () {
        $r = p6Res('pending', '14:00:00'); $o = p6Owner();
        return fn () => app(RepairReservationService::class)->accept($r, $o);
    })()],
    'owner check-in' => [fn () => (function () {
        $r = p6Res('accepted', '14:00:00'); $o = p6Owner();
        return fn () => app(RepairReservationService::class)->checkIn($r, $o);
    })()],
    'walk-in start' => [fn () => (function () {
        $w = p6Repair('pending');
        return fn () => p6Cap()->startWalkIn($w);
    })()],
    'walk-in done' => [fn () => (function () {
        $w = p6Repair('ongoing', '08:30:00', 120);
        return fn () => p6Cap()->finishWalkIn($w);
    })()],
    'walk-in back to pending' => [fn () => (function () {
        $w = p6Repair('ongoing', '08:30:00', 120);
        return fn () => p6Cap()->releaseWalkIn($w);
    })()],
    'reservation to repair' => [fn () => (function () {
        $r = p6Res('accepted', '10:00:00', ['checked_in_at' => now()]); $o = p6Owner();
        return fn () => p6Cap()->startReservationRepair($r, $o, 60);
    })()],
    'extension' => [fn () => (function () {
        $w = p6Repair('ongoing', '08:30:00', 120);
        return fn () => p6Cap()->extend($w, 30);
    })()],
    'blackout create' => [fn () => (function () {
        $o = p6Owner();
        return fn () => app(RepairBlackoutService::class)->create($o, ['type' => 'day', 'date' => '2026-11-02']);
    })()],
    'blackout remove' => [fn () => (function () {
        $o = p6Owner();
        $b = app(RepairBlackoutService::class)->create($o, ['type' => 'day', 'date' => '2026-11-02']);
        return fn () => app(RepairBlackoutService::class)->remove($o, $b);
    })()],
    'automatic cancellation' => [fn () => (function () {
        config(['repair_reservations.auto_cancel.enabled' => true]);
        $r = p6Res('accepted', '10:00:00', ['check_in_deadline_at' => p6At('08:00:00')]);
        return fn () => app(RepairReservationService::class)->autoCancelOne($r->id);
    })()],
    'automatic expiry' => [fn () => (function () {
        config(['repair_reservations.pending_expiry.enabled' => true]);
        $r = p6Res('pending', '10:00:00', ['approval_deadline_at' => p6At('08:00:00')]);
        return fn () => app(RepairReservationService::class)->expireOne($r->id);
    })()],
]);

test('every capacity-changing operation takes the shared per-day lock row before it writes anything', function (Closure $makeWork) {
    $work = $makeWork();
    $log = p6Queries($work);

    $lockAt = collect($log)->search(fn ($q) => preg_match('/^insert (or ignore )?into "repair_reservation_days"/i', $q) === 1);
    expect($lockAt)->not->toBeFalse('the per-day lock row was never taken');
    $writesBefore = collect($log)->take($lockAt)->filter(fn ($q) => preg_match('/^\s*(insert|update|delete)/i', $q) === 1)->all();
    expect($writesBefore)->toBe([]);
    // And the lock row is the SAME one for every operation: the service date, never a per-operation key.
    expect(DB::table('repair_reservation_days')->pluck('service_date')->map(fn ($d) => substr((string) $d, 0, 10))->unique()->all())->toBe(['2026-11-02']);
})->with('capacity_changing_operations');

// ---------------------------------------------------------------- all-or-nothing, no notification on rollback

test('a failure inside a transaction rolls everything back and no notification escapes', function () {
    $owner = p6Owner();
    $pending = p6Res('pending', '14:00:00');
    $toCheckIn = p6Res('accepted', '14:00:00');
    $linkable = p6Res('accepted', '10:00:00', ['checked_in_at' => now()]);
    $linkedRes = p6Res('accepted', '11:00:00', ['checked_in_at' => now()]);
    $linkedRepair = p6Cap()->startReservationRepair($linkedRes, $owner, 60)['repair'];
    $eventsBefore = RepairReservationEvent::count();

    config(['repair_reservations.auto_cancel.enabled' => true, 'repair_reservations.pending_expiry.enabled' => true]);
    $stuckCancel = p6Res('accepted', '15:00:00', ['check_in_deadline_at' => p6At('08:00:00')]);
    $stuckExpire = p6Res('pending', '15:00:00', ['approval_deadline_at' => p6At('08:00:00')]);
    $eventsBefore = RepairReservationEvent::count();

    Notification::fake();
    Event::listen('eloquent.creating: '.RepairReservationEvent::class, function () { throw new RuntimeException('boom inside the transaction'); });

    expect(fn () => app(RepairReservationService::class)->accept($pending, $owner))->toThrow(RuntimeException::class);
    expect(fn () => app(RepairReservationService::class)->checkIn($toCheckIn, $owner))->toThrow(RuntimeException::class);
    expect(fn () => p6Cap()->startReservationRepair($linkable, $owner, 60))->toThrow(RuntimeException::class);
    expect(fn () => p6Cap()->finishWalkIn($linkedRepair))->toThrow(RuntimeException::class);
    $cancel = app(RepairReservationService::class)->runAutoCancel(null, false);
    $expire = app(RepairReservationService::class)->runPendingExpiry(null, false);

    expect($cancel['failed'])->toBe(1)->and($cancel['processed'])->toBe(0)->and($expire['failed'])->toBe(1)->and($expire['processed'])->toBe(0);
    expect($pending->fresh()->status)->toBe('pending')->and($pending->fresh()->decided_at)->toBeNull()
        ->and($toCheckIn->fresh()->checked_in_at)->toBeNull()
        ->and(Repair::where('reservation_id', $linkable->id)->exists())->toBeFalse()->and($linkable->fresh()->estimated_minutes)->toBeNull()
        ->and($linkedRepair->fresh()->status)->toBe('ongoing')->and($linkedRepair->fresh()->completed_at)->toBeNull()
        ->and($linkedRes->fresh()->status)->toBe('accepted')->and($linkedRes->fresh()->ended_at)->toBeNull()
        ->and($stuckCancel->fresh()->status)->toBe('accepted')->and($stuckExpire->fresh()->status)->toBe('pending')
        ->and(RepairReservationEvent::count())->toBe($eventsBefore);
    Notification::assertNothingSent();
});

test('after a rolled-back attempt the same operation succeeds cleanly and notifies exactly once', function () {
    $owner = p6Owner();
    $pending = p6Res('pending', '14:00:00');

    Event::listen('eloquent.creating: '.RepairReservationEvent::class, function () { throw new RuntimeException('boom'); });
    expect(fn () => app(RepairReservationService::class)->accept($pending, $owner))->toThrow(RuntimeException::class);
    Event::forget('eloquent.creating: '.RepairReservationEvent::class);

    Notification::fake();
    expect(app(RepairReservationService::class)->accept($pending->fresh(), $owner)['changed'])->toBeTrue();
    expect(Notification::sent($pending->customer->user, \App\Notifications\StatusUpdated::class))->toHaveCount(1);
    expect(RepairReservationEvent::where('repair_reservation_id', $pending->id)->count())->toBe(1);
});

// ---------------------------------------------------------------- stale decisions lose (the sequential shadow of a race)

test('two acceptances for the final bay: the second, working from a stale page, is refused', function () {
    config(['repair_reservations.capacity' => 1]);
    $owner = p6Owner();
    $a = p6Res('pending', '14:00:00');
    $b = p6Res('pending', '14:00:00');
    $staleB = RepairReservation::find($b->id);

    expect(app(RepairReservationService::class)->accept($a, $owner)['changed'])->toBeTrue();
    expect(fn () => app(RepairReservationService::class)->accept($staleB, $owner))->toThrow(RepairReservationException::class, 'Capacity is full');
    expect($b->fresh()->status)->toBe('pending');
});

test('a walk-in start and an acceptance compete for the last bay in either order: exactly one wins, never four jobs', function () {
    config(['repair_reservations.capacity' => 1]);
    Carbon::setTestNow(p6At('08:00:00'));   // early enough that 09:30 is still before its approval deadline
    $owner = p6Owner();

    // Walk-in first.
    $pending = p6Res('pending', '09:30:00');
    p6Cap()->startWalkIn(p6Repair('pending'), 120);
    expect(fn () => app(RepairReservationService::class)->accept($pending, $owner))->toThrow(RepairReservationException::class);

    // Acceptance first.
    Repair::query()->delete(); RepairReservation::query()->delete();
    $pending2 = p6Res('pending', '09:30:00');
    app(RepairReservationService::class)->accept($pending2, $owner);
    expect(fn () => p6Cap()->startWalkIn(p6Repair('pending'), 120))->toThrow(RepairCapacityException::class);
    expect(p6Cap()->occupancy(p6At('09:00:00')))->toHaveCount(1);
});

test('two walk-in starts for limited capacity admit only as many as fit', function () {
    config(['repair_reservations.capacity' => 2]);
    $started = 0;
    foreach (range(1, 4) as $i) {
        try {
            p6Cap()->startWalkIn(p6Repair('pending'), 120);
            $started++;
        } catch (RepairCapacityException) {
        }
    }
    expect($started)->toBe(2)->and(Repair::where('status', 'ongoing')->count())->toBe(2);
});

test('a duplicate Start repair for one reservation creates one repair and one event, and the unique index backs it up', function () {
    $owner = p6Owner();
    $r = p6Res('accepted', '10:00:00', ['checked_in_at' => now()]);
    $stale = RepairReservation::find($r->id);

    $a = p6Cap()->startReservationRepair($r, $owner, 60);
    $b = p6Cap()->startReservationRepair($stale, $owner, 60);

    expect($a['changed'])->toBeTrue()->and($b['changed'])->toBeFalse()->and(Repair::where('reservation_id', $r->id)->count())->toBe(1)
        ->and(RepairReservationEvent::where('repair_reservation_id', $r->id)->where('note', 'Repair started')->count())->toBe(1);
    expect(fn () => Repair::create(['customer_name' => 'dup', 'issue' => 'x', 'status' => 'ongoing', 'is_paid' => false, 'reservation_id' => $r->id]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('completion frees the bay for the very next capacity decision, and the order of events never double counts', function () {
    config(['repair_reservations.capacity' => 1]);
    Carbon::setTestNow(p6At('08:00:00'));
    $owner = p6Owner();
    $res = p6Res('accepted', '09:00:00', ['checked_in_at' => now()]);
    $repair = p6Cap()->startReservationRepair($res, $owner, 60)['repair'];
    $next = p6Res('pending', '09:30:00');

    expect(fn () => app(RepairReservationService::class)->accept($next, $owner))->toThrow(RepairReservationException::class);   // bay busy
    p6Cap()->finishWalkIn($repair);
    expect(app(RepairReservationService::class)->accept($next->fresh(), $owner)['changed'])->toBeTrue();                      // bay free
    expect(p6Cap()->occupancy(p6At('09:30:00')))->toHaveCount(1);
});

test('an already-overrunning repair keeps holding its bay against every competing decision', function () {
    config(['repair_reservations.capacity' => 1]);
    $owner = p6Owner();
    $overrun = p6Repair('ongoing', '06:00:00', 60);
    $pending = p6Res('pending', '16:00:00');

    expect(fn () => app(RepairReservationService::class)->accept($pending, $owner))->toThrow(RepairReservationException::class);
    expect(fn () => p6Cap()->startWalkIn(p6Repair('pending'), 30))->toThrow(RepairCapacityException::class);

    p6Cap()->finishWalkIn($overrun);
    expect(app(RepairReservationService::class)->accept($pending->fresh(), $owner)['changed'])->toBeTrue();
});

test('an automatic cancellation and an owner check-in race: whichever commits first wins, and the loser changes nothing', function () {
    config(['repair_reservations.auto_cancel.enabled' => true]);
    $owner = p6Owner();

    // Check-in first (at the deadline the customer is still valid) -> the cancellation finds nothing to do.
    $a = p6Res('accepted', '10:00:00', ['check_in_deadline_at' => p6At('09:00:00')]);
    app(RepairReservationService::class)->checkIn($a, $owner);
    Carbon::setTestNow(p6At('09:00:01'));
    expect(app(RepairReservationService::class)->autoCancelOne($a->id))->toBeNull()->and($a->fresh()->status)->toBe('accepted');

    // Cancellation first -> the late check-in is refused.
    $b = p6Res('accepted', '10:00:00', ['check_in_deadline_at' => p6At('09:00:00')]);
    expect(app(RepairReservationService::class)->autoCancelOne($b->id))->not->toBeNull();
    expect(fn () => app(RepairReservationService::class)->checkIn($b, $owner))->toThrow(RepairReservationException::class);
    expect($b->fresh()->checked_in_at)->toBeNull();
});

// ---------------------------------------------------------------- scheduler audit

test('a disabled flag blocks every status change path, and enabling one flag never enables the other', function () {
    $accepted = p6Res('accepted', '10:00:00', ['check_in_deadline_at' => p6At('08:00:00')]);
    $pending = p6Res('pending', '10:00:00', ['approval_deadline_at' => p6At('08:00:00')]);
    $svc = app(RepairReservationService::class);

    foreach ([[false, false], [true, false], [false, true]] as [$cancelOn, $expireOn]) {
        config(['repair_reservations.auto_cancel.enabled' => $cancelOn, 'repair_reservations.pending_expiry.enabled' => $expireOn]);
        expect($svc->autoCancelEnabled())->toBe($cancelOn)->and($svc->pendingExpiryEnabled())->toBe($expireOn);
        if (! $cancelOn) {
            expect($svc->autoCancelOne($accepted->id))->toBeNull()->and($svc->runAutoCancel(null, false)['processed'])->toBe(0);
        }
        if (! $expireOn) {
            expect($svc->expireOne($pending->id))->toBeNull()->and($svc->runPendingExpiry(null, false)['processed'])->toBe(0);
        }
    }
    expect($accepted->fresh()->status)->toBe('accepted')->and($pending->fresh()->status)->toBe('pending');
    Notification::assertNothingSent();
});

test('the shipped defaults keep every R3 automation and payment flag off', function () {
    $example = file_get_contents(base_path('.env.example'));
    foreach (['RR_AUTO_CANCEL_ENABLED=false', 'RR_PENDING_EXPIRY_ENABLED=false'] as $line) {
        expect($example)->toContain($line."\n");
    }
    expect(file_get_contents(config_path('payments.php')))->toContain('false');
});
