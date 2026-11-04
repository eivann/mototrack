<?php

use App\Models\Customer;
use App\Models\Repair;
use App\Models\RepairReservation;
use App\Models\RepairReservationEvent;
use App\Models\User;
use App\Notifications\StatusUpdated;
use App\Services\RepairCapacityService;
use App\Services\RepairReservationException;
use App\Services\RepairReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * RD2-R3 Phase 5: flag-gated no-check-in cancellation and pending-request expiry, both from STORED deadlines.
 * Test data only: fixed clock (Mon 2 Nov 2026, 09:00 Asia/Manila), faked notifications, isolated SQLite.
 * Every test that needs a process enabled turns that single flag on itself; the default is off.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::parse('2026-11-02 09:00:00', 'Asia/Manila'));
    config([
        'repair_reservations.capacity' => 3,
        'repair_reservations.capacity_unit' => 'simultaneous',
        'repair_reservations.appointment_minutes' => 60,
        'repair_reservations.auto_cancel.enabled' => false,
        'repair_reservations.pending_expiry.enabled' => false,
        'repair_reservations.batch_size' => 100,
    ]);
});

afterEach(fn () => Carbon::setTestNow());

function p5At(string $hms): Carbon
{
    return Carbon::parse('2026-11-02 '.$hms, 'Asia/Manila');
}

function p5Owner(): User
{
    $owner = User::factory()->create(['role' => 'owner']);
    Cache::forget('users_role_owner_ids');

    return $owner;
}

/** A reservation with explicit STORED deadlines (stored values are what the processes must use). */
function p5Res(string $status, string $checkInDeadline = '08:00:00', string $approvalDeadline = '08:00:00', array $over = []): RepairReservation
{
    static $n = 0;
    $n++;
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

    return RepairReservation::create(array_merge([
        'reference' => 'RR-P5-'.$n, 'customer_id' => $customer->id, 'service_date' => '2026-11-02',
        'scheduled_at' => p5At('10:00:00'), 'check_in_deadline_at' => p5At($checkInDeadline), 'approval_deadline_at' => p5At($approvalDeadline),
        'service_type' => 'brakes', 'bike_model' => 'Click 125', 'description' => 'phase five row', 'status' => $status,
        'active_day_key' => in_array($status, ['pending', 'accepted'], true) ? 'p5:'.$n : null,
    ], $over));
}

function p5Svc(): RepairReservationService
{
    return app(RepairReservationService::class);
}

function p5Titles(User $u): array
{
    return Notification::sent($u, StatusUpdated::class)->map(fn ($n) => $n->title)->values()->all();
}

// ---------------------------------------------------------------- feature flags and dry-run

test('both flags are false in the shipped configuration and .env.example', function () {
    $example = file_get_contents(base_path('.env.example'));
    expect($example)->toContain("RR_AUTO_CANCEL_ENABLED=false\n")->and($example)->toContain("RR_PENDING_EXPIRY_ENABLED=false\n")
        ->and($example)->not->toContain('RR_AUTO_CANCEL_STATUSES');
    $cfg = file_get_contents(config_path('repair_reservations.php'));
    expect($cfg)->toContain("env('RR_AUTO_CANCEL_ENABLED', false)")->and($cfg)->toContain("env('RR_PENDING_EXPIRY_ENABLED', false)");
});

test('with both flags off, nothing changes in any mode', function () {
    $accepted = p5Res('accepted');
    $pending = p5Res('pending');

    $this->artisan('repair-reservations:auto-cancel')->expectsOutputToContain('disabled')->assertSuccessful();
    $this->artisan('repair-reservations:auto-cancel', ['--execute' => true])->expectsOutputToContain('disabled')->assertSuccessful();
    $this->artisan('repair-reservations:expire-pending')->expectsOutputToContain('disabled')->assertSuccessful();
    $this->artisan('repair-reservations:expire-pending', ['--execute' => true])->expectsOutputToContain('disabled')->assertSuccessful();
    expect(p5Svc()->autoCancelOne($accepted->id))->toBeNull()->and(p5Svc()->expireOne($pending->id))->toBeNull();

    expect($accepted->fresh()->status)->toBe('accepted')->and($pending->fresh()->status)->toBe('pending')
        ->and(RepairReservationEvent::count())->toBe(0);
    Notification::assertNothingSent();
});

test('each flag works independently of the other', function () {
    $accepted = p5Res('accepted');
    $pending = p5Res('pending');

    config(['repair_reservations.auto_cancel.enabled' => true]);
    $this->artisan('repair-reservations:expire-pending', ['--execute' => true])->expectsOutputToContain('disabled');
    $this->artisan('repair-reservations:auto-cancel', ['--execute' => true])->assertSuccessful();
    expect($accepted->fresh()->status)->toBe('cancelled')->and($pending->fresh()->status)->toBe('pending');

    config(['repair_reservations.auto_cancel.enabled' => false, 'repair_reservations.pending_expiry.enabled' => true]);
    $other = p5Res('accepted');
    $this->artisan('repair-reservations:auto-cancel', ['--execute' => true])->expectsOutputToContain('disabled');
    $this->artisan('repair-reservations:expire-pending', ['--execute' => true])->assertSuccessful();
    expect($pending->fresh()->status)->toBe('expired')->and($other->fresh()->status)->toBe('accepted');
});

test('a manual run is a read-only dry-run: no writes and no notifications', function () {
    config(['repair_reservations.auto_cancel.enabled' => true, 'repair_reservations.pending_expiry.enabled' => true]);
    $accepted = p5Res('accepted');
    $pending = p5Res('pending');

    DB::enableQueryLog(); DB::flushQueryLog();
    Artisan::call('repair-reservations:auto-cancel');
    $out1 = Artisan::output();
    Artisan::call('repair-reservations:expire-pending');
    $out2 = Artisan::output();
    Artisan::call('repair-reservations:auto-cancel', ['--execute' => true, '--dry-run' => true]);   // dry-run wins
    $out3 = Artisan::output();
    $writes = collect(DB::getQueryLog())->pluck('query')->filter(fn ($q) => preg_match('/^\s*(insert|update|delete|replace)/i', $q))->all();
    DB::disableQueryLog();

    expect($out1)->toContain('[dry-run]')->toContain($accepted->reference)
        ->and($out2)->toContain('[dry-run]')->toContain($pending->reference)
        ->and($out3)->toContain('[dry-run]');
    expect($writes)->toBe([])
        ->and($accepted->fresh()->status)->toBe('accepted')->and($pending->fresh()->status)->toBe('pending')
        ->and(RepairReservationEvent::count())->toBe(0);
    Notification::assertNothingSent();
});

test('the scheduler entries exist, pass --execute, and are still gated by the flags', function () {
    $commands = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->map(fn ($e) => $e->command);
    expect($commands->contains(fn ($c) => str_contains($c, 'repair-reservations:auto-cancel') && str_contains($c, '--execute')))->toBeTrue()
        ->and($commands->contains(fn ($c) => str_contains($c, 'repair-reservations:expire-pending') && str_contains($c, '--execute')))->toBeTrue();
});

// ---------------------------------------------------------------- no-check-in cancellation

test('auto-cancel boundary: exactly at the check-in deadline is NOT cancelled, one second later is', function () {
    config(['repair_reservations.auto_cancel.enabled' => true]);
    $r = p5Res('accepted', '09:00:00');

    Carbon::setTestNow(p5At('09:00:00'));
    expect(p5Svc()->runAutoCancel(null, false)['processed'])->toBe(0)->and($r->fresh()->status)->toBe('accepted');

    Carbon::setTestNow(p5At('09:00:01'));
    expect(p5Svc()->runAutoCancel(null, false)['processed'])->toBe(1);
    expect($r->fresh()->status)->toBe('cancelled')->and($r->fresh()->cancelled_by)->toBe('system')->and($r->fresh()->active_day_key)->toBeNull()
        ->and($r->fresh()->decision_reason)->toContain('no check-in');
});

test('auto-cancel records history and sends exactly one customer notification and one owner notice', function () {
    config(['repair_reservations.auto_cancel.enabled' => true]);
    $owner = p5Owner();
    $r = p5Res('accepted');

    $result = p5Svc()->runAutoCancel(null, false);
    p5Svc()->runAutoCancel(null, false);   // overlapping/duplicate run
    p5Svc()->autoCancelOne($r->id);        // and a stale direct call

    expect($result['processed'])->toBe(1)->and($result['eligible'])->toBe(1);
    $events = RepairReservationEvent::where('repair_reservation_id', $r->id)->get();
    expect($events)->toHaveCount(1)->and($events[0]->from_status)->toBe('accepted')->and($events[0]->to_status)->toBe('cancelled')
        ->and($events[0]->actor_role)->toBe('system')->and($events[0]->note)->toContain('no check-in');
    expect(p5Titles($r->customer->user))->toBe(['Service appointment cancelled'])
        ->and(p5Titles($owner))->toBe(['Service request auto-cancelled']);
});

test('auto-cancel never touches checked-in, linked-repair, completed, terminal or pending reservations', function () {
    config(['repair_reservations.auto_cancel.enabled' => true, 'repair_reservations.pending_expiry.enabled' => false]);
    $checkedIn = p5Res('accepted', '08:00:00', '08:00:00', ['checked_in_at' => p5At('07:50:00')]);
    $withRepair = p5Res('accepted');
    Repair::create(['customer_name' => 'X', 'issue' => 'y', 'status' => 'ongoing', 'is_paid' => false, 'reservation_id' => $withRepair->id]);
    $completed = p5Res('completed');
    $cancelled = p5Res('cancelled');
    $rejected = p5Res('rejected');
    $expired = p5Res('expired');
    $pending = p5Res('pending');
    $target = p5Res('accepted');

    $result = p5Svc()->runAutoCancel(null, false);

    expect($result['processed'])->toBe(1)->and($target->fresh()->status)->toBe('cancelled');
    foreach ([[$checkedIn, 'accepted'], [$withRepair, 'accepted'], [$completed, 'completed'], [$cancelled, 'cancelled'], [$rejected, 'rejected'], [$expired, 'expired'], [$pending, 'pending']] as [$row, $status]) {
        expect($row->fresh()->status)->toBe($status);
    }
    expect(RepairReservationEvent::count())->toBe(1);
});

test('auto-cancel uses the STORED deadline, never today\'s configuration, and reports legacy rows without one', function () {
    config(['repair_reservations.auto_cancel.enabled' => true]);
    $stored = p5Res('accepted', '09:30:00');                       // snapshot says 09:30
    $legacy = p5Res('accepted', '08:00:00', '08:00:00', ['check_in_deadline_at' => null]);

    // Changing today's rules cannot move the snapshot in either direction.
    config(['repair_reservations.check_in.minutes_before' => 0, 'repair_reservations.approval.buffer_minutes' => 600]);
    $result = p5Svc()->runAutoCancel(null, false);
    expect($result['processed'])->toBe(0)->and($result['skipped'])->toBe(1)->and($stored->fresh()->status)->toBe('accepted')
        ->and($legacy->fresh()->status)->toBe('accepted');          // skipped and counted, never guessed

    config(['repair_reservations.check_in.minutes_before' => 600]);
    Carbon::setTestNow(p5At('09:30:01'));
    expect(p5Svc()->runAutoCancel(null, false)['processed'])->toBe(1)->and($stored->fresh()->status)->toBe('cancelled');
});

test('a stale candidate is rechecked under the lock: checking in or starting the repair first wins', function () {
    config(['repair_reservations.auto_cancel.enabled' => true]);
    $owner = p5Owner();
    $a = p5Res('accepted');
    $b = p5Res('accepted');
    $c = p5Res('accepted');
    $ids = [$a->id, $b->id, $c->id];                              // the "candidate list" a slow run already built

    $a->update(['checked_in_at' => now()]);                        // customer checked in meanwhile
    Repair::create(['customer_name' => 'X', 'issue' => 'y', 'status' => 'ongoing', 'is_paid' => false, 'reservation_id' => $b->id]);
    $c->update(['status' => 'cancelled']);                         // cancelled by someone else meanwhile

    foreach ($ids as $id) {
        expect(p5Svc()->autoCancelOne($id))->toBeNull();
    }
    expect($a->fresh()->status)->toBe('accepted')->and($b->fresh()->status)->toBe('accepted')->and(RepairReservationEvent::count())->toBe(0);
    Notification::assertNothingSent();
});

test('a checked-in reservation can still start its repair after its deadline, and the repair is counted once', function () {
    config(['repair_reservations.auto_cancel.enabled' => true]);
    $owner = p5Owner();
    $r = p5Res('accepted', '08:30:00', '08:00:00', ['check_in_deadline_at' => p5At('09:30:00')]);
    app(RepairReservationService::class)->checkIn($r, $owner);

    Carbon::setTestNow(p5At('09:45:00'));
    expect(p5Svc()->runAutoCancel(null, false)['processed'])->toBe(0);
    $result = app(RepairCapacityService::class)->startReservationRepair($r, $owner, 60);
    expect($result['changed'])->toBeTrue()->and($r->fresh()->status)->toBe('accepted')
        ->and(app(RepairCapacityService::class)->occupancy(p5At('09:45:00')))->toHaveCount(1);
});

// ---------------------------------------------------------------- pending expiry

test('pending expiry boundary: one second before the approval deadline is NOT expired, exactly at it is', function () {
    config(['repair_reservations.pending_expiry.enabled' => true]);
    $r = p5Res('pending', '08:00:00', '09:30:00');

    Carbon::setTestNow(p5At('09:29:59'));
    expect(p5Svc()->runPendingExpiry(null, false)['processed'])->toBe(0)->and($r->fresh()->status)->toBe('pending');

    Carbon::setTestNow(p5At('09:30:00'));
    expect(p5Svc()->runPendingExpiry(null, false)['processed'])->toBe(1);
    expect($r->fresh()->status)->toBe('expired')->and($r->fresh()->active_day_key)->toBeNull()->and($r->fresh()->decision_reason)->toContain('Expired');
});

test('expiry records history, notifies the customer once, and a repeat or stale call changes nothing', function () {
    config(['repair_reservations.pending_expiry.enabled' => true]);
    $r = p5Res('pending');

    p5Svc()->runPendingExpiry(null, false);
    p5Svc()->runPendingExpiry(null, false);
    expect(p5Svc()->expireOne($r->id))->toBeNull();

    $events = RepairReservationEvent::where('repair_reservation_id', $r->id)->get();
    expect($events)->toHaveCount(1)->and($events[0]->from_status)->toBe('pending')->and($events[0]->to_status)->toBe('expired')->and($events[0]->actor_role)->toBe('system');
    expect(p5Titles($r->customer->user))->toBe(['Service request expired']);
});

test('expiry touches only pending requests, ignores blackouts, and uses the stored deadline', function () {
    config(['repair_reservations.pending_expiry.enabled' => true]);
    $future = p5Res('pending', '08:00:00', '09:30:00');
    $legacy = p5Res('pending', '08:00:00', '08:00:00', ['approval_deadline_at' => null]);
    $accepted = p5Res('accepted');
    $rejected = p5Res('rejected');
    $cancelled = p5Res('cancelled');
    $completed = p5Res('completed');
    $already = p5Res('expired');
    $target = p5Res('pending');

    // A blackout over the whole day is NOT a reason to expire or reject anything on its own.
    app(\App\Services\RepairBlackoutService::class)->create(p5Owner(), ['type' => 'day', 'date' => '2026-11-02']);
    config(['repair_reservations.approval.buffer_minutes' => 999, 'repair_reservations.check_in.minutes_before' => 999]);

    $result = p5Svc()->runPendingExpiry(null, false);

    expect($result['processed'])->toBe(1)->and($result['skipped'])->toBe(1)
        ->and($target->fresh()->status)->toBe('expired')
        ->and($future->fresh()->status)->toBe('pending')            // inside the blackout, deadline not reached: untouched
        ->and($legacy->fresh()->status)->toBe('pending')
        ->and($accepted->fresh()->status)->toBe('accepted')
        ->and($rejected->fresh()->status)->toBe('rejected')
        ->and($cancelled->fresh()->status)->toBe('cancelled')
        ->and($completed->fresh()->status)->toBe('completed')
        ->and($already->fresh()->status)->toBe('expired');
    expect(RepairReservationEvent::count())->toBe(1);
});

test('a stale pending candidate that was accepted or rejected first is not expired', function () {
    config(['repair_reservations.pending_expiry.enabled' => true]);
    $owner = p5Owner();
    $a = p5Res('pending', '08:00:00', '08:00:00');
    $b = p5Res('pending', '08:00:00', '08:00:00');
    $ids = [$a->id, $b->id];

    $a->update(['status' => 'accepted']);
    $b->update(['status' => 'rejected']);

    foreach ($ids as $id) {
        expect(p5Svc()->expireOne($id))->toBeNull();
    }
    expect($a->fresh()->status)->toBe('accepted')->and($b->fresh()->status)->toBe('rejected')->and(RepairReservationEvent::count())->toBe(0);
});

// ---------------------------------------------------------------- bounded batches

test('each run is bounded by the batch size and reports counts only', function () {
    config(['repair_reservations.pending_expiry.enabled' => true, 'repair_reservations.batch_size' => 2]);
    foreach (range(1, 5) as $i) {
        p5Res('pending');
    }

    $first = p5Svc()->runPendingExpiry(null, false);
    expect($first['processed'])->toBe(2)->and($first['eligible'])->toBe(2)->and(RepairReservation::where('status', 'expired')->count())->toBe(2);
    p5Svc()->runPendingExpiry(null, false);
    p5Svc()->runPendingExpiry(null, false);
    expect(RepairReservation::where('status', 'expired')->count())->toBe(5);
    expect(array_keys($first))->toBe(['enabled', 'dry_run', 'eligible', 'processed', 'skipped', 'failed', 'would']);
});

// ---------------------------------------------------------------- visibility and blocked actions

test('expired and auto-cancelled requests are labelled accurately for the customer and the owner, and cannot be accepted or checked in', function () {
    config(['repair_reservations.auto_cancel.enabled' => true, 'repair_reservations.pending_expiry.enabled' => true]);
    $owner = p5Owner();
    $pending = p5Res('pending');
    $accepted = p5Res('accepted');
    p5Svc()->runPendingExpiry(null, false);
    p5Svc()->runAutoCancel(null, false);

    $expiredPage = $this->actingAs($pending->customer->user)->get(route('repair-reservations.show', $pending->reference))->assertOk()->getContent();
    expect($expiredPage)->toContain('Expired')->toContain('did not answer');
    $cancelPage = $this->actingAs($accepted->customer->user)->get(route('repair-reservations.show', $accepted->reference))->assertOk()->getContent();
    expect($cancelPage)->toContain('automatically')->toContain('No check-in was recorded');

    $history = $this->actingAs($owner)->get(route('owner.repair-reservations.index', ['tab' => 'history']))->assertOk()->getContent();
    expect($history)->toContain($pending->reference)->toContain('Expired')->toContain('did not answer')
        ->and($history)->toContain($accepted->reference)->toContain('no check-in');

    expect(fn () => p5Svc()->accept($pending, $owner))->toThrow(RepairReservationException::class, 'expired');
    expect(fn () => p5Svc()->checkIn($pending, $owner))->toThrow(RepairReservationException::class);
    expect(fn () => p5Svc()->checkIn($accepted, $owner))->toThrow(RepairReservationException::class);
    $this->actingAs($owner)->post(route('owner.repair-reservations.accept', $pending))->assertSessionHas('error');
    expect($pending->fresh()->status)->toBe('expired');
});

test('existing booking, check-in, completion and payment workflows are unaffected while the flags are off', function () {
    $owner = p5Owner();
    $r = p5Res('pending', '08:00:00', '09:30:00');

    expect(p5Svc()->accept($r, $owner)['changed'])->toBeTrue();
    $r->update(['check_in_deadline_at' => p5At('09:30:00')]);
    expect(p5Svc()->checkIn($r, $owner)['changed'])->toBeTrue();
    $repair = app(RepairCapacityService::class)->startReservationRepair($r, $owner, 60)['repair'];
    $this->actingAs($owner)->put("/repairs/{$repair->id}/cost", ['cost' => 800])->assertSessionHas('success');
    $this->actingAs($owner)->put("/repairs/{$repair->id}", ['status' => 'done']);
    $this->actingAs($owner)->put("/repairs/{$repair->id}/pay", ['amount_tendered' => 800])->assertSessionHas('success');

    expect($r->fresh()->status)->toBe('completed')->and((bool) $repair->fresh()->is_paid)->toBeTrue();
});
