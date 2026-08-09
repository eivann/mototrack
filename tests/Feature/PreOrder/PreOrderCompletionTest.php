<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function makePreOrderCustomer(): Customer
{
    $user = User::factory()->create(['role' => 'customer']);

    return Customer::create([
        'user_id' => $user->id,
        'name'    => $user->name,
        'email'   => $user->email,
    ]);
}

test('completing a linked pre-order restocks the referenced inventory item', function () {
    Notification::fake();

    $owner    = User::factory()->create(['role' => 'owner']);
    $customer = makePreOrderCustomer();
    $item     = InventoryItem::create(['part_name' => 'Brake Pad', 'price' => 500, 'quantity' => 4]);

    $preorder = PreOrder::create([
        'customer_id'       => $customer->id,
        'inventory_item_id' => $item->id,
        'customer_name'     => $customer->name,
        'part_name'         => 'Brake Pad',
        'quantity'          => 3,
        'price'             => 500,
        'total'             => 1500,
        'status'            => 'pending',
        'is_paid'           => true,
    ]);

    $response = $this->actingAs($owner)->post(route('preorders.complete', $preorder));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($item->fresh()->quantity)->toBe(7);
    expect($preorder->fresh()->status)->toBe('completed');
});

test('completing a legacy pre-order restocks via part_name and model match', function () {
    Notification::fake();

    $owner    = User::factory()->create(['role' => 'owner']);
    $customer = makePreOrderCustomer();
    $item     = InventoryItem::create([
        'part_name' => 'Clutch Cable',
        'model'     => 'Yamaha R1',
        'price'     => 300,
        'quantity'  => 2,
    ]);

    // No inventory_item_id — simulates a pre-order created before the FK existed.
    $preorder = PreOrder::create([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'part_name'     => 'Clutch Cable',
        'model'         => 'Yamaha R1',
        'quantity'      => 5,
        'price'         => 300,
        'total'         => 1500,
        'status'        => 'pending',
        'is_paid'       => true,
    ]);

    $response = $this->actingAs($owner)->post(route('preorders.complete', $preorder));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($item->fresh()->quantity)->toBe(7);
});

test('completing a custom pre-order with no matching item warns and leaves stock untouched', function () {
    Notification::fake();

    $owner    = User::factory()->create(['role' => 'owner']);
    $customer = makePreOrderCustomer();
    $item     = InventoryItem::create(['part_name' => 'Air Filter', 'price' => 250, 'quantity' => 6]);

    // Custom/sourced part: no FK and a part_name that matches nothing in the catalog.
    $preorder = PreOrder::create([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'part_name'     => 'Custom Fairing Kit',
        'quantity'      => 2,
        'price'         => 8000,
        'total'         => 16000,
        'status'        => 'pending',
        'is_paid'       => true,
    ]);

    $response = $this->actingAs($owner)->post(route('preorders.complete', $preorder));

    $response->assertRedirect();
    $response->assertSessionHas('warning');
    expect($item->fresh()->quantity)->toBe(6); // untouched
    expect($preorder->fresh()->status)->toBe('completed');
});

test('deleting a referenced inventory item nulls the pre-order foreign key', function () {
    $customer = makePreOrderCustomer();
    $item     = InventoryItem::create(['part_name' => 'Spark Plug', 'price' => 150, 'quantity' => 10]);

    $preorder = PreOrder::create([
        'customer_id'       => $customer->id,
        'inventory_item_id' => $item->id,
        'customer_name'     => $customer->name,
        'part_name'         => 'Spark Plug',
        'quantity'          => 1,
        'price'             => 150,
        'total'             => 150,
        'status'            => 'pending',
        'is_paid'           => true,
    ]);

    $item->delete();

    expect($preorder->fresh()->inventory_item_id)->toBeNull();
});
