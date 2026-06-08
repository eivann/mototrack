<?php

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\DamagedItem;
use App\Models\ProductReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\CheckLowStockAlertJob;

uses(RefreshDatabase::class);

test('owner can access the adjustments page', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $response = $this->actingAs($owner)->get('/inventory/adjustments');
    $response->assertStatus(200);
});

test('non-owner cannot access adjustments page', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $response = $this->actingAs($user)->get('/inventory/adjustments');
    $response->assertStatus(403);
});

test('owner can record damaged items which decrements quantity', function () {
    Queue::fake();

    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create([
        'part_name' => 'Brake Pad',
        'price' => 500.00,
        'quantity' => 10,
        'low_stock_threshold' => 5,
        'low_stock_notified' => false,
    ]);

    $response = $this->actingAs($owner)->post('/inventory/adjustments/damaged', [
        'inventory_item_id' => $item->id,
        'quantity'          => 3,
        'notes'             => 'Cracked coating'
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('damaged_items', [
        'inventory_item_id' => $item->id,
        'quantity'          => 3,
        'notes'             => 'Cracked coating'
    ]);

    expect($item->fresh()->quantity)->toBe(7);
    Queue::assertPushed(CheckLowStockAlertJob::class);
});

test('owner cannot record damaged items with quantity exceeding stock', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create([
        'part_name' => 'Spark Plug',
        'price' => 150.00,
        'quantity' => 2,
    ]);

    $response = $this->actingAs($owner)->post('/inventory/adjustments/damaged', [
        'inventory_item_id' => $item->id,
        'quantity'          => 3,
    ]);

    // Should fail with standard redirect back due to custom validation or exception
    $response->assertRedirect();
    $this->assertDatabaseMissing('damaged_items', [
        'inventory_item_id' => $item->id
    ]);
});

test('owner can record restocked product returns which increments quantity', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create([
        'part_name' => 'Clutch Cable',
        'price' => 300.00,
        'quantity' => 5,
    ]);

    $response = $this->actingAs($owner)->post('/inventory/adjustments/returns', [
        'inventory_item_id' => $item->id,
        'quantity'          => 2,
        'action_taken'      => 'restocked',
        'notes'             => 'Unused return'
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('product_returns', [
        'inventory_item_id' => $item->id,
        'quantity'          => 2,
        'action_taken'      => 'restocked'
    ]);

    expect($item->fresh()->quantity)->toBe(7);
});

test('owner can record discarded product returns which does not increment quantity', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create([
        'part_name' => 'Clutch Cable',
        'price' => 300.00,
        'quantity' => 5,
    ]);

    $response = $this->actingAs($owner)->post('/inventory/adjustments/returns', [
        'inventory_item_id' => $item->id,
        'quantity'          => 2,
        'action_taken'      => 'discarded',
        'notes'             => 'Opened package and dirty'
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('product_returns', [
        'inventory_item_id' => $item->id,
        'quantity'          => 2,
        'action_taken'      => 'discarded'
    ]);

    expect($item->fresh()->quantity)->toBe(5); // Remains unchanged
});
