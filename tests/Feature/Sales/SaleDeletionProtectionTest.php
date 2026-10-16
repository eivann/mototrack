<?php

use App\Http\Controllers\Sales\SaleController;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\ProductReturn;
use App\Models\RefundRequest;
use App\Models\Sale;
use App\Models\User;
use App\Policies\SalePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * Sale rows are permanent financial / cancellation / inventory-traceability
 * history. No role may delete them, through the (removed) route or through the
 * controller/policy if a route is ever re-added.
 */

function protectedSale(string $status, array $extra = []): Sale
{
    $item = InventoryItem::create([
        'part_name' => 'Part ' . $status, 'price' => 100, 'quantity' => 5, 'low_stock_threshold' => 1,
    ]);

    $paid = in_array($status, ['completed', 'refunded', 'refund_requested'], true);

    return Sale::create(array_merge([
        'inventory_item_id'    => $item->id,
        'quantity'             => 1,
        'price'                => 100,
        'total_price'          => 100,
        'amount_tendered'      => $paid ? 100 : 0,
        'change'               => 0,
        'status'               => $status,
        'transaction_reference'=> 'TRX-' . strtoupper($status) . '-' . random_int(1000, 9999),
        'reserved_at'          => $status === 'pending' ? now() : null,
        'expires_at'           => $status === 'pending' ? now()->addHours(24) : null,
        'payment_confirmed_at' => $paid ? now() : null,
    ], $extra));
}

function snapshot(Sale $sale): array
{
    return [
        'sales_total'   => Sale::withTrashed()->count(),
        'visible_sales' => Sale::count(),
        'revenue'       => (float) Sale::whereNotNull('payment_confirmed_at')->sum('total_price'),
        'stock'         => (int) InventoryItem::find($sale->inventory_item_id)->quantity,
        'soft_deleted_audits' => AuditLog::where('action', 'soft_deleted')->count(),
        'deleted_audits'      => AuditLog::whereIn('action', ['deleted', 'soft_deleted'])->count(),
        'refunds'       => RefundRequest::count(),
        'returns'       => ProductReturn::count(),
    ];
}

dataset('sale_statuses', ['completed', 'refunded', 'refund_requested', 'pending', 'cancelled', 'expired']);

test('a crafted DELETE /sales/{id} by the owner cannot remove any sale', function (string $status) {
    $owner = User::factory()->create(['role' => 'owner']);
    $sale = protectedSale($status);

    // Related history that must survive.
    $cust = \App\Models\Customer::create([
        'user_id' => User::factory()->create(['role' => 'customer'])->id,
        'name' => 'Test Customer', 'phone' => '09171234567',
    ]);
    RefundRequest::create([
        'sale_id' => $sale->id, 'customer_id' => $cust->id, 'reason' => 'x', 'details' => 'y', 'status' => 'pending',
    ]);
    ProductReturn::create([
        'sale_id' => $sale->id, 'inventory_item_id' => $sale->inventory_item_id,
        'quantity' => 1, 'refund_amount' => 0, 'action_taken' => 'restocked',
    ]);

    $before = snapshot($sale);

    $response = $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->delete('/sales/' . $sale->id);

    expect($response->getStatusCode())->toBeIn([403, 404, 405]);
    expect(Sale::find($sale->id))->not->toBeNull()
        ->and(Sale::find($sale->id)->trashed())->toBeFalse()
        ->and(snapshot($sale))->toEqual($before);
    expect(Sale::find($sale->id)->status)->toBe($status);
})->with('sale_statuses');

test('customers and guests cannot delete sales either', function () {
    $sale = protectedSale('completed');
    $customer = User::factory()->create(['role' => 'customer']);
    $before = snapshot($sale);

    $asCustomer = $this->actingAs($customer)->delete('/sales/' . $sale->id);
    expect($asCustomer->getStatusCode())->toBeIn([403, 404, 405]);

    auth()->logout();
    $guest = $this->delete('/sales/' . $sale->id);

    expect($guest->getStatusCode())->toBeIn([302, 401, 403, 404, 405])
        ->and(snapshot($sale))->toEqual($before);
});

test('the DELETE route no longer exists and the route name is gone', function () {
    expect(Route::has('sales.destroy'))->toBeFalse();

    $deleteRoutes = collect(Route::getRoutes()->getRoutes())->filter(
        fn ($r) => in_array('DELETE', $r->methods(), true) && str_starts_with($r->uri(), 'sales')
    );
    expect($deleteRoutes)->toBeEmpty();
});

test('defense in depth: even if a DELETE route is re-added the controller refuses and nothing changes', function (string $status) {
    $owner = User::factory()->create(['role' => 'owner']);
    $sale = protectedSale($status);
    $before = snapshot($sale);

    // Simulate a future developer re-adding the route.
    Route::middleware(['web', 'auth', 'owner'])
        ->delete('/_probe/sales/{id}', [SaleController::class, 'destroy']);

    $response = $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->delete('/_probe/sales/' . $sale->id);

    $response->assertStatus(405);
    expect(snapshot($sale))->toEqual($before)
        ->and(Sale::find($sale->id)->trashed())->toBeFalse();
})->with('sale_statuses');

test('the Sale policy denies deletion for every role and status', function (string $status) {
    $policy = new SalePolicy();
    $sale = protectedSale($status);

    foreach (['owner', 'customer'] as $role) {
        expect($policy->delete(User::factory()->create(['role' => $role]), $sale))->toBeFalse();
    }
})->with('sale_statuses');

test('rejected deletion leaves sales history totals and owner view intact', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $sale = protectedSale('completed');

    $this->actingAs($owner)->withSession(['otp_verified' => true])->delete('/sales/' . $sale->id);

    $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->get('/sales/history')
        ->assertOk()
        ->assertViewHas('totalSales', fn ($v) => (float) $v === 100.0);
});
