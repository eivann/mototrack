<?php

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * SYS4-06 D3: sales:backfill-headers. Runs only on the isolated test database. Fixtures are raw rows
 * so soft-deleted, NULL-reference and historical shapes can be reproduced exactly.
 */

beforeEach(fn () => Notification::fake());

function bfCustomer(): int
{
    $u = User::factory()->create(['role' => 'customer']);

    return Customer::create(['user_id' => $u->id, 'name' => $u->name, 'email' => $u->email])->id;
}

function bfSale(array $o = []): int
{
    static $n = 0;
    $n++;
    $stamp = '2026-03-01 10:00:0'.($n % 10);

    return DB::table('sales')->insertGetId(array_merge([
        'inventory_item_id' => null, 'customer_id' => null, 'quantity' => 1, 'price' => 100, 'total_price' => 100,
        'transaction_reference' => 'TRX-BF-'.$n, 'amount_tendered' => 0, 'change' => 0, 'status' => 'completed',
        'payment_confirmed_at' => '2026-03-01 10:05:00', 'created_at' => $stamp, 'updated_at' => $stamp,
    ], $o));
}

function bfRun(string ...$args)
{
    $params = [];
    foreach ($args as $a) {
        [$k, $v] = array_pad(explode('=', $a, 2), 2, true);
        $params[$k] = $v;
    }

    return test()->artisan('sales:backfill-headers', $params);
}

function bfFinancials(): array
{
    return DB::table('sales')->orderBy('id')->get()->map(fn ($r) => collect((array) $r)->except('sale_header_id')->all())->all();
}

function bfSnapshot(): array
{
    return [
        'sales' => DB::table('sales')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
        'headers' => DB::table('sale_headers')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
        'audit' => AuditLog::count(),
    ];
}

function bfHeader(string $ref): ?object
{
    return DB::table('sale_headers')->where('transaction_reference', $ref)->first();
}

// ── Options and dry-run ──────────────────────────────────────────────────────

test('exactly one of --dry-run or --execute is required', function () {
    bfSale();
    $before = bfSnapshot();

    test()->artisan('sales:backfill-headers')->assertExitCode(2);
    test()->artisan('sales:backfill-headers', ['--dry-run' => true, '--execute' => true])->assertExitCode(2);

    expect(bfSnapshot())->toEqual($before);
});

test('dry-run issues no write statements and changes nothing', function () {
    bfSale(['transaction_reference' => 'TRX-DRY']);
    bfSale(['transaction_reference' => 'TRX-DRY']);
    bfSale(['transaction_reference' => null]);
    bfSale(['transaction_reference' => 'TRX-DRY-BAD', 'customer_id' => bfCustomer()]);
    bfSale(['transaction_reference' => 'TRX-DRY-BAD', 'customer_id' => bfCustomer()]);
    $before = bfSnapshot();

    $statements = [];
    DB::listen(function ($q) use (&$statements) { $statements[] = $q->sql; });

    bfRun('--dry-run')->expectsOutputToContain('DRY RUN')->expectsOutputToContain('Headers that would be created: 2')
        ->expectsOutputToContain('mixed_customers: 1')->assertExitCode(0);

    $writes = array_filter($statements, fn ($sql) => preg_match('/^\s*(insert|update|delete|create|alter|drop)\b|for update/i', $sql));
    expect($writes)->toBe([])->and(count($statements))->toBeGreaterThan(0)->and(bfSnapshot())->toEqual($before);
});

// ── Group shapes ─────────────────────────────────────────────────────────────

test('a single-line historical sale gets one header with its own values', function () {
    $c = bfCustomer();
    $id = bfSale(['transaction_reference' => 'TRX-ONE', 'customer_id' => $c, 'price' => 250, 'total_price' => 250, 'amount_tendered' => 300, 'change' => 50,
        'payment_confirmed_at' => '2026-03-01 10:05:00', 'picked_up_at' => '2026-03-01 10:06:00']);

    bfRun('--execute')->assertExitCode(0);

    $h = bfHeader('TRX-ONE');
    expect($h)->not->toBeNull()->and((float) $h->total_amount)->toBe(250.0)->and((float) $h->amount_tendered)->toBe(300.0)
        ->and((float) $h->change_amount)->toBe(50.0)->and($h->customer_id)->toBe($c)->and($h->transaction_type)->toBe('legacy')
        ->and($h->payment_confirmed_at)->toBe('2026-03-01 10:05:00')->and($h->picked_up_at)->toBe('2026-03-01 10:06:00')
        ->and($h->created_at)->toBe(DB::table('sales')->where('id', $id)->value('created_at'))
        ->and(DB::table('sales')->where('id', $id)->value('sale_header_id'))->toBe($h->id);
});

test('a multi-line transaction becomes one header with the original total and all lines linked', function () {
    $a = bfSale(['transaction_reference' => 'TRX-MULTI', 'price' => 100, 'total_price' => 100, 'amount_tendered' => 400, 'change' => 50]);
    $b = bfSale(['transaction_reference' => 'TRX-MULTI', 'price' => 250, 'total_price' => 250]);
    $other = bfSale(['transaction_reference' => 'TRX-MULTI-OTHER']);

    bfRun('--execute')->assertExitCode(0);

    $h = bfHeader('TRX-MULTI');
    expect((float) $h->total_amount)->toBe(350.0)->and((float) $h->amount_tendered)->toBe(400.0)->and((float) $h->change_amount)->toBe(50.0)
        ->and(DB::table('sales')->whereIn('id', [$a, $b])->pluck('sale_header_id')->unique()->all())->toBe([$h->id])
        ->and(DB::table('sales')->where('id', $other)->value('sale_header_id'))->not->toBe($h->id)
        ->and(DB::table('sale_headers')->count())->toBe(2);
});

test('a NULL or blank reference sale gets LEGACY-<id> on the header and keeps its own reference', function () {
    $a = bfSale(['transaction_reference' => null]);
    $b = bfSale(['transaction_reference' => '  ']);

    bfRun('--execute')->assertExitCode(0);

    expect(bfHeader('LEGACY-'.$a))->not->toBeNull()->and(bfHeader('LEGACY-'.$b))->not->toBeNull()
        ->and(DB::table('sales')->where('id', $a)->value('transaction_reference'))->toBeNull()
        ->and(DB::table('sales')->where('id', $b)->value('transaction_reference'))->toBe('  ')
        ->and(DB::table('sale_headers')->count())->toBe(2);
});

test('tender on a higher-id line is used once and never summed with repeated values', function () {
    bfSale(['transaction_reference' => 'TRX-HI', 'price' => 100, 'total_price' => 100]);
    bfSale(['transaction_reference' => 'TRX-HI', 'price' => 250, 'total_price' => 250, 'amount_tendered' => 400, 'change' => 50]);
    bfSale(['transaction_reference' => 'TRX-REP', 'amount_tendered' => 200, 'change' => 100]);
    bfSale(['transaction_reference' => 'TRX-REP', 'amount_tendered' => 200, 'change' => 100]);

    bfRun('--execute')->assertExitCode(0);

    expect((float) bfHeader('TRX-HI')->amount_tendered)->toBe(400.0)->and((float) bfHeader('TRX-HI')->change_amount)->toBe(50.0)
        ->and((float) bfHeader('TRX-REP')->amount_tendered)->toBe(200.0)->and((float) bfHeader('TRX-REP')->change_amount)->toBe(100.0);
});

test('differing tender values are ambiguous and the group is skipped untouched', function () {
    $a = bfSale(['transaction_reference' => 'TRX-AMB', 'amount_tendered' => 200, 'change' => 100]);
    $b = bfSale(['transaction_reference' => 'TRX-AMB', 'amount_tendered' => 150, 'change' => 50]);

    bfRun('--execute')->expectsOutputToContain('ambiguous_tender: 1')->assertExitCode(0);

    expect(DB::table('sale_headers')->count())->toBe(0)->and(DB::table('sales')->whereNotNull('sale_header_id')->count())->toBe(0);
});

test('an unpaid reservation keeps reservation and expiry times and has no payment', function () {
    $c = bfCustomer();
    foreach ([100, 250] as $p) {
        bfSale(['transaction_reference' => 'TRX-RES', 'customer_id' => $c, 'price' => $p, 'total_price' => $p, 'status' => 'pending',
            'payment_confirmed_at' => null, 'reserved_at' => '2026-03-02 09:00:00', 'expires_at' => '2026-03-03 09:00:00']);
    }

    bfRun('--execute')->assertExitCode(0);

    $h = bfHeader('TRX-RES');
    expect($h->transaction_type)->toBe('online_reservation')->and($h->payment_confirmed_at)->toBeNull()->and($h->picked_up_at)->toBeNull()
        ->and($h->reserved_at)->toBe('2026-03-02 09:00:00')->and($h->expires_at)->toBe('2026-03-03 09:00:00')
        ->and((float) $h->total_amount)->toBe(350.0)->and((float) $h->amount_tendered)->toBe(0.0);
});

test('a completed paid sale keeps its payment and pickup times', function () {
    bfSale(['transaction_reference' => 'TRX-PAID', 'amount_tendered' => 100, 'payment_confirmed_at' => '2026-03-05 12:00:00', 'picked_up_at' => '2026-03-05 12:30:00',
        'reserved_at' => '2026-03-04 09:00:00', 'expires_at' => '2026-03-05 09:00:00']);

    bfRun('--execute')->assertExitCode(0);

    $h = bfHeader('TRX-PAID');
    expect($h->payment_confirmed_at)->toBe('2026-03-05 12:00:00')->and($h->picked_up_at)->toBe('2026-03-05 12:30:00')
        ->and($h->transaction_type)->toBe('online_reservation');
});

test('mixed completed and refunded lines keep the original total and every line status', function () {
    $a = bfSale(['transaction_reference' => 'TRX-MIX', 'price' => 100, 'total_price' => 100, 'status' => 'refunded', 'amount_tendered' => 500, 'change' => 150]);
    $b = bfSale(['transaction_reference' => 'TRX-MIX', 'price' => 250, 'total_price' => 250, 'status' => 'completed']);

    bfRun('--execute')->assertExitCode(0);

    expect((float) bfHeader('TRX-MIX')->total_amount)->toBe(350.0)
        ->and(DB::table('sales')->where('id', $a)->value('status'))->toBe('refunded')
        ->and(DB::table('sales')->where('id', $b)->value('status'))->toBe('completed')
        ->and(Schema::hasColumn('sale_headers', 'status'))->toBeFalse();
});

test('repair and preorder references are typed only when the shape agrees', function () {
    bfSale(['transaction_reference' => 'REPAIR-AAAA1111']);
    bfSale(['transaction_reference' => 'PRE-BBBB2222']);
    bfSale(['transaction_reference' => 'REPAIR-CCCC3333', 'inventory_item_id' => DB::table('inventory_items')->insertGetId(['part_name' => 'X', 'sku' => 'BFX', 'price' => 1, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()])]);
    bfSale(['transaction_reference' => 'PRE-DDDD4444']);
    bfSale(['transaction_reference' => 'PRE-DDDD4444']);

    bfRun('--execute')->expectsOutputToContain('inconsistent_type: 2')->assertExitCode(0);

    expect(bfHeader('REPAIR-AAAA1111')->transaction_type)->toBe('repair')->and(bfHeader('PRE-BBBB2222')->transaction_type)->toBe('preorder')
        ->and(bfHeader('REPAIR-CCCC3333'))->toBeNull()->and(bfHeader('PRE-DDDD4444'))->toBeNull();
});

// ── Anomalies ────────────────────────────────────────────────────────────────

test('anomalous groups are skipped with a reason and left untouched', function () {
    // mixed customers
    bfSale(['transaction_reference' => 'TRX-A1', 'customer_id' => bfCustomer()]);
    bfSale(['transaction_reference' => 'TRX-A1', 'customer_id' => bfCustomer()]);
    // customer vs none
    bfSale(['transaction_reference' => 'TRX-A2', 'customer_id' => bfCustomer()]);
    bfSale(['transaction_reference' => 'TRX-A2', 'customer_id' => null]);
    // pending mixed with settled
    bfSale(['transaction_reference' => 'TRX-A3', 'status' => 'pending', 'payment_confirmed_at' => null]);
    bfSale(['transaction_reference' => 'TRX-A3', 'status' => 'completed']);
    // cancelled mixed with completed
    bfSale(['transaction_reference' => 'TRX-A4', 'status' => 'cancelled']);
    bfSale(['transaction_reference' => 'TRX-A4', 'status' => 'completed']);
    // unknown status
    bfSale(['transaction_reference' => 'TRX-A5', 'status' => 'weird']);
    // invalid amounts
    bfSale(['transaction_reference' => 'TRX-A6', 'total_price' => -5]);
    // mixed reservation markers
    bfSale(['transaction_reference' => 'TRX-A7', 'reserved_at' => '2026-03-01 09:00:00']);
    bfSale(['transaction_reference' => 'TRX-A7']);
    $before = bfSnapshot();

    bfRun('--execute')
        ->expectsOutputToContain('mixed_customers: 2')->expectsOutputToContain('mixed_pending_and_settled: 1')
        ->expectsOutputToContain('mixed_cancelled_or_expired: 1')->expectsOutputToContain('unknown_status: 1')
        ->expectsOutputToContain('invalid_amounts: 1')->expectsOutputToContain('mixed_reservation_markers: 1')
        ->assertExitCode(0);

    expect(bfSnapshot())->toEqual($before);
});

test('soft-deleted rows, existing headers and synthetic reference conflicts are never merged or overwritten', function () {
    // group with a soft-deleted line
    bfSale(['transaction_reference' => 'TRX-SD']);
    bfSale(['transaction_reference' => 'TRX-SD', 'deleted_at' => '2026-03-09 00:00:00']);
    // a fully soft-deleted single sale
    bfSale(['transaction_reference' => 'TRX-SD2', 'deleted_at' => '2026-03-09 00:00:00']);
    // an unlinked header already owns this reference
    DB::table('sale_headers')->insert(['transaction_reference' => 'TRX-HDR', 'transaction_type' => 'legacy', 'total_amount' => 1, 'created_at' => now(), 'updated_at' => now()]);
    bfSale(['transaction_reference' => 'TRX-HDR']);
    // a NULL-ref sale whose synthetic reference is already used by ANOTHER sale's reference
    $legacy = bfSale(['transaction_reference' => null]);
    bfSale(['transaction_reference' => 'LEGACY-'.$legacy]);
    $before = bfSnapshot();

    bfRun('--execute')->expectsOutputToContain('contains_soft_deleted: 2')->expectsOutputToContain('header_reference_exists: 1')
        ->expectsOutputToContain('synthetic_reference_conflict: 1')->assertExitCode(0);

    $after = bfSnapshot();
    // Only the unrelated LEGACY-<id> reference sale (which is itself a valid single group) was processed.
    expect(DB::table('sale_headers')->count())->toBe(2)
        ->and(DB::table('sales')->where('transaction_reference', 'TRX-HDR')->value('sale_header_id'))->toBeNull()
        ->and(DB::table('sales')->where('id', $legacy)->value('sale_header_id'))->toBeNull();
});

test('an already linked group is untouched and a partially linked group is skipped', function () {
    $linkedHeader = DB::table('sale_headers')->insertGetId(['transaction_reference' => 'TRX-LINKED', 'transaction_type' => 'legacy', 'total_amount' => 100, 'created_at' => now(), 'updated_at' => now()]);
    bfSale(['transaction_reference' => 'TRX-LINKED', 'sale_header_id' => $linkedHeader]);
    $partialHeader = DB::table('sale_headers')->insertGetId(['transaction_reference' => 'TRX-PART', 'transaction_type' => 'legacy', 'total_amount' => 100, 'created_at' => now(), 'updated_at' => now()]);
    bfSale(['transaction_reference' => 'TRX-PART', 'sale_header_id' => $partialHeader]);
    $orphan = bfSale(['transaction_reference' => 'TRX-PART']);
    $before = bfSnapshot();

    bfRun('--execute')->expectsOutputToContain('partially_linked: 1')->expectsOutputToContain('Headers created: 0')->assertExitCode(0);

    expect(bfSnapshot())->toEqual($before)->and(DB::table('sales')->where('id', $orphan)->value('sale_header_id'))->toBeNull();
});

// ── Safety properties ────────────────────────────────────────────────────────

test('a second run changes nothing and no financial value, timestamp, audit log or notification is touched', function () {
    $c = bfCustomer();
    bfSale(['transaction_reference' => 'TRX-I1', 'customer_id' => $c, 'amount_tendered' => 100]);
    bfSale(['transaction_reference' => 'TRX-I2']);
    bfSale(['transaction_reference' => 'TRX-I2']);
    bfSale(['transaction_reference' => null]);
    $financialsBefore = bfFinancials();
    $auditBefore = AuditLog::count();

    bfRun('--execute')->expectsOutputToContain('Headers created: 3')->assertExitCode(0);
    $afterFirst = bfSnapshot();
    bfRun('--execute')->expectsOutputToContain('Headers created: 0')->assertExitCode(0);

    expect(bfSnapshot())->toEqual($afterFirst)->and(bfFinancials())->toEqual($financialsBefore)
        ->and(AuditLog::count())->toBe($auditBefore)->and(DB::table('sale_headers')->count())->toBe(3);
    Notification::assertNothingSent();
});

test('--limit bounds a run and later runs continue without duplicates; tiny chunks cover everything', function () {
    foreach (['TRX-L1', 'TRX-L2', 'TRX-L3'] as $r) {
        bfSale(['transaction_reference' => $r]);
    }
    bfSale(['transaction_reference' => null]);

    bfRun('--execute', '--chunk=1')->assertExitCode(0); // runs unlimited to prove keyset paging first
    expect(DB::table('sale_headers')->count())->toBe(4);

    DB::table('sales')->update(['sale_header_id' => null]);
    DB::table('sale_headers')->delete();

    bfRun('--execute', '--limit=2', '--chunk=1')->assertExitCode(0);
    expect(DB::table('sale_headers')->count())->toBe(2);
    bfRun('--execute', '--limit=2', '--chunk=1')->assertExitCode(0);
    expect(DB::table('sale_headers')->count())->toBe(4)->and(DB::table('sales')->whereNull('sale_header_id')->count())->toBe(0);
});

test('a failure halfway rolls back only the failing group, and a retry completes it without duplicates', function () {
    bfSale(['transaction_reference' => 'TRX-A-OK']);
    bfSale(['transaction_reference' => 'TRX-B-BOOM']);
    bfSale(['transaction_reference' => 'TRX-B-BOOM']);
    bfSale(['transaction_reference' => 'TRX-C-OK']);

    if (DB::getDriverName() === 'pgsql') {
        DB::unprepared("CREATE FUNCTION bf_fail() RETURNS trigger AS \$\$ BEGIN IF NEW.transaction_reference = 'TRX-B-BOOM' AND NEW.sale_header_id IS NOT NULL THEN RAISE EXCEPTION 'injected failure'; END IF; RETURN NEW; END; \$\$ LANGUAGE plpgsql");
        DB::unprepared('CREATE TRIGGER bf_fail BEFORE UPDATE ON sales FOR EACH ROW EXECUTE FUNCTION bf_fail()');
    } else {
        DB::unprepared("CREATE TRIGGER bf_fail BEFORE UPDATE OF sale_header_id ON sales WHEN NEW.transaction_reference = 'TRX-B-BOOM' BEGIN SELECT RAISE(ABORT, 'injected failure'); END");
    }

    bfRun('--execute')->expectsOutputToContain('error:QueryException: 1')->assertExitCode(1);

    expect(bfHeader('TRX-A-OK'))->not->toBeNull()->and(bfHeader('TRX-C-OK'))->not->toBeNull()
        ->and(bfHeader('TRX-B-BOOM'))->toBeNull()
        ->and(DB::table('sales')->where('transaction_reference', 'TRX-B-BOOM')->whereNotNull('sale_header_id')->count())->toBe(0)
        ->and(DB::table('sale_headers')->count())->toBe(2);

    DB::unprepared(DB::getDriverName() === 'pgsql' ? 'DROP TRIGGER bf_fail ON sales' : 'DROP TRIGGER bf_fail');
    bfRun('--execute')->expectsOutputToContain('Headers created: 1')->assertExitCode(0);

    expect(DB::table('sale_headers')->count())->toBe(3)
        ->and(DB::table('sales')->where('transaction_reference', 'TRX-B-BOOM')->pluck('sale_header_id')->unique()->count())->toBe(1)
        ->and(DB::table('sales')->whereNull('sale_header_id')->count())->toBe(0);
});


// ── Reused references / contradictory pending data ───────────────────────────

test('reused reference weeks apart is skipped untouched, safe groups continue, reruns stay idempotent', function () {
    $c = bfCustomer();
    $a = ['transaction_reference' => 'TRX-REUSE', 'customer_id' => $c, 'amount_tendered' => 500, 'change' => 300];
    bfSale($a + ['created_at' => '2026-02-01 10:00:00', 'updated_at' => '2026-02-01 10:00:00']);
    bfSale($a + ['created_at' => '2026-03-01 15:30:00', 'updated_at' => '2026-03-01 15:30:00']);
    // legitimate multi-item sale created together
    bfSale(['transaction_reference' => 'TRX-OK', 'customer_id' => $c, 'created_at' => '2026-03-02 09:00:00']);
    bfSale(['transaction_reference' => 'TRX-OK', 'customer_id' => $c, 'created_at' => '2026-03-02 09:00:01']);
    // contradictory pending (tender and payment timestamp)
    bfSale(['transaction_reference' => 'TRX-PEND', 'status' => 'pending', 'amount_tendered' => 100, 'payment_confirmed_at' => null]);
    bfSale(['transaction_reference' => 'TRX-PEND2', 'status' => 'pending', 'payment_confirmed_at' => '2026-03-01 10:05:00']);
    // multi-line with missing timestamp fails closed
    bfSale(['transaction_reference' => 'TRX-NOTS', 'created_at' => null]);
    bfSale(['transaction_reference' => 'TRX-NOTS', 'created_at' => '2026-03-01 10:00:00']);

    $before = bfSnapshot();
    $writes = 0;
    DB::listen(function ($q) use (&$writes) {
        if (preg_match('/^\s*(insert|update|delete)/i', $q->sql)) {
            $writes++;
        }
    });
    bfRun('--dry-run')
        ->expectsOutputToContain('suspected_reused_reference: 1')
        ->expectsOutputToContain('pending_with_payment_data: 2')
        ->expectsOutputToContain('missing_created_at: 1')
        ->assertExitCode(0);
    expect($writes)->toBe(0)->and(bfSnapshot())->toBe($before);

    bfRun('--execute')->expectsOutputToContain('suspected_reused_reference: 1')->assertExitCode(0);
    expect(SaleHeader::pluck('transaction_reference')->all())->toBe(['TRX-OK']);
    foreach (['TRX-REUSE', 'TRX-PEND', 'TRX-PEND2', 'TRX-NOTS'] as $ref) {
        expect(DB::table('sales')->where('transaction_reference', $ref)->whereNotNull('sale_header_id')->count())->toBe(0);
    }
    $after = bfSnapshot();
    bfRun('--execute')->assertExitCode(0);
    bfRun('--execute')->assertExitCode(0);
    expect(bfSnapshot())->toBe($after)->and(SaleHeader::count())->toBe(1);
});
