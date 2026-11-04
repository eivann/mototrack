<?php

use App\Models\Customer;
use App\Models\RepairReservation;
use App\Models\User;
use App\Notifications\StatusUpdated;
use App\Services\RepairReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * RD2-R6: the automatic no-check-in cancellation rule is disclosed to the customer on the booking form (next to the
 * confirmation checkbox) and in the Terms, ONLY while RR_AUTO_CANCEL_ENABLED is true, and the wording is derived from
 * the same configuration that computes the stored check-in deadline the scheduler enforces.
 * Test data only: fixed clock (Mon 2 Nov 2026, 09:00 Asia/Manila), faked notifications, isolated SQLite.
 */
beforeEach(function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::parse('2026-11-02 09:00:00', 'Asia/Manila'));
    config([
        'repair_reservations.capacity' => 3,
        'repair_reservations.capacity_unit' => 'simultaneous',
        'repair_reservations.appointment_minutes' => 60,
        'repair_reservations.check_in.minutes_before' => 30,
        'repair_reservations.auto_cancel.enabled' => false,
        'repair_reservations.pending_expiry.enabled' => false,
    ]);
});

afterEach(fn () => Carbon::setTestNow());

function acdCustomer(): User
{
    $user = User::factory()->create(['role' => 'customer']);
    Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

    return $user;
}

function acdOwner(): User
{
    $owner = User::factory()->create(['role' => 'owner']);
    Cache::forget('users_role_owner_ids');

    return $owner;
}

function acdBookingPage(): string
{
    return test()->actingAs(acdCustomer())->get(route('repair-reservations.create'))->assertOk()->getContent();
}

/** Book through the real HTTP flow, then accept as the owner; returns the accepted reservation. */
function acdAccepted(User $owner, string $time = '12:00'): RepairReservation
{
    $user = acdCustomer();
    test()->actingAs($user)->post(route('repair-reservations.store'), [
        'appointment_date' => '2026-11-02', 'appointment_time' => $time, 'service_type' => 'brakes',
        'bike_model' => 'Honda Click 125', 'description' => 'Rear brake feels soft and squeaks.', 'accept_terms' => '1',
    ])->assertSessionHasNoErrors();
    $r = RepairReservation::latest('id')->first();
    app(RepairReservationService::class)->accept($r, $owner);

    return $r->fresh();
}

test('the notice appears next to the confirmation checkbox when automatic cancellation is enabled', function () {
    config(['repair_reservations.auto_cancel.enabled' => true]);
    $html = acdBookingPage();

    expect($html)->toContain('data-auto-cancel-notice')
        ->toContain('Automatic cancellation rule')
        ->toContain('at least <strong>30 minutes</strong> before your appointment time')
        ->toContain('cancelled automatically')
        ->toContain('Example: for a 2:00 PM appointment, arrive by 1:30 PM.')
        ->toContain('I also understand the automatic cancellation rule above.');

    // The notice sits inside the confirmation block: after the terms, before the checkbox the customer ticks.
    $terms = strpos($html, 'Service appointment terms');
    $notice = strpos($html, 'data-auto-cancel-notice');
    $checkbox = strpos($html, 'name="accept_terms"');
    expect($terms)->toBeLessThan($notice)->and($notice)->toBeLessThan($checkbox);
});

test('the notice and the extra acknowledgement are hidden when automatic cancellation is disabled', function () {
    config(['repair_reservations.auto_cancel.enabled' => false]);
    $html = acdBookingPage();

    foreach (['data-auto-cancel-notice', 'data-auto-cancel-terms', 'Automatic cancellation rule', 'cancelled automatically', 'automatic cancellation rule above'] as $needle) {
        expect($html)->not->toContain($needle);
    }
    // The ordinary confirmation text is untouched.
    expect($html)->toContain('I understand this is a <strong>request</strong>')->toContain('name="accept_terms"');
});

test('the Terms page states the rule only while automatic cancellation is enabled, consistently with the booking form', function () {
    config(['repair_reservations.auto_cancel.enabled' => true]);
    $on = $this->get('/terms')->assertOk()->getContent();
    expect($on)->toContain('data-auto-cancel-terms')
        ->toContain('<strong>Automatic cancellation.</strong>')
        ->toContain('at least <strong>30 minutes</strong> before your appointment time')
        ->toContain('Example: for a 2:00 PM appointment, arrive by 1:30 PM.');

    // Same sentence on both pages (single source), inside the "Service appointment requests" section of the Terms.
    $sentence = 'If the shop has not recorded your arrival by then, your appointment is <strong>cancelled automatically</strong> and you and the shop are notified.';
    expect(acdBookingPage())->toContain($sentence);
    expect(strpos($on, 'Service appointment requests'))->toBeLessThan(strpos($on, 'data-auto-cancel-terms'));

    config(['repair_reservations.auto_cancel.enabled' => false]);
    $off = $this->get('/terms')->assertOk()->getContent();
    foreach (['data-auto-cancel-terms', 'cancelled automatically', 'Automatic cancellation.'] as $needle) {
        expect($off)->not->toContain($needle);
    }
});

test('the wording follows the configured number of minutes and never claims a different deadline', function () {
    config(['repair_reservations.auto_cancel.enabled' => true, 'repair_reservations.check_in.minutes_before' => 45]);
    $html = acdBookingPage();
    expect($html)->toContain('at least <strong>45 minutes</strong>')->toContain('arrive by 1:15 PM')->not->toContain('30 minutes');

    config(['repair_reservations.check_in.minutes_before' => 0]);
    $zero = acdBookingPage();
    expect($zero)->toContain('check in with the shop by your appointment time')->not->toContain('at least <strong>');
});

test('the displayed rule matches the implemented cancellation trigger exactly', function () {
    config(['repair_reservations.auto_cancel.enabled' => true]);
    $owner = acdOwner();
    $svc = app(RepairReservationService::class);

    // 1) The minutes the page promises are the minutes stored as the deadline of a real booking.
    $shown = (int) (preg_match('/at least <strong>(\d+) minutes<\/strong>/', acdBookingPage(), $m) ? $m[1] : -1);
    $late = acdAccepted($owner, '12:00');
    expect($shown)->toBe(30)
        ->and($late->scheduled_at->diffInMinutes($late->check_in_deadline_at, true))->toBe((float) $shown)
        ->and($late->check_in_deadline_at->timezone('Asia/Manila')->format('H:i:s'))->toBe('11:30:00');

    // 2) "If the shop has not recorded your arrival by then": exactly at the deadline is still fine, one second later it is cancelled.
    $deadline = Carbon::parse('2026-11-02 11:30:00', 'Asia/Manila');
    expect($svc->autoCancelOne($late->id, $deadline))->toBeNull();
    expect($late->fresh()->status)->toBe('accepted');

    $cancelled = $svc->autoCancelOne($late->id, $deadline->copy()->addSecond());
    expect($cancelled)->not->toBeNull()
        ->and($late->fresh()->status)->toBe('cancelled')
        ->and($late->fresh()->cancelled_by)->toBe('system');

    // 3) "you and the shop are notified" (the text the customer sees says the cancellation is automatic and about check-in).
    Notification::assertSentTo($late->customer->user, StatusUpdated::class,
        fn ($n) => $n->title === 'Service appointment cancelled' && str_contains($n->message, 'automatically cancelled because no check-in was recorded by the deadline'));
    Notification::assertSentTo($owner, StatusUpdated::class, fn ($n) => $n->title === 'Service request auto-cancelled');

    // 4) A customer whose arrival the shop recorded in time (even exactly at the deadline) is NOT cancelled.
    $arrived = acdAccepted($owner, '12:00');
    Carbon::setTestNow($deadline);
    $svc->checkIn($arrived, $owner);
    expect($arrived->fresh()->checked_in_at)->not->toBeNull();
    expect($svc->autoCancelOne($arrived->id, $deadline->copy()->addMinutes(5)))->toBeNull();
    expect($arrived->fresh()->status)->toBe('accepted');
});

test('with automatic cancellation disabled nothing is announced and nothing is cancelled', function () {
    config(['repair_reservations.auto_cancel.enabled' => false]);
    $owner = acdOwner();
    $r = acdAccepted($owner, '12:00');

    expect(acdBookingPage())->not->toContain('cancelled automatically');
    expect(app(RepairReservationService::class)->autoCancelOne($r->id, Carbon::parse('2026-11-02 11:45:00', 'Asia/Manila')))->toBeNull();
    expect($r->fresh()->status)->toBe('accepted');
});
