<?php

use App\Actions\CommitOrder;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\SaleHeader;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * SYS4-06 D2A: CommitOrder (POS + online reservation) writes exactly one sale_headers row per
 * committed transaction and links every line created, atomically. Repair/preorder writers,
 * pickup/cancel/expiry/refund logic and all readers are intentionally untouched.
 */

function coItem(string $name, float $price, int $qty = 20): InventoryItem
{
    static $n = 0;
    $n++;

    return InventoryItem::create(['part_name' => $name, 'sku' => 'CO-'.$n, 'price' => $price, 'quantity' => $qty]);
}

function coOwner(): User
{
    return User::factory()->create(['role' => 'owner']);
}

/** Real POS request: owner posts the cart to the real controller. */
function coPos(User $owner, array $cart, float $tendered)
{
    return test()->actingAs($owner)->post('/sales/store', [
        'cart_data' => json_encode($cart),
        'amount_tendered' => $tendered,
    ]);
}

/** Every line of a reference is linked to the one header, and the header total is the lines' sum. */
function coAssertLinked(string $ref, int $expectedLines): SaleHeader
{
    $headers = SaleHeader::where('transaction_reference', $ref)->get();
    expect($headers)->toHaveCount(1);
    $header = $headers->first();

    $lines = Sale::where('transaction_reference', $ref)->get();
    expect($lines)->toHaveCount($expectedLines)
        ->and($lines->pluck('sale_header_id')->unique()->all())->toBe([$header->id])
        ->and($header->lines()->count())->toBe($expectedLines)
        ->and(round((float) $lines->sum('total_price'), 2))->toBe(round((float) $header->total_amount, 2));

    return $header;
}

test('single-item POS sale creates one header and one linked line', function () {
    $part = coItem('Brake Pad', 250, 10);

    $res = coPos(coOwner(), [['id' => $part->id, 'quantity' => 1]], 300);
    $res->assertRedirect();
    $ref = Sale::first()->transaction_reference;

    $header = coAssertLinked($ref, 1);
    $line = Sale::first();

    expect($header->transaction_type)->toBe(SaleHeader::TYPE_POS)
        ->and($header->customer_id)->toBeNull()
        ->and((float) $header->total_amount)->toBe(250.0)
        ->and((float) $header->amount_tendered)->toBe(300.0)
        ->and((float) $header->change_amount)->toBe(50.0)
        ->and($header->payment_confirmed_at)->not->toBeNull()
        ->and($header->reserved_at)->toBeNull()
        ->and($header->expires_at)->toBeNull()
        ->and($header->picked_up_at)->toBeNull()
        // existing line behaviour is exactly as before
        ->and($line->status)->toBe('completed')
        ->and((float) $line->amount_tendered)->toBe(300.0)
        ->and((float) $line->change)->toBe(50.0)
        ->and((float) $line->total_price)->toBe(250.0)
        ->and($line->payment_confirmed_at)->not->toBeNull()
        ->and($part->fresh()->quantity)->toBe(9);
});

test('multi-item POS sale creates one header with every line linked, tender only on the first line', function () {
    $a = coItem('Chain', 100);
    $b = coItem('Sprocket', 120);
    $c = coItem('Grip', 110);

    // item $a appears twice: the existing normalisation merges it into one line
    coPos(coOwner(), [
        ['id' => $c->id, 'quantity' => 2], ['id' => $a->id, 'quantity' => 1],
        ['id' => $b->id, 'quantity' => 1], ['id' => $a->id, 'quantity' => 1],
    ], 600);
    $ref = Sale::first()->transaction_reference;

    $header = coAssertLinked($ref, 3);               // 2 x 100 + 120 + 2 x 110 = 540
    $lines = Sale::where('transaction_reference', $ref)->orderBy('id')->get();

    expect((float) $header->total_amount)->toBe(540.0)
        ->and((float) $header->amount_tendered)->toBe(600.0)
        ->and((float) $header->change_amount)->toBe(60.0)
        // existing semantics: tender and change live on the first inserted line only
        ->and($lines->pluck('amount_tendered')->map(fn ($v) => (float) $v)->all())->toBe([600.0, 0.0, 0.0])
        ->and($lines->pluck('change')->map(fn ($v) => (float) $v)->all())->toBe([60.0, 0.0, 0.0])
        ->and($a->fresh()->quantity)->toBe(18)
        ->and($b->fresh()->quantity)->toBe(19)
        ->and($c->fresh()->quantity)->toBe(18);
});

test('online checkout creates a pending-reservation header with matching amounts and dates', function () {
    $a = coItem('Mirror', 150);
    $b = coItem('Bulb', 100);
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => 'Ana', 'email' => $user->email]);

    $total = app(CheckoutService::class)->processTransaction(
        [$a->id => ['quantity' => 1], $b->id => ['quantity' => 2]], 'TRX-CO-RES', $customer->id, 0
    );

    $header = coAssertLinked('TRX-CO-RES', 2);
    $lines = Sale::where('transaction_reference', 'TRX-CO-RES')->get();

    expect((float) $total)->toBe(350.0)
        ->and($header->transaction_type)->toBe(SaleHeader::TYPE_ONLINE_RESERVATION)
        ->and($header->customer_id)->toBe($customer->id)
        ->and((float) $header->total_amount)->toBe(350.0)
        ->and((float) $header->amount_tendered)->toBe(0.0)
        ->and((float) $header->change_amount)->toBe(0.0)
        ->and($header->payment_confirmed_at)->toBeNull()
        ->and($header->picked_up_at)->toBeNull()
        ->and($header->reserved_at)->not->toBeNull()
        ->and($header->expires_at->equalTo($header->reserved_at->copy()->addHours(24)))->toBeTrue()
        ->and($lines->pluck('status')->unique()->all())->toBe(['pending'])
        ->and($lines->pluck('customer_id')->unique()->all())->toBe([$customer->id])
        ->and($lines->every(fn ($l) => $l->reserved_at->equalTo($header->reserved_at) && $l->expires_at->equalTo($header->expires_at)))->toBeTrue()
        ->and($lines->every(fn ($l) => $l->payment_confirmed_at === null))->toBeTrue()
        ->and($a->fresh()->quantity)->toBe(19)
        ->and($b->fresh()->quantity)->toBe(18);
});

test('the real customer checkout request produces the same header', function () {
    $part = coItem('Handlebar', 400);
    $user = User::factory()->create(['role' => 'customer']);
    Customer::create(['user_id' => $user->id, 'name' => 'Cy', 'email' => $user->email]);

    $this->actingAs($user)->post('/cart/add/'.$part->id);
    $this->actingAs($user)->post('/checkout')->assertRedirect('/checkout/receipt');

    $header = SaleHeader::first();
    expect(SaleHeader::count())->toBe(1)
        ->and($header->transaction_type)->toBe(SaleHeader::TYPE_ONLINE_RESERVATION)
        ->and((float) $header->total_amount)->toBe(400.0)
        ->and($header->lines()->count())->toBe(1)
        ->and(Sale::whereNull('sale_header_id')->count())->toBe(0);

    $this->actingAs($user)->get('/checkout/receipt')->assertOk()->assertSee('400.00');
});

test('a stock failure rolls everything back: no header, no lines, no stock change', function () {
    $ok = coItem('Plenty', 100, 10);
    $short = coItem('Scarce', 50, 1);

    expect(fn () => app(CommitOrder::class)->execute(
        [['id' => $ok->id, 'quantity' => 2], ['id' => $short->id, 'quantity' => 5]], 'TRX-CO-FAIL', null,
        'Out of stock: ', fn ($f) => $f ? 1000.0 : 0.0, fn ($f) => 0.0, false, 'completed'
    ))->toThrow(Exception::class, 'Out of stock: Scarce');

    expect(SaleHeader::count())->toBe(0)
        ->and(Sale::withTrashed()->count())->toBe(0)
        ->and($ok->fresh()->quantity)->toBe(10)
        ->and($short->fresh()->quantity)->toBe(1);

    // the real POS endpoint behaves the same way
    coPos(coOwner(), [['id' => $ok->id, 'quantity' => 2], ['id' => $short->id, 'quantity' => 5]], 1000);
    expect(SaleHeader::count())->toBe(0)->and(Sale::count())->toBe(0)->and($ok->fresh()->quantity)->toBe(10);
});

test('insufficient cash and a mid-write failure also leave nothing behind', function () {
    $part = coItem('Pedal', 300, 5);

    $res = coPos(coOwner(), [['id' => $part->id, 'quantity' => 1]], 100);
    $res->assertSessionHas('error'); // the controller turns the validation failure into a flash error
    expect(SaleHeader::count())->toBe(0)->and(Sale::count())->toBe(0)->and($part->fresh()->quantity)->toBe(5);

    // a failure AFTER the header and the first line exist (second line write explodes) rolls both back
    $other = coItem('Other', 10, 5);
    $armed = true;
    Sale::creating(function () use (&$armed) {
        static $count = 0;
        if ($armed && ++$count === 2) {
            throw new RuntimeException('simulated line failure');
        }
    });

    expect(fn () => app(CommitOrder::class)->execute(
        [['id' => $part->id, 'quantity' => 1], ['id' => $other->id, 'quantity' => 1]], 'TRX-CO-MID', null,
        'Out of stock: ', fn ($f) => $f ? 500.0 : 0.0, fn ($f) => 0.0, false, 'completed'
    ))->toThrow(RuntimeException::class, 'simulated line failure');
    $armed = false;

    expect(SaleHeader::count())->toBe(0)
        ->and(Sale::withTrashed()->count())->toBe(0)
        ->and($part->fresh()->quantity)->toBe(5)
        ->and($other->fresh()->quantity)->toBe(5);
});

test('a reference already used by a header is refused and never shared', function () {
    $part = coItem('Cable', 80, 10);
    app(CommitOrder::class)->execute(
        [['id' => $part->id, 'quantity' => 1]], 'TRX-CO-DUP', null, 'Out of stock: ',
        fn ($f) => $f ? 100.0 : 0.0, fn ($f, $t) => $f ? round(100 - $t, 2) : 0.0, false, 'completed'
    );
    $original = SaleHeader::first();

    expect(fn () => app(CommitOrder::class)->execute(
        [['id' => $part->id, 'quantity' => 2]], 'TRX-CO-DUP', null, 'Out of stock: ',
        fn ($f) => $f ? 500.0 : 0.0, fn ($f) => 0.0, false, 'completed'
    ))->toThrow(RuntimeException::class, 'already in use');

    expect(SaleHeader::count())->toBe(1)
        ->and(Sale::count())->toBe(1)
        ->and($original->fresh()->lines()->count())->toBe(1)
        ->and((float) $original->fresh()->total_amount)->toBe(80.0)
        ->and($part->fresh()->quantity)->toBe(9);   // the refused attempt took no stock
});

test('a reference already used by legacy header-less sales is refused too', function () {
    $part = coItem('Legacy Part', 60, 10);
    Sale::create([
        'inventory_item_id' => $part->id, 'quantity' => 1, 'price' => 60, 'total_price' => 60,
        'transaction_reference' => 'TRX-CO-LEGACY', 'status' => 'completed', 'amount_tendered' => 100, 'change' => 40,
    ]);

    expect(fn () => app(CommitOrder::class)->execute(
        [['id' => $part->id, 'quantity' => 1]], 'TRX-CO-LEGACY', null, 'Out of stock: ',
        fn () => 0, fn () => 0, false, 'pending'
    ))->toThrow(RuntimeException::class, 'already in use');

    expect(SaleHeader::count())->toBe(0)
        ->and(Sale::where('transaction_reference', 'TRX-CO-LEGACY')->count())->toBe(1)
        ->and(Sale::first()->sale_header_id)->toBeNull();
});

test('two transactions get two separate headers and never mix lines', function () {
    $part = coItem('Shared Part', 100, 20);
    $owner = coOwner();

    coPos($owner, [['id' => $part->id, 'quantity' => 1]], 100);
    coPos($owner, [['id' => $part->id, 'quantity' => 2]], 300);

    $headers = SaleHeader::orderBy('id')->get();
    expect($headers)->toHaveCount(2)
        ->and($headers[0]->transaction_reference)->not->toBe($headers[1]->transaction_reference)
        ->and($headers[0]->lines()->count())->toBe(1)
        ->and($headers[1]->lines()->count())->toBe(1)
        ->and(Sale::whereNull('sale_header_id')->count())->toBe(0);
});

test('stock, audit rows and receipts behave as before; legacy header-less sales stay readable', function () {
    $part = coItem('Audit Part', 90, 10);
    $owner = coOwner();

    $auditBefore = AuditLog::where('auditable_type', Sale::class)->where('action', 'created')->count();
    coPos($owner, [['id' => $part->id, 'quantity' => 2]], 200);
    $ref = Sale::first()->transaction_reference;

    // one 'created' audit row per sale line, none for the header model, stock decremented once
    expect(AuditLog::where('auditable_type', Sale::class)->where('action', 'created')->count())->toBe($auditBefore + 1)
        ->and(AuditLog::where('auditable_type', SaleHeader::class)->count())->toBe(0)
        ->and($part->fresh()->quantity)->toBe(8);

    // the existing receipts (which do not read headers yet) are unchanged
    $this->actingAs($owner)->get("/sales/{$ref}/receipt")->assertOk()
        ->assertSee('Amount Tendered:')->assertSee('₱200.00')->assertSee('₱20.00');
    $r = $this->actingAs($owner)->get("/sales/{$ref}/download");
    $r->assertOk()->assertHeader('content-type', 'application/pdf');

    // a pre-header (NULL) sale created directly, as legacy code and older tests do
    $legacy = Sale::create([
        'inventory_item_id' => $part->id, 'quantity' => 1, 'price' => 90, 'total_price' => 90,
        'transaction_reference' => 'TRX-CO-OLD', 'status' => 'completed', 'amount_tendered' => 100, 'change' => 10,
        'payment_confirmed_at' => now(),
    ]);
    expect($legacy->fresh()->sale_header_id)->toBeNull();
    $this->actingAs($owner)->get('/sales/TRX-CO-OLD/receipt')->assertOk()->assertSee('₱100.00');
    $this->actingAs($owner)->get('/sales/history')->assertOk();
});

test('every line created by CommitOrder references exactly one existing header', function () {
    $a = coItem('One', 10);
    $b = coItem('Two', 20);
    $owner = coOwner();
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => 'Di', 'email' => $user->email]);

    coPos($owner, [['id' => $a->id, 'quantity' => 1], ['id' => $b->id, 'quantity' => 3]], 100);
    app(CheckoutService::class)->processTransaction([$a->id => ['quantity' => 2]], 'TRX-CO-ALL', $customer->id, 0);

    $orphans = DB::table('sales')->leftJoin('sale_headers', 'sales.sale_header_id', '=', 'sale_headers.id')
        ->whereNull('sale_headers.id')->count();

    expect(Sale::count())->toBe(3)
        ->and(Sale::whereNull('sale_header_id')->count())->toBe(0)
        ->and($orphans)->toBe(0)
        ->and(SaleHeader::count())->toBe(2)
        ->and(SaleHeader::withCount('lines')->get()->pluck('lines_count')->sort()->values()->all())->toBe([1, 2]);
});

// ── Payment callbacks are evaluated once, in the original order ──────────────

test('first-line tender and change callbacks each run exactly once; later lines once per line', function () {
    $a = coItem('Cb A', 100);
    $b = coItem('Cb B', 100);
    $c = coItem('Cb C', 100);
    $calls = [];

    app(CommitOrder::class)->execute(
        [['id' => $a->id, 'quantity' => 1], ['id' => $b->id, 'quantity' => 1], ['id' => $c->id, 'quantity' => 1]],
        'TRX-CO-CB', null, 'Out of stock: ',
        function ($first, $total) use (&$calls) { $calls[] = 'tender:'.($first ? 'first' : 'later'); return $first ? 400.0 : 0.0; },
        function ($first, $total) use (&$calls) { $calls[] = 'change:'.($first ? 'first' : 'later'); return $first ? round(400 - $total, 2) : 0.0; },
        false, 'completed'
    );

    // 3 lines => 3 tender + 3 change calls in total (NOT +2 for the header), original order.
    expect($calls)->toBe([
        'tender:first', 'change:first',
        'tender:later', 'change:later',
        'tender:later', 'change:later',
    ])
        ->and(count(array_keys($calls, 'tender:first')))->toBe(1)
        ->and(count(array_keys($calls, 'change:first')))->toBe(1)
        ->and(count($calls))->toBe(6);
});

test('a single-line transaction calls each callback exactly once', function () {
    $a = coItem('Cb Solo', 100);
    $tender = 0;
    $change = 0;

    app(CommitOrder::class)->execute(
        [['id' => $a->id, 'quantity' => 1]], 'TRX-CO-SOLO', null, 'Out of stock: ',
        function () use (&$tender) { $tender++; return 100.0; },
        function () use (&$change) { $change++; return 0.0; },
        false, 'completed'
    );

    expect($tender)->toBe(1)->and($change)->toBe(1);
});

test('header and first line always carry identical payment amounts, even with stateful callbacks', function () {
    $a = coItem('St A', 100);
    $b = coItem('St B', 100);
    $n = 0;
    $m = 0;

    app(CommitOrder::class)->execute(
        [['id' => $a->id, 'quantity' => 1], ['id' => $b->id, 'quantity' => 1]], 'TRX-CO-STATE', null, 'Out of stock: ',
        function ($first) use (&$n) { $n++; return $n * 111.0; },       // 111, 222, ... differs on every call
        function ($first) use (&$m) { $m++; return $m * 11.0; },         // 11, 22, ...
        false, 'completed'
    );

    $header = SaleHeader::first();
    $lines = Sale::orderBy('id')->get();

    // First-line values are the very first results; the second line gets the second results.
    expect((float) $header->amount_tendered)->toBe(111.0)
        ->and((float) $header->change_amount)->toBe(11.0)
        ->and((float) $lines[0]->amount_tendered)->toBe((float) $header->amount_tendered)
        ->and((float) $lines[0]->change)->toBe((float) $header->change_amount)
        ->and((float) $lines[1]->amount_tendered)->toBe(222.0)
        ->and((float) $lines[1]->change)->toBe(22.0);
});

test('POS and reservation payment values are unchanged by the single evaluation', function () {
    $a = coItem('Same A', 250);
    $b = coItem('Same B', 100);
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => 'Ed', 'email' => $user->email]);

    coPos(coOwner(), [['id' => $a->id, 'quantity' => 1], ['id' => $b->id, 'quantity' => 1]], 400);
    app(CheckoutService::class)->processTransaction([$a->id => ['quantity' => 1]], 'TRX-CO-SAME-R', $customer->id, 0);

    $pos = Sale::whereNotNull('payment_confirmed_at')->orderBy('id')->get();
    $res = Sale::where('transaction_reference', 'TRX-CO-SAME-R')->get();

    expect($pos->pluck('amount_tendered')->map(fn ($v) => (float) $v)->all())->toBe([400.0, 0.0])
        ->and($pos->pluck('change')->map(fn ($v) => (float) $v)->all())->toBe([50.0, 0.0])
        ->and((float) SaleHeader::where('transaction_type', 'pos')->first()->change_amount)->toBe(50.0)
        ->and($res->pluck('amount_tendered')->map(fn ($v) => (float) $v)->all())->toBe([0.0])
        ->and((float) SaleHeader::where('transaction_type', 'online_reservation')->first()->amount_tendered)->toBe(0.0);
});

test('a callback exception still rolls back header, lines and stock, and runs no later callbacks', function () {
    $a = coItem('Ex A', 100, 10);
    $b = coItem('Ex B', 100, 10);
    $calls = 0;

    // A regular closure (not an arrow fn) so $calls is shared by reference with the callback.
    $run = function () use (&$calls, $a, $b) {
        return app(CommitOrder::class)->execute(
            [['id' => $a->id, 'quantity' => 2], ['id' => $b->id, 'quantity' => 1]], 'TRX-CO-EX', null, 'Out of stock: ',
            function () use (&$calls) { $calls++; throw new RuntimeException('tender rejected'); },
            fn () => 0.0, false, 'completed'
        );
    };

    expect($run)->toThrow(RuntimeException::class, 'tender rejected');

    expect($calls)->toBe(1)                    // fails on the very first evaluation, before the header exists
        ->and(SaleHeader::count())->toBe(0)
        ->and(Sale::withTrashed()->count())->toBe(0)
        ->and($a->fresh()->quantity)->toBe(10)
        ->and($b->fresh()->quantity)->toBe(10);
});
