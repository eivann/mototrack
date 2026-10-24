<?php

use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\SaleHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;

uses(RefreshDatabase::class);

/**
 * POS "New Sale" screen: the amounts it shows come from public/js/pos-totals.js and must agree with the
 * receipt the server produces. The page payload stays small, and a price that changed after the screen
 * was opened is refused instead of charging an amount the cashier never saw.
 */

beforeEach(fn () => Notification::fake());

function ptOwner(): User
{
    return User::query()->where('role', 'owner')->first() ?? User::factory()->create(['role' => 'owner']);
}

function ptItem(string $name, string $price, int $qty = 50): InventoryItem
{
    static $n = 0;
    $n++;

    return InventoryItem::create(['part_name' => $name, 'sku' => 'PT-'.$n, 'price' => $price, 'quantity' => $qty]);
}

function ptPost(array $cart, string $tendered, ?string $expected = null)
{
    $payload = ['cart_data' => json_encode($cart), 'amount_tendered' => $tendered];
    if ($expected !== null) {
        $payload['expected_total'] = $expected;
    }

    return test()->actingAs(ptOwner())->post('/sales/store', $payload);
}

/** Node.js executable, found the same way on Windows (node.exe via PATHEXT) and Unix; null if absent. */
function ptNodeBinary(): ?string
{
    return (new ExecutableFinder())->find('node');
}

/**
 * Runs the browser calculation (the exact file the POS page loads) under Node, if Node is installed.
 * Arguments are passed as an array and the data via stdin, so nothing goes through a shell.
 */
function ptJsTotals(array $lines, string $tendered): ?array
{
    $node = ptNodeBinary();
    if ($node === null) {
        return null;
    }
    $result = Process::input(json_encode(['lines' => $lines, 'tendered' => $tendered]))
        ->run([$node, base_path('tests/js/pos-compute-cli.cjs')]);
    expect($result->successful())->toBeTrue($result->errorOutput());

    return json_decode($result->output(), true);
}

test('the POS page ships only the fields it uses, prices as 2-decimal strings, and loads the shared calculator', function () {
    ptItem('Brake Pad', '250');
    InventoryItem::create(['part_name' => 'Pictured Part', 'sku' => 'PT-IMG', 'price' => 99.5, 'quantity' => 3, 'image_path' => 'products/x.jpg']);

    $response = $this->actingAs(ptOwner())->get('/sales')->assertOk();

    $items = $response->viewData('items');
    expect($items->first())->toHaveKeys(['id', 'sku', 'part_name', 'brand', 'model', 'category', 'price', 'quantity'])
        ->and($items->first())->not->toHaveKey('image_url')
        ->and($items->firstWhere('part_name', 'Pictured Part')['price'])->toBe('99.50')
        ->and($response->viewData('restoreDraft'))->toBeFalse();
    $response->assertDontSee('data:image\/svg+xml', false)
        ->assertDontSee('image_url', false)
        ->assertSee('js/pos-totals.js', false)
        ->assertSee('name="expected_total"', false);
});

test('the amounts the POS shows equal the receipt for multi-item carts with awkward prices', function () {
    $a = ptItem('Tiny A', '0.10');
    $b = ptItem('Tiny B', '0.20');
    $c = ptItem('Clutch', '1250.50');
    $d = ptItem('Oil', '99.99');

    $cases = [
        [[[$a, 1], [$b, 1]], '1'],
        [[[$c, 3], [$d, 7]], '5000'],
        [[[$d, 1]], '99.99'],
    ];

    foreach ($cases as [$lines, $tendered]) {
        $jsLines = array_map(fn ($l) => ['price' => number_format((float) $l[0]->price, 2, '.', ''), 'quantity' => $l[1]], $lines);
        $js = ptJsTotals($jsLines, $tendered);
        if ($js === null) {
            $this->markTestSkipped('Node.js is not installed; the browser calculation cannot be executed here.');
        }
        expect($js['canCheckout'])->toBeTrue();

        $before = SaleHeader::count();
        ptPost(array_map(fn ($l) => ['id' => $l[0]->id, 'quantity' => $l[1]], $lines), $js['tendered'], $js['expected'])
            ->assertRedirect();
        expect(SaleHeader::count())->toBe($before + 1);

        $header = SaleHeader::latest('id')->first();
        $receiptTotal = (int) round(Sale::where('sale_header_id', $header->id)->sum('total_price') * 100);
        expect($receiptTotal)->toBe($js['total'])
            ->and((int) round((float) $header->total_amount * 100))->toBe($js['total'])
            ->and((int) round((float) $header->change_amount * 100))->toBe($js['change']);

        // The rendered receipt prints the same figures the POS displayed.
        $this->actingAs(ptOwner())->get(route('sales.receipt', $header->transaction_reference))->assertOk()
            ->assertSee('₱'.number_format($js['total'] / 100, 2))
            ->assertSee('₱'.number_format($js['change'] / 100, 2));
    }
});

test('a price change after the POS was opened is refused with nothing written, and the cart is restored', function () {
    $item = ptItem('Chain', '300.00', 10);
    $item->update(['price' => 320]); // changed by someone else after the cashier loaded the screen

    ptPost([['id' => $item->id, 'quantity' => 2]], '1000', '600.00')
        ->assertRedirect()
        ->assertSessionHas('error', fn ($m) => str_contains($m, 'price changed'));

    expect(Sale::count())->toBe(0)
        ->and(SaleHeader::count())->toBe(0)
        ->and($item->fresh()->quantity)->toBe(10);

    // The next POS load asks the page to restore the cashier's cart draft (re-priced client-side).
    expect($this->actingAs(ptOwner())->get('/sales')->viewData('restoreDraft'))->toBeTrue();
});

test('a matching expected total is accepted and the submission without it keeps working', function () {
    $item = ptItem('Spark Plug', '120.00', 10);

    ptPost([['id' => $item->id, 'quantity' => 2]], '300.00', '240.00')->assertRedirect();
    ptPost([['id' => $item->id, 'quantity' => 1]], '200')->assertRedirect();

    expect(SaleHeader::count())->toBe(2)
        ->and((float) SaleHeader::orderBy('id')->first()->change_amount)->toBe(60.0)
        ->and($item->fresh()->quantity)->toBe(7);
});

test('insufficient cash and malformed amounts are still refused before anything is written', function () {
    $item = ptItem('Mirror', '150.00', 5);

    ptPost([['id' => $item->id, 'quantity' => 1]], '149.99', '150.00')->assertSessionHas('error');
    ptPost([['id' => $item->id, 'quantity' => 1]], '200', '150.001')->assertSessionHasErrors('expected_total');
    ptPost([['id' => $item->id, 'quantity' => 1]], '200.005', '150.00')->assertSessionHasErrors('amount_tendered');

    expect(Sale::count())->toBe(0)->and(SaleHeader::count())->toBe(0)->and($item->fresh()->quantity)->toBe(5);
});

test('a price that changes between the check and the stock lock is still refused inside the transaction', function () {
    $item = ptItem('Grip', '80.00', 10);
    // The pre-check reads plain prices; the locked read inside CommitOrder sees a newer price.
    InventoryItem::retrieved(function (InventoryItem $model) {
        $model->price = '95.00';
    });

    ptPost([['id' => $item->id, 'quantity' => 1]], '100.00', '80.00')->assertSessionHas('error');

    expect(Sale::count())->toBe(0)->and(SaleHeader::count())->toBe(0)
        ->and((int) InventoryItem::query()->toBase()->where('id', $item->id)->value('quantity'))->toBe(10);
});

test('the POS calculator caps a line at min(stock, 9,999) like the server, and the server agrees', function () {
    $bulk = ptItem('Bulk Washer', '1.00', 12000);

    $over = ptJsTotals([['price' => '1.00', 'quantity' => 10000, 'maxStock' => 12000]], '20000');
    $max = ptJsTotals([['price' => '1.00', 'quantity' => 9999, 'maxStock' => 12000]], '20000');
    if ($over === null) {
        $this->markTestSkipped('Node.js is not installed; the browser calculation cannot be executed here.');
    }
    expect($over['canCheckout'])->toBeFalse()->and($over['problem'])->toBe('quantity')
        ->and($max['canCheckout'])->toBeTrue();

    // The server refuses 10,000 units of one item even with stock available, and accepts 9,999.
    ptPost([['id' => $bulk->id, 'quantity' => 10000]], '20000', '10000.00')->assertSessionHas('error');
    expect(SaleHeader::count())->toBe(0)->and($bulk->fresh()->quantity)->toBe(12000);

    ptPost([['id' => $bulk->id, 'quantity' => 9999]], '20000', $max['expected'])->assertRedirect();
    expect(SaleHeader::count())->toBe(1)->and($bulk->fresh()->quantity)->toBe(2001);
});

test('offsetting price changes that keep the total equal are refused by the per-item price snapshot', function () {
    $a = ptItem('Lever', '100.00', 10);
    $b = ptItem('Cable', '200.00', 10);
    // Prices changed after the POS loaded: +20 and -20, so the grand total is still 300.00.
    $a->update(['price' => 120]);
    $b->update(['price' => 180]);

    ptPost([['id' => $a->id, 'quantity' => 1, 'price' => '100.00'], ['id' => $b->id, 'quantity' => 1, 'price' => '200.00']], '300.00', '300.00')
        ->assertSessionHas('error', fn ($m) => str_contains($m, 'price changed'));
    expect(Sale::count())->toBe(0)->and(SaleHeader::count())->toBe(0)
        ->and($a->fresh()->quantity)->toBe(10)->and($b->fresh()->quantity)->toBe(10);

    // With the current prices shown, the same cart goes through and the receipt lines match them.
    ptPost([['id' => $a->id, 'quantity' => 1, 'price' => '120.00'], ['id' => $b->id, 'quantity' => 1, 'price' => '180.00']], '300.00', '300.00')
        ->assertRedirect();
    expect(Sale::orderBy('id')->pluck('price')->map(fn ($p) => (float) $p)->all())->toBe([120.0, 180.0]);
});

test('offsetting changes landing between the pre-check and the stock lock are refused inside the transaction', function () {
    $a = ptItem('Pedal', '100.00', 10);
    $b = ptItem('Spring', '200.00', 10);
    // Simulates another cashier changing both prices at the moment CommitOrder locks the rows: the
    // locked reads see the new values while the pre-check (plain query) saw the old ones.
    InventoryItem::retrieved(function (InventoryItem $model) use ($a, $b) {
        $new = [$a->id => '130.00', $b->id => '170.00'][$model->id] ?? null;
        if ($new !== null && (string) $model->getRawOriginal('price') !== $new) {
            DB::table('inventory_items')->where('id', $model->id)->update(['price' => $new]);
            $model->setRawAttributes(array_merge($model->getAttributes(), ['price' => $new]), true);
        }
    });

    ptPost([['id' => $a->id, 'quantity' => 1, 'price' => '100.00'], ['id' => $b->id, 'quantity' => 1, 'price' => '200.00']], '300.00', '300.00')
        ->assertSessionHas('error');

    expect(Sale::count())->toBe(0)->and(SaleHeader::count())->toBe(0)
        ->and((int) DB::table('inventory_items')->where('id', $a->id)->value('quantity'))->toBe(10);
});

test('price snapshots are optional and validated', function () {
    $item = ptItem('Valve', '50.00', 10);

    // Legacy payload (no snapshot, no expected total) is unchanged.
    ptPost([['id' => $item->id, 'quantity' => 1]], '50')->assertRedirect();
    expect(SaleHeader::count())->toBe(1);

    foreach (['abc', '50.001', '-1', ['x']] as $bad) {
        ptPost([['id' => $item->id, 'quantity' => 1, 'price' => $bad]], '50')->assertSessionHas('error', 'Invalid cart data.');
    }
    expect(SaleHeader::count())->toBe(1)->and($item->fresh()->quantity)->toBe(9);
});
