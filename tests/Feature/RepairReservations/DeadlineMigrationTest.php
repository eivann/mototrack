<?php

use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RD2-R3 Phase 1: the additive migration that persists the check-in and approval deadlines on
 * repair_reservations. Isolated SQLite (phpunit.xml). Proves the columns, named indexes, the
 * backfill of existing rows, and a clean rollback.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-02 09:00:00', 'Asia/Manila'));
    config([
        'repair_reservations.check_in.minutes_before' => 30,
        'repair_reservations.approval.buffer_minutes' => 0,
    ]);
});

afterEach(fn () => Carbon::setTestNow());

test('the migration adds the two nullable deadline columns', function () {
    expect(Schema::hasColumn('repair_reservations', 'check_in_deadline_at'))->toBeTrue()
        ->and(Schema::hasColumn('repair_reservations', 'approval_deadline_at'))->toBeTrue();
});

test('the migration creates the two named scheduler indexes (sqlite)', function () {
    if (DB::connection()->getDriverName() !== 'sqlite') {
        $this->markTestSkipped('Index-name assertion is written for the SQLite test database.');
    }
    $indexes = collect(DB::select("select name from sqlite_master where type='index' and tbl_name='repair_reservations'"))->pluck('name');
    expect($indexes)->toContain('rr_deadline_idx')->toContain('rr_approval_idx');
});

test('up() backfills existing pending/accepted rows from scheduled_at and leaves status unchanged', function () {
    $migration = require database_path('migrations/2026_11_03_090000_add_deadline_columns_to_repair_reservations.php');

    // Simulate a database that predates this migration.
    $migration->down();
    expect(Schema::hasColumn('repair_reservations', 'check_in_deadline_at'))->toBeFalse();

    $user = User::factory()->create();
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    foreach (['pending', 'accepted'] as $i => $status) {
        DB::table('repair_reservations')->insert([
            'reference' => 'RR-LEGACY-'.$i, 'customer_id' => $customer->id, 'service_date' => '2026-11-02',
            'scheduled_at' => '2026-11-02 10:00:00', 'service_type' => 'brakes', 'bike_model' => 'X',
            'description' => 'legacy row', 'status' => $status, 'active_day_key' => 'legacy:'.$i,
            'created_at' => '2026-11-02 08:00:00', 'updated_at' => '2026-11-02 08:00:00',
        ]);
    }

    $migration->up();

    foreach (['pending', 'accepted'] as $i => $status) {
        $row = DB::table('repair_reservations')->where('reference', 'RR-LEGACY-'.$i)->first();
        expect($row->status)->toBe($status)                                                     // status untouched
            ->and(Carbon::parse($row->check_in_deadline_at)->format('Y-m-d H:i'))->toBe('2026-11-02 09:30')  // 10:00 − 30
            ->and(Carbon::parse($row->approval_deadline_at)->format('Y-m-d H:i'))->toBe('2026-11-02 09:30'); // buffer 0
    }
});

test('down() removes the columns again (reversible)', function () {
    $migration = require database_path('migrations/2026_11_03_090000_add_deadline_columns_to_repair_reservations.php');
    $migration->down();

    expect(Schema::hasColumn('repair_reservations', 'check_in_deadline_at'))->toBeFalse()
        ->and(Schema::hasColumn('repair_reservations', 'approval_deadline_at'))->toBeFalse();
});
