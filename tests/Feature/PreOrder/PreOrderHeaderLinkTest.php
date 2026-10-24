<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\Sale;
use App\Models\SaleHeader;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * SYS4-06 D2D: pre_orders.sale_header_id links a newly paid pre-order to the exact header created
 * for it, so collecting later stamps one consistent pickup time on pre-order, sale and header.
 */

const PHL_MIG = 'database/migrations/2026_10_24_150000_add_sale_header_id_to_pre_orders_table.php';

beforeEach(fn () => Notification::fake());

function phlOwner(): User
{
    return User::query()->where('role', 'owner')->first() ?? User::factory()->create(['role' => 'owner']);
}

function phlOrder(array $o = []): PreOrder
{
    $u = User::factory()->create(['role' => 'customer']);
    $c = Customer::create(['user_id' => $u->id, 'name' => $u->name, 'email' => $u->email]);
    $item = InventoryItem::create(['part_name' => 'Phl Part', 'sku' => 'PHL'.uniqid(), 'price' => 500, 'quantity' => 3]);

    return PreOrder::create(array_merge([
        'customer_id' => $c->id, 'inventory_item_id' => $item->id, 'customer_name' => $c->name,
        'part_name' => 'Phl Part', 'quantity' => 2, 'price' => 500, 'total' => 1000,
        'status' => 'pending', 'is_paid' => false, 'is_stocked' => true,
        'sourced_at' => now()->subDay(), 'ready_notified_at' => now()->subHour(), 'pickup_expires_at' => now()->addHours(23),
    ], $o));
}

function phlPay($t, PreOrder $o, float $cash)
{
    return $t->actingAs(phlOwner())->post(route('preorders.pay', $o), ['amount_tendered' => $cash]);
}

function phlPickup($t, PreOrder $o, float $cash = 0)
{
    return $t->actingAs(phlOwner())->post(route('preorders.pickup', $o), ['amount_tendered' => $cash]);
}

function phlComplete($t, PreOrder $o)
{
    return $t->actingAs(phlOwner())->post(route('preorders.complete', $o));
}

function phlPaymentSnapshot(SaleHeader $h, Sale $s): array
{
    return [
        'h' => $h->fresh()->only(['transaction_reference', 'total_amount', 'amount_tendered', 'change_amount', 'payment_confirmed_at', 'reserved_at', 'expires_at']),
        's' => $s->fresh()->only(['transaction_reference', 'total_price', 'amount_tendered', 'change', 'status', 'payment_confirmed_at', 'quantity', 'inventory_item_id']),
    ];
}

function phlFailHeaderUpdates(): void
{
    if (DB::getDriverName() === 'pgsql') {
        DB::unprepared("CREATE FUNCTION phl_fail() RETURNS trigger AS \$\$ BEGIN RAISE EXCEPTION 'injected failure'; END; \$\$ LANGUAGE plpgsql");
        DB::unprepared('CREATE TRIGGER phl_fail BEFORE UPDATE ON sale_headers FOR EACH ROW EXECUTE FUNCTION phl_fail()');

        return;
    }

    DB::unprepared("CREATE TRIGGER phl_fail BEFORE UPDATE ON sale_headers BEGIN SELECT RAISE(ABORT, 'injected failure'); END");
}

// ── Pay first, collect later ─────────────────────────────────────────────────

test('paying links the exact header and sale to the pre-order', function () {
    $o = phlOrder();

    phlPay($this, $o, 1200)->assertSessionHas('success');

    $header = SaleHeader::sole();
    $sale = Sale::sole();
    expect($o->fresh()->sale_header_id)->toBe($header->id)->and($sale->sale_header_id)->toBe($header->id)
        ->and((bool) $o->fresh()->is_paid)->toBeTrue()->and($o->fresh()->status)->toBe('pending')
        ->and($o->fresh()->saleHeader->is($header))->toBeTrue()->and($header->preorder->is($o))->toBeTrue()
        ->and($header->picked_up_at)->toBeNull()->and($sale->picked_up_at)->toBeNull();
});

test('collecting a paid linked pre-order via complete stamps one consistent pickup time and keeps payment values', function () {
    $o = phlOrder();
    phlPay($this, $o, 1200);
    $header = SaleHeader::sole();
    $sale = Sale::sole();
    $before = phlPaymentSnapshot($header, $sale);

    phlComplete($this, $o)->assertSessionHas('success');

    $o = $o->fresh();
    $header = $header->fresh();
    $sale = $sale->fresh();
    expect($o->status)->toBe('completed')->and($o->picked_up_at)->not->toBeNull()
        ->and($header->picked_up_at->equalTo($o->picked_up_at))->toBeTrue()
        ->and($sale->picked_up_at->equalTo($o->picked_up_at))->toBeTrue()
        ->and(phlPaymentSnapshot($header, $sale))->toEqual($before)
        ->and((float) $header->total_amount)->toBe(1000.0)->and((float) $header->amount_tendered)->toBe(1200.0)->and((float) $header->change_amount)->toBe(200.0)
        ->and($sale->status)->toBe('completed')
        ->and($o->inventoryItem->quantity)->toBe(3)
        ->and(Sale::count())->toBe(1)->and(SaleHeader::count())->toBe(1);
});

test('collecting a paid linked pre-order via the pickup route behaves the same', function () {
    $o = phlOrder();
    phlPay($this, $o, 1000);
    $header = SaleHeader::sole();
    $sale = Sale::sole();
    $before = phlPaymentSnapshot($header, $sale);

    phlPickup($this, $o)->assertSessionHas('success');

    $o = $o->fresh();
    expect($o->status)->toBe('completed')
        ->and($header->fresh()->picked_up_at->equalTo($o->picked_up_at))->toBeTrue()
        ->and($sale->fresh()->picked_up_at->equalTo($o->picked_up_at))->toBeTrue()
        ->and(phlPaymentSnapshot($header, $sale))->toEqual($before)
        ->and(Sale::count())->toBe(1)->and(SaleHeader::count())->toBe(1);
});

test('repeating collection or payment creates nothing and does not move the pickup time', function () {
    $o = phlOrder();
    phlPay($this, $o, 1000);
    phlComplete($this, $o);
    $header = SaleHeader::sole();
    $pickedAt = $header->picked_up_at;

    phlPay($this, $o, 1000)->assertSessionHas('error');
    phlComplete($this, $o)->assertSessionHas('error');
    phlPickup($this, $o)->assertSessionHas('warning');

    expect(Sale::count())->toBe(1)->and(SaleHeader::count())->toBe(1)
        ->and($header->fresh()->picked_up_at->equalTo($pickedAt))->toBeTrue()
        ->and($o->fresh()->sale_header_id)->toBe($header->id);
});

// ── Unpaid pickup ────────────────────────────────────────────────────────────

test('unpaid pickup creates one header and sale, links them, and shares one pickup instant', function () {
    $o = phlOrder();

    phlPickup($this, $o, 1200)->assertSessionHas('success');

    $o = $o->fresh();
    $header = SaleHeader::sole();
    $sale = Sale::sole();
    expect($o->sale_header_id)->toBe($header->id)->and($sale->sale_header_id)->toBe($header->id)
        ->and($o->picked_up_at->equalTo($header->picked_up_at))->toBeTrue()
        ->and($o->picked_up_at->equalTo($sale->picked_up_at))->toBeTrue()
        ->and($header->payment_confirmed_at->equalTo($sale->payment_confirmed_at))->toBeTrue()
        ->and((float) $header->amount_tendered)->toBe(1200.0)->and((float) $header->change_amount)->toBe(200.0)
        ->and($o->status)->toBe('completed')->and($o->inventoryItem->quantity)->toBe(3);

    phlPickup($this, $o, 1200)->assertSessionHas('warning');
    expect(Sale::count())->toBe(1)->and(SaleHeader::count())->toBe(1);
});

test('short cash at pickup links and creates nothing', function () {
    $o = phlOrder();

    phlPickup($this, $o, 999)->assertSessionHas('error');

    expect($o->fresh()->sale_header_id)->toBeNull()->and(SaleHeader::count())->toBe(0)->and(Sale::count())->toBe(0);
});

// ── Legacy (NULL link) behavior ──────────────────────────────────────────────

test('an already-paid legacy pre-order with no link completes exactly as before and stays NULL', function () {
    // A neighbouring sale/header for the same customer and item must never be picked up by guesswork.
    $legacy = phlOrder(['is_paid' => true]);
    $decoy = phlOrder(['customer_id' => $legacy->customer_id, 'inventory_item_id' => $legacy->inventory_item_id]);
    phlPay($this, $decoy, 1000);
    $decoyHeader = SaleHeader::sole();
    $decoySale = Sale::sole();
    $before = phlPaymentSnapshot($decoyHeader, $decoySale);

    phlComplete($this, $legacy)->assertSessionHas('success');
    $second = phlOrder(['is_paid' => true, 'customer_id' => $legacy->customer_id, 'inventory_item_id' => $legacy->inventory_item_id]);
    phlPickup($this, $second)->assertSessionHas('success');

    expect($legacy->fresh()->status)->toBe('completed')->and($legacy->fresh()->picked_up_at)->not->toBeNull()
        ->and($legacy->fresh()->sale_header_id)->toBeNull()->and($second->fresh()->sale_header_id)->toBeNull()
        ->and($decoyHeader->fresh()->picked_up_at)->toBeNull()->and($decoySale->fresh()->picked_up_at)->toBeNull()
        ->and(phlPaymentSnapshot($decoyHeader, $decoySale))->toEqual($before)
        ->and(Sale::count())->toBe(1)->and(SaleHeader::count())->toBe(1);
});

test('collecting one pre-order never touches another pre-order\'s header', function () {
    $a = phlOrder();
    $b = phlOrder();
    phlPay($this, $a, 1000);
    phlPay($this, $b, 1000);
    $headerB = $b->fresh()->saleHeader;
    $saleB = Sale::where('sale_header_id', $headerB->id)->sole();

    phlComplete($this, $a);

    expect($a->fresh()->saleHeader->picked_up_at)->not->toBeNull()
        ->and($headerB->fresh()->picked_up_at)->toBeNull()->and($saleB->fresh()->picked_up_at)->toBeNull()
        ->and($a->fresh()->sale_header_id)->not->toBe($b->fresh()->sale_header_id);
});

test('the database refuses two pre-orders sharing one header, while many NULLs are allowed', function () {
    $header = SaleHeader::create(['transaction_reference' => 'PRE-UNIQ', 'transaction_type' => 'preorder', 'total_amount' => 1]);
    $a = phlOrder();
    $b = phlOrder();
    phlOrder();

    $a->sale_header_id = $header->id;
    $a->save();
    $b->sale_header_id = $header->id;

    // savepoint: PostgreSQL aborts the surrounding test transaction after a constraint error
    expect(fn () => DB::transaction(fn () => $b->save()))->toThrow(QueryException::class);
    expect(PreOrder::whereNull('sale_header_id')->count())->toBeGreaterThanOrEqual(2);
});

test('sale_header_id cannot be mass-assigned through the model', function () {
    $header = SaleHeader::create(['transaction_reference' => 'PRE-MASS', 'transaction_type' => 'preorder', 'total_amount' => 1]);
    $o = phlOrder();

    $o->update(['sale_header_id' => $header->id]);

    expect($o->fresh()->sale_header_id)->toBeNull();
});

// ── Atomicity ────────────────────────────────────────────────────────────────

test('a failure while stamping pickup on the header rolls back sale, header, pre-order and status', function () {
    $o = phlOrder();
    phlPay($this, $o, 1000);
    $header = SaleHeader::sole();
    phlFailHeaderUpdates();

    $this->withoutExceptionHandling();
    expect(fn () => phlComplete($this, $o))->toThrow(QueryException::class, 'injected failure');

    $o = $o->fresh();
    expect($o->status)->toBe('pending')->and($o->picked_up_at)->toBeNull()
        ->and($header->fresh()->picked_up_at)->toBeNull()->and(Sale::sole()->picked_up_at)->toBeNull();
});

test('a failed payment save leaves no header, sale, paid flag or link', function () {
    $o = phlOrder();
    if (DB::getDriverName() === 'pgsql') {
        DB::unprepared("CREATE FUNCTION phl_fail2() RETURNS trigger AS \$\$ BEGIN RAISE EXCEPTION 'injected failure'; END; \$\$ LANGUAGE plpgsql");
        DB::unprepared('CREATE TRIGGER phl_fail2 BEFORE UPDATE ON pre_orders FOR EACH ROW EXECUTE FUNCTION phl_fail2()');
    } else {
        DB::unprepared("CREATE TRIGGER phl_fail2 BEFORE UPDATE ON pre_orders BEGIN SELECT RAISE(ABORT, 'injected failure'); END");
    }

    $this->withoutExceptionHandling();
    expect(fn () => phlPay($this, $o, 1000))->toThrow(QueryException::class, 'injected failure');

    expect(SaleHeader::count())->toBe(0)->and(Sale::count())->toBe(0)
        ->and((bool) $o->fresh()->is_paid)->toBeFalse()->and($o->fresh()->sale_header_id)->toBeNull();
});

test('a failed unpaid pickup leaves no header, sale, link, completion or pickup time', function () {
    $o = phlOrder();
    if (DB::getDriverName() === 'pgsql') {
        DB::unprepared("CREATE FUNCTION phl_fail3() RETURNS trigger AS \$\$ BEGIN RAISE EXCEPTION 'injected failure'; END; \$\$ LANGUAGE plpgsql");
        DB::unprepared('CREATE TRIGGER phl_fail3 BEFORE UPDATE ON pre_orders FOR EACH ROW EXECUTE FUNCTION phl_fail3()');
    } else {
        DB::unprepared("CREATE TRIGGER phl_fail3 BEFORE UPDATE ON pre_orders BEGIN SELECT RAISE(ABORT, 'injected failure'); END");
    }

    $this->withoutExceptionHandling();
    expect(fn () => phlPickup($this, $o, 1000))->toThrow(QueryException::class, 'injected failure');

    $fresh = $o->fresh();
    expect(SaleHeader::count())->toBe(0)->and(Sale::count())->toBe(0)->and($fresh->sale_header_id)->toBeNull()
        ->and($fresh->status)->toBe('pending')->and($fresh->picked_up_at)->toBeNull();
});

// ── Authorization and migration ──────────────────────────────────────────────

test('only an owner can pay, pick up or complete a pre-order', function () {
    $o = phlOrder();
    $customer = User::factory()->create(['role' => 'customer']);

    foreach (['preorders.pay', 'preorders.pickup'] as $route) {
        $this->actingAs($customer)->post(route($route, $o), ['amount_tendered' => 1000])->assertStatus(403);
    }
    $this->actingAs($customer)->post(route('preorders.complete', $o))->assertStatus(403);

    expect($o->fresh()->sale_header_id)->toBeNull()->and(SaleHeader::count())->toBe(0)->and(Sale::count())->toBe(0);
});

test('the migration adds a nullable, unique, restrictive link and existing rows stay NULL', function () {
    expect(Schema::hasColumn('pre_orders', 'sale_header_id'))->toBeTrue()
        ->and(Schema::hasIndex('pre_orders', 'uq_pre_orders_sale_header_id'))->toBeTrue();

    $o = phlOrder();
    expect($o->fresh()->sale_header_id)->toBeNull();

    $header = SaleHeader::create(['transaction_reference' => 'PRE-FK', 'transaction_type' => 'preorder', 'total_amount' => 1]);
    $o->sale_header_id = $header->id;
    $o->save();

    expect(fn () => DB::transaction(fn () => $header->delete()))->toThrow(QueryException::class); // restrictive FK
});

test('rollback refuses while any pre-order is linked, then works once links are NULL and can re-run', function () {
    $migration = require base_path(PHL_MIG);
    $o = phlOrder();
    phlPay($this, $o, 1000);

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, '1 pre-order(s) are linked');
    expect(Schema::hasColumn('pre_orders', 'sale_header_id'))->toBeTrue()
        ->and($o->fresh()->sale_header_id)->not->toBeNull()
        ->and(Schema::hasIndex('pre_orders', 'uq_pre_orders_sale_header_id'))->toBeTrue();

    DB::table('pre_orders')->update(['sale_header_id' => null]);
    $migration->down();
    expect(Schema::hasColumn('pre_orders', 'sale_header_id'))->toBeFalse();
    $migration->down(); // harmless repeat

    $migration->up();
    $migration->up(); // idempotent
    expect(Schema::hasColumn('pre_orders', 'sale_header_id'))->toBeTrue()
        ->and(Schema::hasIndex('pre_orders', 'uq_pre_orders_sale_header_id'))->toBeTrue();
});

// ── Fail closed on inconsistent linked records ───────────────────────────────

dataset('phl_inconsistencies', [
    'header is not a preorder header' => fn (SaleHeader $h, Sale $s, PreOrder $o) => $h->update(['transaction_type' => 'repair']),
    'header was never payment-confirmed' => fn (SaleHeader $h, Sale $s, PreOrder $o) => $h->update(['payment_confirmed_at' => null]),
    'sale belongs to a different customer' => fn (SaleHeader $h, Sale $s, PreOrder $o) => DB::table('sales')->where('id', $s->id)->update(['customer_id' => null]),
    'sale reference differs from the header' => fn (SaleHeader $h, Sale $s, PreOrder $o) => DB::table('sales')->where('id', $s->id)->update(['transaction_reference' => 'PRE-OTHER']),
    'sale quantity differs from the pre-order' => fn (SaleHeader $h, Sale $s, PreOrder $o) => DB::table('sales')->where('id', $s->id)->update(['quantity' => 9]),
    'sale total differs from the pre-order' => fn (SaleHeader $h, Sale $s, PreOrder $o) => DB::table('sales')->where('id', $s->id)->update(['total_price' => 1]),
    'sale already has a pickup time' => fn (SaleHeader $h, Sale $s, PreOrder $o) => DB::table('sales')->where('id', $s->id)->update(['picked_up_at' => now()->subDay()]),
    'header already has a pickup time' => fn (SaleHeader $h, Sale $s, PreOrder $o) => $h->update(['picked_up_at' => now()->subDay()]),
    'sale line is pending' => fn (SaleHeader $h, Sale $s, PreOrder $o) => DB::table('sales')->where('id', $s->id)->update(['status' => 'pending']),
    'sale line is refunded' => fn (SaleHeader $h, Sale $s, PreOrder $o) => DB::table('sales')->where('id', $s->id)->update(['status' => 'refunded']),
    'sale line has no payment confirmation' => fn (SaleHeader $h, Sale $s, PreOrder $o) => DB::table('sales')->where('id', $s->id)->update(['payment_confirmed_at' => null]),
    'header has a second sale line' => fn (SaleHeader $h, Sale $s, PreOrder $o) => Sale::create([
        'sale_header_id' => $h->id, 'inventory_item_id' => $s->inventory_item_id, 'customer_id' => $s->customer_id, 'quantity' => 1,
        'price' => 1, 'total_price' => 1, 'status' => 'completed', 'transaction_reference' => $h->transaction_reference,
    ]),
]);

test('an inconsistent linked record is refused on both collection routes with nothing changed', function (Closure $break) {
    foreach (['preorders.complete', 'preorders.pickup'] as $route) {
        $o = phlOrder();
        phlPay($this, $o, 1000);
        $header = $o->fresh()->saleHeader;
        $sale = Sale::where('sale_header_id', $header->id)->first();
        $break($header, $sale, $o->fresh());

        $snapshot = [
            'h' => SaleHeader::find($header->id)->getAttributes(),
            'lines' => Sale::where('sale_header_id', $header->id)->orderBy('id')->get()->map->getAttributes()->all(),
            'stock' => $o->fresh()->inventoryItem->quantity,
        ];

        $this->actingAs(phlOwner())->post(route($route, $o), ['amount_tendered' => 0])->assertSessionHas('error');

        $fresh = $o->fresh();
        expect($fresh->status)->toBe('pending')->and($fresh->picked_up_at)->toBeNull()
            ->and(SaleHeader::find($header->id)->getAttributes())->toEqual($snapshot['h'])
            ->and(Sale::where('sale_header_id', $header->id)->orderBy('id')->get()->map->getAttributes()->all())->toEqual($snapshot['lines'])
            ->and($fresh->inventoryItem->quantity)->toBe($snapshot['stock']);
        expect(session('error'))->toContain('needs review');
    }
})->with('phl_inconsistencies');

// ── Unpaid but already linked ────────────────────────────────────────────────

test('an unpaid pre-order that already has a link cannot take another payment through pay() or pickup()', function (string $route) {
    $o = phlOrder();
    phlPay($this, $o, 1000);
    $linked = $o->fresh()->sale_header_id;
    DB::table('pre_orders')->where('id', $o->id)->update(['is_paid' => false]); // inconsistent state

    $before = [
        'headers' => SaleHeader::count(), 'sales' => Sale::count(),
        'header' => SaleHeader::find($linked)->getAttributes(),
        'lines' => Sale::where('sale_header_id', $linked)->get()->map->getAttributes()->all(),
        'stock' => $o->fresh()->inventoryItem->quantity,
    ];

    $this->actingAs(phlOwner())->post(route($route, $o), ['amount_tendered' => 5000])->assertSessionHas('error');

    $fresh = $o->fresh();
    expect(session('error'))->toContain('needs review')
        ->and($fresh->sale_header_id)->toBe($linked)->and((bool) $fresh->is_paid)->toBeFalse()
        ->and($fresh->status)->toBe('pending')->and($fresh->picked_up_at)->toBeNull()
        ->and(SaleHeader::count())->toBe($before['headers'])->and(Sale::count())->toBe($before['sales'])
        ->and(SaleHeader::find($linked)->getAttributes())->toEqual($before['header'])
        ->and(Sale::where('sale_header_id', $linked)->get()->map->getAttributes()->all())->toEqual($before['lines'])
        ->and($fresh->inventoryItem->quantity)->toBe($before['stock']);
})->with(['preorders.pay', 'preorders.pickup']);

test('a normal pay then pickup still succeeds with one consistent pickup time', function () {
    $o = phlOrder();
    phlPay($this, $o, 1200)->assertSessionHas('success');
    phlPickup($this, $o)->assertSessionHas('success');

    $fresh = $o->fresh();
    $header = $fresh->saleHeader;
    $sale = Sale::where('sale_header_id', $header->id)->first();
    expect($fresh->status)->toBe('completed')->and(SaleHeader::count())->toBe(1)->and(Sale::count())->toBe(1)
        ->and((float) $header->amount_tendered)->toBe(1200.0)->and((float) $header->change_amount)->toBe(200.0)
        ->and($header->picked_up_at->equalTo($fresh->picked_up_at))->toBeTrue()
        ->and($sale->picked_up_at->equalTo($fresh->picked_up_at))->toBeTrue();
});
