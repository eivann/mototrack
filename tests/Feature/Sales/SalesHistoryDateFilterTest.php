<?php

/**
 * F13 — Sales History Date-Range Boundary Regression Tests
 *
 * Verifies that the custom from/to date filter correctly includes transactions
 * throughout the entire last day (not just at midnight).
 *
 * Storage: Sales timestamps are stored as TIMESTAMP WITHOUT TIME ZONE.
 * Laravel app timezone: Asia/Manila (UTC+8).
 * Carbon::now() / today() already return Manila-local time.
 *
 * The original bug: whereBetween('created_at', ['2026-11-12', '2026-11-12'])
 * resolved to ['2026-11-12 00:00:00', '2026-11-12 00:00:00'], excluding every
 * transaction created after midnight on the to-date.
 *
 * The fix uses startOfDay() for 'from' and endOfDay() for 'to', both in the
 * app timezone, so the full day is always included.
 *
 * All tests run against SQLite in-memory — production Supabase is never touched.
 */

use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// ─── Helper ───────────────────────────────────────────────────────────────────

function dateFilterItem(): InventoryItem
{
    return InventoryItem::create([
        'part_name' => 'Filter Test Part',
        'category'  => 'Test',
        'brand'     => 'Brand',
        'model'     => 'Model',
        'quantity'  => 20,
        'price'     => 100.00,
    ]);
}

function dateFilterSale(InventoryItem $item, Carbon $at, array $extra = []): Sale
{
    $sale = new Sale(array_merge([
        'inventory_item_id' => $item->id,
        'quantity'          => 1,
        'price'             => 100.00,
        'total_price'       => 100.00,
        'amount_tendered'   => 100.00,
        'change'            => 0.00,
        'status'            => 'completed',
        'payment_confirmed_at' => $at,
        'transaction_reference' => 'TRX-DF-' . uniqid(),
    ], $extra));

    $sale->timestamps = false;
    $sale->created_at = $at;
    $sale->updated_at = $at;
    $sale->save();

    return $sale;
}

// ─── Tests ────────────────────────────────────────────────────────────────────

test('F13: history page includes transactions created at 23:59 on the to-date', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = dateFilterItem();

    // A sale created at 11:59 PM on the boundary date — the original bug excluded this
    $lateOnBoundary = Carbon::parse('2026-11-12 23:59:00', 'Asia/Manila');
    $sale = dateFilterSale($item, $lateOnBoundary);

    $response = $this->actingAs($owner)
        ->get('/sales/history?from=2026-11-12&to=2026-11-12');

    $response->assertStatus(200);
    $ids = $response->viewData('sales')->pluck('id')->all();
    expect($ids)->toContain($sale->id);
});

test('F13: history page includes transactions created at 00:01 on the from-date', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = dateFilterItem();

    $earlyOnFrom = Carbon::parse('2026-11-10 00:01:00', 'Asia/Manila');
    $sale = dateFilterSale($item, $earlyOnFrom);

    $response = $this->actingAs($owner)
        ->get('/sales/history?from=2026-11-10&to=2026-11-12');

    $response->assertStatus(200);
    $ids = $response->viewData('sales')->pluck('id')->all();
    expect($ids)->toContain($sale->id);
});

test('F13: history page excludes transactions strictly before the from-date', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = dateFilterItem();

    // 23:59 the day before the from-date must be excluded
    $dayBefore = Carbon::parse('2026-11-09 23:59:00', 'Asia/Manila');
    $sale = dateFilterSale($item, $dayBefore);

    $response = $this->actingAs($owner)
        ->get('/sales/history?from=2026-11-10&to=2026-11-12');

    $response->assertStatus(200);
    $ids = $response->viewData('sales')->pluck('id')->all();
    expect($ids)->not->toContain($sale->id);
});

test('F13: history page excludes transactions strictly after the to-date', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = dateFilterItem();

    // 00:01 the day after the to-date must be excluded
    $dayAfter = Carbon::parse('2026-11-13 00:01:00', 'Asia/Manila');
    $sale = dateFilterSale($item, $dayAfter);

    $response = $this->actingAs($owner)
        ->get('/sales/history?from=2026-11-10&to=2026-11-12');

    $response->assertStatus(200);
    $ids = $response->viewData('sales')->pluck('id')->all();
    expect($ids)->not->toContain($sale->id);
});

test('F13: single-day filter from=to includes all transactions throughout that day', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = dateFilterItem();

    $morning   = Carbon::parse('2026-11-11 08:00:00', 'Asia/Manila');
    $afternoon = Carbon::parse('2026-11-11 14:30:00', 'Asia/Manila');
    $evening   = Carbon::parse('2026-11-11 22:45:00', 'Asia/Manila');

    $s1 = dateFilterSale($item, $morning);
    $s2 = dateFilterSale($item, $afternoon);
    $s3 = dateFilterSale($item, $evening);

    $response = $this->actingAs($owner)
        ->get('/sales/history?from=2026-11-11&to=2026-11-11');

    $response->assertStatus(200);
    $ids = $response->viewData('sales')->pluck('id')->all();

    expect($ids)->toContain($s1->id);
    expect($ids)->toContain($s2->id);
    expect($ids)->toContain($s3->id);
});

test('F13: reversed date range (from > to) returns no results without crashing', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    // from is after to — should return an empty result, not crash
    $response = $this->actingAs($owner)
        ->get('/sales/history?from=2026-11-12&to=2026-11-01');

    $response->assertStatus(200);
    // Should not throw an exception; sales should be empty or at most zero for that "impossible" range
    $sales = $response->viewData('sales');
    expect($sales->count())->toBe(0);
});

test('F13: half-open boundary includes transaction at 23:59:59 and excludes exactly 00:00:00 of next day', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = dateFilterItem();

    $endOfDay = Carbon::parse('2026-11-12 23:59:59', 'Asia/Manila');
    $exactNextDay = Carbon::parse('2026-11-13 00:00:00', 'Asia/Manila');

    $s1 = dateFilterSale($item, $endOfDay);
    $s2 = dateFilterSale($item, $exactNextDay);

    $response = $this->actingAs($owner)
        ->get('/sales/history?from=2026-11-12&to=2026-11-12');

    $response->assertStatus(200);
    $ids = $response->viewData('sales')->pluck('id')->all();

    expect($ids)->toContain($s1->id);
    expect($ids)->not->toContain($s2->id);
});

test('F13: revenue total (grossSalesQuery) includes payment_confirmed_at on the to-date end of day', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = dateFilterItem();

    // Late payment on the boundary date
    $latePayment = Carbon::parse('2026-11-12 23:30:00', 'Asia/Manila');
    dateFilterSale($item, $latePayment, [
        'payment_confirmed_at' => $latePayment,
        'amount_tendered'      => 100.00,
    ]);

    $response = $this->actingAs($owner)
        ->get('/sales/history?from=2026-11-12&to=2026-11-12');

    $response->assertStatus(200);
    // The total sales shown must include the late-day payment (≥ 100)
    $total = $response->viewData('totalSales');
    expect((float) $total)->toBeGreaterThanOrEqual(100.0);
});
