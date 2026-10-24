<?php

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\RefundRequest;
use App\Models\Sale;
use App\Models\SaleHeader;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * SYS4-06 D2C: only counter pickup of a reservation writes payment onto its header. Cancel, expiry,
 * refund and return leave the header untouched (line status is authoritative, header total is the
 * original amount). Preorder pay-then-pickup cannot be linked deterministically (no relationship).
 */

beforeEach(fn () => Notification::fake());

function lcItem(string $name, float $price, int $qty = 20): InventoryItem
{
    static $n = 0;
    $n++;

    return InventoryItem::create(['part_name' => $name, 'sku' => 'LC-'.$n, 'price' => $price, 'quantity' => $qty]);
}

function lcOwner(): User
{
    return User::query()->where('role', 'owner')->first() ?? User::factory()->create(['role' => 'owner']);
}

function lcCustomer(): Customer
{
    $u = User::factory()->create(['role' => 'customer']);

    return Customer::create(['user_id' => $u->id, 'name' => $u->name, 'email' => $u->email]);
}

/** Online reservation of 250 + 100 = 350 through the real checkout service. */
function lcReserve(string $ref = 'TRX-LC-1', ?Customer $c = null): array
{
    $c ??= lcCustomer();
    $a = lcItem('Lc A', 250);
    $b = lcItem('Lc B', 100);
    app(CheckoutService::class)->processTransaction([$a->id => ['quantity' => 1], $b->id => ['quantity' => 1]], $ref, $c->id, 0);

    return [$c, $a, $b, SaleHeader::where('transaction_reference', $ref)->sole()];
}

function lcComplete($t, string $ref, float $cash)
{
    return $t->actingAs(lcOwner())->post(route('sales.complete', $ref), ['amount_tendered' => $cash]);
}

function lcLines(string $ref)
{
    return Sale::where('transaction_reference', $ref)->orderBy('id')->get();
}

function lcHeaderSnapshot(SaleHeader $h): array
{
    return $h->fresh()->only(['transaction_reference', 'transaction_type', 'total_amount', 'amount_tendered', 'change_amount', 'reserved_at', 'expires_at', 'payment_confirmed_at', 'picked_up_at']);
}

// ── Reservation completion ───────────────────────────────────────────────────

test('paying a multi-item reservation puts one consistent payment result on the header', function () {
    [, , , $header] = lcReserve();

    expect($header->payment_confirmed_at)->toBeNull()->and($header->picked_up_at)->toBeNull()
        ->and((float) $header->total_amount)->toBe(350.0);
    $reservedAt = $header->reserved_at;
    $expiresAt = $header->expires_at;

    lcComplete($this, 'TRX-LC-1', 400)->assertSessionHas('success');

    $h = $header->fresh();
    [$l1, $l2] = lcLines('TRX-LC-1');

    expect((float) $h->amount_tendered)->toBe(400.0)->and((float) $h->change_amount)->toBe(50.0)
        ->and($h->payment_confirmed_at)->not->toBeNull()->and($h->picked_up_at)->not->toBeNull()
        ->and($h->payment_confirmed_at->equalTo($l1->payment_confirmed_at))->toBeTrue()
        ->and($h->picked_up_at->equalTo($l1->picked_up_at))->toBeTrue()
        ->and($l2->payment_confirmed_at->equalTo($h->payment_confirmed_at))->toBeTrue()
        // original facts are not rewritten
        ->and((float) $h->total_amount)->toBe(350.0)
        ->and($h->reserved_at->equalTo($reservedAt))->toBeTrue()->and($h->expires_at->equalTo($expiresAt))->toBeTrue()
        ->and($h->transaction_type)->toBe('online_reservation')
        // line semantics unchanged: tender/change on the first line only
        ->and([(float) $l1->amount_tendered, (float) $l1->change, $l1->status])->toBe([400.0, 50.0, 'completed'])
        ->and([(float) $l2->amount_tendered, (float) $l2->change, $l2->status])->toBe([0.0, 0.0, 'completed'])
        ->and(SaleHeader::count())->toBe(1);
});

test('a repeated pickup cannot overwrite payment values or create anything', function () {
    [, , , $header] = lcReserve();
    lcComplete($this, 'TRX-LC-1', 400);
    $before = lcHeaderSnapshot($header);
    $lineBefore = lcLines('TRX-LC-1')->map->only(['id', 'amount_tendered', 'change', 'status'])->all();

    lcComplete($this, 'TRX-LC-1', 999)->assertSessionHas('success');

    expect(lcHeaderSnapshot($header))->toEqual($before)
        ->and(lcLines('TRX-LC-1')->map->only(['id', 'amount_tendered', 'change', 'status'])->all())->toEqual($lineBefore)
        ->and(Sale::count())->toBe(2)->and(SaleHeader::count())->toBe(1);
});

/** Everything the completion could touch, for "nothing changed" assertions. */
function lcFullSnapshot(string $ref, array $items): array
{
    return [
        'headers' => SaleHeader::orderBy('id')->get()->map->getAttributes()->all(),
        'lines' => lcLines($ref)->map->getAttributes()->all(),
        'stock' => collect($items)->map(fn ($i) => $i->fresh()->quantity)->all(),
    ];
}

const LC_REVIEW = 'needs review';

test('a header that already carries a payment while lines are pending fails closed and changes nothing', function () {
    [, $a, $b, $header] = lcReserve();
    $header->update(['amount_tendered' => 1, 'change_amount' => 0, 'payment_confirmed_at' => now()->subDay()]);
    $before = lcFullSnapshot('TRX-LC-1', [$a, $b]);

    $res = lcComplete($this, 'TRX-LC-1', 400);

    expect(session('error'))->toContain(LC_REVIEW)
        ->and(lcFullSnapshot('TRX-LC-1', [$a, $b]))->toEqual($before)
        ->and(lcLines('TRX-LC-1')->pluck('status')->unique()->all())->toBe(['pending'])
        ->and(lcLines('TRX-LC-1')->every(fn ($l) => (float) $l->amount_tendered === 0.0))->toBeTrue();
});

test('mixed NULL and non-NULL header ids are refused with nothing changed', function () {
    [, $a, $b, $header] = lcReserve();
    DB::table('sales')->where('id', lcLines('TRX-LC-1')->last()->id)->update(['sale_header_id' => null]);
    $before = lcFullSnapshot('TRX-LC-1', [$a, $b]);

    lcComplete($this, 'TRX-LC-1', 400);

    expect(session('error'))->toContain(LC_REVIEW)->and(lcFullSnapshot('TRX-LC-1', [$a, $b]))->toEqual($before)
        ->and(SaleHeader::count())->toBe(1);
});

test('two different header ids inside one reference group are refused with nothing changed', function () {
    [, $a, $b] = lcReserve();
    $other = SaleHeader::create(['transaction_reference' => 'TRX-LC-OTHER', 'transaction_type' => 'online_reservation', 'total_amount' => 1]);
    DB::table('sales')->where('id', lcLines('TRX-LC-1')->last()->id)->update(['sale_header_id' => $other->id]);
    $before = lcFullSnapshot('TRX-LC-1', [$a, $b]);

    lcComplete($this, 'TRX-LC-1', 400);

    expect(session('error'))->toContain(LC_REVIEW)->and(lcFullSnapshot('TRX-LC-1', [$a, $b]))->toEqual($before)
        ->and($other->fresh()->payment_confirmed_at)->toBeNull();
});

test('a referenced header that cannot be found is refused with nothing changed', function () {
    [, $a, $b] = lcReserve();
    // Point every line at a header id that does not exist (SQLite only: PostgreSQL's foreign key forbids it).
    DB::statement('PRAGMA defer_foreign_keys = ON'); // checked only at commit, which the test transaction never reaches
    DB::table('sales')->where('transaction_reference', 'TRX-LC-1')->update(['sale_header_id' => 987654]);
    $before = lcFullSnapshot('TRX-LC-1', [$a, $b]);

    lcComplete($this, 'TRX-LC-1', 400);

    expect(session('error'))->toContain(LC_REVIEW)->and(lcFullSnapshot('TRX-LC-1', [$a, $b]))->toEqual($before)
        ->and(SaleHeader::where('payment_confirmed_at', '!=', null)->count())->toBe(0);
})->skip(fn () => DB::getDriverName() === 'pgsql', 'The foreign key makes an orphaned header id impossible on PostgreSQL.');

test('the header is never guessed from the transaction reference', function () {
    [, $a, $b] = lcReserve();
    // Lines lose their link; a header with the same reference still exists but must not be used.
    DB::table('sales')->where('transaction_reference', 'TRX-LC-1')->update(['sale_header_id' => null]);
    $header = SaleHeader::where('transaction_reference', 'TRX-LC-1')->sole();

    lcComplete($this, 'TRX-LC-1', 400)->assertSessionHas('success');

    expect(lcLines('TRX-LC-1')->pluck('status')->unique()->all())->toBe(['completed'])
        ->and($header->fresh()->payment_confirmed_at)->toBeNull()->and((float) $header->fresh()->amount_tendered)->toBe(0.0);
});

test('insufficient cash leaves header and lines untouched', function () {
    [, , , $header] = lcReserve();
    $before = lcHeaderSnapshot($header);

    lcComplete($this, 'TRX-LC-1', 349)->assertSessionHasErrors();

    expect(lcHeaderSnapshot($header))->toEqual($before)
        ->and(lcLines('TRX-LC-1')->pluck('status')->unique()->all())->toBe(['pending']);
});

test('legacy reservations without a header still complete exactly as before', function () {
    $c = lcCustomer();
    $i = lcItem('Legacy', 100);
    foreach ([1, 2] as $q) {
        Sale::create(['inventory_item_id' => $i->id, 'customer_id' => $c->id, 'quantity' => $q, 'price' => 100, 'total_price' => 100 * $q,
            'transaction_reference' => 'TRX-LC-LEG', 'status' => 'pending', 'amount_tendered' => 0, 'change' => 0,
            'reserved_at' => now(), 'expires_at' => now()->addDay()]);
    }

    lcComplete($this, 'TRX-LC-LEG', 400)->assertSessionHas('success');

    [$l1, $l2] = lcLines('TRX-LC-LEG');
    expect([(float) $l1->amount_tendered, (float) $l1->change, $l1->status])->toBe([400.0, 100.0, 'completed'])
        ->and([(float) $l2->amount_tendered, $l2->status])->toBe([0.0, 'completed'])
        ->and(SaleHeader::count())->toBe(0);
});

test('a failure while updating the header rolls back every line update too', function () {
    [, , , $header] = lcReserve();
    $before = lcHeaderSnapshot($header);
    if (DB::getDriverName() === 'pgsql') {
        DB::unprepared("CREATE FUNCTION lc_fail() RETURNS trigger AS \$\$ BEGIN RAISE EXCEPTION 'injected failure'; END; \$\$ LANGUAGE plpgsql");
        DB::unprepared('CREATE TRIGGER lc_fail BEFORE UPDATE ON sale_headers FOR EACH ROW EXECUTE FUNCTION lc_fail()');
    } else {
        DB::unprepared("CREATE TRIGGER lc_fail BEFORE UPDATE ON sale_headers BEGIN SELECT RAISE(ABORT, 'injected failure'); END");
    }

    $this->withoutExceptionHandling();
    expect(fn () => lcComplete($this, 'TRX-LC-1', 400))->toThrow(Illuminate\Database\QueryException::class, 'injected failure');

    expect(lcHeaderSnapshot($header))->toEqual($before)
        ->and(lcLines('TRX-LC-1')->pluck('status')->unique()->all())->toBe(['pending'])
        ->and(lcLines('TRX-LC-1')->every(fn ($l) => $l->payment_confirmed_at === null && (float) $l->amount_tendered === 0.0))->toBeTrue();
});

test('tender placed on a higher-id line (historical shape) is still the receipt payment line and the header is untouched', function () {
    [, , , $header] = lcReserve();
    lcComplete($this, 'TRX-LC-1', 400);
    [$l1, $l2] = lcLines('TRX-LC-1');
    // Reproduce historical data: tender/change on the higher-id line.
    $l1->forceFill(['amount_tendered' => 0, 'change' => 0])->saveQuietly();
    $l2->forceFill(['amount_tendered' => 400, 'change' => 50])->saveQuietly();
    $before = lcHeaderSnapshot($header);

    expect(Sale::paymentLine(lcLines('TRX-LC-1'))->id)->toBe($l2->id);

    $this->actingAs(lcOwner())->get(route('sales.receipt', 'TRX-LC-1'))->assertOk()->assertSee('400.00')->assertSee('50.00');
    expect(lcHeaderSnapshot($header))->toEqual($before)->and((float) $header->fresh()->amount_tendered)->toBe(400.0);
});

test('only an owner can complete a reservation and a rejected request changes nothing', function () {
    [$c, , , $header] = lcReserve();
    $before = lcHeaderSnapshot($header);

    $res = $this->actingAs($c->user)->post(route('sales.complete', 'TRX-LC-1'), ['amount_tendered' => 400]);

    expect($res->status())->not->toBe(200)->and(lcHeaderSnapshot($header))->toEqual($before)
        ->and(lcLines('TRX-LC-1')->pluck('status')->unique()->all())->toBe(['pending']);
});

test('HTML and PDF receipts stay correct after a header-synced pickup', function () {
    [, , , $header] = lcReserve();
    lcComplete($this, 'TRX-LC-1', 400);

    $this->actingAs(lcOwner())->get(route('sales.receipt', 'TRX-LC-1'))->assertOk()->assertSee('350.00')->assertSee('400.00')->assertSee('50.00');

    $pdf = $this->actingAs(lcOwner())->get(route('sales.download', 'TRX-LC-1'));
    $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(substr($pdf->getContent(), 0, 5))->toBe('%PDF-');
});

// ── Cancellation and expiry: no header mutation needed ───────────────────────

test('customer cancellation keeps the header exactly as created and restores stock', function () {
    [$c, $a, $b, $header] = lcReserve();
    $before = lcHeaderSnapshot($header);
    $line = lcLines('TRX-LC-1')->first();

    expect(app(ReservationService::class)->releaseGroup($line, 'Changed my mind', OrderStatus::SALE_CANCELLED, $c->id))->toBeTrue();

    expect(lcHeaderSnapshot($header))->toEqual($before)
        ->and($before['payment_confirmed_at'])->toBeNull()->and((float) $before['amount_tendered'])->toBe(0.0)
        ->and(lcLines('TRX-LC-1')->pluck('status')->unique()->all())->toBe(['cancelled'])
        ->and($a->fresh()->quantity)->toBe(20)->and($b->fresh()->quantity)->toBe(20)
        ->and(SaleHeader::count())->toBe(1);
});

test('the expiry sweep keeps the header exactly as created', function () {
    [, $a, , $header] = lcReserve();
    $before = lcHeaderSnapshot($header);
    Sale::where('transaction_reference', 'TRX-LC-1')->update(['expires_at' => now()->subMinute()]);

    $this->artisan('reservations:expire')->assertSuccessful();

    expect(lcHeaderSnapshot($header))->toEqual($before)
        ->and(lcLines('TRX-LC-1')->pluck('status')->unique()->all())->toBe(['expired'])
        ->and($a->fresh()->quantity)->toBe(20);
});

test('a cancelled or expired reservation can no longer be completed or paid onto the header', function () {
    [$c, , , $header] = lcReserve();
    app(ReservationService::class)->releaseGroup(lcLines('TRX-LC-1')->first(), 'x', OrderStatus::SALE_CANCELLED, $c->id);
    $before = lcHeaderSnapshot($header);

    lcComplete($this, 'TRX-LC-1', 400)->assertSessionHas('error');

    expect(lcHeaderSnapshot($header))->toEqual($before);
});

// ── Refunds and returns: no header mutation, original totals preserved ───────

test('refund approval and rejection never touch the header or its payment record', function () {
    [$c, , , $header] = lcReserve();
    lcComplete($this, 'TRX-LC-1', 400);
    $before = lcHeaderSnapshot($header);
    [$l1, $l2] = lcLines('TRX-LC-1');

    $r1 = RefundRequest::create(['sale_id' => $l1->id, 'customer_id' => $c->id, 'reason' => 'Defective', 'status' => OrderStatus::REFUND_PENDING, 'refund_method' => 'on_site']);
    $l1->forceFill(['status' => OrderStatus::SALE_REFUND_REQUESTED])->saveQuietly();
    $r2 = RefundRequest::create(['sale_id' => $l2->id, 'customer_id' => $c->id, 'reason' => 'Wrong part', 'status' => OrderStatus::REFUND_PENDING, 'refund_method' => 'on_site']);
    $l2->forceFill(['status' => OrderStatus::SALE_REFUND_REQUESTED])->saveQuietly();

    $this->actingAs(lcOwner())->post(route('admin.refund.approve', $r1->id), ['admin_notes' => 'ok', 'restock' => 1])->assertSessionHas('success');
    $this->actingAs(lcOwner())->post(route('admin.refund.reject', $r2->id), ['admin_notes' => 'no'])->assertSessionHas('success');

    expect(lcHeaderSnapshot($header))->toEqual($before)
        ->and((float) $header->fresh()->total_amount)->toBe(350.0)->and((float) $header->fresh()->amount_tendered)->toBe(400.0)
        ->and($l1->fresh()->status)->toBe('refunded')->and($l2->fresh()->status)->toBe('completed');
});

test('a counter return never touches the header of a POS sale', function () {
    $a = lcItem('Ret A', 250);
    $b = lcItem('Ret B', 100);
    $this->actingAs(lcOwner())->post('/sales/store', [
        'cart_data' => json_encode([['id' => $a->id, 'quantity' => 1], ['id' => $b->id, 'quantity' => 1]]),
        'amount_tendered' => 400,
    ]);
    $header = SaleHeader::sole();
    $before = lcHeaderSnapshot($header);
    $line = Sale::where('inventory_item_id', $a->id)->first();

    $this->actingAs(lcOwner())->post(route('inventory.adjustments.store_return'), [
        'sale_id' => $line->id, 'inventory_item_id' => $a->id, 'quantity' => 1, 'refund_amount' => 250, 'action_taken' => 'restocked',
    ])->assertSessionHasNoErrors();

    expect($line->fresh()->status)->toBe('refunded')
        ->and(lcHeaderSnapshot($header))->toEqual($before)
        ->and((float) $header->fresh()->total_amount)->toBe(350.0)->and((float) $header->fresh()->amount_tendered)->toBe(400.0);
});

// ── Pre-order pay-then-pickup: documented blocker, behavior unchanged ────────

test('a preorder paid earlier cannot be linked to its sale, so completing it leaves header and sale unchanged', function () {
    $c = lcCustomer();
    $item = lcItem('Pre', 500, 3);
    $o = PreOrder::create([
        'customer_id' => $c->id, 'inventory_item_id' => $item->id, 'customer_name' => $c->name, 'part_name' => 'Pre', 'quantity' => 2,
        'price' => 500, 'total' => 1000, 'status' => 'pending', 'is_paid' => false, 'is_stocked' => true,
        'sourced_at' => now()->subDay(), 'ready_notified_at' => now()->subHour(), 'pickup_expires_at' => now()->addHours(23),
    ]);

    $this->actingAs(lcOwner())->post(route('preorders.pay', $o), ['amount_tendered' => 1000]);
    $header = SaleHeader::sole();
    $before = lcHeaderSnapshot($header);
    $saleBefore = Sale::sole()->only(['picked_up_at', 'amount_tendered', 'status']);

    $this->actingAs(lcOwner())->post(route('preorders.complete', $o))->assertSessionHas('success');

    expect($o->fresh()->status)->toBe('completed')->and($o->fresh()->picked_up_at)->not->toBeNull()
        ->and(Schema::hasColumn('pre_orders', 'sale_header_id'))->toBeFalse() // the missing relationship
        ->and(lcHeaderSnapshot($header))->toEqual($before)
        ->and(Sale::sole()->only(['picked_up_at', 'amount_tendered', 'status']))->toEqual($saleBefore)
        ->and(SaleHeader::count())->toBe(1)->and(Sale::count())->toBe(1);
});
