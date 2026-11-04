<?php

use App\Models\Customer;
use App\Models\Repair;
use App\Models\RepairReservation;
use App\Models\RepairReservationEvent;
use App\Models\User;
use App\Notifications\StatusUpdated;
use App\Services\RepairReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * RD2-R3: same-day repair / service appointment requests.
 * Test data only: fixed clock (Mon 2 Nov 2026, 09:00 Asia/Manila), faked notifications, isolated SQLite.
 */

beforeEach(function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::parse('2026-11-02 09:00:00', 'Asia/Manila'));
    config([
        'repair_reservations.capacity' => 3,
        'repair_reservations.capacity_unit' => 'per_day',
        'repair_reservations.auto_cancel.enabled' => false,
        'repair_reservations.auto_cancel.eligible_statuses' => [],
    ]);
});

afterEach(fn () => Carbon::setTestNow());

function rrCustomer(): array
{
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

    return [$user, $customer];
}

function rrOwner(): User
{
    $owner = User::factory()->create(['role' => 'owner']);
    Cache::forget('users_role_owner_ids');

    return $owner;
}

function rrData(array $over = []): array
{
    return array_merge([
        'appointment_date' => '2026-11-02',
        'appointment_time' => '10:00',
        'service_type' => 'brakes',
        'bike_model' => 'Honda Click 125',
        'description' => 'Rear brake feels soft and squeaks.',
        'accept_terms' => '1',
    ], $over);
}

function rrBook(User $user, array $over = [])
{
    return test()->actingAs($user)->post(route('repair-reservations.store'), rrData($over));
}

/** Book through the HTTP flow with a fresh customer; returns [user, reservation]. */
function rrPending(array $over = []): array
{
    [$user] = rrCustomer();
    rrBook($user, $over)->assertSessionHasNoErrors()->assertSessionHas('success');

    return [$user, RepairReservation::latest('id')->first()];
}

test('an authenticated customer can request a same-day appointment and it stays pending', function () {
    $owner = rrOwner();
    [$user, $customer] = rrCustomer();

    $response = rrBook($user);
    $r = RepairReservation::first();
    $response->assertRedirect(route('repair-reservations.show', $r->reference))->assertSessionHas('success');

    expect($r->status)->toBe('pending')->and($r->customer_id)->toBe($customer->id)
        ->and($r->service_date->format('Y-m-d'))->toBe('2026-11-02')
        ->and($r->scheduled_at->timezone('Asia/Manila')->format('H:i'))->toBe('10:00')
        ->and($r->terms_accepted_at)->not->toBeNull();

    // Pending is not an appointment: it consumes no capacity.
    expect(app(RepairReservationService::class)->acceptedCount($r->scheduled_at))->toBe(0);

    $this->actingAs($user)->get(route('repair-reservations.show', $r->reference))->assertOk()
        ->assertSee('Awaiting owner approval')->assertSee('not a confirmed appointment');

    Notification::assertSentToTimes($user, StatusUpdated::class, 1);
    Notification::assertSentTo($user, StatusUpdated::class, fn ($n) => $n->title === 'Service request received' && str_contains($n->message, 'NOT confirmed'));
    Notification::assertSentTo($owner, StatusUpdated::class, fn ($n) => $n->title === 'New service request' && $n->link === route('owner.repair-reservations.index'));
    expect(RepairReservationEvent::where('repair_reservation_id', $r->id)->count())->toBe(1);
});

test('guests, owners and other customers cannot book or see someone else\'s request', function () {
    $this->get(route('repair-reservations.create'))->assertRedirect('/login');
    $this->post(route('repair-reservations.store'), rrData())->assertRedirect('/login');
    expect(RepairReservation::count())->toBe(0);

    $this->actingAs(rrOwner())->post(route('repair-reservations.store'), rrData())->assertForbidden();
    expect(RepairReservation::count())->toBe(0);

    [$owner1, $r] = rrPending();
    [$stranger] = rrCustomer();
    $this->actingAs($stranger)->get(route('repair-reservations.show', $r->reference))->assertNotFound();
    $this->actingAs($stranger)->post(route('repair-reservations.cancel', $r->reference))->assertNotFound();
    expect($r->fresh()->status)->toBe('pending');

    // Customers cannot reach the owner screens or decisions.
    $this->actingAs($stranger)->get(route('owner.repair-reservations.index'))->assertStatus(403);
    $this->actingAs($stranger)->post(route('owner.repair-reservations.accept', $r))->assertStatus(403);
    $this->actingAs($stranger)->post(route('owner.repair-reservations.reject', $r), ['reason' => 'x'])->assertStatus(403);
    expect($r->fresh()->status)->toBe('pending');
});

test('only today is accepted: future and past dates are refused', function () {
    foreach (['2026-11-03', '2026-11-09', '2027-01-01', '2026-11-01'] as $date) {
        [$user] = rrCustomer();
        rrBook($user, ['appointment_date' => $date])->assertSessionHas('error');
    }
    expect(RepairReservation::count())->toBe(0);

    [$user] = rrCustomer();
    rrBook($user, ['appointment_date' => 'not-a-date'])->assertSessionHasErrors('appointment_date');
});

test('a request must be sent strictly before the approval deadline (start − 30 min)', function () {
    // now = 09:00. Approval deadline = start − 30 min (buffer 0). 09:30's deadline is exactly 09:00 = now,
    // so it is refused; the boundary is strict.
    foreach (['08:00', '08:30', '09:00', '09:30'] as $time) {
        [$user] = rrCustomer();
        rrBook($user, ['appointment_time' => $time])->assertSessionHas('error');
    }
    expect(RepairReservation::count())->toBe(0);

    // 10:00 (deadline 09:30, strictly after now) is the earliest time that can still be requested.
    [$user] = rrCustomer();
    rrBook($user, ['appointment_time' => '10:00'])->assertSessionHas('success');
});

test('only slots inside 7:30 AM–5:00 PM (inclusive) are accepted', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-02 05:00:00', 'Asia/Manila'));

    foreach (['07:00', '06:59', '17:30', '23:00', '09:15'] as $time) {
        [$user] = rrCustomer();
        rrBook($user, ['appointment_time' => $time])->assertSessionHas('error');
    }
    expect(RepairReservation::count())->toBe(0);

    // 07:30 is the first start and 17:00 the last (inclusive).
    foreach (['07:30', '17:00'] as $time) {
        [$user] = rrCustomer();
        rrBook($user, ['appointment_time' => $time])->assertSessionHas('success');
    }
    expect(RepairReservation::count())->toBe(2);

    $slots = app(RepairReservationService::class)->availableSlots();
    expect($slots[0]['label'])->toBe('7:30 AM')->and(end($slots)['label'])->toBe('5:00 PM')->and(count($slots))->toBe(20);
});

test('available slots hide times past their approval deadline and the form shows only what can be chosen', function () {
    $slots = collect(app(RepairReservationService::class)->availableSlots());
    expect($slots->firstWhere('value', '08:30')['available'])->toBeFalse()
        ->and($slots->firstWhere('value', '09:00')['available'])->toBeFalse()
        ->and($slots->firstWhere('value', '09:30')['available'])->toBeFalse()
        ->and($slots->firstWhere('value', '10:00')['available'])->toBeTrue();

    [$user] = rrCustomer();
    $this->actingAs($user)->get(route('repair-reservations.create'))->assertOk()
        ->assertSee('Same-day only')->assertSee('not a confirmed appointment', false);
});

test('validation covers description, bike model, terms, service type and cannot set status', function () {
    [$user] = rrCustomer();
    rrBook($user, ['description' => 'short'])->assertSessionHasErrors('description');
    rrBook($user, ['bike_model' => ''])->assertSessionHasErrors('bike_model');
    rrBook($user, ['accept_terms' => null])->assertSessionHasErrors('accept_terms');
    rrBook($user, ['service_type' => 'free-money'])->assertSessionHas('error');
    expect(RepairReservation::count())->toBe(0);

    rrBook($user, ['status' => 'accepted', 'customer_id' => 999, 'decided_by' => 1])->assertSessionHas('success');
    $r = RepairReservation::first();
    expect($r->status)->toBe('pending')->and($r->decided_by)->toBeNull()->and($r->customer_id)->not->toBe(999);
});

test('a double submit creates one request; a new one is possible after cancelling', function () {
    [$user] = rrCustomer();
    rrBook($user)->assertSessionHas('success');
    rrBook($user, ['appointment_time' => '11:00'])->assertSessionHas('error');
    rrBook($user)->assertSessionHas('error');
    expect(RepairReservation::count())->toBe(1);

    $r = RepairReservation::first();
    $this->actingAs($user)->post(route('repair-reservations.cancel', $r->reference))->assertSessionHas('success');
    rrBook($user, ['appointment_time' => '11:00'])->assertSessionHas('success');
    expect(RepairReservation::count())->toBe(2);
});

test('the owner accepts a request: status, history, customer notification, and a repeat changes nothing', function () {
    $owner = rrOwner();
    [$user, $r] = rrPending();
    Notification::fake();

    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $r))->assertSessionHas('success');
    $r->refresh();
    expect($r->status)->toBe('accepted')->and($r->decided_by)->toBe($owner->id)->and($r->decided_at)->not->toBeNull();
    Notification::assertSentTo($user, StatusUpdated::class, fn ($n) => $n->title === 'Service appointment accepted' && $n->link === route('repair-reservations.show', $r->reference));
    Notification::assertSentToTimes($user, StatusUpdated::class, 1);

    // Repeated click / double submit: no change, no second notification, no second history row.
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $r))->assertSessionHas('success');
    Notification::assertSentToTimes($user, StatusUpdated::class, 1);
    expect(RepairReservationEvent::where('repair_reservation_id', $r->id)->where('to_status', 'accepted')->count())->toBe(1);

    $this->actingAs($user)->get(route('repair-reservations.show', $r->reference))->assertOk()->assertSee('Accepted.');
});

test('the owner rejects with a reason; the customer is told and can book again', function () {
    $owner = rrOwner();
    [$user, $r] = rrPending();
    Notification::fake();

    $this->actingAs($owner)->post(route('owner.repair-reservations.reject', $r), ['reason' => ''])->assertSessionHasErrors('reason');
    expect($r->fresh()->status)->toBe('pending');

    $this->actingAs($owner)->post(route('owner.repair-reservations.reject', $r), ['reason' => 'Mechanic unavailable at that time'])->assertSessionHas('success');
    $r->refresh();
    expect($r->status)->toBe('rejected')->and($r->decision_reason)->toBe('Mechanic unavailable at that time')->and($r->active_day_key)->toBeNull();
    Notification::assertSentTo($user, StatusUpdated::class, fn ($n) => $n->title === 'Service request not accepted' && str_contains($n->message, 'Mechanic unavailable'));

    $this->actingAs($user)->get(route('repair-reservations.show', $r->reference))->assertSee('Not accepted')->assertSee('Mechanic unavailable');
    $this->actingAs($owner)->post(route('owner.repair-reservations.reject', $r), ['reason' => 'again'])->assertSessionHas('success');
    Notification::assertSentToTimes($user, StatusUpdated::class, 1);

    rrBook($user, ['appointment_time' => '14:00'])->assertSessionHas('success');
});

test('invalid transitions are refused and change nothing', function () {
    $owner = rrOwner();

    [, $rejected] = rrPending();
    $this->actingAs($owner)->post(route('owner.repair-reservations.reject', $rejected), ['reason' => 'No slot']);
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $rejected))->assertSessionHas('error');
    $this->actingAs($owner)->post(route('owner.repair-reservations.cancel', $rejected), ['reason' => 'x'])->assertSessionHas('error');
    expect($rejected->fresh()->status)->toBe('rejected');

    [, $accepted] = rrPending(['appointment_time' => '11:00']);
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $accepted));
    $this->actingAs($owner)->post(route('owner.repair-reservations.reject', $accepted), ['reason' => 'late'])->assertSessionHas('error');
    expect($accepted->fresh()->status)->toBe('accepted');

    // A pending request whose time has passed cannot be accepted any more.
    [, $stale] = rrPending(['appointment_time' => '10:00']);
    Carbon::setTestNow(Carbon::parse('2026-11-02 10:30:00', 'Asia/Manila'));
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $stale))->assertSessionHas('error');
    expect($stale->fresh()->status)->toBe('pending');
});

test('cancelling: customer own request, owner with reason, notifications go to the other side only', function () {
    $owner = rrOwner();
    [$user, $r] = rrPending();
    Notification::fake();

    $this->actingAs($user)->post(route('repair-reservations.cancel', $r->reference), ['reason' => 'changed plans'])->assertSessionHas('success');
    expect($r->fresh()->status)->toBe('cancelled')->and($r->fresh()->cancelled_by)->toBe('customer');
    Notification::assertSentTo($owner, StatusUpdated::class, fn ($n) => $n->title === 'Service request cancelled');
    $this->actingAs($user)->post(route('repair-reservations.cancel', $r->reference))->assertSessionHas('success'); // repeat
    Notification::assertSentToTimes($owner, StatusUpdated::class, 1);

    [$user2, $r2] = rrPending(['appointment_time' => '13:00']);
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $r2));
    Notification::fake();
    $this->actingAs($owner)->post(route('owner.repair-reservations.cancel', $r2), ['reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs($owner)->post(route('owner.repair-reservations.cancel', $r2), ['reason' => 'Mechanic is sick'])->assertSessionHas('success');
    expect($r2->fresh()->status)->toBe('cancelled')->and($r2->fresh()->cancelled_by)->toBe('owner');
    Notification::assertSentTo($user2, StatusUpdated::class, fn ($n) => $n->title === 'Service appointment cancelled' && str_contains($n->message, 'Mechanic is sick'));
});

test('capacity (per day) is enforced on acceptance, pending never consumes it, and freeing a slot reopens it', function () {
    config(['repair_reservations.capacity' => 2]);
    $owner = rrOwner();
    $rs = [];
    foreach (['10:00', '11:00', '12:00', '13:00'] as $t) {
        $rs[] = rrPending(['appointment_time' => $t])[1];   // all four can be pending
    }
    expect(RepairReservation::where('status', 'pending')->count())->toBe(4);

    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $rs[0]))->assertSessionHas('success');
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $rs[1]))->assertSessionHas('success');
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $rs[2]))->assertSessionHas('error');
    expect($rs[2]->fresh()->status)->toBe('pending')
        ->and(RepairReservation::where('status', 'accepted')->count())->toBe(2);

    // Once the day is full, new customer requests are refused up front and slots show as unavailable.
    [$late] = rrCustomer();
    rrBook($late, ['appointment_time' => '15:00'])->assertSessionHas('error');
    expect(collect(app(RepairReservationService::class)->availableSlots())->where('available', true)->count())->toBe(0);

    $this->actingAs($owner)->post(route('owner.repair-reservations.cancel', $rs[0]), ['reason' => 'No parts']);
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $rs[2]))->assertSessionHas('success');
    expect(RepairReservation::where('status', 'accepted')->count())->toBe(2);
});

test('capacity unit per_slot and simultaneous behave as configured', function () {
    $owner = rrOwner();
    config(['repair_reservations.capacity' => 1, 'repair_reservations.capacity_unit' => 'per_slot']);
    [, $a] = rrPending(['appointment_time' => '10:00']);
    [, $b] = rrPending(['appointment_time' => '10:00']);
    [, $c] = rrPending(['appointment_time' => '11:00']);
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $a))->assertSessionHas('success');
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $b))->assertSessionHas('error');
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $c))->assertSessionHas('success');

    [, $d] = rrPending(['appointment_time' => '10:30']);   // overlaps the accepted 10:00 and 11:00 windows
    [, $e] = rrPending(['appointment_time' => '15:00']);   // overlaps nothing accepted
    config(['repair_reservations.capacity_unit' => 'simultaneous', 'repair_reservations.appointment_minutes' => 60]);
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $d))->assertSessionHas('error');
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $e))->assertSessionHas('success');
});

test('capacity is configuration, clamped to 1–5, and an unknown unit falls back safely', function () {
    $svc = app(RepairReservationService::class);
    foreach ([[0, 1], [3, 3], [5, 5], [99, 5], [-4, 1]] as [$set, $expect]) {
        config(['repair_reservations.capacity' => $set]);
        expect($svc->capacity())->toBe($expect);
    }
    config(['repair_reservations.capacity_unit' => 'nonsense']);
    expect($svc->capacityUnit())->toBe('per_day');
});

test('concurrent owner decisions cannot exceed capacity or double-apply', function () {
    config(['repair_reservations.capacity' => 1]);
    $owner = rrOwner();
    $other = rrOwner();
    [, $a] = rrPending(['appointment_time' => '10:00']);
    [, $b] = rrPending(['appointment_time' => '10:30']);
    [, $c] = rrPending(['appointment_time' => '14:00']);
    $svc = app(RepairReservationService::class);

    // Two owner sessions act on stale copies of both rows (as two simultaneous requests would).
    $staleA1 = RepairReservation::find($a->id); $staleA2 = RepairReservation::find($a->id);
    $staleB = RepairReservation::find($b->id);

    expect($svc->accept($staleA1, $owner)['changed'])->toBeTrue();
    expect($svc->accept($staleA2, $other)['changed'])->toBeFalse();      // same request twice: idempotent
    expect(fn () => $svc->accept($staleB, $other))->toThrow(\App\Services\RepairReservationException::class);   // capacity re-checked on fresh data

    expect(RepairReservation::where('status', 'accepted')->count())->toBe(1)
        ->and(RepairReservationEvent::where('to_status', 'accepted')->count())->toBe(1);

    // Accept vs reject on the same request: whichever lands first wins, the other is refused.
    $staleC = RepairReservation::find($c->id);
    $svc->reject($c, $owner, 'Closed early');
    expect(fn () => $svc->accept($staleC, $other))->toThrow(\App\Services\RepairReservationException::class);
    expect($c->fresh()->status)->toBe('rejected');

    // The per-day lock row used for serialisation exists.
    expect(DB::table('repair_reservation_days')->where('service_date', '2026-11-02')->exists())->toBeTrue();
});

test('a non-owner can never decide through the service either', function () {
    [$user, $r] = rrPending();
    $svc = app(RepairReservationService::class);
    expect(fn () => $svc->accept($r, $user))->toThrow(\App\Services\RepairReservationException::class)
        ->and(fn () => $svc->reject($r, $user, 'x'))->toThrow(\App\Services\RepairReservationException::class);
    expect($r->fresh()->status)->toBe('pending');
});

test('the owner list shows pending requests with time, service and details and marks passed times', function () {
    $owner = rrOwner();
    rrPending(['description' => 'Chain keeps slipping on hills', 'appointment_time' => '10:00']);
    $page = $this->actingAs($owner)->get(route('owner.repair-reservations.index'))->assertOk()
        ->assertSee('Chain keeps slipping on hills')->assertSee('10:00 AM')->assertSee('Brakes')->assertSee('not an appointment')->getContent();
    expect($page)->toContain('Accept')->toContain('Reject');

    Carbon::setTestNow(Carbon::parse('2026-11-02 10:30:00', 'Asia/Manila'));
    $this->actingAs($owner)->get(route('owner.repair-reservations.index'))->assertSee('Time has passed');
    $this->actingAs($owner)->get(route('owner.repair-reservations.index', ['tab' => 'history']))->assertOk();
});

// Phase 5 replaced the old pre-appointment auto-cancel window tests; the new rules are in Phase5AutoDeadlineTest.php.

test('the existing repair module and customer tracking are unaffected', function () {
    $owner = rrOwner();
    [$user, $customer] = rrCustomer();
    $repair = Repair::create(['customer_name' => $user->name, 'customer_id' => $customer->id, 'bike_model' => 'Yamaha NMAX', 'issue' => 'Oil leak', 'status' => 'ongoing', 'cost' => 560, 'is_paid' => false]);

    rrBook($user)->assertSessionHas('success');
    expect(Repair::count())->toBe(1)->and($repair->fresh()->status)->toBe('ongoing');

    $this->actingAs($user)->get('/customer/dashboard?tab=repairs')->assertOk()
        ->assertSee('Track My Repairs')->assertSee('Yamaha NMAX')->assertSee('Service Appointments')->assertSee('Book a service today')
        ->assertSee('Awaiting owner approval');

    $this->actingAs($owner)->get(route('repairs.index'))->assertOk();
    $this->actingAs($owner)->put(route('repairs.update', $repair), ['customer_name' => $user->name, 'bike_model' => 'Yamaha NMAX', 'issue' => 'Oil leak', 'status' => 'done', 'cost' => 560])->assertStatus(302);
    expect(RepairReservation::count())->toBe(1);

    // Another customer's dashboard never lists this customer's service request.
    [$other] = rrCustomer();
    $this->actingAs($other)->get('/customer/dashboard?tab=repairs')->assertOk()->assertDontSee('Honda Click 125');
});

test('the slot list gives the same answer as the per-slot capacity check, in a constant number of queries', function () {
    $owner = rrOwner();
    foreach (['per_day', 'per_slot', 'simultaneous'] as $unit) {
        config(['repair_reservations.capacity' => 1, 'repair_reservations.capacity_unit' => $unit, 'repair_reservations.appointment_minutes' => 60]);
        RepairReservation::query()->delete();
        DB::table('repair_reservation_days')->delete();
        config(['repair_reservations.capacity_unit' => 'per_slot']);          // create two accepted requests regardless of the unit under test
        foreach (['10:00', '14:00'] as $t) {
            [, $r] = rrPending(['appointment_time' => $t]);
            $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $r));
        }
        config(['repair_reservations.capacity_unit' => $unit]);

        $svc = app(RepairReservationService::class);
        DB::enableQueryLog(); DB::flushQueryLog();
        $slots = $svc->availableSlots();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($queries)->toBeLessThanOrEqual(2);   // data query + the Phase 4 blackout lookup: constant, not per slot
        foreach ($slots as $slot) {
            $at = Carbon::parse('2026-11-02 '.$slot['value'], 'Asia/Manila');
            $expected = $svc->now()->lt($svc->approvalDeadlineFor($at)) && ! $svc->isFull($at);
            expect($slot['available'])->toBe($expected, "$unit {$slot['value']}");
        }
    }
});

// RD2-R5: service appointment terms state only confirmed policies.
test('booking page shows service appointment terms without unapproved policies', function () {
    [$user] = rrCustomer();
    $html = $this->actingAs($user)->get(route('repair-reservations.create'))->assertOk()
        ->assertSee('Service appointment terms')
        ->assertSee('today only')
        ->assertSee('accept')->getContent();

    $terms = strtolower(strip_tags(substr($html, strpos($html, 'Service appointment terms'), 2500)));
    foreach (['automatically cancel', 'auto-cancel', 'refund', 'maximum', 'capacity'] as $banned) {
        expect($terms)->not->toContain($banned);
    }
});

// RD2-R3 Phase 1: both deadlines are stored at creation and used as the strict acceptance boundary.
test('both deadlines are stored when the request is created and acceptance does not move them', function () {
    [, $r] = rrPending(['appointment_time' => '10:00']);   // now 09:00; start 10:00

    expect($r->check_in_deadline_at)->not->toBeNull()->and($r->approval_deadline_at)->not->toBeNull()
        ->and($r->check_in_deadline_at->timezone('Asia/Manila')->format('H:i'))->toBe('09:30')   // 10:00 − 30 min
        ->and($r->approval_deadline_at->timezone('Asia/Manila')->format('H:i'))->toBe('09:30');  // buffer 0 → same instant

    $before = [$r->check_in_deadline_at->toIso8601String(), $r->approval_deadline_at->toIso8601String()];

    $owner = rrOwner();
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $r))->assertSessionHas('success');
    $r->refresh();

    expect($r->status)->toBe('accepted')
        ->and([$r->check_in_deadline_at->toIso8601String(), $r->approval_deadline_at->toIso8601String()])->toBe($before);
});

test('acceptance is allowed up to just before the approval deadline and refused at or after it', function () {
    $owner = rrOwner();
    $svc = app(RepairReservationService::class);

    // 09:29:59 — strictly before the 09:30 approval deadline of a 10:00 start → accept succeeds.
    [, $early] = rrPending(['appointment_time' => '10:00']);
    Carbon::setTestNow(Carbon::parse('2026-11-02 09:29:59', 'Asia/Manila'));
    expect($svc->accept(RepairReservation::find($early->id), $owner)['changed'])->toBeTrue();

    // 09:30:00 — exactly at the approval deadline → refused; the request stays pending.
    Carbon::setTestNow(Carbon::parse('2026-11-02 09:00:00', 'Asia/Manila'));
    [, $late] = rrPending(['appointment_time' => '10:00']);
    Carbon::setTestNow(Carbon::parse('2026-11-02 09:30:00', 'Asia/Manila'));
    expect(fn () => $svc->accept(RepairReservation::find($late->id), $owner))
        ->toThrow(\App\Services\RepairReservationException::class);
    expect($late->fresh()->status)->toBe('pending');
});

test('a config change after creation does not move the stored deadlines', function () {
    [, $r] = rrPending(['appointment_time' => '10:00']);
    $stored = $r->check_in_deadline_at->toIso8601String();

    // Change the check-in window after the fact; the already-stored snapshot must not move.
    config(['repair_reservations.check_in.minutes_before' => 60]);
    expect($r->fresh()->check_in_deadline_at->toIso8601String())->toBe($stored);
});
