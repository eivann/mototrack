<?php

/**
 * SYS4-01 (Corrected) — Sales History Receipt Regression Tests
 *
 * Tests the corrected two-route design:
 *   GET /sales/by-id/{id}/receipt      → SaleController::receiptById()   [history page]
 *   GET /sales/by-id/{id}/download     → SaleController::downloadReceiptById()
 *   GET /sales/{id}/receipt            → SaleController::receipt()        [TRX reference / POS redirect]
 *   GET /sales/{transaction}/download  → SaleController::downloadReceipt()
 *
 * Corrected design eliminates both ChatGPT-identified defects:
 *   A) No collision: /sales/by-id/{id}/... is structurally separate from
 *      /sales/{id}/receipt (which uses a transaction_reference string).
 *   B) Multi-item receipts: receiptById() finds the anchor sale by PK,
 *      then loads ALL lines sharing its transaction_reference.
 *
 * All tests run against in-memory SQLite — production Supabase never touched.
 */

use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function histItem(array $o = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'part_name' => 'Test Part',
        'price'     => 500,
        'quantity'  => 10,
    ], $o));
}

function histSale(InventoryItem $item, ?string $txRef = 'TRX-TEST', array $o = []): Sale
{
    return Sale::create(array_merge([
        'inventory_item_id'    => $item->id,
        'quantity'             => 1,
        'price'                => $item->price,
        'total_price'          => $item->price,
        'transaction_reference' => $txRef,
        'status'               => 'completed',
        'payment_confirmed_at'  => now(),
    ], $o));
}

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 1: By-ID route — history page access
// ═══════════════════════════════════════════════════════════════════════════════

test('SYS4-01: by-id route returns 200 for sale accessed by numeric primary key', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = histItem();
    $sale  = histSale($item, 'TRX-HIST-A');

    $response = $this->actingAs($owner)->get(route('sales.receipt.by-id', $sale->id));

    $response->assertStatus(200);
    $response->assertViewHas('sales');
});

test('SYS4-01: by-id receipt view passes downloadUrl to the view', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = histItem();
    $sale  = histSale($item, 'TRX-HIST-B');

    $response = $this->actingAs($owner)->get(route('sales.receipt.by-id', $sale->id));

    $response->assertStatus(200);
    $response->assertViewHas('downloadUrl');
    // downloadUrl must point to the by-id download route, not the TRX route
    $this->assertStringContainsString(
        "/sales/by-id/{$sale->id}/download",
        $response->viewData('downloadUrl')
    );
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 2: Multi-item receipt completeness (ChatGPT defect B)
// ═══════════════════════════════════════════════════════════════════════════════

test('SYS4-01: by-id receipt returns ALL lines of a multi-item transaction', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $i1 = histItem(['part_name' => 'Brake Pad', 'price' => 300]);
    $i2 = histItem(['part_name' => 'Air Filter', 'price' => 200]);

    // Two sales lines sharing one TRX — anchor on $s1 but receipt must show both
    $s1 = histSale($i1, 'TRX-MULTI', ['total_price' => 300]);
    $s2 = histSale($i2, 'TRX-MULTI', ['total_price' => 200]);

    // Access by s1's ID — must return both lines
    $response = $this->actingAs($owner)->get(route('sales.receipt.by-id', $s1->id));
    $response->assertStatus(200);

    $returned = $response->viewData('sales');
    expect($returned)->toHaveCount(2);
    $returnedIds = $returned->pluck('id')->sort()->values()->all();
    expect($returnedIds)->toBe(collect([$s1->id, $s2->id])->sort()->values()->all());
});

test('SYS4-01: by-id receipt accessed from line 2 also returns ALL lines', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $i1 = histItem(['part_name' => 'Chain', 'price' => 450]);
    $i2 = histItem(['part_name' => 'Sprocket', 'price' => 550]);

    $s1 = histSale($i1, 'TRX-MULTI-2', ['total_price' => 450]);
    $s2 = histSale($i2, 'TRX-MULTI-2', ['total_price' => 550]);

    // Access by line 2's ID — still must return both
    $response = $this->actingAs($owner)->get(route('sales.receipt.by-id', $s2->id));
    $response->assertStatus(200);
    expect($response->viewData('sales'))->toHaveCount(2);
});

test('SYS4-01: by-id PDF download returns 200 and PDF content-type for multi-item transaction', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $i1 = histItem(['part_name' => 'Piston', 'price' => 800]);
    $i2 = histItem(['part_name' => 'Gasket', 'price' => 150]);

    $s1 = histSale($i1, 'TRX-MULTI-PDF', ['total_price' => 800]);
    histSale($i2, 'TRX-MULTI-PDF', ['total_price' => 150]);

    $response = $this->actingAs($owner)->get(route('sales.download.by-id', $s1->id));
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 3: Numeric collision safety (ChatGPT defect A)
// ═══════════════════════════════════════════════════════════════════════════════

test('SYS4-01: by-id route never retrieves the wrong transaction', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = histItem();

    $saleA = histSale($item, 'TRX-COLLISION-A');
    $saleB = histSale($item, 'TRX-COLLISION-B');

    // Request saleA by its numeric ID via the by-id route
    $response = $this->actingAs($owner)->get(route('sales.receipt.by-id', $saleA->id));
    $response->assertStatus(200);

    $refs = $response->viewData('sales')->pluck('transaction_reference')->unique()->values()->all();
    expect($refs)->not->toContain('TRX-COLLISION-B');
    expect($response->viewData('sales')->pluck('transaction_reference')->unique())->toHaveCount(1);
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 4: Legacy sales (NULL transaction_reference)
// ═══════════════════════════════════════════════════════════════════════════════

test('SYS4-01: by-id receipt shows legacy sale with NULL transaction_reference', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = histItem(['part_name' => 'Legacy Part']);
    $sale  = histSale($item, null); // NULL transaction_reference

    $response = $this->actingAs($owner)->get(route('sales.receipt.by-id', $sale->id));

    $response->assertStatus(200);
    $returned = $response->viewData('sales');
    expect($returned)->toHaveCount(1);
    expect($returned->first()->transaction_reference)->toBeNull();
});

test('SYS4-01: by-id PDF download works for legacy sale with NULL transaction_reference', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = histItem(['part_name' => 'Legacy PDF Part']);
    $sale  = histSale($item, null);

    $response = $this->actingAs($owner)->get(route('sales.download.by-id', $sale->id));
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
    // Filename must use SALE-{id} fallback when transaction_reference is null
    $this->assertStringContainsString(
        "Receipt_SALE-{$sale->id}.pdf",
        $response->headers->get('content-disposition')
    );
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 5: Missing and soft-deleted sales → 404
// ═══════════════════════════════════════════════════════════════════════════════

test('SYS4-01: by-id receipt returns 404 for non-existent sale ID', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $this->actingAs($owner)->get(route('sales.receipt.by-id', 999999))->assertStatus(404);
});

test('SYS4-01: by-id PDF download returns 404 for non-existent sale ID', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $this->actingAs($owner)->get(route('sales.download.by-id', 999999))->assertStatus(404);
});

test('SYS4-01: by-id receipt returns 404 for soft-deleted sale', function () {
    $owner  = User::factory()->create(['role' => 'owner']);
    $item   = histItem();
    $sale   = histSale($item, 'TRX-SOFT');
    $saleId = $sale->id;
    $sale->delete(); // soft delete

    $this->actingAs($owner)->get(route('sales.receipt.by-id', $saleId))->assertStatus(404);
});

test('SYS4-01: by-id PDF download returns 404 for soft-deleted sale', function () {
    $owner  = User::factory()->create(['role' => 'owner']);
    $item   = histItem();
    $sale   = histSale($item, 'TRX-SOFT-DL');
    $saleId = $sale->id;
    $sale->delete();

    $this->actingAs($owner)->get(route('sales.download.by-id', $saleId))->assertStatus(404);
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 6: Authorization
// ═══════════════════════════════════════════════════════════════════════════════

test('SYS4-01: unauthenticated user is redirected from by-id receipt', function () {
    $this->get('/sales/by-id/1/receipt')->assertRedirect('/login');
});

test('SYS4-01: unauthenticated user is redirected from by-id download', function () {
    $this->get('/sales/by-id/1/download')->assertRedirect('/login');
});

test('SYS4-01: customer role cannot access by-id receipt (403)', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $this->actingAs($customer)->get('/sales/by-id/1/receipt')->assertStatus(403);
});

test('SYS4-01: customer role cannot access by-id PDF download (403)', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $this->actingAs($customer)->get('/sales/by-id/1/download')->assertStatus(403);
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 7: Original TRX-reference routes still work (POS redirect)
// ═══════════════════════════════════════════════════════════════════════════════

test('SYS4-01: TRX-reference receipt route still works after correction', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = histItem();
    histSale($item, 'TRX-POS-REDIRECT');

    $response = $this->actingAs($owner)->get('/sales/TRX-POS-REDIRECT/receipt');
    $response->assertStatus(200);
    $response->assertViewHas('downloadUrl');
    // TRX download URL must use the standard download route
    $this->assertStringContainsString(
        '/sales/TRX-POS-REDIRECT/download',
        $response->viewData('downloadUrl')
    );
});

test('SYS4-01: TRX-reference PDF download still works after correction', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = histItem();
    histSale($item, 'TRX-POS-DL');

    $response = $this->actingAs($owner)->get('/sales/TRX-POS-DL/download');
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
    $this->assertStringContainsString(
        'Receipt_TRX-POS-DL.pdf',
        $response->headers->get('content-disposition')
    );
});

test('SYS4-01: TRX-reference route returns 404 for non-existent reference', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $this->actingAs($owner)->get('/sales/TRX-GHOST/receipt')->assertStatus(404);
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 8: History page generates the correct rendered links
// ═══════════════════════════════════════════════════════════════════════════════

test('SYS4-01: sales history page renders by-id receipt links not TRX-reference links', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = histItem();
    $sale  = histSale($item, 'TRX-HIST-RENDER');

    $response = $this->actingAs($owner)->get('/sales/history');
    $response->assertStatus(200);
    // The rendered page must contain the by-id path
    $response->assertSee("/sales/by-id/{$sale->id}/receipt", false);
    // Must NOT contain the old ambiguous numeric-ID path
    $response->assertDontSee("/sales/{$sale->id}/receipt", false);
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 9: POS sale with null customer_id
// ═══════════════════════════════════════════════════════════════════════════════

test('SYS4-01: by-id receipt works for POS sale with null customer_id', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = histItem();
    $sale  = histSale($item, 'TRX-NULL-CUST', ['customer_id' => null]);

    $this->actingAs($owner)->get(route('sales.receipt.by-id', $sale->id))->assertStatus(200);
});