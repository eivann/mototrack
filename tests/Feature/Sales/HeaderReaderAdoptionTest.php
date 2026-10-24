<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\SaleHeader;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * SYS4-06 D4: readers use the header + linked lines when a transaction is linked and fall back to the
 * legacy reference / single-row behaviour otherwise. Revenue and counts stay line-based.
 */

beforeEach(fn () => Notification::fake());

function hrOwner(): User
{
    return User::query()->where('role', 'owner')->first() ?? User::factory()->create(['role' => 'owner']);
}

function hrCustomer(): Customer
{
    $u = User::factory()->create(['role' => 'customer']);

    return Customer::create(['user_id' => $u->id, 'name' => $u->name, 'email' => $u->email]);
}

function hrItem(string $name, float $price): InventoryItem
{
    static $n = 0;
    $n++;

    return InventoryItem::create(['part_name' => $name, 'sku' => 'HR-'.$n, 'price' => $price, 'quantity' => 50]);
}

/** Raw legacy line (no header). */
function hrLegacy(array $o = []): Sale
{
    static $n = 0;
    $n++;
    $item = $o['item'] ?? hrItem('Legacy Part '.$n, 100);
    unset($o['item']);

    $id = DB::table('sales')->insertGetId(array_merge([
        'inventory_item_id' => $item->id, 'customer_id' => null, 'quantity' => 1, 'price' => $item->price, 'total_price' => $item->price,
        'transaction_reference' => 'TRX-HR-L'.$n, 'amount_tendered' => 0, 'change' => 0, 'status' => 'completed',
        'payment_confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
    ], $o));

    return Sale::find($id);
}

/** POS sale of 250 + 100 through the real controller (creates a header). */
function hrPos(float $tender = 400): string
{
    $a = hrItem('Pos A', 250);
    $b = hrItem('Pos B', 100);
    test()->actingAs(hrOwner())->post('/sales/store', ['cart_data' => json_encode([['id' => $a->id, 'quantity' => 1], ['id' => $b->id, 'quantity' => 1]]), 'amount_tendered' => $tender]);

    return SaleHeader::latest('id')->first()->transaction_reference;
}

function hrReserve(?Customer $c = null, string $ref = 'TRX-HR-R1'): Customer
{
    $c ??= hrCustomer();
    $a = hrItem('Res A', 250);
    $b = hrItem('Res B', 100);
    app(CheckoutService::class)->processTransaction([$a->id => ['quantity' => 1], $b->id => ['quantity' => 1]], $ref, $c->id, 0);

    return $c;
}

// ── Owner receipts ───────────────────────────────────────────────────────────

test('a linked multi-item POS receipt shows every line, the header tender and change, in HTML and PDF', function () {
    $ref = hrPos(400);
    $firstId = Sale::where('transaction_reference', $ref)->min('id');

    foreach ([route('sales.receipt', $ref), route('sales.receipt.by-id', $firstId)] as $url) {
        $this->actingAs(hrOwner())->get($url)->assertOk()->assertSee('Pos A')->assertSee('Pos B')->assertSee('350.00')->assertSee('400.00')->assertSee('50.00');
    }

    foreach ([route('sales.download', $ref), route('sales.download.by-id', $firstId)] as $url) {
        $pdf = $this->actingAs(hrOwner())->get($url);
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        expect(substr($pdf->getContent(), 0, 5))->toBe('%PDF-');
    }
});

test('the header is the authoritative tender source for a linked transaction, with a safe fallback', function () {
    $ref = hrPos(400);
    $header = SaleHeader::where('transaction_reference', $ref)->sole();

    $header->update(['amount_tendered' => 700, 'change_amount' => 350]); // deliberately different from the lines
    $this->actingAs(hrOwner())->get(route('sales.receipt', $ref))->assertOk()->assertSee('700.00')->assertSee('350.00');

    $header->update(['amount_tendered' => 0, 'change_amount' => 0]);  // zero-tender header: fall back to the lines
    $this->actingAs(hrOwner())->get(route('sales.receipt', $ref))->assertOk()->assertSee('400.00')->assertSee('50.00');
});

test('legacy multi-item receipts (no header) still group by reference, by key and by id', function () {
    $a = hrLegacy(['transaction_reference' => 'TRX-HR-LEG', 'amount_tendered' => 0]);
    $b = hrLegacy(['transaction_reference' => 'TRX-HR-LEG', 'amount_tendered' => 500, 'change' => 150]); // tender on the HIGHER id line
    hrLegacy(['transaction_reference' => 'TRX-HR-OTHER']);

    foreach ([route('sales.receipt', 'TRX-HR-LEG'), route('sales.receipt.by-id', $a->id), route('sales.receipt.by-id', $b->id)] as $url) {
        $r = $this->actingAs(hrOwner())->get($url)->assertOk();
        $r->assertSee($a->item->part_name)->assertSee($b->item->part_name)->assertSee('500.00')->assertSee('150.00');
        expect(substr_count($r->getContent(), 'TRX-HR-OTHER'))->toBe(0);
    }
});

test('a reference-less legacy sale is its own receipt by id and by SALE-<id>, never merged with others', function () {
    $a = hrLegacy(['transaction_reference' => null]);
    $b = hrLegacy(['transaction_reference' => null]);

    $this->actingAs(hrOwner())->get(route('sales.receipt.by-id', $a->id))->assertOk()->assertSee($a->item->part_name)->assertDontSee($b->item->part_name);
    $this->actingAs(hrOwner())->get(route('sales.receipt', 'SALE-'.$b->id))->assertOk()->assertSee($b->item->part_name)->assertDontSee($a->item->part_name);
});

test('a backfilled reference-less sale resolves through its LEGACY header reference', function () {
    $a = hrLegacy(['transaction_reference' => null]);
    hrLegacy(['transaction_reference' => null]);
    $this->artisan('sales:backfill-headers', ['--execute' => true])->assertExitCode(0);

    $this->actingAs(hrOwner())->get(route('sales.receipt', 'LEGACY-'.$a->id))->assertOk()->assertSee($a->item->part_name);
    $this->actingAs(hrOwner())->get(route('sales.receipt.by-id', $a->id))->assertOk()->assertSee($a->item->part_name);
});

test('a reused reference can never pull another transaction into a receipt', function () {
    $ref = hrPos(400);                                                       // linked transaction
    $stray = hrLegacy(['transaction_reference' => $ref]);                    // anomalous unlinked row reusing the reference
    $linkedId = Sale::where('sale_header_id', '!=', null)->where('transaction_reference', $ref)->min('id');

    $linked = $this->actingAs(hrOwner())->get(route('sales.receipt.by-id', $linkedId))->assertOk();
    $linked->assertSee('Pos A')->assertSee('Pos B')->assertDontSee($stray->item->part_name);

    $alone = $this->actingAs(hrOwner())->get(route('sales.receipt.by-id', $stray->id))->assertOk();
    $alone->assertSee($stray->item->part_name)->assertDontSee('Pos A');
});

test('pending, cancelled and expired reservations keep their receipt wording', function () {
    hrReserve(null, 'TRX-HR-P');
    $this->actingAs(hrOwner())->get(route('sales.receipt', 'TRX-HR-P'))->assertOk()->assertSee('350.00');
    $pdfHtml = view('sales.pdf_receipt', ['sales' => Sale::transactionByKey('TRX-HR-P'), 'transactionId' => 'TRX-HR-P', 'date' => 'x'])->render();
    expect($pdfHtml)->toContain('TOTAL DUE:')->and($pdfHtml)->not->toContain('GRAND TOTAL');

    Sale::where('transaction_reference', 'TRX-HR-P')->update(['status' => 'cancelled']);
    $this->actingAs(hrOwner())->get(route('sales.receipt', 'TRX-HR-P'))->assertOk();
    Sale::where('transaction_reference', 'TRX-HR-P')->update(['status' => 'expired']);
    $this->actingAs(hrOwner())->get(route('sales.receipt', 'TRX-HR-P'))->assertOk();

    $ref = hrPos(400);
    $paid = view('sales.pdf_receipt', ['sales' => Sale::transactionByKey($ref), 'transactionId' => $ref, 'date' => 'x'])->render();
    expect($paid)->toContain('GRAND TOTAL:')->and($paid)->not->toContain('TOTAL DUE');
});

// ── Customer access and views ────────────────────────────────────────────────

test('a customer sees only their own receipt, by sale reference or header reference; owners see any', function () {
    $mine = hrReserve(null, 'TRX-HR-MINE');
    $theirs = hrReserve(null, 'TRX-HR-THEIRS');

    $this->actingAs($mine->user)->get('/checkout/receipt?trx=TRX-HR-MINE')->assertOk()->assertSee('Res A');
    $this->actingAs($mine->user)->get('/checkout/receipt?trx=TRX-HR-THEIRS')->assertRedirect('/customer/dashboard');
    $this->actingAs($mine->user)->get(route('checkout.receipt.download', 'TRX-HR-THEIRS'))->assertNotFound();
    $this->actingAs(hrOwner())->get('/checkout/receipt?trx=TRX-HR-THEIRS')->assertOk();

    // a backfilled reference-less sale of the other customer is not reachable through its LEGACY key
    $other = hrLegacy(['transaction_reference' => null, 'customer_id' => $theirs->id]);
    $this->artisan('sales:backfill-headers', ['--execute' => true])->assertExitCode(0);
    $this->actingAs($mine->user)->get('/checkout/receipt?trx=LEGACY-'.$other->id)->assertRedirect('/customer/dashboard');
    $this->actingAs($theirs->user)->get('/checkout/receipt?trx=LEGACY-'.$other->id)->assertOk();
});

test('non-owners cannot use the owner receipt routes', function () {
    $ref = hrPos(400);
    $c = hrCustomer();

    $this->actingAs($c->user)->get(route('sales.receipt', $ref))->assertForbidden();
    $this->actingAs($c->user)->get(route('sales.download', $ref))->assertForbidden();
});

test('the customer purchase list keeps one entry per transaction and no longer lumps reference-less sales together', function () {
    $c = hrCustomer();
    hrReserve($c, 'TRX-HR-C1');
    $a = hrLegacy(['transaction_reference' => null, 'customer_id' => $c->id]);
    $b = hrLegacy(['transaction_reference' => null, 'customer_id' => $c->id]);
    hrLegacy(['transaction_reference' => 'TRX-HR-C2', 'customer_id' => $c->id]);
    hrLegacy(['transaction_reference' => 'TRX-HR-C2', 'customer_id' => $c->id]);

    $purchases = $this->actingAs($c->user)->get(route('customer.dashboard'))->assertOk()->viewData('purchases');

    expect($purchases->keys()->sort()->values()->all())->toBe(collect(['TRX-HR-C1', 'TRX-HR-C2', 'SALE-'.$a->id, 'SALE-'.$b->id])->sort()->values()->all())
        ->and($purchases['TRX-HR-C1'])->toHaveCount(2)->and($purchases['TRX-HR-C2'])->toHaveCount(2)
        ->and($purchases->flatten()->count())->toBe(6);
});

// ── Owner dashboard / history: counts, revenue, filters, pagination ─────────

test('mixed linked and legacy data is counted once per transaction on the dashboard and history', function () {
    hrReserve(null, 'TRX-HR-D1');                                                       // linked, pending, 2 lines
    $c = hrCustomer();
    hrLegacy(['transaction_reference' => 'TRX-HR-D2', 'status' => 'pending', 'customer_id' => $c->id, 'payment_confirmed_at' => null]); // legacy pending, 2 lines
    hrLegacy(['transaction_reference' => 'TRX-HR-D2', 'status' => 'pending', 'customer_id' => $c->id, 'payment_confirmed_at' => null]);
    hrLegacy(['transaction_reference' => null, 'status' => 'pending', 'payment_confirmed_at' => null]);                              // reference-less pending
    hrPos(400);                                                                         // linked, completed, 2 lines

    $dash = $this->actingAs(hrOwner())->get('/dashboard')->assertOk();
    expect($dash->viewData('pendingReservationCount'))->toBe(3);

    $history = $this->actingAs(hrOwner())->get('/sales/history')->assertOk();
    expect($history->viewData('totalTransactions'))->toBe(4); // D1, D2, the reference-less one, the POS sale
});

test('revenue is line-based and identical for linked and legacy data, including partial and full refunds', function () {
    $scenario = function (bool $linked) {
        DB::table('product_returns')->delete();
        DB::table('sales')->delete();
        DB::table('sale_headers')->delete();
        $a = hrItem('Rev A', 250);
        $b = hrItem('Rev B', 100);
        $headerId = null;
        if ($linked) {
            $headerId = DB::table('sale_headers')->insertGetId(['transaction_reference' => 'TRX-HR-REV-L', 'transaction_type' => 'pos', 'total_amount' => 350, 'amount_tendered' => 400, 'change_amount' => 50, 'payment_confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        $ref = $linked ? 'TRX-HR-REV-L' : 'TRX-HR-REV-N';
        $s1 = hrLegacy(['item' => $a, 'transaction_reference' => $ref, 'sale_header_id' => $headerId, 'amount_tendered' => 400, 'change' => 50]);
        $s2 = hrLegacy(['item' => $b, 'transaction_reference' => $ref, 'sale_header_id' => $headerId]);

        $totals = [];
        $totals['none'] = $this->actingAs(hrOwner())->get('/sales/history')->viewData('totalSales');

        $this->actingAs(hrOwner())->post(route('inventory.adjustments.store_return'), ['sale_id' => $s1->id, 'inventory_item_id' => $a->id, 'quantity' => 1, 'refund_amount' => 250, 'action_taken' => 'restocked']);
        $totals['partial'] = $this->actingAs(hrOwner())->get('/sales/history')->viewData('totalSales');

        $this->actingAs(hrOwner())->post(route('inventory.adjustments.store_return'), ['sale_id' => $s2->id, 'inventory_item_id' => $b->id, 'quantity' => 1, 'refund_amount' => 100, 'action_taken' => 'restocked']);
        $totals['full'] = $this->actingAs(hrOwner())->get('/sales/history')->viewData('totalSales');

        $header = $headerId ? DB::table('sale_headers')->where('id', $headerId)->first() : null;

        return [$totals, $header ? (float) $header->total_amount : null];
    };

    [$legacy] = $scenario(false);
    [$linked, $headerTotal] = $scenario(true);

    expect($legacy)->toEqual(['none' => 350.0, 'partial' => 100.0, 'full' => 0.0])
        ->and($linked)->toEqual($legacy)
        ->and($headerTotal)->toBe(350.0); // the header keeps the ORIGINAL total and is never used as net revenue
});

test('history keeps its date filters and line pagination with linked data', function () {
    $ref = hrPos(400);                                    // 2 linked lines today
    for ($i = 0; $i < 11; $i++) {
        hrLegacy(['transaction_reference' => 'TRX-HR-PG'.$i]);
    }
    hrLegacy(['transaction_reference' => 'TRX-HR-OLD', 'created_at' => now()->subYears(2), 'payment_confirmed_at' => now()->subYears(2)]);

    $page1 = $this->actingAs(hrOwner())->get('/sales/history')->viewData('sales');
    $page2 = $this->actingAs(hrOwner())->get('/sales/history?page=2')->viewData('sales');
    $today = $this->actingAs(hrOwner())->get('/sales/history?filter=today')->viewData('sales');

    expect($page1->total())->toBe(14)->and($page1->count())->toBe(10)->and($page2->count())->toBe(4)
        ->and($today->total())->toBe(13)                         // the two-year-old row is excluded
        ->and($page1->pluck('id')->merge($page2->pluck('id'))->unique()->count())->toBe(14); // no duplicates
});

test('receipt resolution uses a constant number of queries regardless of line count', function () {
    $count = function (int $lines) {
        $c = hrCustomer();
        $ref = 'TRX-HR-Q'.$lines;
        $items = [];
        for ($i = 0; $i < $lines; $i++) {
            $items[hrItem('Q'.$i, 10)->id] = ['quantity' => 1];
        }
        app(CheckoutService::class)->processTransaction($items, $ref, $c->id, 0);
        $first = Sale::where('transaction_reference', $ref)->min('id');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs(hrOwner())->get(route('sales.receipt.by-id', $first))->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    $count(1); // warm-up: first request in a process also boots session / config queries

    expect($count(8))->toBe($count(2));
});

// ── Ambiguity safeguards ─────────────────────────────────────────────────────

/** Link raw legacy lines to a fresh header (own header reference, sales keep their reference). */
function hrLink(array $sales, string $headerRef): SaleHeader
{
    $h = SaleHeader::forceCreate([
        'transaction_reference' => $headerRef, 'customer_id' => $sales[0]->customer_id,
        'transaction_type' => SaleHeader::TYPE_LEGACY, 'total_amount' => 100 * count($sales),
    ]);
    DB::table('sales')->whereIn('id', collect($sales)->pluck('id'))->update(['sale_header_id' => $h->id]);

    return $h;
}

test('a reference reused by two distinct linked headers is refused by key but each resolves by id', function () {
    $a = hrLegacy(['transaction_reference' => 'TRX-DUP']);
    $b = hrLegacy(['transaction_reference' => 'TRX-DUP']);
    hrLink([$a], 'H-ONE');
    hrLink([$b], 'H-TWO');

    $this->actingAs(hrOwner())->get(route('sales.receipt', 'TRX-DUP'))->assertNotFound();
    $this->actingAs(hrOwner())->get(route('sales.download', 'TRX-DUP'))->assertNotFound();
    $this->actingAs(hrOwner())->get(route('sales.receipt', 'H-ONE'))->assertOk()->assertSee($a->item->part_name)->assertDontSee($b->item->part_name);
    $this->actingAs(hrOwner())->get(route('sales.receipt.by-id', $b->id))->assertOk()->assertSee($b->item->part_name)->assertDontSee($a->item->part_name);
});

test('a reference shared by one linked and one unlinked transaction is refused by key', function () {
    $a = hrLegacy(['transaction_reference' => 'TRX-MIX']);
    $b = hrLegacy(['transaction_reference' => 'TRX-MIX']);
    hrLink([$a], 'H-MIX');

    $this->actingAs(hrOwner())->get(route('sales.receipt', 'TRX-MIX'))->assertNotFound();
    $this->actingAs(hrOwner())->get(route('sales.download', 'TRX-MIX'))->assertNotFound();
    $this->actingAs(hrOwner())->get(route('sales.receipt.by-id', $a->id))->assertOk()->assertSee($a->item->part_name)->assertDontSee($b->item->part_name);
});

test('unlinked purchases weeks apart sharing a reference are never combined, by key or by id', function () {
    $a = hrLegacy(['transaction_reference' => 'TRX-OLD', 'created_at' => '2026-02-01 10:00:00']);
    $b = hrLegacy(['transaction_reference' => 'TRX-OLD', 'created_at' => '2026-03-01 10:00:00']);

    foreach ([route('sales.receipt', 'TRX-OLD'), route('sales.download', 'TRX-OLD'),
        route('sales.receipt.by-id', $a->id), route('sales.receipt.by-id', $b->id),
        route('sales.download.by-id', $a->id)] as $url) {
        $this->actingAs(hrOwner())->get($url)->assertNotFound();
    }
});

test('a multi-line group with missing created_at evidence is refused', function () {
    $a = hrLegacy(['transaction_reference' => 'TRX-NOTS', 'created_at' => null]);
    hrLegacy(['transaction_reference' => 'TRX-NOTS']);
    $this->actingAs(hrOwner())->get(route('sales.receipt', 'TRX-NOTS'))->assertNotFound();
    $this->actingAs(hrOwner())->get(route('sales.receipt.by-id', $a->id))->assertNotFound();
});

test('a legitimate legacy multi-line transaction created together still renders in full', function () {
    $a = hrLegacy(['transaction_reference' => 'TRX-TOGETHER', 'created_at' => '2026-03-01 10:00:00']);
    $b = hrLegacy(['transaction_reference' => 'TRX-TOGETHER', 'created_at' => '2026-03-01 10:00:02']);

    foreach ([route('sales.receipt', 'TRX-TOGETHER'), route('sales.receipt.by-id', $b->id)] as $url) {
        $this->actingAs(hrOwner())->get($url)->assertOk()->assertSee($a->item->part_name)->assertSee($b->item->part_name);
    }
    $this->actingAs(hrOwner())->get(route('sales.download', 'TRX-TOGETHER'))->assertOk();
});

test('customer scope hides other customers: no disclosure and no phantom ambiguity', function () {
    $mine = hrCustomer();
    $theirs = hrCustomer();
    $a = hrLegacy(['transaction_reference' => 'TRX-SCOPE', 'customer_id' => $mine->id]);
    $b = hrLegacy(['transaction_reference' => 'TRX-SCOPE', 'customer_id' => $theirs->id]);
    hrLink([$b], 'H-THEIRS');

    // Only the caller's own rows count: mine is unambiguous, theirs is not found for me.
    $this->actingAs($mine->user)->get('/checkout/receipt?trx=TRX-SCOPE')->assertOk()->assertSee($a->item->part_name)->assertDontSee($b->item->part_name);
    $this->actingAs($mine->user)->get('/checkout/receipt?trx=H-THEIRS')->assertRedirect('/customer/dashboard');
    $this->actingAs($mine->user)->get(route('checkout.receipt.download', 'H-THEIRS'))->assertNotFound();
    // The owner sees the real ambiguity.
    $this->actingAs(hrOwner())->get(route('sales.receipt', 'TRX-SCOPE'))->assertNotFound();
});

test('an ambiguous customer-visible key is refused for the customer in HTML and PDF', function () {
    $c = hrCustomer();
    $a = hrLegacy(['transaction_reference' => 'TRX-CAMB', 'customer_id' => $c->id]);
    $b = hrLegacy(['transaction_reference' => 'TRX-CAMB', 'customer_id' => $c->id]);
    hrLink([$a], 'H-CA');
    hrLink([$b], 'H-CB');

    $this->actingAs($c->user)->get('/checkout/receipt?trx=TRX-CAMB')->assertRedirect('/customer/dashboard');
    $this->actingAs($c->user)->get(route('checkout.receipt.download', 'TRX-CAMB'))->assertNotFound();
    $this->actingAs($c->user)->get('/checkout/receipt?trx=H-CA')->assertOk()->assertSee($a->item->part_name);
});

test('the ambiguity checks keep receipt queries bounded', function () {
    $count = function (int $lines, string $ref) {
        $created = [];
        for ($i = 0; $i < $lines; $i++) {
            $created[] = hrLegacy(['transaction_reference' => $ref]);
        }
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs(hrOwner())->get(route('sales.receipt', $ref))->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };
    $count(1, 'TRX-BQ0');

    expect($count(10, 'TRX-BQ10'))->toBe($count(2, 'TRX-BQ2'));
});

// ── Customer / shop consistency of legacy groups ─────────────────────────────

test('legacy lines sharing a reference and timestamp but different customers are refused', function () {
    $c1 = hrCustomer();
    $c2 = hrCustomer();
    $stamp = '2026-03-01 10:00:00';
    $a = hrLegacy(['transaction_reference' => 'TRX-DC', 'customer_id' => $c1->id, 'created_at' => $stamp]);
    $b = hrLegacy(['transaction_reference' => 'TRX-DC', 'customer_id' => $c2->id, 'created_at' => $stamp]);

    foreach ([route('sales.receipt', 'TRX-DC'), route('sales.download', 'TRX-DC'),
        route('sales.receipt.by-id', $a->id), route('sales.receipt.by-id', $b->id)] as $url) {
        $this->actingAs(hrOwner())->get($url)->assertNotFound();
    }

    // Each customer sees only their own authorized row, never the other's.
    $this->actingAs($c1->user)->get('/checkout/receipt?trx=TRX-DC')->assertOk()->assertSee($a->item->part_name)->assertDontSee($b->item->part_name);
    $this->actingAs($c2->user)->get('/checkout/receipt?trx=TRX-DC')->assertOk()->assertSee($b->item->part_name)->assertDontSee($a->item->part_name);
});

test('legacy lines sharing a reference and timestamp but different shops are refused', function () {
    $stamp = '2026-03-01 10:00:00';
    $a = hrLegacy(['transaction_reference' => 'TRX-DS', 'shop_id' => 1, 'created_at' => $stamp]);
    $b = hrLegacy(['transaction_reference' => 'TRX-DS', 'shop_id' => 2, 'created_at' => $stamp]);

    foreach ([route('sales.receipt', 'TRX-DS'), route('sales.download', 'TRX-DS'), route('sales.receipt.by-id', $a->id)] as $url) {
        $this->actingAs(hrOwner())->get($url)->assertNotFound();
    }
});

test('NULL versus non-NULL customer or shop is treated as inconsistent', function () {
    $c = hrCustomer();
    $stamp = '2026-03-01 10:00:00';
    hrLegacy(['transaction_reference' => 'TRX-NC', 'customer_id' => $c->id, 'created_at' => $stamp]);
    hrLegacy(['transaction_reference' => 'TRX-NC', 'customer_id' => null, 'created_at' => $stamp]);
    hrLegacy(['transaction_reference' => 'TRX-NS', 'shop_id' => 1, 'created_at' => $stamp]);
    hrLegacy(['transaction_reference' => 'TRX-NS', 'shop_id' => null, 'created_at' => $stamp]);

    $this->actingAs(hrOwner())->get(route('sales.receipt', 'TRX-NC'))->assertNotFound();
    $this->actingAs(hrOwner())->get(route('sales.receipt', 'TRX-NS'))->assertNotFound();
});

test('matching customer and shop (including both NULL) keeps the legacy multi-line receipt working', function () {
    $c = hrCustomer();
    $stamp = '2026-03-01 10:00:00';
    $a = hrLegacy(['transaction_reference' => 'TRX-OKC', 'customer_id' => $c->id, 'shop_id' => 1, 'created_at' => $stamp]);
    $b = hrLegacy(['transaction_reference' => 'TRX-OKC', 'customer_id' => $c->id, 'shop_id' => 1, 'created_at' => $stamp]);
    $this->actingAs(hrOwner())->get(route('sales.receipt', 'TRX-OKC'))->assertOk()->assertSee($a->item->part_name)->assertSee($b->item->part_name);
    $this->actingAs($c->user)->get('/checkout/receipt?trx=TRX-OKC')->assertOk()->assertSee($b->item->part_name);

    $n1 = hrLegacy(['transaction_reference' => 'TRX-OKN', 'created_at' => $stamp]);
    $n2 = hrLegacy(['transaction_reference' => 'TRX-OKN', 'created_at' => $stamp]);
    $this->actingAs(hrOwner())->get(route('sales.receipt', 'TRX-OKN'))->assertOk()->assertSee($n1->item->part_name)->assertSee($n2->item->part_name);
});
