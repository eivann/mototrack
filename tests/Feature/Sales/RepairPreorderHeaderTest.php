<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\SaleHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Str::createRandomStringsNormally();
});

afterEach(fn () => Str::createRandomStringsNormally());

function rhCustomer(): Customer
{
    $user = User::factory()->create(['role' => 'customer']);

    return Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
}

function rhOwner(): User
{
    return User::query()->where('role', 'owner')->first() ?? User::factory()->create(['role' => 'owner']);
}

function rhRepair(float $cost = 800): Repair
{
    $c = rhCustomer();

    return Repair::create(['customer_name' => $c->name, 'customer_id' => $c->id, 'bike_model' => 'Click', 'issue' => 'Brakes', 'status' => 'completed', 'cost' => $cost, 'is_paid' => false]);
}

function rhPreorder(array $o = []): PreOrder
{
    $c = rhCustomer();
    $item = InventoryItem::create(['part_name' => 'Pre Part', 'sku' => 'RH'.uniqid(), 'price' => 500, 'quantity' => 3]);

    return PreOrder::create(array_merge([
        'customer_id' => $c->id, 'inventory_item_id' => $item->id, 'customer_name' => $c->name,
        'part_name' => 'Pre Part', 'quantity' => 2, 'price' => 500, 'total' => 1000,
        'status' => 'pending', 'is_paid' => false, 'is_stocked' => true,
        'sourced_at' => now()->subDay(), 'ready_notified_at' => now()->subHour(), 'pickup_expires_at' => now()->addHours(23),
    ], $o));
}

function rhPayRepair($t, Repair $r, float $cash)
{
    return $t->actingAs(rhOwner())->put(route('repairs.pay', $r), ['amount_tendered' => $cash]);
}

function rhFailSaleInserts(): void
{
    if (DB::getDriverName() === 'pgsql') {
        DB::unprepared("CREATE FUNCTION rh_fail() RETURNS trigger AS \$\$ BEGIN RAISE EXCEPTION 'injected failure'; END; \$\$ LANGUAGE plpgsql");
        DB::unprepared('CREATE TRIGGER rh_fail BEFORE INSERT ON sales FOR EACH ROW EXECUTE FUNCTION rh_fail()');

        return;
    }

    DB::unprepared("CREATE TRIGGER rh_fail BEFORE INSERT ON sales BEGIN SELECT RAISE(ABORT, 'injected failure'); END");
}

function rhAssertLinked(string $type, Sale $sale, float $total, float $tender, float $change): SaleHeader
{
    $header = SaleHeader::where('transaction_reference', $sale->transaction_reference)->sole();

    expect($sale->sale_header_id)->toBe($header->id)
        ->and($header->transaction_type)->toBe($type)
        ->and((float) $header->total_amount)->toBe($total)
        ->and((float) $header->amount_tendered)->toBe($tender)
        ->and((float) $header->change_amount)->toBe($change)
        ->and((float) $sale->amount_tendered)->toBe($tender)
        ->and((float) $sale->change)->toBe($change)
        ->and($header->customer_id)->toBe($sale->customer_id)
        ->and($header->payment_confirmed_at->equalTo($sale->payment_confirmed_at))->toBeTrue()
        ->and($header->reserved_at)->toBeNull()
        ->and($header->expires_at)->toBeNull();

    return $header;
}

// ── Repair ───────────────────────────────────────────────────────────────────

test('repair payment creates one repair header and one linked sale', function () {
    $r = rhRepair(800);

    rhPayRepair($this, $r, 1000)->assertSessionHas('success');

    $sale = Sale::sole();
    $header = rhAssertLinked('repair', $sale, 800.0, 1000.0, 200.0);
    expect($sale->transaction_reference)->toStartWith('REPAIR-')->and(strlen($sale->transaction_reference))->toBe(15)
        ->and($sale->inventory_item_id)->toBeNull()->and($sale->customer_id)->toBe($r->customer_id)
        ->and($sale->status)->toBe('completed')->and($sale->quantity)->toBe(1)
        ->and($header->picked_up_at)->toBeNull()
        ->and((bool) $r->fresh()->is_paid)->toBeTrue()
        ->and(SaleHeader::count())->toBe(1);
});

test('repeat repair payment creates no second sale or header', function () {
    $r = rhRepair(800);
    rhPayRepair($this, $r, 800);
    rhPayRepair($this, $r, 800)->assertSessionHas('error');

    expect(Sale::count())->toBe(1)->and(SaleHeader::count())->toBe(1);
});

test('insufficient repair cash records nothing', function () {
    $r = rhRepair(800);

    rhPayRepair($this, $r, 799)->assertSessionHas('error');

    expect(Sale::count())->toBe(0)->and(SaleHeader::count())->toBe(0)->and((bool) $r->fresh()->is_paid)->toBeFalse();
});

test('repair failure after the header insert rolls back header, sale and paid flag', function () {
    $r = rhRepair(800);
    rhFailSaleInserts();

    rhPayRepair($this, $r, 800)->assertSessionHas('error');

    expect(SaleHeader::count())->toBe(0)->and(Sale::withTrashed()->count())->toBe(0)->and((bool) $r->fresh()->is_paid)->toBeFalse();
});

test('repair route stays owner-only', function () {
    $r = rhRepair(800);
    $staff = User::factory()->create(['role' => 'customer']);

    $this->actingAs($staff)->put(route('repairs.pay', $r), ['amount_tendered' => 800])->assertForbidden();

    expect(Sale::count())->toBe(0)->and(SaleHeader::count())->toBe(0);
});

// ── Pre-order pay ────────────────────────────────────────────────────────────

test('preorder payment creates one preorder header and one linked sale', function () {
    $o = rhPreorder();

    $this->actingAs(rhOwner())->post(route('preorders.pay', $o), ['amount_tendered' => 1200])->assertSessionHas('success');

    $sale = Sale::sole();
    $header = rhAssertLinked('preorder', $sale, 1000.0, 1200.0, 200.0);
    expect($sale->transaction_reference)->toStartWith('PRE-')->and(strlen($sale->transaction_reference))->toBe(12)
        ->and($sale->inventory_item_id)->toBe($o->inventory_item_id)->and($sale->quantity)->toBe(2)
        ->and($sale->picked_up_at)->toBeNull()->and($header->picked_up_at)->toBeNull()
        ->and($o->fresh()->is_paid)->toBeTrue()->and($o->fresh()->status)->toBe('pending')
        ->and($o->fresh()->inventoryItem->quantity)->toBe(3);
});

test('repeat preorder payment and insufficient cash create nothing extra', function () {
    $o = rhPreorder();
    $o2 = rhPreorder();

    $this->actingAs(rhOwner())->post(route('preorders.pay', $o2), ['amount_tendered' => 999])->assertSessionHas('error');
    expect(Sale::count())->toBe(0)->and(SaleHeader::count())->toBe(0);

    $this->actingAs(rhOwner())->post(route('preorders.pay', $o), ['amount_tendered' => 1000]);
    $this->actingAs(rhOwner())->post(route('preorders.pay', $o), ['amount_tendered' => 1000])->assertSessionHas('error');

    expect(Sale::count())->toBe(1)->and(SaleHeader::count())->toBe(1);
});

test('preorder payment failure rolls back header, sale and paid flag', function () {
    $o = rhPreorder();
    rhFailSaleInserts();

    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs(rhOwner())->post(route('preorders.pay', $o), ['amount_tendered' => 1000]))
        ->toThrow(Illuminate\Database\QueryException::class, 'injected failure');

    expect(SaleHeader::count())->toBe(0)->and(Sale::withTrashed()->count())->toBe(0)->and($o->fresh()->is_paid)->toBeFalse();
});

// ── Pre-order pickup ─────────────────────────────────────────────────────────

test('unpaid preorder pickup creates one header and one linked sale with pickup time', function () {
    $o = rhPreorder();

    $this->actingAs(rhOwner())->post(route('preorders.pickup', $o), ['amount_tendered' => 1200])->assertSessionHas('success');

    $sale = Sale::sole();
    $header = rhAssertLinked('preorder', $sale, 1000.0, 1200.0, 200.0);
    expect($sale->picked_up_at)->not->toBeNull()->and($header->picked_up_at->equalTo($sale->picked_up_at))->toBeTrue()
        ->and($o->fresh()->status)->toBe('completed')->and($o->fresh()->is_paid)->toBeTrue()
        ->and($o->fresh()->inventoryItem->quantity)->toBe(3);
});

test('already-paid legacy preorder pickup creates no sale and no header', function () {
    $o = rhPreorder(['is_paid' => true]);

    $this->actingAs(rhOwner())->post(route('preorders.pickup', $o), ['amount_tendered' => 0])->assertSessionHas('success');

    expect(Sale::count())->toBe(0)->and(SaleHeader::count())->toBe(0)->and($o->fresh()->status)->toBe('completed');
});

test('repeat pickup and short cash create nothing extra', function () {
    $o = rhPreorder();
    $short = rhPreorder();

    $this->actingAs(rhOwner())->post(route('preorders.pickup', $short), ['amount_tendered' => 999])->assertSessionHas('error');
    expect(SaleHeader::count())->toBe(0);

    $this->actingAs(rhOwner())->post(route('preorders.pickup', $o), ['amount_tendered' => 1000]);
    $this->actingAs(rhOwner())->post(route('preorders.pickup', $o), ['amount_tendered' => 1000])->assertSessionHas('warning');

    expect(Sale::count())->toBe(1)->and(SaleHeader::count())->toBe(1);
});

test('pickup failure rolls back header, sale, paid flag and completion', function () {
    $o = rhPreorder();
    rhFailSaleInserts();

    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs(rhOwner())->post(route('preorders.pickup', $o), ['amount_tendered' => 1000]))
        ->toThrow(Illuminate\Database\QueryException::class, 'injected failure');

    $fresh = $o->fresh();
    expect(SaleHeader::count())->toBe(0)->and(Sale::withTrashed()->count())->toBe(0)
        ->and($fresh->is_paid)->toBeFalse()->and($fresh->status)->toBe('pending')->and($fresh->picked_up_at)->toBeNull();
});

// ── Reference collisions ─────────────────────────────────────────────────────

test('a colliding header or header-less legacy sale (even soft-deleted) forces regeneration', function () {
    SaleHeader::create(['transaction_reference' => 'REPAIR-AAAAAAAA', 'transaction_type' => 'repair', 'total_amount' => 1]);
    $legacy = Sale::create(['inventory_item_id' => null, 'quantity' => 1, 'price' => 1, 'total_price' => 1, 'status' => 'completed', 'transaction_reference' => 'REPAIR-BBBBBBBB']);
    $legacy->delete(); // soft-deleted, no header

    $r = rhRepair(800);
    rhOwner(); // factories also use Str::random, so create everything before pinning the sequence

    $seq = ['AAAAAAAA', 'BBBBBBBB', 'CCCCCCCC'];
    Str::createRandomStringsUsing(function () use (&$seq) { return array_shift($seq); });
    rhPayRepair($this, $r, 800)->assertSessionHas('success');

    $new = Sale::where('transaction_reference', 'REPAIR-CCCCCCCC')->sole();
    expect($new->sale_header_id)->not->toBeNull()->and(SaleHeader::count())->toBe(2)
        ->and(Sale::withTrashed()->whereNull('sale_header_id')->count())->toBe(1);
});

test('exhausting bounded regeneration fails cleanly with no writes', function () {
    SaleHeader::create(['transaction_reference' => 'PRE-ZZZZZZZZ', 'transaction_type' => 'preorder', 'total_amount' => 1]);
    $o = rhPreorder();
    rhOwner();
    Str::createRandomStringsUsing(fn () => 'ZZZZZZZZ');

    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs(rhOwner())->post(route('preorders.pickup', $o), ['amount_tendered' => 1000]))
        ->toThrow(RuntimeException::class, 'unique');

    expect(SaleHeader::count())->toBe(1)->and(Sale::count())->toBe(0)
        ->and($o->fresh()->status)->toBe('pending')->and($o->fresh()->is_paid)->toBeFalse();
});

test('the database unique index is the final safeguard against a duplicate header reference', function () {
    SaleHeader::create(['transaction_reference' => 'PRE-DUPLICATE', 'transaction_type' => 'preorder', 'total_amount' => 1]);

    expect(fn () => SaleHeader::create(['transaction_reference' => 'PRE-DUPLICATE', 'transaction_type' => 'preorder', 'total_amount' => 1]))
        ->toThrow(Illuminate\Database\QueryException::class);
});

// ── Action-owned fields cannot be overridden by the caller's $line ───────────

function rhConflictingLine(InventoryItem $item, Customer $c): array
{
    return [
        // ordinary attributes that must be preserved
        'inventory_item_id' => $item->id, 'quantity' => 3, 'price' => 100, 'total_price' => 300, 'customer_id' => $c->id,
        // authoritative attributes the caller tries to override
        'sale_header_id' => 987654, 'transaction_reference' => 'EVIL-REF', 'amount_tendered' => 1, 'change' => 99,
        'status' => 'pending', 'payment_confirmed_at' => '2001-01-01 00:00:00', 'picked_up_at' => '2002-02-02 00:00:00',
    ];
}

function rhRunAction(array $line, float $tendered, bool $pickedUp): Sale
{
    return DB::transaction(fn () => app(App\Actions\RecordHeaderedSale::class)
        ->execute(SaleHeader::TYPE_PREORDER, 'PRE-', $line, $tendered, $pickedUp));
}

test('conflicting $line keys never override the action-generated link, reference, payment fields', function () {
    $c = rhCustomer();
    $item = InventoryItem::create(['part_name' => 'Conf', 'sku' => 'CF'.uniqid(), 'price' => 100, 'quantity' => 5]);

    $sale = rhRunAction(rhConflictingLine($item, $c), 500, false)->fresh();
    $header = SaleHeader::sole();

    expect($sale->sale_header_id)->toBe($header->id)->and($header->id)->not->toBe(987654)
        ->and($sale->transaction_reference)->toBe($header->transaction_reference)
        ->and($sale->transaction_reference)->not->toBe('EVIL-REF')->and($sale->transaction_reference)->toStartWith('PRE-')
        ->and((float) $sale->amount_tendered)->toBe(500.0)->and((float) $sale->change)->toBe(200.0)
        ->and((float) $header->amount_tendered)->toBe(500.0)->and((float) $header->change_amount)->toBe(200.0)
        ->and($sale->status)->toBe('completed')
        ->and($sale->payment_confirmed_at->year)->toBe(now()->year)
        ->and($sale->payment_confirmed_at->equalTo($header->payment_confirmed_at))->toBeTrue()
        ->and($sale->picked_up_at)->toBeNull()->and($header->picked_up_at)->toBeNull();
});

test('pickup time follows the $pickedUp argument, not a caller-supplied value', function () {
    $c = rhCustomer();
    $item = InventoryItem::create(['part_name' => 'Conf2', 'sku' => 'CF'.uniqid(), 'price' => 100, 'quantity' => 5]);

    $sale = rhRunAction(rhConflictingLine($item, $c), 300, true)->fresh();
    $header = SaleHeader::sole();

    expect($sale->picked_up_at->year)->toBe(now()->year)
        ->and($sale->picked_up_at->equalTo($header->picked_up_at))->toBeTrue()
        ->and($sale->picked_up_at->equalTo($sale->payment_confirmed_at))->toBeTrue();
});

test('ordinary caller attributes are preserved', function () {
    $c = rhCustomer();
    $item = InventoryItem::create(['part_name' => 'Conf3', 'sku' => 'CF'.uniqid(), 'price' => 100, 'quantity' => 5]);

    $sale = rhRunAction(rhConflictingLine($item, $c), 300, false)->fresh();
    $header = SaleHeader::sole();

    expect($sale->inventory_item_id)->toBe($item->id)->and($sale->quantity)->toBe(3)
        ->and((float) $sale->price)->toBe(100.0)->and((float) $sale->total_price)->toBe(300.0)
        ->and($sale->customer_id)->toBe($c->id)->and($header->customer_id)->toBe($c->id)
        ->and((float) $header->total_amount)->toBe(300.0)->and((float) $sale->change)->toBe(0.0);
});

test('an insert failure with conflicting keys still rolls back header, sale and caller state', function () {
    $c = rhCustomer();
    $item = InventoryItem::create(['part_name' => 'Conf4', 'sku' => 'CF'.uniqid(), 'price' => 100, 'quantity' => 5]);
    rhFailSaleInserts();

    $run = fn () => DB::transaction(function () use ($item, $c) {
        $item->update(['quantity' => 1]); // stand-in for the caller's own state change in the same transaction
        app(App\Actions\RecordHeaderedSale::class)->execute(SaleHeader::TYPE_PREORDER, 'PRE-', rhConflictingLine($item, $c), 300, true);
    });

    expect($run)->toThrow(Illuminate\Database\QueryException::class, 'injected failure');

    expect(SaleHeader::count())->toBe(0)->and(Sale::withTrashed()->count())->toBe(0)->and($item->fresh()->quantity)->toBe(5);
});
