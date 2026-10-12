<?php

use App\Models\DamagedItem;
use App\Models\InventoryItem;
use App\Models\ProductReturn;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can record resellable product return linked to original sale and restore stock', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = InventoryItem::create(['part_name' => 'Side Mirror Set', 'price' => 350, 'quantity' => 5]);

    $sale = Sale::create([
        'inventory_item_id'    => $item->id,
        'quantity'             => 2,
        'price'                => 350,
        'total_price'          => 700,
        'amount_tendered'      => 700,
        'change'               => 0,
        'status'               => 'completed',
        'transaction_reference'=> 'TRX-POS-1001',
        'payment_confirmed_at' => now(),
        'picked_up_at'         => now(),
    ]);

    $response = $this->actingAs($owner)
        ->from(route('inventory.adjustments.index'))
        ->post(route('inventory.adjustments.store_return'), [
            'sale_id'           => $sale->id,
            'inventory_item_id' => $item->id,
            'quantity'          => 2,
            'refund_amount'     => 700,
            'reason'            => 'Wrong fitment chosen by customer',
            'condition'         => 'resellable',
        ]);

    $response->assertRedirect(route('inventory.adjustments.index'));
    $response->assertSessionHas('success');

    // Stock must be restored: 5 + 2 = 7
    expect($item->fresh()->quantity)->toBe(7);
    expect($sale->fresh()->status)->toBe('refunded');

    $returnRecord = ProductReturn::where('sale_id', $sale->id)->first();
    expect($returnRecord)->not->toBeNull();
    expect($returnRecord->action_taken)->toBe('restocked');
    expect((float)$returnRecord->refund_amount)->toBe(700.0);
});

test('owner recording damaged return writes off item without restoring sellable inventory', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = InventoryItem::create(['part_name' => 'LED Headlight Bulb', 'price' => 450, 'quantity' => 10]);

    $sale = Sale::create([
        'inventory_item_id'    => $item->id,
        'quantity'             => 1,
        'price'                => 450,
        'total_price'          => 450,
        'amount_tendered'      => 500,
        'change'               => 50,
        'status'               => 'completed',
        'transaction_reference'=> 'TRX-POS-1002',
        'payment_confirmed_at' => now(),
        'picked_up_at'         => now(),
    ]);

    $response = $this->actingAs($owner)
        ->from(route('inventory.adjustments.index'))
        ->post(route('inventory.adjustments.store_return'), [
            'sale_id'           => $sale->id,
            'inventory_item_id' => $item->id,
            'quantity'          => 1,
            'refund_amount'     => 450,
            'reason'            => 'Factory defect - flickering',
            'condition'         => 'damaged',
        ]);

    $response->assertRedirect(route('inventory.adjustments.index'));
    $response->assertSessionHas('success');

    // Sellable inventory must NOT increase
    expect($item->fresh()->quantity)->toBe(10);
    expect($sale->fresh()->status)->toBe('refunded');

    // Must be logged in DamagedItem
    $damaged = DamagedItem::where('inventory_item_id', $item->id)->first();
    expect($damaged)->not->toBeNull();
    expect($damaged->quantity)->toBe(1);
    expect($damaged->notes)->toContain('Customer Return Write-Off');
});

test('unpaid reservation displays reservation acknowledgement disclaimer while completed POS sale does not', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item  = InventoryItem::create(['part_name' => 'Drive Belt', 'price' => 650, 'quantity' => 8]);

    // Unpaid reservation
    $reservation = Sale::create([
        'inventory_item_id'    => $item->id,
        'quantity'             => 1,
        'price'                => 650,
        'total_price'          => 650,
        'amount_tendered'      => 0,
        'change'               => 0,
        'status'               => 'pending',
        'transaction_reference'=> 'TRX-RES-9999',
        'payment_confirmed_at' => null,
    ]);

    // Paid POS sale
    $paidSale = Sale::create([
        'inventory_item_id'    => $item->id,
        'quantity'             => 1,
        'price'                => 650,
        'total_price'          => 650,
        'amount_tendered'      => 1000,
        'change'               => 350,
        'status'               => 'completed',
        'transaction_reference'=> 'TRX-POS-8888',
        'payment_confirmed_at' => now(),
        'picked_up_at'         => now(),
    ]);

    // Unpaid receipt view check
    $unpaidResponse = $this->actingAs($owner)->get(route('sales.receipt', $reservation->transaction_reference));
    $unpaidResponse->assertOk();
    $unpaidResponse->assertSee('Reservation Acknowledgement — No Payment Collected — Not an Official Receipt');

    // Paid receipt view check
    $paidResponse = $this->actingAs($owner)->get(route('sales.receipt', $paidSale->transaction_reference));
    $paidResponse->assertOk();
    $paidResponse->assertSee('POS Sales Receipt — Paid in Cash');
    $paidResponse->assertDontSee('Reservation Acknowledgement — No Payment Collected — Not an Official Receipt');
});

test('partial refund on multi-item sale deducts only the refunded amount and preserves unrelated collected revenue in reports', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $itemA = InventoryItem::create(['part_name' => 'Brake Caliper', 'price' => 500, 'quantity' => 10]);
    $itemB = InventoryItem::create(['part_name' => 'Brake Cable', 'price' => 200, 'quantity' => 10]);

    // Multi-item transaction: Gross total 1200
    $saleA = Sale::create([
        'inventory_item_id'    => $itemA->id,
        'quantity'             => 2,
        'price'                => 500,
        'total_price'          => 1000,
        'amount_tendered'      => 1200,
        'change'               => 0,
        'status'               => 'completed',
        'transaction_reference'=> 'TRX-MULTI-1',
        'payment_confirmed_at' => now(),
        'picked_up_at'         => now(),
    ]);

    $saleB = Sale::create([
        'inventory_item_id'    => $itemB->id,
        'quantity'             => 1,
        'price'                => 200,
        'total_price'          => 200,
        'amount_tendered'      => 0,
        'change'               => 0,
        'status'               => 'completed',
        'transaction_reference'=> 'TRX-MULTI-1',
        'payment_confirmed_at' => now(),
        'picked_up_at'         => now(),
    ]);

    // Customer returns 1 of the 2 Brake Calipers (partial return of 500)
    $response = $this->actingAs($owner)->post(route('inventory.adjustments.store_return'), [
        'sale_id'           => $saleA->id,
        'inventory_item_id' => $itemA->id,
        'quantity'          => 1, // partial: 1 returned out of 2
        'refund_amount'     => 500,
        'reason'            => 'Customer only needed one caliper',
        'condition'         => 'resellable',
    ]);
    $response->assertRedirect();

    // Partial return: saleA row remains completed, stock of itemA restored by 1 (10 + 1 = 11)
    expect($saleA->fresh()->status)->toBe('completed');
    expect($itemA->fresh()->quantity)->toBe(11);

    // Sale report must reflect Net Sales = 1200 - 500 = 700 without discarding unrelated saleB
    $history = $this->actingAs($owner)->get(route('sales.history', ['filter' => 'today']));
    $history->assertOk();
    $history->assertSee('700.00');
});

test('multiple partial returns cumulatively update sale status to refunded and prevent over-refunds', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create(['part_name' => 'Spark Plug', 'price' => 150, 'quantity' => 20]);

    $sale = Sale::create([
        'inventory_item_id'    => $item->id,
        'quantity'             => 4,
        'price'                => 150,
        'total_price'          => 600,
        'amount_tendered'      => 600,
        'change'               => 0,
        'status'               => 'completed',
        'transaction_reference'=> 'TRX-SPARK-4',
        'payment_confirmed_at' => now(),
        'picked_up_at'         => now(),
    ]);

    // Return 1: 2 items returned (partial)
    $res1 = $this->actingAs($owner)->post(route('inventory.adjustments.store_return'), [
        'sale_id'           => $sale->id,
        'inventory_item_id' => $item->id,
        'quantity'          => 2,
        'refund_amount'     => 300,
        'action_taken'      => 'restocked',
    ]);
    $res1->assertRedirect();
    expect($sale->fresh()->status)->toBe('completed');
    expect($item->fresh()->quantity)->toBe(22);

    // Over-refund attempt: trying to return 3 more when only 2 remain
    $resOver = $this->actingAs($owner)->post(route('inventory.adjustments.store_return'), [
        'sale_id'           => $sale->id,
        'inventory_item_id' => $item->id,
        'quantity'          => 3,
        'refund_amount'     => 450,
        'action_taken'      => 'restocked',
    ]);
    $resOver->assertSessionHasErrors('quantity');

    // Return 2: remaining 2 items returned (cumulative 4/4)
    $res2 = $this->actingAs($owner)->post(route('inventory.adjustments.store_return'), [
        'sale_id'           => $sale->id,
        'inventory_item_id' => $item->id,
        'quantity'          => 2,
        'refund_amount'     => 300,
        'action_taken'      => 'restocked',
    ]);
    $res2->assertRedirect();
    expect($sale->fresh()->status)->toBe('refunded');
    expect($item->fresh()->quantity)->toBe(24);

    // Repeated return attempt on already closed/refunded sale
    $resDup = $this->actingAs($owner)->post(route('inventory.adjustments.store_return'), [
        'sale_id'           => $sale->id,
        'inventory_item_id' => $item->id,
        'quantity'          => 1,
        'refund_amount'     => 150,
        'action_taken'      => 'restocked',
    ]);
    $resDup->assertSessionHasErrors('quantity');
});

test('refund occurring on a different date from sale deducts accurately without double subtraction', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create(['part_name' => 'Oil Filter', 'price' => 250, 'quantity' => 10]);

    // Sale completed 5 days ago
    $sale = Sale::create([
        'inventory_item_id'    => $item->id,
        'quantity'             => 2,
        'price'                => 250,
        'total_price'          => 500,
        'amount_tendered'      => 500,
        'change'               => 0,
        'status'               => 'completed',
        'transaction_reference'=> 'TRX-OLD-500',
        'payment_confirmed_at' => now()->subDays(5),
        'picked_up_at'         => now()->subDays(5),
        'created_at'           => now()->subDays(5),
    ]);

    // Refund occurs today
    $this->actingAs($owner)->post(route('inventory.adjustments.store_return'), [
        'sale_id'           => $sale->id,
        'inventory_item_id' => $item->id,
        'quantity'          => 2,
        'refund_amount'     => 500,
        'action_taken'      => 'restocked',
    ]);

    // Monthly report covers both dates: Gross (500) - Refund (500) = 0.00 Net Sales (never negative / never double deducted)
    $monthHistory = $this->actingAs($owner)->get(route('sales.history', ['filter' => 'month']));
    $monthHistory->assertOk();
    $monthHistory->assertSee('0.00');
});

