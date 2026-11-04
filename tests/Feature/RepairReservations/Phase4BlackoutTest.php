<?php

use App\Models\Customer;
use App\Models\Repair;
use App\Models\RepairBlackout;
use App\Models\RepairReservation;
use App\Models\User;
use App\Services\RepairBlackoutService;
use App\Services\RepairCapacityService;
use App\Services\RepairReservationException;
use App\Services\RepairReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

/**
 * RD2-R3 Phase 4: owner-managed blackouts (slot / range / whole day). Test data only: fixed clock
 * (Mon 2 Nov 2026, 09:00 Asia/Manila), faked notifications, isolated SQLite.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::parse('2026-11-02 09:00:00', 'Asia/Manila'));
    config([
        'repair_reservations.capacity' => 3,
        'repair_reservations.capacity_unit' => 'simultaneous',
        'repair_reservations.appointment_minutes' => 60,
        'repair_reservations.slot_minutes' => 30,
        'repair_reservations.auto_cancel.enabled' => false,
        'repair_reservations.pending_expiry.enabled' => false,
    ]);
});

afterEach(fn () => Carbon::setTestNow());

function boAt(string $hm): Carbon
{
    return Carbon::parse('2026-11-02 '.$hm, 'Asia/Manila');
}

function boOwner(): User
{
    $owner = User::factory()->create(['role' => 'owner']);
    Cache::forget('users_role_owner_ids');

    return $owner;
}

function boSvc(): RepairBlackoutService
{
    return app(RepairBlackoutService::class);
}

/** Create a blackout through the owner service (the same path as the form). */
function boMake(string $type, array $over = []): RepairBlackout
{
    static $owner = null;
    $owner = $owner ?: User::factory()->create(['role' => 'owner']);

    return boSvc()->create($owner, array_merge(['type' => $type, 'date' => '2026-11-02'], $over));
}

function boBooking(string $hm, string $status = 'accepted', bool $checkedIn = false): RepairReservation
{
    static $n = 0;
    $n++;
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    $at = boAt($hm);

    return RepairReservation::create([
        'reference' => 'RR-BO-'.$n, 'customer_id' => $customer->id, 'service_date' => '2026-11-02',
        'scheduled_at' => $at, 'check_in_deadline_at' => $at->copy()->subMinutes(30), 'approval_deadline_at' => $at->copy()->subMinutes(30),
        'service_type' => 'brakes', 'bike_model' => 'Click 125', 'description' => 'blackout test row', 'status' => $status,
        'active_day_key' => in_array($status, ['pending', 'accepted'], true) ? 'bo:'.$n : null, 'checked_in_at' => $checkedIn ? now() : null,
    ]);
}

function boSlots(): array
{
    return collect(app(RepairReservationService::class)->availableSlots())->keyBy('value')->map(fn ($s) => $s['available'])->all();
}

function boRequest(string $hm): array
{
    return ['appointment_date' => '2026-11-02', 'appointment_time' => $hm, 'service_type' => 'brakes', 'bike_model' => 'Honda Click 125',
        'description' => 'Rear brake feels soft and squeaks.', 'accept_terms' => '1'];
}

// ---------------------------------------------------------------- migration

test('the blackout table stores explicit half-open intervals and indexes them', function () {
    expect(Schema::hasTable('repair_blackouts'))->toBeTrue();
    foreach (['starts_at', 'ends_at', 'kind', 'reason', 'created_by'] as $c) {
        expect(Schema::hasColumn('repair_blackouts', $c))->toBeTrue($c);
    }
    $indexes = collect(\DB::select("select name from sqlite_master where type='index' and tbl_name='repair_blackouts'"))->pluck('name');
    expect($indexes)->toContain('rb_window_idx');

    $slot = boMake('slot', ['time' => '14:00']);
    $range = boMake('range', ['start_time' => '10:00', 'end_time' => '12:00']);
    $day = boMake('day');
    expect($slot->starts_at->timezone('Asia/Manila')->format('H:i'))->toBe('14:00')
        ->and($slot->ends_at->timezone('Asia/Manila')->format('H:i'))->toBe('14:30')
        ->and($range->ends_at->timezone('Asia/Manila')->format('H:i'))->toBe('12:00')
        ->and($day->starts_at->timezone('Asia/Manila')->format('Y-m-d H:i'))->toBe('2026-11-02 00:00')
        ->and($day->ends_at->timezone('Asia/Manila')->format('Y-m-d H:i'))->toBe('2026-11-03 00:00');
});

// ---------------------------------------------------------------- overlap rule

test('one slot: it blocks itself and any 60-minute booking that runs into it, but not the neighbours that only touch it', function () {
    boMake('slot', ['time' => '14:00']);   // [14:00, 14:30)
    $slots = boSlots();

    expect($slots['14:00'])->toBeFalse()
        ->and($slots['13:30'])->toBeFalse()   // 13:30-14:30 runs into it although it STARTS earlier
        ->and($slots['13:00'])->toBeTrue()    // 13:00-14:00 ends exactly when the blackout starts
        ->and($slots['14:30'])->toBeTrue()    // starts exactly when it ends
        ->and($slots['15:00'])->toBeTrue();
});

test('a time range blocks overlapping bookings with exact start and end boundaries', function () {
    Carbon::setTestNow(boAt('07:00'));       // early enough that the 09:00 slot is still bookable
    boMake('range', ['start_time' => '10:00', 'end_time' => '12:00']);
    $slots = boSlots();

    expect($slots['09:00'])->toBeTrue()       // 09:00-10:00 touches only
        ->and($slots['09:30'])->toBeFalse()   // 09:30-10:30 overlaps
        ->and($slots['10:00'])->toBeFalse()
        ->and($slots['11:30'])->toBeFalse()   // 11:30-12:30 overlaps
        ->and($slots['12:00'])->toBeTrue();   // starts exactly at the (exclusive) end
});

test('a whole-day blackout removes every slot that day and does not touch other days', function () {
    boMake('day');
    expect(collect(boSlots())->filter()->count())->toBe(0);

    $service = boSvc();
    expect($service->firstBlocking(Carbon::parse('2026-11-03 07:30', 'Asia/Manila')))->toBeNull()
        ->and($service->firstBlocking(Carbon::parse('2026-11-01 17:00', 'Asia/Manila')))->toBeNull()
        ->and($service->firstBlocking(boAt('17:00')))->not->toBeNull();
});

test('the overlap follows the configured appointment duration', function () {
    boMake('range', ['start_time' => '12:00', 'end_time' => '13:00']);
    expect(boSlots()['11:00'])->toBeTrue();            // 11:00-12:00 touches only

    config(['repair_reservations.appointment_minutes' => 90]);
    expect(boSlots()['11:00'])->toBeFalse()            // now 11:00-12:30
        ->and(boSlots()['10:30'])->toBeTrue();         // 10:30-12:00
});

test('the schedule is unchanged: 20 starts from 7:30 AM to 5:00 PM inclusive, and an unrelated blackout hides none of them', function () {
    boMake('range', ['start_time' => '23:00', 'end_time' => '23:30']);
    $slots = boSlots();
    expect($slots)->toHaveCount(20)->and(array_key_first($slots))->toBe('07:30')->and(array_key_last($slots))->toBe('17:00');
});

// ---------------------------------------------------------------- customer booking and acceptance

test('a customer cannot request a blocked time, directly or via a stale form, and sees no owner reason', function () {
    boMake('range', ['start_time' => '14:00', 'end_time' => '15:00', 'reason' => 'Mechanic training (private)']);
    $user = User::factory()->create(['role' => 'customer']);

    $response = $this->actingAs($user)->post(route('repair-reservations.store'), boRequest('14:00'));
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('not taking appointments')->not->toContain('Mechanic training');
    expect(RepairReservation::count())->toBe(0);

    $this->actingAs($user)->post(route('repair-reservations.store'), boRequest('13:30'))->assertSessionHas('error');   // runs into it
    expect(RepairReservation::count())->toBe(0);

    $this->actingAs($user)->post(route('repair-reservations.store'), boRequest('15:00'))->assertSessionHas('success');
    expect(RepairReservation::count())->toBe(1);
});

test('the slot list, a new request and owner acceptance all use the same rule', function () {
    $owner = boOwner();
    $pending = boBooking('14:00', 'pending');
    boMake('slot', ['time' => '14:00']);

    expect(boSlots()['14:00'])->toBeFalse();
    expect(fn () => app(RepairReservationService::class)->create($pending->customer, boRequest('14:00')))->toThrow(RepairReservationException::class, 'not taking appointments');
    expect(fn () => app(RepairReservationService::class)->accept($pending, $owner))->toThrow(RepairReservationException::class, 'blacked out');
    expect($pending->fresh()->status)->toBe('pending');
});

test('acceptance rechecks the blackout on fresh data, so a stale owner page cannot bypass it', function () {
    $owner = boOwner();
    $pending = boBooking('14:00', 'pending');
    $stale = RepairReservation::find($pending->id);          // what the owner's open page was built from

    boMake('day', ['reason' => 'Holiday']);

    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $stale))->assertSessionHas('error');
    expect(session('error'))->toContain('blacked out')->toContain('Holiday');
    expect($pending->fresh()->status)->toBe('pending')->and($pending->fresh()->decided_at)->toBeNull();
});

test('removing the blackout restores eligibility when capacity and deadlines allow', function () {
    $owner = boOwner();
    $pending = boBooking('14:00', 'pending');
    $blackout = boMake('slot', ['time' => '14:00']);

    expect(boSlots()['14:00'])->toBeFalse();
    $this->actingAs($owner)->delete(route('owner.repair-blackouts.destroy', $blackout))->assertSessionHas('success');
    expect(RepairBlackout::count())->toBe(0)->and(boSlots()['14:00'])->toBeTrue();

    expect(app(RepairReservationService::class)->accept($pending, $owner)['changed'])->toBeTrue();
    expect($pending->fresh()->status)->toBe('accepted');
});

test('removal does not bypass capacity or deadlines', function () {
    $owner = boOwner();
    config(['repair_reservations.capacity' => 1]);
    boBooking('14:00');                                      // accepted, fills the only bay at 14:00
    $blackout = boMake('slot', ['time' => '14:00']);

    $this->actingAs($owner)->delete(route('owner.repair-blackouts.destroy', $blackout));
    expect(boSlots()['14:00'])->toBeFalse();                 // still full by capacity

    Carbon::setTestNow(boAt('13:45'));                       // past the 14:00 slot's approval deadline (13:30)
    expect(boSlots()['14:00'])->toBeFalse();
});

// ---------------------------------------------------------------- existing reservations

test('an existing accepted reservation stays accepted, is flagged with the reason, and can still check in and start its repair', function () {
    $owner = boOwner();
    $accepted = boBooking('11:00');
    boMake('range', ['start_time' => '10:30', 'end_time' => '12:00', 'reason' => 'Power outage']);

    expect($accepted->fresh()->status)->toBe('accepted');
    $html = $this->actingAs($owner)->get(route('owner.repair-reservations.index', ['tab' => 'accepted']))->assertOk()->getContent();
    expect($html)->toContain('Blackout conflict')->toContain('Power outage')->toContain('stays accepted');

    $page = $this->actingAs($owner)->get(route('owner.repair-blackouts.index'))->assertOk()->getContent();
    expect($page)->toContain($accepted->reference)->toContain('stays accepted');

    // Already-approved work is not blocked by the later blackout.
    expect(app(RepairReservationService::class)->checkIn($accepted, $owner)['changed'])->toBeTrue();
    Carbon::setTestNow(boAt('11:00'));
    $result = app(RepairCapacityService::class)->startReservationRepair($accepted, $owner, 60);
    expect($result['changed'])->toBeTrue()->and($accepted->fresh()->status)->toBe('accepted')
        ->and(app(RepairCapacityService::class)->occupancy(boAt('11:00')))->toHaveCount(1);   // one job, counted once

    // Cancelling remains an explicit owner decision under the existing rules (refused once the repair started).
    $other = boBooking('11:30');
    expect(app(RepairReservationService::class)->cancel($other, $owner, 'Closed for the outage')['changed'])->toBeTrue();
});

test('pending requests are shown as conflicts, cannot be accepted, and are only rejected by an explicit owner action', function () {
    $owner = boOwner();
    $pending = boBooking('11:00', 'pending');
    $untouched = boBooking('16:00', 'pending');
    boMake('range', ['start_time' => '10:30', 'end_time' => '12:00', 'reason' => 'Holiday']);

    expect($pending->fresh()->status)->toBe('pending')->and($untouched->fresh()->status)->toBe('pending');   // nothing auto-rejected

    $html = $this->actingAs($owner)->get(route('owner.repair-reservations.index', ['tab' => 'pending']))->assertOk()->getContent();
    expect($html)->toContain('Blackout conflict')->toContain('Holiday')->toContain('Accept (blacked out)');

    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $pending))->assertSessionHas('error');
    expect($pending->fresh()->status)->toBe('pending');

    $this->actingAs($owner)->post(route('owner.repair-reservations.reject', $pending), ['reason' => 'Shop closed at that time'])->assertSessionHas('success');
    expect($pending->fresh()->status)->toBe('rejected')->and($pending->fresh()->decision_reason)->toBe('Shop closed at that time')
        ->and($untouched->fresh()->status)->toBe('pending');
});

test('a blackout never consumes capacity: it blocks bookings but not the three bays', function () {
    $svc = app(RepairCapacityService::class);
    boMake('range', ['start_time' => '10:00', 'end_time' => '12:00']);

    expect($svc->occupancy(boAt('09:00')))->toBe([])->and($svc->reservationFits(boAt('10:00')))->toBeTrue();   // capacity unaffected
    boBooking('09:00'); boBooking('09:00'); boBooking('09:00');
    expect($svc->reservationFits(boAt('09:00')))->toBeFalse();                                                // three bays still enforced
});

// ---------------------------------------------------------------- authorization and input

test('only the owner can see, create or remove blackouts', function () {
    $owner = boOwner();
    $blackout = boMake('day');
    $customer = User::factory()->create(['role' => 'customer']);
    $payload = ['type' => 'day', 'date' => '2026-11-04'];

    $this->get(route('owner.repair-blackouts.index'))->assertRedirect();
    $this->post(route('owner.repair-blackouts.store'), $payload)->assertRedirect();
    $this->delete(route('owner.repair-blackouts.destroy', $blackout))->assertRedirect();
    $this->actingAs($customer)->get(route('owner.repair-blackouts.index'))->assertForbidden();
    $this->actingAs($customer)->post(route('owner.repair-blackouts.store'), $payload)->assertForbidden();
    $this->actingAs($customer)->delete(route('owner.repair-blackouts.destroy', $blackout))->assertForbidden();
    expect(RepairBlackout::count())->toBe(1);
    expect(fn () => boSvc()->create($customer, $payload))->toThrow(RepairReservationException::class);

    $this->actingAs($owner)->post(route('owner.repair-blackouts.store'), $payload + ['reason' => 'Holiday'])->assertSessionHas('success');
    expect(RepairBlackout::count())->toBe(2);
});

test('the blackout form rejects invalid input and keeps CSRF protection', function () {
    $owner = boOwner();
    $post = fn (array $d) => $this->actingAs($owner)->post(route('owner.repair-blackouts.store'), $d);

    $post(['type' => 'forever', 'date' => '2026-11-02'])->assertSessionHasErrors('type');
    $post(['type' => 'day', 'date' => '02/11/2026'])->assertSessionHasErrors('date');
    $post(['type' => 'day', 'date' => '2026-11-01'])->assertSessionHasErrors('date');                       // yesterday
    $post(['type' => 'slot', 'date' => '2026-11-03'])->assertSessionHasErrors('time');
    $post(['type' => 'slot', 'date' => '2026-11-03', 'time' => '07:15'])->assertSessionHas('error');         // not a real slot
    $post(['type' => 'slot', 'date' => '2026-11-03', 'time' => '17:30'])->assertSessionHas('error');         // after the last start
    $post(['type' => 'range', 'date' => '2026-11-03', 'start_time' => '12:00', 'end_time' => '12:00'])->assertSessionHas('error');
    $post(['type' => 'range', 'date' => '2026-11-03', 'start_time' => '13:00', 'end_time' => '12:00'])->assertSessionHas('error');
    $post(['type' => 'range', 'date' => '2026-11-03'])->assertSessionHasErrors(['start_time', 'end_time']);
    $post(['type' => 'range', 'date' => '2026-11-02', 'start_time' => '07:00', 'end_time' => '08:00'])->assertSessionHas('error');   // already over
    $post(['type' => 'day', 'date' => '2026-11-03', 'reason' => str_repeat('x', 256)])->assertSessionHasErrors('reason');
    expect(RepairBlackout::count())->toBe(0);

    // The form is a web-group POST with a CSRF token (the test kernel switches the CSRF check off).
    $this->actingAs($owner)->get(route('owner.repair-blackouts.index'))->assertSee('name="_token"', false);
});

test('a blackout that is already over is kept as history and cannot be removed', function () {
    $owner = boOwner();
    $blackout = boMake('range', ['start_time' => '09:30', 'end_time' => '10:00']);

    Carbon::setTestNow(boAt('10:30'));
    $this->actingAs($owner)->delete(route('owner.repair-blackouts.destroy', $blackout))->assertSessionHas('error');
    expect(RepairBlackout::count())->toBe(1);
    $this->actingAs($owner)->get(route('owner.repair-blackouts.index'))->assertOk()->assertSee('Past (history)');
});

test('the owner reservation page links to blackouts and customers never see the reason on their request page', function () {
    $owner = boOwner();
    $accepted = boBooking('11:00');
    boMake('day', ['reason' => 'Private owner reason']);

    $this->actingAs($owner)->get(route('owner.repair-reservations.index'))->assertOk()->assertSee('Blackouts (closed times)');
    $this->actingAs($accepted->customer->user)->get(route('repair-reservations.show', $accepted->reference))->assertOk()->assertDontSee('Private owner reason');
    $this->actingAs($accepted->customer->user)->get(route('repair-reservations.create'))->assertOk()->assertDontSee('Private owner reason');
});

test('Phase 4 leaves the automatic features off', function () {
    expect(config('repair_reservations.auto_cancel.enabled'))->toBeFalse()
        ->and(config('repair_reservations.pending_expiry.enabled'))->toBeFalse();
});
