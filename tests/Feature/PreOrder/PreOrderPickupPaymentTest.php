<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function pickupOrder(array $overrides = [], int $stockAfterAllocation = 3): PreOrder
{
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    // Stock already reflects the allocation made when the part was marked arrived.
    $item = InventoryItem::create(['part_name' => 'Pickup Part', 'sku' => 'PP' . uniqid(), 'price' => 500, 'quantity' => $stockAfterAllocation]);

    return PreOrder::create(array_merge([
        'customer_id' => $customer->id, 'inventory_item_id' => $item->id, 'customer_name' => $customer->name,
        'part_name' => 'Pickup Part', 'quantity' => 2, 'price' => 500, 'total' => 1000,
        'status' => 'pending', 'is_paid' => false, 'is_stocked' => true,
        'sourced_at' => now()->subDay(), 'ready_notified_at' => now()->subHour(), 'pickup_expires_at' => now()->addHours(23),
    ], $overrides));
}

function ownerPost($test, PreOrder $order, float $cash)
{
    return $test->actingAs(User::query()->where('role', 'owner')->first() ?? User::factory()->create(['role' => 'owner']))
        ->post(route('preorders.pickup', $order), ['amount_tendered' => $cash]);
}

beforeEach(fn () => Notification::fake());

test('one cash action records the sale and completes the pre-order without touching stock', function () {
    $order = pickupOrder();

    ownerPost($this, $order, 1200)->assertSessionHas('success');

    $fresh = $order->fresh();
    expect($fresh->status)->toBe('completed')->and($fresh->is_paid)->toBeTrue()->and($fresh->picked_up_at)->not->toBeNull();
    expect($fresh->inventoryItem->quantity)->toBe(3); // no second deduction

    $sales = Sale::where('customer_id', $order->customer_id)->get();
    expect($sales)->toHaveCount(1);
    expect((float) $sales[0]->total_price)->toBe(1000.0)->and((float) $sales[0]->change)->toBe(200.0)->and($sales[0]->status)->toBe('completed');
    expect($sales[0]->payment_confirmed_at)->not->toBeNull();
});

test('insufficient cash records nothing', function () {
    $order = pickupOrder();

    ownerPost($this, $order, 999)->assertSessionHas('error');

    expect($order->fresh()->status)->toBe('pending')->and($order->fresh()->is_paid)->toBeFalse();
    expect(Sale::count())->toBe(0);
});

test('rapid double submit creates exactly one sale', function () {
    $order = pickupOrder();

    ownerPost($this, $order, 1000)->assertSessionHas('success');
    ownerPost($this, $order, 1000)->assertSessionHas('warning')->assertSessionMissing('success');

    expect(Sale::count())->toBe(1);
    expect($order->fresh()->inventoryItem->quantity)->toBe(3);
});

test('a stale second tab loaded before the first pickup cannot charge again', function () {
    $order = pickupOrder();
    $tabA = PreOrder::find($order->id); // both tabs hold the same pending record
    $tabB = PreOrder::find($order->id);

    ownerPost($this, $tabA, 1000);
    ownerPost($this, $tabB, 5000)->assertSessionMissing('success');

    expect(Sale::count())->toBe(1);
    expect((float) Sale::first()->amount_tendered)->toBe(1000.0);
});

test('a paid-but-uncollected pre-order completes without a second charge', function () {
    $order = pickupOrder(['is_paid' => true]);
    Sale::create(['inventory_item_id' => $order->inventory_item_id, 'quantity' => 2, 'price' => 500, 'total_price' => 1000,
        'amount_tendered' => 1000, 'change' => 0, 'status' => 'completed', 'customer_id' => $order->customer_id,
        'payment_confirmed_at' => now()->subHours(3), 'transaction_reference' => 'PRE-LEGACY01']);

    ownerPost($this, $order, 0)->assertSessionHas('success');

    expect($order->fresh()->status)->toBe('completed')->and($order->fresh()->picked_up_at)->not->toBeNull();
    expect(Sale::count())->toBe(1); // still only the original sale
});

test('pickup is refused before the part has arrived', function () {
    $order = pickupOrder(['is_stocked' => false, 'ready_notified_at' => null, 'pickup_expires_at' => null]);

    ownerPost($this, $order, 1000)->assertSessionHas('error');

    expect(Sale::count())->toBe(0)->and($order->fresh()->status)->toBe('pending');
});

test('pickup is refused after the 24-hour deadline even before the scheduler runs', function () {
    $order = pickupOrder(['pickup_expires_at' => now()->subMinute()]);

    ownerPost($this, $order, 1000)->assertSessionHas('error');

    expect(Sale::count())->toBe(0)->and($order->fresh()->status)->toBe('pending');
});

test('cancelled and expired pre-orders cannot be picked up', function () {
    foreach (['cancelled', 'expired'] as $status) {
        $order = pickupOrder(['status' => $status, 'is_stocked' => false]);
        ownerPost($this, $order, 1000)->assertSessionHas('error');
        expect($order->fresh()->status)->toBe($status);
    }
    expect(Sale::count())->toBe(0);
});

test('expiry after pickup does not restore stock or touch the completed order', function () {
    $order = pickupOrder(['pickup_expires_at' => now()->addMinute()]);
    ownerPost($this, $order, 1000)->assertSessionHas('success');

    $this->travel(2)->minutes();
    $this->artisan('preorders:expire')->assertSuccessful();

    expect($order->fresh()->status)->toBe('completed');
    expect($order->fresh()->inventoryItem->quantity)->toBe(3); // not restored
    expect(Sale::count())->toBe(1);
});

test('pickup after expiry runs cannot sell stock that was returned to the shelf', function () {
    $order = pickupOrder(['pickup_expires_at' => now()->addMinute()]);

    $this->travel(2)->minutes();
    $this->artisan('preorders:expire')->assertSuccessful();
    expect($order->fresh()->status)->toBe('expired');
    expect($order->fresh()->inventoryItem->quantity)->toBe(5); // restored once

    ownerPost($this, $order, 1000)->assertSessionHas('error');

    expect(Sale::count())->toBe(0)->and($order->fresh()->inventoryItem->quantity)->toBe(5);
});

test('customers cannot use the pickup action', function () {
    $order = pickupOrder();
    $customerUser = $order->customer->user;

    $this->actingAs($customerUser)->post(route('preorders.pickup', $order), ['amount_tendered' => 1000])->assertForbidden();

    expect(Sale::count())->toBe(0);
});

test('the legacy pay then complete path still cannot produce a second sale after pickup', function () {
    $order = pickupOrder();
    ownerPost($this, $order, 1000);

    $owner = User::where('role', 'owner')->first();
    $this->actingAs($owner)->post(route('preorders.pay', $order), ['amount_tendered' => 1000])->assertSessionHas('error');

    expect(Sale::count())->toBe(1);
});

test('the owner pre-order list offers the pickup modal with total due and cash input', function () {
    $order = pickupOrder();
    $owner = User::factory()->create(['role' => 'owner']);

    $html = $this->actingAs($owner)->get(route('preorders.index'))->getContent();

    expect($html)->toContain(route('preorders.pickup', $order))
        ->and($html)->toContain('name="amount_tendered"')
        ->and($html)->toContain('Total due')
        ->and($html)->toContain('Change');
});

test('an already-paid pre-order shows no cash field and states no extra charge', function () {
    $order = pickupOrder(['is_paid' => true]);
    $owner = User::factory()->create(['role' => 'owner']);

    $html = $this->actingAs($owner)->get(route('preorders.index'))->getContent();

    expect($html)->toContain('No additional cash will be collected')
        ->and($html)->not->toContain('name="amount_tendered"');
});
