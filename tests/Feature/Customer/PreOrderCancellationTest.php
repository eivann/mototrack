<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function makeStoreCustomer(string $name = 'Test Rider'): array
{
    $user = User::factory()->create(['role' => 'customer', 'name' => $name]);
    $customer = Customer::create([
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ]);

    return [$user, $customer];
}

function makeStockedItem(string $name, float $price, int $qty): InventoryItem
{
    return InventoryItem::create([
        'part_name' => $name,
        'sku' => strtoupper(str_replace(' ', '-', $name)),
        'price' => $price,
        'quantity' => $qty,
    ]);
}

// ── Customer cancellation of unstocked sourcing requests ────

test('customer can cancel an unstocked sourcing pre-order without moving any stock', function () {
    Notification::fake();

    [$user, $customer] = makeStoreCustomer();
    $item = makeStockedItem('Rare Exhaust', 3500, 10);

    $preorder = PreOrder::create([
        'customer_id' => $customer->id,
        'inventory_item_id' => null,
        'customer_name' => $customer->name,
        'part_name' => 'Custom Rare Exhaust',
        'quantity' => 1,
        'price' => 0,
        'total' => 0,
        'status' => 'pending',
        'is_paid' => false,
        'is_stocked' => false,
    ]);

    $this->actingAs($user)
        ->post(route('customer.preorder.cancel', $preorder->id))
        ->assertRedirect();

    $preorder->refresh();
    expect($preorder->status)->toBe('cancelled');
    expect($item->fresh()->quantity)->toBe(10); // untouched
});

test('customer can cancel a stocked catalog-linked pre-order and allocated stock is restored exactly once', function () {
    Notification::fake();

    [$user, $customer] = makeStoreCustomer();
    $item = makeStockedItem('Clutch Cable', 250, 10);

    $preorder = PreOrder::create([
        'customer_id' => $customer->id,
        'inventory_item_id' => $item->id,
        'customer_name' => $customer->name,
        'part_name' => 'Clutch Cable',
        'quantity' => 3,
        'price' => 250,
        'total' => 750,
        'status' => 'arrived',
        'is_paid' => false,
        'is_stocked' => true,
    ]);

    $item->update(['quantity' => 7]); // already decremented when it was stocked

    $this->actingAs($user)
        ->post(route('customer.preorder.cancel', $preorder->id))
        ->assertRedirect();

    $preorder->refresh();
    expect($preorder->status)->toBe('cancelled');
    expect($preorder->is_stocked)->toBeFalse();
    expect($item->fresh()->quantity)->toBe(10); // 7 + 3
});

test('repeated pre-order cancellation does not restore stock twice', function () {
    Notification::fake();

    [$user, $customer] = makeStoreCustomer();
    $item = makeStockedItem('Clutch Cable', 250, 10);

    $preorder = PreOrder::create([
        'customer_id' => $customer->id,
        'inventory_item_id' => $item->id,
        'customer_name' => $customer->name,
        'part_name' => 'Clutch Cable',
        'quantity' => 2,
        'price' => 250,
        'total' => 500,
        'status' => 'pending',
        'is_paid' => false,
        'is_stocked' => true,
    ]);

    $item->update(['quantity' => 8]);

    $this->actingAs($user)->post(route('customer.preorder.cancel', $preorder->id))->assertRedirect();
    expect($item->fresh()->quantity)->toBe(10);

    $this->actingAs($user)->post(route('customer.preorder.cancel', $preorder->id));
    expect($item->fresh()->quantity)->toBe(10);
});

test('custom physical arrival without a linked catalog item does not invent or restore stock', function () {
    Notification::fake();

    [$user, $customer] = makeStoreCustomer();
    $item = makeStockedItem('Clutch Cable', 250, 10);

    $preorder = PreOrder::create([
        'customer_id' => $customer->id,
        'inventory_item_id' => null, // custom part, no catalog row
        'customer_name' => $customer->name,
        'part_name' => 'Custom Intake Manifold',
        'quantity' => 1,
        'price' => 2000,
        'total' => 2000,
        'status' => 'pending',
        'is_paid' => false,
        'is_stocked' => true, // arrived and marked stocked without a catalog decrement
    ]);

    $this->actingAs($user)
        ->post(route('customer.preorder.cancel', $preorder->id))
        ->assertRedirect();

    $preorder->refresh();
    expect($preorder->status)->toBe('cancelled');
    expect(InventoryItem::count())->toBe(1);
    expect($item->fresh()->quantity)->toBe(10);
});

test('another customer cannot cancel a pre-order they do not own', function () {
    Notification::fake();

    [, $ownerCustomer] = makeStoreCustomer('Owner Rider');
    [$otherUser] = makeStoreCustomer('Other Rider');

    $preorder = PreOrder::create([
        'customer_id' => $ownerCustomer->id,
        'customer_name' => $ownerCustomer->name,
        'part_name' => 'Brake Pad',
        'quantity' => 1,
        'price' => 300,
        'total' => 300,
        'status' => 'pending',
        'is_paid' => false,
        'is_stocked' => false,
    ]);

    $this->actingAs($otherUser)
        ->post(route('customer.preorder.cancel', $preorder->id))
        ->assertForbidden();

    expect($preorder->fresh()->status)->toBe('pending');
});

test('paid pre-order cancellation is rejected and touches no stock', function () {
    Notification::fake();

    [$user, $customer] = makeStoreCustomer();
    $item = makeStockedItem('Clutch Cable', 250, 10);

    $preorder = PreOrder::create([
        'customer_id' => $customer->id,
        'inventory_item_id' => $item->id,
        'customer_name' => $customer->name,
        'part_name' => 'Clutch Cable',
        'quantity' => 1,
        'price' => 250,
        'total' => 250,
        'status' => 'pending',
        'is_paid' => true, // already paid — refunds are in-store only
        'is_stocked' => false,
    ]);

    $this->actingAs($user)->post(route('customer.preorder.cancel', $preorder->id));

    expect($preorder->fresh()->status)->toBe('pending');
    expect($item->fresh()->quantity)->toBe(10);
});

test('completed pre-order cancellation is rejected', function () {
    Notification::fake();

    [$user, $customer] = makeStoreCustomer();

    $preorder = PreOrder::create([
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'part_name' => 'Brake Pad',
        'quantity' => 1,
        'price' => 300,
        'total' => 300,
        'status' => 'completed',
        'is_paid' => true,
        'is_stocked' => false,
    ]);

    $this->actingAs($user)->post(route('customer.preorder.cancel', $preorder->id));

    expect($preorder->fresh()->status)->toBe('completed');
});

test('pre-order cancellation notifies the customer and the owner', function () {
    Notification::fake();

    [$user, $customer] = makeStoreCustomer();
    $owner = User::factory()->create(['role' => 'owner']);

    $preorder = PreOrder::create([
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'part_name' => 'Brake Pad',
        'quantity' => 1,
        'price' => 300,
        'total' => 300,
        'status' => 'pending',
        'is_paid' => false,
        'is_stocked' => false,
    ]);

    $this->actingAs($user)
        ->post(route('customer.preorder.cancel', $preorder->id))
        ->assertRedirect();

    Notification::assertSentTo($user, StatusUpdated::class);
    Notification::assertSentTo($owner, StatusUpdated::class);
});