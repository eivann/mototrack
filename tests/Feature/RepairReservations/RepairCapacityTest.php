<?php

use App\Models\Customer;
use App\Models\Repair;
use App\Models\RepairReservation;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Services\RepairCapacityException;
use App\Services\RepairCapacityService;
use App\Services\RepairReservationException;
use App\Services\RepairReservationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * RD2-R3 Phase 2: shop-wide SIMULTANEOUS repair capacity (accepted bookings + ongoing walk-ins together).
 * Test data only: fixed clock (Mon 2 Nov 2026, 09:00 Asia/Manila), faked notifications, isolated SQLite.
 */

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

function rcAt(string $hm): Carbon
{
    return Carbon::parse('2026-11-02 '.$hm.':00', 'Asia/Manila');
}

function rcOwner(): User
{
    $owner = User::factory()->create(['role' => 'owner']);
    Illuminate\Support\Facades\Cache::forget('users_role_owner_ids');

    return $owner;
}

/** An ACCEPTED online reservation at a start time (inserted directly; acceptance is covered separately). */
function rcBooking(string $hm, string $status = 'accepted'): RepairReservation
{
    static $n = 0;
    $n++;
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    $at = rcAt($hm);

    return RepairReservation::create([
        'reference' => 'RR-CAP-'.$n, 'customer_id' => $customer->id, 'service_date' => '2026-11-02',
        'scheduled_at' => $at, 'check_in_deadline_at' => $at->copy()->subMinutes(30), 'approval_deadline_at' => $at->copy()->subMinutes(30),
        'service_type' => 'brakes', 'bike_model' => 'Click 125', 'description' => 'capacity test row', 'status' => $status,
        'active_day_key' => $status === 'accepted' ? 'cap:'.$n : null,
    ]);
}

/** A walk-in repair in a given state; ongoing ones can carry a start time and estimate. */
function rcRepair(string $status = 'pending', ?string $startHm = null, ?int $minutes = null): Repair
{
    $r = Repair::create(['customer_name' => 'Walk In', 'bike_model' => 'Mio', 'issue' => 'Noise', 'cost' => 500, 'status' => $status, 'is_paid' => false]);
    if ($startHm) {
        $r->forceFill(['started_at' => rcAt($startHm), 'estimated_minutes' => $minutes])->save();
    }

    return $r;
}

function rcSvc(): RepairCapacityService
{
    return app(RepairCapacityService::class);
}

// ---------------------------------------------------------------- pure interval maths

test('different durations: a long job overlaps a later short one, and back-to-back jobs do not overlap', function () {
    $svc = rcSvc();
    $occupied = [[rcAt('10:00'), rcAt('12:00')]];           // a 2-hour job

    expect($svc->fits(rcAt('11:00'), rcAt('11:30'), $occupied, 1))->toBeFalse()   // inside it
        ->and($svc->fits(rcAt('09:30'), rcAt('10:30'), $occupied, 1))->toBeFalse()   // overlaps its start
        ->and($svc->fits(rcAt('09:00'), rcAt('10:00'), $occupied, 1))->toBeTrue()    // ends exactly when it starts
        ->and($svc->fits(rcAt('12:00'), rcAt('13:00'), $occupied, 1))->toBeTrue();   // starts exactly when it ends
});

test('the peak is measured at every instant, not by head-count', function () {
    $svc = rcSvc();
    // Three jobs in total, but never more than 2 at once: A 10–11, B 10:30–11:30, C 11–12.
    $occupied = [[rcAt('10:00'), rcAt('11:00')], [rcAt('10:30'), rcAt('11:30')], [rcAt('11:00'), rcAt('12:00')]];

    expect($svc->fits(rcAt('10:00'), rcAt('12:00'), $occupied, 3))->toBeTrue()    // peak 2 existing + 1 = 3
        ->and($svc->fits(rcAt('10:00'), rcAt('12:00'), $occupied, 2))->toBeFalse()
        ->and($svc->fits(rcAt('10:00'), rcAt('10:30'), $occupied, 2))->toBeTrue();   // only A is active in that window
});

test('an open-ended (overrunning) interval blocks everything after its start', function () {
    $svc = rcSvc();
    $occupied = [[rcAt('08:00'), null]];

    expect($svc->fits(rcAt('15:00'), rcAt('16:00'), $occupied, 1))->toBeFalse()
        ->and($svc->fits(rcAt('15:00'), rcAt('16:00'), $occupied, 2))->toBeTrue();
});

// ---------------------------------------------------------------- reservations

test('accepting a reservation counts ongoing walk-ins and refuses the 4th overlapping job', function () {
    $owner = rcOwner();
    rcRepair('ongoing', '09:00', 120);   // walk-in busy 09:00–11:00
    rcBooking('10:00');
    rcBooking('10:30');
    $fourth = rcBooking('10:00', 'pending');

    expect(fn () => app(RepairReservationService::class)->accept($fourth, $owner))
        ->toThrow(RepairReservationException::class, 'Capacity is full');
    expect($fourth->fresh()->status)->toBe('pending');
});

test('a reservation that starts exactly when a walk-in is due to end is accepted (back-to-back)', function () {
    $owner = rcOwner();
    rcRepair('ongoing', '09:00', 120);   // ends 11:00
    rcBooking('10:00');
    rcBooking('10:30');
    $backToBack = rcBooking('11:30', 'pending');   // 10:30's job ends 11:30; walk-in ended 11:00 -> only one active

    $result = app(RepairReservationService::class)->accept($backToBack, $owner);
    expect($result['changed'])->toBeTrue();
});

test('a pending reservation does not consume capacity, an accepted one does', function () {
    rcBooking('10:00', 'pending');
    rcBooking('10:00', 'pending');
    rcBooking('10:00', 'pending');
    expect(rcSvc()->reservationFits(rcAt('10:00')))->toBeTrue();

    rcBooking('10:00');
    rcBooking('10:30');
    rcBooking('10:45');
    expect(rcSvc()->reservationFits(rcAt('10:15')))->toBeFalse();
});

test('the customer slot list hides slots that a walk-in has filled, in one query', function () {
    rcRepair('ongoing', '09:00', 180);
    rcRepair('ongoing', '09:00', 180);
    rcRepair('ongoing', '09:00', 180);   // three bays busy until 12:00

    DB::enableQueryLog(); DB::flushQueryLog();
    $slots = collect(app(RepairReservationService::class)->availableSlots())->keyBy('value');
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBeLessThanOrEqual(2)   // occupancy + the Phase 4 blackout lookup: still constant, not per slot
        ->and($slots['10:00']['available'])->toBeFalse()
        ->and($slots['11:30']['available'])->toBeFalse()   // 11:30–12:30 still overlaps the 12:00 end
        ->and($slots['12:00']['available'])->toBeTrue();
});

// ---------------------------------------------------------------- walk-in start / done

test('a walk-in start is refused when three jobs already overlap, and allowed once one is done', function () {
    $a = rcRepair('ongoing', '08:30', 120);
    rcRepair('ongoing', '08:45', 120);
    rcBooking('09:30');                   // accepted booking 09:30–10:30
    $walkIn = rcRepair('pending');

    expect(fn () => rcSvc()->startWalkIn($walkIn))->toThrow(RepairCapacityException::class);
    expect($walkIn->fresh()->status)->toBe('pending')->and($walkIn->fresh()->started_at)->toBeNull();

    rcSvc()->finishWalkIn($a);
    expect(rcSvc()->startWalkIn($walkIn)->status)->toBe('ongoing');
    expect($walkIn->fresh()->started_at->format('H:i'))->toBe('09:00')
        ->and($walkIn->fresh()->estimated_minutes)->toBe(60);
});

test('a walk-in that would collide with an accepted future booking is refused', function () {
    rcBooking('09:30');
    rcBooking('09:30');
    rcBooking('09:30');                   // three bookings 09:30–10:30
    $walkIn = rcRepair('pending');

    // 09:00–10:00 (default 60 min) overlaps all three from 09:30 -> refused.
    expect(fn () => rcSvc()->startWalkIn($walkIn))->toThrow(RepairCapacityException::class);
    // A 30-minute job (09:00–09:30) finishes exactly when they start -> allowed.
    expect(rcSvc()->startWalkIn($walkIn, 30)->status)->toBe('ongoing');
});

test('starting an already-ongoing walk-in again changes nothing', function () {
    $r = rcRepair('ongoing', '08:30', 120);
    $before = $r->fresh()->started_at->toDateTimeString();

    expect(rcSvc()->startWalkIn($r)->started_at->toDateTimeString())->toBe($before);
});

test('an overrunning walk-in keeps holding its bay until it is marked done', function () {
    $overrun = rcRepair('ongoing', '06:00', 60);   // should have finished at 07:00, still ongoing at 09:00
    rcRepair('ongoing', '08:30', 120);
    rcRepair('ongoing', '08:45', 120);

    // The new job fits only if the overrunning one frees up: it does not.
    expect(fn () => rcSvc()->startWalkIn(rcRepair('pending')))->toThrow(RepairCapacityException::class);
    // Even a booking hours later is blocked by the open-ended overrun ... as long as the others overlap it.
    expect(rcSvc()->reservationFits(rcAt('09:30')))->toBeFalse();

    rcSvc()->finishWalkIn($overrun);
    expect(rcSvc()->startWalkIn(rcRepair('pending'))->status)->toBe('ongoing');
});

test('a legacy ongoing walk-in without a start time is counted conservatively, never ignored', function () {
    $legacy = rcRepair('ongoing');                  // started_at NULL, estimated_minutes NULL
    DB::table('repairs')->where('id', $legacy->id)->update(['updated_at' => '2026-11-01 15:00:00']);   // days-old row
    rcRepair('ongoing', '08:30', 120);
    rcRepair('ongoing', '08:45', 120);

    expect(fn () => rcSvc()->startWalkIn(rcRepair('pending')))->toThrow(RepairCapacityException::class);
    config(['repair_reservations.capacity' => 1]);                       // only the open-ended legacy job is left at 15:00
    expect(rcSvc()->reservationFits(rcAt('15:00')))->toBeFalse()       // the overrun legacy job is open-ended
        ->and(Repair::find($legacy->id)->started_at)->toBeNull();      // and the row itself is left alone

    rcSvc()->finishWalkIn($legacy);
    expect(rcSvc()->reservationFits(rcAt('15:00')))->toBeTrue();
});

test('moving an ongoing job back to pending releases its bay', function () {
    $r = rcRepair('ongoing', '08:30', 120);
    rcRepair('ongoing', '08:30', 120);
    rcRepair('ongoing', '08:30', 120);
    expect(rcSvc()->reservationFits(rcAt('09:30')))->toBeFalse();

    rcSvc()->releaseWalkIn($r);
    expect($r->fresh()->started_at)->toBeNull()->and(rcSvc()->reservationFits(rcAt('09:30')))->toBeTrue();
});

test('pending, done and completed repairs never occupy a bay', function () {
    foreach (['pending', 'done', 'completed'] as $status) {
        rcRepair($status, '08:30', 600);
    }
    expect(rcSvc()->occupancy(rcAt('09:00')))->toBe([]);
});

// ---------------------------------------------------------------- the Repairs module itself

test('the Repairs screen status change uses the capacity rule, and keeps notifying and redirecting as before', function () {
    $owner = rcOwner();
    rcRepair('ongoing', '08:30', 120);
    rcRepair('ongoing', '08:30', 120);
    rcRepair('ongoing', '08:30', 120);
    $pending = rcRepair('pending');

    $this->actingAs($owner)->put("/repairs/{$pending->id}", ['status' => 'ongoing'])
        ->assertRedirect('/repairs')->assertSessionHas('error');
    expect($pending->fresh()->status)->toBe('pending');

    $done = Repair::where('status', 'ongoing')->first();
    $this->actingAs($owner)->put("/repairs/{$done->id}", ['status' => 'done'])
        ->assertRedirect('/repairs')->assertSessionHas('success');
    expect($done->fresh()->status)->toBe('done')->and($done->fresh()->completed_at)->not->toBeNull();

    $this->actingAs($owner)->put("/repairs/{$pending->id}", ['status' => 'ongoing', 'estimated_minutes' => 45])->assertSessionHas('success');
    expect($pending->fresh()->status)->toBe('ongoing')->and($pending->fresh()->estimated_minutes)->toBe(45);
});

test('repair receipts and payment records are unaffected by the occupancy columns', function () {
    $owner = rcOwner();
    $r = rcRepair('done', '08:30', 60);

    $this->actingAs($owner)->get("/repairs/{$r->id}/receipt")->assertOk();
    $this->actingAs($owner)->get('/repairs')->assertOk();
});

test('Phase 2 leaves the automatic features switched off', function () {
    expect(config('repair_reservations.auto_cancel.enabled'))->toBeFalse()
        ->and(config('repair_reservations.pending_expiry.enabled'))->toBeFalse();
});
