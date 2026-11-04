<?php

use App\Models\Customer;
use App\Models\Repair;
use App\Models\RepairReservation;
use App\Models\RepairReservationEvent;
use App\Models\User;
use App\Notifications\StatusUpdated;
use App\Services\RepairCapacityException;
use App\Services\RepairCapacityService;
use App\Services\RepairReservationException;
use App\Services\RepairReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

/**
 * RD2-R3 Phase 3: owner check-in, reservation -> repair conversion (no double counting), completion and
 * extensions. Test data only: fixed clock (Mon 2 Nov 2026, 09:00 Asia/Manila), faked notifications, isolated SQLite.
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

function p3At(string $hm): Carbon
{
    return Carbon::parse('2026-11-02 '.$hm, 'Asia/Manila');
}

function p3Owner(): User
{
    $owner = User::factory()->create(['role' => 'owner']);
    Cache::forget('users_role_owner_ids');

    return $owner;
}

/** Accepted reservation at a start time (deadline = start − 30 min), optionally already checked in. */
function p3Booking(string $hm, bool $checkedIn = false, string $status = 'accepted'): RepairReservation
{
    static $n = 0;
    $n++;
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    $at = p3At($hm);

    return RepairReservation::create([
        'reference' => 'RR-P3-'.$n, 'customer_id' => $customer->id, 'service_date' => '2026-11-02',
        'scheduled_at' => $at, 'check_in_deadline_at' => $at->copy()->subMinutes(30), 'approval_deadline_at' => $at->copy()->subMinutes(30),
        'service_type' => 'brakes', 'bike_model' => 'Click 125', 'description' => 'phase three row', 'status' => $status,
        'active_day_key' => 'p3:'.$n, 'checked_in_at' => $checkedIn ? now() : null,
    ]);
}

function p3Walkin(string $status = 'ongoing', ?string $startHm = '08:30', ?int $minutes = 120): Repair
{
    $r = Repair::create(['customer_name' => 'Walk In', 'bike_model' => 'Mio', 'issue' => 'Noise', 'cost' => 500, 'status' => $status, 'is_paid' => false]);
    if ($startHm) {
        $r->forceFill(['started_at' => p3At($startHm), 'estimated_minutes' => $minutes])->save();
    }

    return $r;
}

function p3Svc(): RepairCapacityService
{
    return app(RepairCapacityService::class);
}

// ---------------------------------------------------------------- migration

test('the Phase 3 migration adds the nullable columns and a unique reservation link, and keeps completed_at as the only repair end', function () {
    foreach (['checked_in_at', 'checked_in_by', 'received_by', 'estimated_minutes', 'expected_end_at', 'ended_at'] as $c) {
        expect(Schema::hasColumn('repair_reservations', $c))->toBeTrue("repair_reservations.$c");
    }
    expect(Schema::hasColumn('repairs', 'reservation_id'))->toBeTrue()
        ->and(Schema::hasColumn('repairs', 'completed_at'))->toBeTrue()
        ->and(Schema::hasColumn('repairs', 'ended_at'))->toBeFalse()
        ->and(Schema::hasColumn('repairs', 'expected_end_at'))->toBeFalse();

    $res = p3Booking('10:00');
    Repair::create(['customer_name' => 'A', 'issue' => 'x', 'status' => 'ongoing', 'is_paid' => false, 'reservation_id' => $res->id]);
    expect(fn () => Repair::create(['customer_name' => 'B', 'issue' => 'y', 'status' => 'ongoing', 'is_paid' => false, 'reservation_id' => $res->id]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

// ---------------------------------------------------------------- check-in

test('check-in is valid at exactly the deadline and invalid one second later (07:30 appointment)', function () {
    $owner = p3Owner();
    $res = p3Booking('07:30');   // deadline 07:00:00

    Carbon::setTestNow(p3At('07:00:01'));
    expect(fn () => app(RepairReservationService::class)->checkIn($res, $owner))->toThrow(RepairReservationException::class, 'deadline');
    expect($res->fresh()->checked_in_at)->toBeNull();

    Carbon::setTestNow(p3At('07:00:00'));
    $result = app(RepairReservationService::class)->checkIn($res, $owner, 'Mang Tonyo');
    expect($result['changed'])->toBeTrue()
        ->and($res->fresh()->status)->toBe('accepted')                   // stays accepted
        ->and($res->fresh()->received_by)->toBe('Mang Tonyo')
        ->and($res->fresh()->checked_in_by)->toBe($owner->id);
});

test('a repeated check-in creates no second event and no second notification', function () {
    $owner = p3Owner();
    $res = p3Booking('10:00');
    $customerUser = $res->customer->user;

    $first = app(RepairReservationService::class)->checkIn($res, $owner);
    $again = app(RepairReservationService::class)->checkIn($res, $owner, 'Someone else');

    expect($first['changed'])->toBeTrue()->and($again['changed'])->toBeFalse()
        ->and($res->fresh()->received_by)->toBeNull();                    // first record wins
    expect(RepairReservationEvent::where('repair_reservation_id', $res->id)->where('note', 'like', 'Checked in%')->count())->toBe(1);
    Notification::assertSentToTimes($customerUser, StatusUpdated::class, 1);
});

test('only an authenticated owner can check in: customers, guests and the service itself refuse', function () {
    $owner = p3Owner();
    $res = p3Booking('10:00');
    $customerUser = $res->customer->user;
    $route = route('owner.repair-reservations.check-in', $res);

    $this->post($route)->assertRedirect();                                          // guest
    $this->actingAs($customerUser)->post($route)->assertForbidden();                // the customer's own booking
    $this->actingAs(User::factory()->create(['role' => 'customer']))->post($route)->assertForbidden();
    expect(fn () => app(RepairReservationService::class)->checkIn($res, $customerUser))->toThrow(RepairReservationException::class);
    expect($res->fresh()->checked_in_at)->toBeNull();

    $this->actingAs($owner)->post($route, ['received_by' => 'Front desk'])->assertSessionHas('success');
    expect($res->fresh()->checked_in_at)->not->toBeNull();
});

test('a pending or cancelled request cannot be checked in', function () {
    $owner = p3Owner();
    foreach (['pending', 'cancelled'] as $status) {
        $res = p3Booking('10:00', false, $status);
        expect(fn () => app(RepairReservationService::class)->checkIn($res, $owner))->toThrow(RepairReservationException::class);
        expect($res->fresh()->checked_in_at)->toBeNull();
    }
});

// ---------------------------------------------------------------- conversion: no double counting

test('starting the repair needs a check-in first, links one repair, keeps the reservation accepted and is idempotent', function () {
    $owner = p3Owner();
    $res = p3Booking('10:00');                       // check-in deadline 09:30; we start early at 09:00

    expect(fn () => p3Svc()->startReservationRepair($res, $owner))->toThrow(RepairReservationException::class, 'check-in');
    expect(Repair::count())->toBe(0);

    app(RepairReservationService::class)->checkIn($res, $owner);
    $a = p3Svc()->startReservationRepair($res, $owner, 90);
    $b = p3Svc()->startReservationRepair($res, $owner, 90);

    expect($a['changed'])->toBeTrue()->and($b['changed'])->toBeFalse()
        ->and(Repair::count())->toBe(1)
        ->and($res->fresh()->status)->toBe('accepted')
        ->and($res->fresh()->stageLabel())->toBe('Repair in progress')
        ->and($a['repair']->reservation_id)->toBe($res->id)
        ->and($a['repair']->status)->toBe('ongoing')
        ->and($a['repair']->estimated_minutes)->toBe(90)
        ->and($res->fresh()->expected_end_at->format('H:i'))->toBe('10:30');
    expect(RepairReservationEvent::where('repair_reservation_id', $res->id)->where('note', 'Repair started')->count())->toBe(1);
});

test('one physical job is counted exactly once before and after conversion (no double count)', function () {
    $owner = p3Owner();
    p3Walkin('ongoing', '08:30', 180);
    p3Walkin('ongoing', '08:30', 180);
    $res = p3Booking('09:00', true);

    $before = p3Svc()->occupancy(p3At('09:00'));
    p3Svc()->startReservationRepair($res, $owner, 60);
    $after = p3Svc()->occupancy(p3At('09:00'));

    expect($before)->toHaveCount(3)->and($after)->toHaveCount(3)
        ->and(p3Svc()->peak(p3At('09:00'), p3At('09:30'), $after))->toBe(3);
    // At the limit of 3 the shop is full both before and after; it is not "4" after (a double count would also
    // make a limit-4 shop look full).
    config(['repair_reservations.capacity' => 4]);
    expect(p3Svc()->fits(p3At('09:00'), p3At('09:30'), $after))->toBeTrue();
});

test('no zero-count gap: the job is continuously counted from scheduled time through a late start', function () {
    $owner = p3Owner();
    $res = p3Booking('09:30', true);                 // scheduled 09:30, checked in

    // Before the late start (clock 10:00): reserved window 09:30-10:30 still holds one bay.
    Carbon::setTestNow(p3At('10:00'));
    $o1 = p3Svc()->occupancy(p3At('10:00'));
    expect($o1)->toHaveCount(1)->and($o1[0][0]->format('H:i'))->toBe('09:30');

    p3Svc()->startReservationRepair($res, $owner, 60);   // late start at 10:00, est. 60 -> ends 11:00
    $o2 = p3Svc()->occupancy(p3At('10:00'));
    expect($o2)->toHaveCount(1)
        ->and($o2[0][0]->format('H:i'))->toBe('09:30')       // still starts at the reserved time: nothing was released
        ->and($o2[0][1]->format('H:i'))->toBe('11:00');
});

test('a late start never frees the reserved capacity, even after the reserved window has passed', function () {
    $owner = p3Owner();
    config(['repair_reservations.capacity' => 1]);
    $res = p3Booking('10:00', true);                 // reserved 10:00-11:00, checked in, not started

    Carbon::setTestNow(p3At('11:30'));               // window over, customer is physically waiting
    expect(p3Svc()->reservationFits(p3At('11:30')))->toBeFalse();   // bay still held

    p3Svc()->startReservationRepair($res, $owner, 30);   // late start, est. 30 -> ends 12:00
    expect(p3Svc()->reservationFits(p3At('11:45')))->toBeFalse()
        ->and(p3Svc()->reservationFits(p3At('12:00')))->toBeTrue();
});

test('an early start that would create a fourth simultaneous repair is refused, with nothing created', function () {
    $owner = p3Owner();
    p3Walkin('ongoing', '08:30', 180);
    p3Walkin('ongoing', '08:30', 180);
    p3Booking('09:30');                              // another accepted booking 09:30-10:30
    $res = p3Booking('10:00', true);                 // would start now (09:00) for 60 min

    expect(fn () => p3Svc()->startReservationRepair($res, $owner, 60))->toThrow(RepairCapacityException::class);
    expect(Repair::where('reservation_id', $res->id)->exists())->toBeFalse()
        ->and($res->fresh()->status)->toBe('accepted');
});

test('an early start with room succeeds, and its job is counted once from the early start', function () {
    $owner = p3Owner();
    p3Walkin('ongoing', '08:30', 180);
    $res = p3Booking('10:00', true);

    p3Svc()->startReservationRepair($res, $owner, 60);   // job 09:00-10:00, booked window to 11:00 is still held
    $occ = p3Svc()->occupancy(p3At('09:00'));
    expect($occ)->toHaveCount(2)
        ->and(collect($occ)->contains(fn ($o) => $o[0]->format('H:i') === '09:00' && $o[1]->format('H:i') === '11:00'))->toBeTrue();
});

test('a walk-in cannot be started into a bay a reservation has converted, and the 4th job is refused', function () {
    $owner = p3Owner();
    p3Walkin('ongoing', '08:30', 180);
    p3Walkin('ongoing', '08:30', 180);
    $res = p3Booking('09:00', true);
    p3Svc()->startReservationRepair($res, $owner, 60);

    expect(fn () => p3Svc()->startWalkIn(p3Walkin('pending', null, null)))->toThrow(RepairCapacityException::class);
});

test('an appointment with a repair already started cannot be cancelled away from under the repair', function () {
    $owner = p3Owner();
    $res = p3Booking('09:00', true);
    p3Svc()->startReservationRepair($res, $owner, 60);

    expect(fn () => app(RepairReservationService::class)->cancel($res, $owner, 'oops'))->toThrow(RepairReservationException::class);
    expect($res->fresh()->status)->toBe('accepted');
});

// ---------------------------------------------------------------- completion

test('marking the linked repair Done completes the reservation in the same step and frees the bay', function () {
    $owner = p3Owner();
    config(['repair_reservations.capacity' => 1]);
    $res = p3Booking('09:00', true);
    $repair = p3Svc()->startReservationRepair($res, $owner, 60)['repair'];
    expect(p3Svc()->reservationFits(p3At('09:15')))->toBeFalse();

    Carbon::setTestNow(p3At('09:40'));
    $this->actingAs($owner)->put("/repairs/{$repair->id}", ['status' => 'done'])->assertRedirect('/repairs');

    $repair->refresh(); $res->refresh();
    expect($repair->status)->toBe('done')->and($repair->completed_at)->not->toBeNull()
        ->and($res->status)->toBe('completed')
        ->and($res->ended_at->equalTo($repair->completed_at))->toBeTrue()
        ->and($res->stageLabel())->toBe('Completed')
        ->and(p3Svc()->reservationFits(p3At('09:45')))->toBeTrue();
    expect(RepairReservationEvent::where('repair_reservation_id', $res->id)->where('to_status', 'completed')->count())->toBe(1);
    Notification::assertSentTo($res->customer->user, StatusUpdated::class, fn ($n) => $n->title === 'Service completed');

    // Doing it again changes nothing and notifies nothing more.
    $this->actingAs($owner)->put("/repairs/{$repair->id}", ['status' => 'done']);
    expect(RepairReservationEvent::where('repair_reservation_id', $res->id)->where('to_status', 'completed')->count())->toBe(1);
    expect(Notification::sent($res->customer->user, StatusUpdated::class)->filter(fn ($n) => $n->title === 'Service completed')->count())->toBe(1);
});

test('a completed linked repair cannot be reopened or sent back to pending, and a running one cannot go to pending', function () {
    $owner = p3Owner();
    $res = p3Booking('09:00', true);
    $repair = p3Svc()->startReservationRepair($res, $owner, 60)['repair'];

    $this->actingAs($owner)->put("/repairs/{$repair->id}", ['status' => 'pending'])->assertSessionHas('error');
    expect($repair->fresh()->status)->toBe('ongoing');

    $this->actingAs($owner)->put("/repairs/{$repair->id}", ['status' => 'done']);
    foreach (['ongoing', 'pending'] as $status) {
        $this->actingAs($owner)->put("/repairs/{$repair->id}", ['status' => $status])->assertSessionHas('error');
    }
    expect($repair->fresh()->status)->toBe('done')->and($repair->fresh()->completed_at)->not->toBeNull()
        ->and($res->fresh()->status)->toBe('completed');
});

test('a plain walk-in keeps working as before: start, done, reopen, and back to pending clears completion', function () {
    $owner = p3Owner();
    $w = p3Walkin('pending', null, null);

    $this->actingAs($owner)->put("/repairs/{$w->id}", ['status' => 'ongoing', 'estimated_minutes' => 45])->assertSessionHas('success');
    $this->actingAs($owner)->put("/repairs/{$w->id}", ['status' => 'completed'])->assertSessionHas('success');
    expect($w->fresh()->status)->toBe('completed')->and($w->fresh()->completed_at)->not->toBeNull();
    $this->actingAs($owner)->put("/repairs/{$w->id}", ['status' => 'ongoing']);
    expect($w->fresh()->status)->toBe('ongoing')->and($w->fresh()->completed_at)->toBeNull();
    $this->actingAs($owner)->put("/repairs/{$w->id}", ['status' => 'pending']);
    expect($w->fresh()->status)->toBe('pending')->and($w->fresh()->started_at)->toBeNull();
});

// ---------------------------------------------------------------- extensions

test('extending a repair adds time, an overdue job is extended from now, and it keeps its bay while unfinished', function () {
    config(['repair_reservations.capacity' => 1]);
    $w = p3Walkin('ongoing', '07:00', 60);           // should have finished at 08:00; now 09:00 -> overdue
    expect($w->fresh()->isOverdue())->toBeTrue()
        ->and(p3Svc()->reservationFits(p3At('15:00')))->toBeFalse();   // open-ended while overdue

    $result = p3Svc()->extend($w, 60);
    expect($result['warning'])->toBeNull()
        ->and($w->fresh()->expectedEndAt()->format('H:i'))->toBe('10:00')      // from NOW (09:00) + 60
        ->and($w->fresh()->isOverdue())->toBeFalse()
        ->and(p3Svc()->reservationFits(p3At('10:00')))->toBeTrue();            // bay frees at the new estimate
});

test('extending into other jobs returns a capacity warning but never evicts anyone', function () {
    config(['repair_reservations.capacity' => 1]);
    $w = p3Walkin('ongoing', '08:30', 60);           // ends 09:30
    $booking = p3Booking('10:00');                   // accepted booking 10:00-11:00

    $result = p3Svc()->extend($w, 120);              // -> 11:30, collides with the booking
    expect($result['warning'])->toContain('overlaps')
        ->and($booking->fresh()->status)->toBe('accepted')
        ->and($w->fresh()->estimated_minutes)->toBe(180);
});

test('extending a linked repair mirrors the estimate onto its reservation; non-ongoing repairs cannot be extended', function () {
    $owner = p3Owner();
    $res = p3Booking('09:00', true);
    $repair = p3Svc()->startReservationRepair($res, $owner, 60)['repair'];

    p3Svc()->extend($repair, 30);
    expect($repair->fresh()->estimated_minutes)->toBe(90)
        ->and($res->fresh()->estimated_minutes)->toBe(90)
        ->and($res->fresh()->expected_end_at->format('H:i'))->toBe('10:30');

    expect(fn () => p3Svc()->extend(p3Walkin('pending', null, null), 30))->toThrow(RepairCapacityException::class);
    expect(fn () => p3Svc()->extend($repair, 2))->toThrow(RepairCapacityException::class);
});

test('a legacy ongoing repair without a start time is resolved by extending (stamped from now) or by marking it done', function () {
    $owner = p3Owner();
    $legacy = p3Walkin('ongoing', null, null);
    DB::table('repairs')->where('id', $legacy->id)->update(['updated_at' => '2026-10-30 10:00:00']);   // days old

    expect($legacy->fresh()->isLegacyOngoing())->toBeTrue();
    p3Svc()->extend($legacy, 30);
    $fresh = $legacy->fresh();
    expect($fresh->started_at->format('H:i'))->toBe('09:00')->and($fresh->estimated_minutes)->toBe(30)->and($fresh->isLegacyOngoing())->toBeFalse();

    $other = p3Walkin('ongoing', null, null);
    $this->actingAs($owner)->put("/repairs/{$other->id}", ['status' => 'done'])->assertSessionHas('success');
    expect($other->fresh()->status)->toBe('done')->and(Repair::count())->toBe(2);        // history kept, nothing deleted
});

// ---------------------------------------------------------------- Repairs screen

test('the Repairs screen shows estimates, overdue and legacy warnings, linked jobs, and treats done and completed alike', function () {
    $owner = p3Owner();
    p3Walkin('ongoing', '07:00', 60);                                 // overdue
    p3Walkin('ongoing', null, null);                                  // legacy
    p3Walkin('completed', '07:00', 60);                               // "completed" must render as Completed
    $res = p3Booking('09:00', true);
    p3Svc()->startReservationRepair($res, $owner, 60);

    $html = $this->actingAs($owner)->get('/repairs')->assertOk()->getContent();
    expect($html)->toContain('Overdue')
        ->and($html)->toContain('No start time recorded')
        ->and($html)->toContain('Online reservation')
        ->and($html)->toContain('Est. 60 min')
        ->and($html)->toContain('Extend')
        ->and($html)->toContain('Estimated duration')
        ->and(preg_match('/<option value="done"\s+selected/', $html))->toBe(1);
});

test('the owner reservation page shows check-in, Start repair and Repair in progress states', function () {
    $owner = p3Owner();
    $new = p3Booking('10:00');
    $in = p3Booking('10:00', true);
    $busy = p3Booking('09:00', true);
    p3Svc()->startReservationRepair($busy, $owner, 60);

    $html = $this->actingAs($owner)->get(route('owner.repair-reservations.index', ['tab' => 'accepted']))->assertOk()->getContent();
    expect($html)->toContain('Record check-in')->toContain('Received by (optional)')
        ->and($html)->toContain('Start repair')->and($html)->toContain('Repair in progress');
});

test('Phase 3 leaves the automatic features off and does not touch the Phase 1 and 2 migrations', function () {
    expect(config('repair_reservations.auto_cancel.enabled'))->toBeFalse()
        ->and(config('repair_reservations.pending_expiry.enabled'))->toBeFalse();
    foreach (['2026_11_03_090000_add_deadline_columns_to_repair_reservations', '2026_11_03_100000_add_occupancy_columns_to_repairs_table'] as $m) {
        expect(file_exists(database_path("migrations/{$m}.php")))->toBeTrue();
    }
});
