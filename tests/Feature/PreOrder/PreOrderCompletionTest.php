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

test('allocating stock to a pre-order decrements catalog inventory', function () {
    Notification::fake();

    $owner    = User::factory()->create(['role' => 'owner']);
    $customer = makePreOrderCustomer();
    $item     = InventoryItem::create(['part_name' => 'Brake Pad', 'price' => 500, 'quantity' => 10]);

    $preorder = PreOrder::create([
        'customer_id'       => $customer->id,
        'inventory_item_id' => $item->id,
        'customer_name'     => $customer->name,
        'part_name'         => 'Brake Pad',
        'quantity'          => 3,
        'price'             => 500,
        'total'             => 1500,
        'status'            => 'pending',
        'is_paid'           => false,
        'is_stocked'        => false,
    ]);

    $response = $this->actingAs($owner)->post(route('preorders.stock', $preorder));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($item->fresh()->quantity)->toBe(7); // 10 - 3
    expect($preorder->fresh()->is_stocked)->toBeTrue();
});

test('cancelling a stocked pre-order restores the inventory', function () {
    Notification::fake();

    $owner    = User::factory()->create(['role' => 'owner']);
    $customer = makePreOrderCustomer();
    $item     = InventoryItem::create(['part_name' => 'Brake Pad', 'price' => 500, 'quantity' => 7]);

    $preorder = PreOrder::create([
        'customer_id'       => $customer->id,
        'inventory_item_id' => $item->id,
        'customer_name'     => $customer->name,
        'part_name'         => 'Brake Pad',
        'quantity'          => 3,
        'price'             => 500,
        'total'             => 1500,
        'status'            => 'pending',
        'is_paid'           => false,
        'is_stocked'        => true, // Already stocked
    ]);

    $response = $this->actingAs($owner)->post(route('preorders.cancel', $preorder));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($item->fresh()->quantity)->toBe(10); // Restored
    expect($preorder->fresh()->status)->toBe('cancelled');
});

test('completing a pre-order requires it to be paid and stocked', function () {
    Notification::fake();

    $owner    = User::factory()->create(['role' => 'owner']);
    $customer = makePreOrderCustomer();

    $preorder = PreOrder::create([
        'customer_id'       => $customer->id,
        'customer_name'     => $customer->name,
        'part_name'         => 'Brake Pad',
        'quantity'          => 3,
        'price'             => 500,
        'total'             => 1500,
        'status'            => 'pending',
        'is_paid'           => false, // NOT PAID
        'is_stocked'        => false, // NOT STOCKED
    ]);

    $response = $this->actingAs($owner)->post(route('preorders.complete', $preorder));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($preorder->fresh()->status)->toBe('pending');
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
