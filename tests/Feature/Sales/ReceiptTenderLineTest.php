<?php

use App\Actions\CommitOrder;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Receipt stage C2: cash tendered / change come from the line that actually holds them
 * (lowest-id line with a positive tender), including historical reservations whose
 * tender was written to a higher-id line. Historical data is simulated here only, in
 * the isolated in-memory database.
 */

function tlItem(string $name, float $price, string $sku): InventoryItem
{
    return InventoryItem::create(['part_name' => $name, 'sku' => $sku, 'price' => $price, 'quantity' => 50]);
}

/**
 * A three-line (100 + 100 + 50 = 250) transaction written row by row, so tender/change
 * can sit on any line. $tender = [line index => [tendered, change]] (0-based).
 *
 * @return array<int, Sale>
 */
function tlTransaction(string $ref, array $tender, string $status = 'completed', ?int $customerId = null): array
{
    $lines = [[100, 'Chain Set', 'TL-A'], [100, 'Sprocket', 'TL-B'], [50, 'Grip', 'TL-C']];
    $rows = [];
    foreach ($lines as $i => [$price, $name, $sku]) {
        $item = tlItem($name, $price, $sku.'-'.$ref);
        [$tendered, $change] = $tender[$i] ?? [0, 0];
        $rows[] = Sale::create([
            'inventory_item_id' => $item->id,
            'customer_id' => $customerId,
            'quantity' => 1,
            'price' => $price,
            'total_price' => $price,
            'amount_tendered' => $tendered,
            'change' => $change,
            'status' => $status,
            'transaction_reference' => $ref,
            'payment_confirmed_at' => $status === 'completed' ? now() : null,
            'reserved_at' => $status === 'pending' ? now() : null,
            'expires_at' => $status === 'pending' ? now()->addDay() : null,
        ]);
    }

    return $rows;
}

function tlOwner(): User
{
    return User::factory()->create(['role' => 'owner']);
}

/** The PDF template's HTML, rendered with the variables the download controllers pass. */
function tlPdfHtml(string $ref): string
{
    $sales = Sale::with('item')->where('transaction_reference', $ref)->orderBy('id')->get();

    return view('sales.pdf_receipt', [
        'sales' => $sales,
        'transactionId' => $ref,
        'date' => $sales->first()->created_at->format('F d, Y h:i A'),
    ])->render();
}

// ── Helper ───────────────────────────────────────────────────────────────────

test('paymentLine: lowest-id positive tender, else lowest-id line, else null', function () {
    $mk = fn (int $id, float $tender) => (new Sale)->forceFill(['id' => $id, 'amount_tendered' => $tender]);

    expect(Sale::paymentLine([]))->toBeNull()
        ->and(Sale::paymentLine(collect()))->toBeNull()
        // holder on a higher id, passed in scrambled order
        ->and(Sale::paymentLine([$mk(9, 0), $mk(7, 300), $mk(3, 0)])->id)->toBe(7)
        // several lines with tender (legacy): lowest id among them
        ->and(Sale::paymentLine([$mk(9, 300), $mk(5, 300), $mk(3, 0)])->id)->toBe(5)
        // nobody has tender: lowest id
        ->and(Sale::paymentLine([$mk(9, 0), $mk(4, 0), $mk(6, 0)])->id)->toBe(4)
        ->and(Sale::paymentLine(collect([$mk(2, 50)]))->id)->toBe(2);
});

// ── Historical sale: tender on a higher-id line ──────────────────────────────

test('historical three-line sale shows tender and change in every receipt', function () {
    [$l1, $l2, $l3] = tlTransaction('TRX-TL-HIST', [1 => [300, 50]]); // tender on the middle (higher-id) line
    expect($l2->id)->toBeGreaterThan($l1->id)->and((float) $l1->amount_tendered)->toBe(0.0);

    $owner = tlOwner();

    foreach (["/sales/TRX-TL-HIST/receipt", "/sales/by-id/{$l3->id}/receipt"] as $url) {
        $this->actingAs($owner)->get($url)->assertOk()
            ->assertSee('Amount Tendered:')->assertSee('₱300.00')->assertSee('Change:')->assertSee('₱50.00')
            ->assertSee('₱250.00');
    }

    $html = tlPdfHtml('TRX-TL-HIST');
    expect($html)->toContain('Amount Tendered:')->toContain('PHP 300.00')
        ->toContain('Change Given:')->toContain('PHP 50.00')
        ->toContain('GRAND TOTAL:')->toContain('PHP 250.00');

    foreach (["/sales/TRX-TL-HIST/download", "/sales/by-id/{$l1->id}/download"] as $url) {
        $r = $this->actingAs($owner)->get($url);
        $r->assertOk()->assertHeader('content-type', 'application/pdf');
        expect(substr($r->getContent(), 0, 5))->toBe('%PDF-');
    }
});

test('historical sale tender also appears on the customer receipt and customer PDF', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => 'Ana', 'email' => $user->email]);
    tlTransaction('TRX-TL-CUST', [2 => [300, 50]], 'completed', $customer->id); // tender on the last line

    $this->actingAs($user)->get('/checkout/receipt?trx=TRX-TL-CUST')->assertOk()
        ->assertSee('Amount Tendered:')->assertSee('₱300.00')->assertSee('₱50.00');

    $r = $this->actingAs($user)->get(route('checkout.receipt.download', ['transaction' => 'TRX-TL-CUST']));
    $r->assertOk()->assertHeader('content-type', 'application/pdf');
});

// ── Normal / legacy layouts ──────────────────────────────────────────────────

test('tender on the first line (current behaviour) is unchanged', function () {
    tlTransaction('TRX-TL-FIRST', [0 => [300, 50]]);

    $this->actingAs(tlOwner())->get('/sales/TRX-TL-FIRST/receipt')->assertOk()
        ->assertSee('₱300.00')->assertSee('₱50.00');
    expect(tlPdfHtml('TRX-TL-FIRST'))->toContain('PHP 300.00')->toContain('PHP 50.00');
});

test('legacy tender stamped on every line shows a single tender and change', function () {
    tlTransaction('TRX-TL-EVERY', [0 => [300, 50], 1 => [300, 50], 2 => [300, 50]]);

    $res = $this->actingAs(tlOwner())->get('/sales/TRX-TL-EVERY/receipt')->assertOk();
    expect(substr_count($res->getContent(), 'Amount Tendered:'))->toBe(1)
        ->and(substr_count(tlPdfHtml('TRX-TL-EVERY'), 'Amount Tendered:'))->toBe(1);
    $res->assertSee('₱300.00')->assertSee('₱50.00');
});

test('single-line repair-style, legacy NULL-reference and no-tender transactions still render', function () {
    $owner = tlOwner();

    $repair = Sale::create([
        'inventory_item_id' => null, 'quantity' => 1, 'price' => 450, 'total_price' => 450,
        'amount_tendered' => 500, 'change' => 50, 'status' => 'completed',
        'payment_confirmed_at' => now(), 'transaction_reference' => 'REPAIR-TL1',
    ]);
    $this->actingAs($owner)->get('/sales/REPAIR-TL1/receipt')->assertOk()->assertSee('₱500.00')->assertSee('₱50.00');

    $item = tlItem('Old Part', 80, 'TL-OLD');
    $legacy = Sale::create([
        'inventory_item_id' => $item->id, 'quantity' => 1, 'price' => 80, 'total_price' => 80,
        'amount_tendered' => 100, 'change' => 20, 'status' => 'completed',
        'payment_confirmed_at' => now(), 'transaction_reference' => null,
    ]);
    $this->actingAs($owner)->get("/sales/by-id/{$legacy->id}/receipt")->assertOk()->assertSee('₱100.00')->assertSee('₱20.00');

    // Completed but with no tender recorded anywhere: no tender rows, no crash.
    tlTransaction('TRX-TL-NOTENDER', []);
    $this->actingAs($owner)->get('/sales/TRX-TL-NOTENDER/receipt')->assertOk()->assertDontSee('Amount Tendered:');
    expect(tlPdfHtml('TRX-TL-NOTENDER'))->not->toContain('Amount Tendered:');
});

test('a pending reservation remains unpaid with no tender rows', function () {
    tlTransaction('TRX-TL-PENDING', [], 'pending');

    $this->actingAs(tlOwner())->get('/sales/TRX-TL-PENDING/receipt')->assertOk()
        ->assertSee('Pending Counter Payment')->assertDontSee('Amount Tendered:');
    $html = tlPdfHtml('TRX-TL-PENDING');
    expect($html)->toContain('TOTAL DUE:')->toContain('Cash at Pickup')->not->toContain('Amount Tendered:');
});

test('a refunded tender-holder still supplies payment details; paid classification is untouched', function () {
    [$l1, $l2] = tlTransaction('TRX-TL-REFUND', [1 => [300, 50]]);
    $l2->update(['status' => 'refunded']); // the tender-holding line is refunded, line 1 stays completed

    $this->actingAs(tlOwner())->get('/sales/TRX-TL-REFUND/receipt')->assertOk()
        ->assertSee('Cash Sales Receipt')->assertSee('₱300.00')->assertSee('₱50.00')->assertSee('₱250.00');
    $html = tlPdfHtml('TRX-TL-REFUND');
    expect($html)->toContain('GRAND TOTAL:')->toContain('PHP 300.00')->toContain('PHP 50.00');
});

// ── Future completion & explicit query ordering ──────────────────────────────

test('completing a multi-line reservation writes tender to the lowest-id pending line', function () {
    $a = tlItem('Mirror', 100, 'TL-M1');
    $b = tlItem('Bulb', 100, 'TL-M2');
    $c = tlItem('Cap', 50, 'TL-M3');
    app(CommitOrder::class)->execute(
        [['id' => $c->id, 'quantity' => 1], ['id' => $a->id, 'quantity' => 1], ['id' => $b->id, 'quantity' => 1]],
        'TRX-TL-FUTURE', null, 'Out of stock: ', fn () => 0.0, fn () => 0.0, false, 'pending'
    );

    $this->actingAs(tlOwner())->post('/sales/TRX-TL-FUTURE/complete', ['amount_tendered' => 300])->assertRedirect();

    $rows = Sale::where('transaction_reference', 'TRX-TL-FUTURE')->orderBy('id')->get();
    expect($rows)->toHaveCount(3)
        ->and((float) $rows[0]->amount_tendered)->toBe(300.0)
        ->and((float) $rows[0]->change)->toBe(50.0)
        ->and((float) $rows[1]->amount_tendered)->toBe(0.0)
        ->and((float) $rows[2]->amount_tendered)->toBe(0.0)
        ->and($rows->pluck('status')->unique()->all())->toBe(['completed']);
});

test('every transaction-line query on the receipt, PDF and pickup paths orders by id explicitly', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => 'Ben', 'email' => $user->email]);
    [$l1] = tlTransaction('TRX-TL-ORDER', [1 => [300, 50]], 'completed', $customer->id);
    tlTransaction('TRX-TL-ORDER-P', [], 'pending', $customer->id);
    $owner = tlOwner();

    $captured = [];
    DB::listen(function ($q) use (&$captured) {
        // transaction-line fetches: sales rows selected by reference (the pickup lock included)
        if (str_contains($q->sql, 'from "sales"') && str_contains($q->sql, '"transaction_reference" = ?')) {
            $captured[] = $q->sql;
        }
    });

    $paths = [
        [$owner, 'get', '/sales/TRX-TL-ORDER/receipt'],
        [$owner, 'get', '/sales/TRX-TL-ORDER/download'],
        [$owner, 'get', "/sales/by-id/{$l1->id}/receipt"],
        [$owner, 'get', "/sales/by-id/{$l1->id}/download"],
        [$user, 'get', '/checkout/receipt?trx=TRX-TL-ORDER'],
        [$user, 'get', '/checkout/receipt/TRX-TL-ORDER/download'],
        [$owner, 'post', '/sales/TRX-TL-ORDER-P/complete'],
    ];

    foreach ($paths as [$actor, $method, $url]) {
        $captured = [];
        $this->actingAs($actor)->$method($url, $method === 'post' ? ['amount_tendered' => 500] : []);
        expect($captured)->not->toBeEmpty("no transaction-line query captured for {$url}");
        foreach ($captured as $sql) {
            expect(str_contains($sql, 'order by "id" asc'))->toBeTrue("unordered query on {$url}: {$sql}");
        }
    }
});

// ── Authorization (retained) ─────────────────────────────────────────────────

test('customers still cannot read another customer receipt or PDF', function () {
    $owner = User::factory()->create(['role' => 'customer']);
    $ownerCustomer = Customer::create(['user_id' => $owner->id, 'name' => 'Owner Cust', 'email' => $owner->email]);
    $other = User::factory()->create(['role' => 'customer']);
    Customer::create(['user_id' => $other->id, 'name' => 'Other', 'email' => $other->email]);
    tlTransaction('TRX-TL-IDOR', [1 => [300, 50]], 'completed', $ownerCustomer->id);

    $this->actingAs($other)->get('/checkout/receipt?trx=TRX-TL-IDOR')->assertRedirect('/customer/dashboard');
    $this->actingAs($other)->get(route('checkout.receipt.download', ['transaction' => 'TRX-TL-IDOR']))->assertNotFound();
    $this->actingAs($owner)->get('/checkout/receipt?trx=TRX-TL-IDOR')->assertOk()->assertSee('₱300.00');
});
