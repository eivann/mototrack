<?php

use App\Models\Customer;
use App\Models\Repair;
use App\Models\RepairReservation;
use App\Models\SaleHeader;
use App\Models\User;
use App\Notifications\StatusUpdated;
use App\Services\RepairCapacityException;
use App\Services\RepairCapacityService;
use App\Services\RepairReservationException;
use App\Services\RepairReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * RD2-R3 Phase 3 closeout: cost/payment for reservation-created repairs, occupancy at every start moment,
 * overrun flags, notification de-duplication and check-in privacy.
 * Test data only: fixed clock (Mon 2 Nov 2026, 09:00 Asia/Manila), faked notifications, isolated SQLite.
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

function closeoutAt(string $hm): Carbon
{
    return Carbon::parse('2026-11-02 '.$hm, 'Asia/Manila');
}

function closeoutOwner(): User
{
    $owner = User::factory()->create(['role' => 'owner']);
    Cache::forget('users_role_owner_ids');

    return $owner;
}

function closeoutBooking(string $hm, bool $checkedIn = false): RepairReservation
{
    static $n = 0;
    $n++;
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    $at = closeoutAt($hm);

    return RepairReservation::create([
        'reference' => 'RR-CO-'.$n, 'customer_id' => $customer->id, 'service_date' => '2026-11-02',
        'scheduled_at' => $at, 'check_in_deadline_at' => $at->copy()->subMinutes(30), 'approval_deadline_at' => $at->copy()->subMinutes(30),
        'service_type' => 'brakes', 'bike_model' => 'Click 125', 'description' => 'closeout row', 'status' => 'accepted',
        'active_day_key' => 'co:'.$n, 'checked_in_at' => $checkedIn ? now() : null,
    ]);
}

function closeoutWalkin(string $startHm, int $minutes): Repair
{
    $r = Repair::create(['customer_name' => 'Walk In', 'bike_model' => 'Mio', 'issue' => 'Noise', 'cost' => 500, 'status' => 'ongoing', 'is_paid' => false]);
    $r->forceFill(['started_at' => closeoutAt($startHm), 'estimated_minutes' => $minutes])->save();

    return $r;
}

function closeoutSvc(): RepairCapacityService
{
    return app(RepairCapacityService::class);
}

/** [start 'H:i', end 'H:i'|null] of the single occupancy entry. */
function closeoutInterval(array $occupancy): array
{
    expect($occupancy)->toHaveCount(1);

    return [$occupancy[0][0]->format('H:i'), $occupancy[0][1]?->format('H:i')];
}

// ---------------------------------------------------------------- 1. cost and payment

test('a reservation-created repair starts with no cost, cannot be paid until the owner sets one, and then uses the normal payment flow', function () {
    $owner = closeoutOwner();
    $res = closeoutBooking('10:00', true);
    $repair = closeoutSvc()->startReservationRepair($res, $owner, 60)['repair'];
    expect($repair->fresh()->cost)->toBeNull();

    // Completing does not price or pay it.
    $this->actingAs($owner)->put("/repairs/{$repair->id}", ['status' => 'done'])->assertRedirect('/repairs');
    expect((bool) $repair->fresh()->is_paid)->toBeFalse()->and($repair->fresh()->cost)->toBeNull();

    // Collecting without a cost is refused: no ₱0 sale, nothing marked paid.
    $headers = SaleHeader::count();
    $this->actingAs($owner)->put("/repairs/{$repair->id}/pay", ['amount_tendered' => 0])->assertSessionHas('error', 'Enter the repair cost before collecting payment.');
    expect((bool) $repair->fresh()->is_paid)->toBeFalse()->and(SaleHeader::count())->toBe($headers);

    // The owner enters the actual cost; an underpayment is still refused by the existing rule; full payment works.
    $this->actingAs($owner)->put("/repairs/{$repair->id}/cost", ['cost' => '1250.50'])->assertSessionHas('success');
    expect((float) $repair->fresh()->cost)->toBe(1250.5)->and((bool) $repair->fresh()->is_paid)->toBeFalse();
    $this->actingAs($owner)->put("/repairs/{$repair->id}/pay", ['amount_tendered' => 1000])->assertSessionHas('error');
    expect((bool) $repair->fresh()->is_paid)->toBeFalse();
    $this->actingAs($owner)->put("/repairs/{$repair->id}/pay", ['amount_tendered' => 1300])->assertSessionHas('success');
    expect((bool) $repair->fresh()->is_paid)->toBeTrue()->and(SaleHeader::count())->toBe($headers + 1);

    // Receipt renders, and a paid repair's cost is locked.
    $this->actingAs($owner)->get("/repairs/{$repair->id}/receipt")->assertOk();
    $this->actingAs($owner)->put("/repairs/{$repair->id}/cost", ['cost' => '1'])->assertSessionHas('error');
    expect((float) $repair->fresh()->cost)->toBe(1250.5);
});

test('only an authenticated owner can change a repair cost, and the value is validated', function () {
    $owner = closeoutOwner();
    $res = closeoutBooking('10:00', true);
    $repair = closeoutSvc()->startReservationRepair($res, $owner, 60)['repair'];
    $customerUser = $res->customer->user;

    $this->put("/repairs/{$repair->id}/cost", ['cost' => 5])->assertRedirect();
    $this->actingAs($customerUser)->put("/repairs/{$repair->id}/cost", ['cost' => 5])->assertForbidden();
    expect($repair->fresh()->cost)->toBeNull();

    foreach (['-1', 'abc', '', '99999999999'] as $bad) {
        $this->actingAs($owner)->put("/repairs/{$repair->id}/cost", ['cost' => $bad])->assertSessionHasErrors('cost');
    }
    expect($repair->fresh()->cost)->toBeNull();
});

test('existing walk-in repairs with a cost still pay exactly as before', function () {
    $owner = closeoutOwner();
    $w = closeoutWalkin('08:30', 60);   // cost 500

    $this->actingAs($owner)->put("/repairs/{$w->id}/pay", ['amount_tendered' => 500])->assertSessionHas('success');
    expect((bool) $w->fresh()->is_paid)->toBeTrue();
});

// ---------------------------------------------------------------- 2. occupancy at every start moment

test('occupancy at the four start moments never shrinks the booked window, stays one job, and is never empty or negative', function () {
    $owner = closeoutOwner();

    // (a) Before the scheduled start: booked 10:00-11:00, started 09:00 for 30 min.
    $a = closeoutBooking('10:00', true);
    closeoutSvc()->startReservationRepair($a, $owner, 30);
    expect(closeoutInterval(closeoutSvc()->occupancy(closeoutAt('09:00'))))->toBe(['09:00', '11:00']);   // booked window still held

    // (b) Exactly at the scheduled start.
    Repair::query()->delete(); RepairReservation::query()->delete();
    Carbon::setTestNow(closeoutAt('10:00'));
    $b = closeoutBooking('10:00', true);
    closeoutSvc()->startReservationRepair($b, $owner, 30);
    expect(closeoutInterval(closeoutSvc()->occupancy(closeoutAt('10:00'))))->toBe(['10:00', '11:00']);

    // (c) After the scheduled start, before the original expected end.
    Repair::query()->delete(); RepairReservation::query()->delete();
    Carbon::setTestNow(closeoutAt('10:30'));
    $c = closeoutBooking('10:00', true);
    closeoutSvc()->startReservationRepair($c, $owner, 60);                                   // ends 11:30
    expect(closeoutInterval(closeoutSvc()->occupancy(closeoutAt('10:30'))))->toBe(['10:00', '11:30']);

    // (d) After the original expected end (very late): 12:00 for a 10:00-11:00 booking, 30 min estimate.
    Repair::query()->delete(); RepairReservation::query()->delete();
    Carbon::setTestNow(closeoutAt('12:00'));
    $d = closeoutBooking('10:00', true);
    closeoutSvc()->startReservationRepair($d, $owner, 30);                                   // ends 12:30
    [$start, $end] = closeoutInterval(closeoutSvc()->occupancy(closeoutAt('12:00')));
    expect($start)->toBe('10:00')->and($end)->toBe('12:30')->and($end > $start)->toBeTrue();
});

test('an early start keeps the booked window held until the job is actually completed', function () {
    $owner = closeoutOwner();
    config(['repair_reservations.capacity' => 1]);
    $res = closeoutBooking('10:00', true);
    $repair = closeoutSvc()->startReservationRepair($res, $owner, 30)['repair'];     // 09:00-09:30 estimate, booked until 11:00

    expect(closeoutSvc()->reservationFits(closeoutAt('10:15')))->toBeFalse();               // booked window is not released early

    Carbon::setTestNow(closeoutAt('09:20'));
    $this->actingAs($owner)->put("/repairs/{$repair->id}", ['status' => 'done']);   // completed early: bay is free now
    expect(closeoutSvc()->reservationFits(closeoutAt('10:15')))->toBeTrue();
});

test('the same job counts once at every start moment and a fourth simultaneous repair is still refused', function () {
    $owner = closeoutOwner();
    closeoutWalkin('08:30', 240);
    closeoutWalkin('08:30', 240);
    $res = closeoutBooking('09:30', true);

    foreach (['09:00', '09:30', '10:00'] as $when) {
        Carbon::setTestNow(closeoutAt($when));
        Repair::query()->where('reservation_id', $res->id)->delete();
        expect(closeoutSvc()->occupancy(closeoutAt($when)))->toHaveCount(3);                    // 2 walk-ins + the booking
    }

    Carbon::setTestNow(closeoutAt('10:00'));
    closeoutSvc()->startReservationRepair($res, $owner, 60);
    expect(closeoutSvc()->occupancy(closeoutAt('10:00')))->toHaveCount(3);                      // still three jobs, not four
    expect(fn () => closeoutSvc()->startWalkIn(Repair::create(['customer_name' => 'X', 'issue' => 'y', 'status' => 'pending', 'is_paid' => false])))
        ->toThrow(RepairCapacityException::class);
});

// ---------------------------------------------------------------- 3. overruns, flags, acceptance, completion, notifications

test('an unfinished repair stays counted after its estimate, existing bookings are flagged, and unsafe acceptances are refused', function () {
    $owner = closeoutOwner();
    config(['repair_reservations.capacity' => 1]);
    $overrun = closeoutWalkin('07:00', 60);                  // should have ended 08:00; still ongoing at 09:00
    $booked = closeoutBooking('10:00');                      // accepted earlier, now squeezed by the overrun

    expect($overrun->fresh()->isOverdue())->toBeTrue()
        ->and(closeoutSvc()->reservationConflicts([$booked->load('repair')]))->toBe([$booked->id => true]);

    $html = $this->actingAs($owner)->get(route('owner.repair-reservations.index', ['tab' => 'accepted']))->assertOk()->getContent();
    expect($html)->toContain('Capacity conflict');
    expect($booked->fresh()->status)->toBe('accepted');                             // flagged, never cancelled

    // A new request is refused at acceptance while the overrun still holds the only bay.
    $pending = closeoutBooking('11:00');
    $pending->update(['status' => 'pending']);
    expect(fn () => app(RepairReservationService::class)->accept($pending, $owner))->toThrow(RepairReservationException::class, 'Capacity is full');
    expect($pending->fresh()->status)->toBe('pending');

    $this->actingAs($owner)->put("/repairs/{$overrun->id}", ['status' => 'done']);
    expect(closeoutSvc()->reservationConflicts([$booked->fresh()->load('repair')]))->toBe([]);
});

test('completion updates the repair, reservation and history consistently, with one customer notification each step', function () {
    $owner = closeoutOwner();
    $res = closeoutBooking('10:00', true);
    $user = $res->customer->user;

    $repair = closeoutSvc()->startReservationRepair($res, $owner, 60)['repair'];
    app(RepairReservationService::class)->notifyRepairStarted($res->fresh('customer'));
    $this->actingAs($owner)->put("/repairs/{$repair->id}", ['status' => 'done']);
    $this->actingAs($owner)->put("/repairs/{$repair->id}", ['status' => 'done']);      // repeated click

    $titles = Notification::sent($user, StatusUpdated::class)->map(fn ($n) => $n->title)->values()->all();
    expect($titles)->toBe(['Repair started', 'Service completed'])                    // no generic "Repair Status Updated" duplicates
        ->and($res->fresh()->status)->toBe('completed')
        ->and($res->fresh()->ended_at->equalTo($repair->fresh()->completed_at))->toBeTrue();
});

// ---------------------------------------------------------------- 4. privacy

test('"Received by" stays owner-only: the customer sees a generic check-in, the owner sees the name', function () {
    $owner = closeoutOwner();
    $res = closeoutBooking('10:00');
    app(RepairReservationService::class)->checkIn($res, $owner, 'Mang Tonyo (front desk)');

    expect($res->fresh()->received_by)->toBe('Mang Tonyo (front desk)');
    $customerView = $this->actingAs($res->customer->user)->get(route('repair-reservations.show', $res->reference))->assertOk()->getContent();
    expect($customerView)->toContain('Checked in')->not->toContain('Mang Tonyo');
    $ownerView = $this->actingAs($owner)->get(route('owner.repair-reservations.index', ['tab' => 'accepted']))->assertOk()->getContent();
    expect($ownerView)->toContain('Mang Tonyo');
});

test('the closeout keeps the automatic features switched off', function () {
    expect(config('repair_reservations.auto_cancel.enabled'))->toBeFalse()
        ->and(config('repair_reservations.pending_expiry.enabled'))->toBeFalse();
});
