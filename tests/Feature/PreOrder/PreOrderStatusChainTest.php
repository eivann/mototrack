<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function chainCustomer(): array
{
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

    return [$user, $customer];
}

function chainPreOrder(array $overrides = [], int $stock = 0): PreOrder
{
    [, $customer] = chainCustomer();
    $item = InventoryItem::create(['part_name' => 'Chain Kit', 'sku' => 'CK' . uniqid(), 'price' => 800, 'quantity' => $stock]);

    return PreOrder::create(array_merge([
        'customer_id' => $customer->id,
        'inventory_item_id' => $item->id,
        'customer_name' => $customer->name,
        'part_name' => 'Chain Kit',
        'quantity' => 2,
        'price' => 800,
        'total' => 1600,
        'status' => 'pending',
        'is_paid' => false,
        'is_stocked' => false,
    ], $overrides));
}

test('sourced_at migration is additive and nullable, and legacy rows keep working', function () {
    expect(Schema::hasColumn('pre_orders', 'sourced_at'))->toBeTrue();

    $legacy = chainPreOrder();
    expect($legacy->fresh()->sourced_at)->toBeNull();
    expect($legacy->fresh()->status)->toBe('pending');
    expect($legacy->fresh()->stage()['key'])->toBe('pending_sourcing');
});

test('stage is derived from existing columns without rewriting statuses', function () {
    expect(chainPreOrder()->stage()['label'])->toBe('Pending Sourcing');
    expect(chainPreOrder(['sourced_at' => now()])->stage()['label'])->toBe('Sourced / Awaiting Arrival');
    expect(chainPreOrder(['is_stocked' => true], 5)->stage()['label'])->toBe('Ready for Pickup');
    // A legacy stocked row with no sourced_at is still Ready for Pickup.
    expect(chainPreOrder(['is_stocked' => true, 'sourced_at' => null])->stage()['key'])->toBe('ready');
    expect(chainPreOrder(['status' => 'completed'])->stage()['label'])->toBe('Completed');
    expect(chainPreOrder(['status' => 'cancelled'])->stage()['label'])->toBe('Cancelled');
    expect(chainPreOrder(['status' => 'expired'])->stage()['label'])->toBe('Pickup expired');
});

test('mark sourced stamps the milestone and moves no stock or status', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $order = chainPreOrder([], 4);

    $this->actingAs($owner)->post(route('preorders.source', $order))->assertSessionHas('success');

    $fresh = $order->fresh();
    expect($fresh->sourced_at)->not->toBeNull();
    expect($fresh->status)->toBe('pending');
    expect($fresh->is_stocked)->toBeFalse();
    expect($fresh->inventoryItem->quantity)->toBe(4);
    expect($fresh->stage()['key'])->toBe('awaiting_arrival');
});

test('marking sourced twice is rejected and keeps the first timestamp', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $order = chainPreOrder();

    $this->actingAs($owner)->post(route('preorders.source', $order));
    $first = $order->fresh()->sourced_at;
    $this->travel(5)->minutes();
    $this->actingAs($owner)->post(route('preorders.source', $order))->assertSessionHas('error');

    expect($order->fresh()->sourced_at->toDateTimeString())->toBe($first->toDateTimeString());
});

test('stocked, cancelled and completed pre-orders cannot be marked sourced', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    foreach ([['is_stocked' => true], ['status' => 'cancelled'], ['status' => 'completed']] as $state) {
        $order = chainPreOrder($state, 5);
        $this->actingAs($owner)->post(route('preorders.source', $order))->assertSessionHas('error');
        expect($order->fresh()->sourced_at)->toBeNull();
    }
});

test('a legacy pre-order with NULL sourced_at can be marked arrived directly and gets the milestone', function () {
    Notification::fake();
    $owner = User::factory()->create(['role' => 'owner']);
    $order = chainPreOrder([], 6);

    $this->actingAs($owner)->post(route('preorders.stock', $order))->assertSessionHas('success');

    $fresh = $order->fresh();
    expect($fresh->is_stocked)->toBeTrue();
    expect($fresh->sourced_at)->not->toBeNull();
    expect($fresh->inventoryItem->quantity)->toBe(4); // allocated once
    expect($fresh->pickup_expires_at)->not->toBeNull(); // 24h deadline starts at the notice
    expect($fresh->stage()['key'])->toBe('ready');
});

test('marking arrived twice never deducts stock twice', function () {
    Notification::fake();
    $owner = User::factory()->create(['role' => 'owner']);
    $order = chainPreOrder([], 6);

    $this->actingAs($owner)->post(route('preorders.stock', $order));
    $this->actingAs($owner)->post(route('preorders.stock', $order))->assertSessionHas('error');

    expect($order->fresh()->inventoryItem->quantity)->toBe(4);
});

test('owner list shows the new stage labels and labelled actions', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    chainPreOrder();
    chainPreOrder(['sourced_at' => now()]);
    chainPreOrder(['is_stocked' => true, 'ready_notified_at' => now(), 'pickup_expires_at' => now()->addHours(5)], 5);

    $html = $this->actingAs($owner)->get(route('preorders.index'))->assertOk()->getContent();

    expect($html)->toContain('Pending Sourcing')
        ->and($html)->toContain('Sourced / Awaiting Arrival')
        ->and($html)->toContain('Ready for Pickup')
        ->and($html)->toContain('Mark sourced')
        ->and($html)->toContain('Mark arrived &amp; notify')
        ->and($html)->toContain('Confirm pickup &amp; payment')
        ->and($html)->toContain('>Cancel<')
        ->and($html)->not->toContain('Collect Payment'); // the separate pay-then-complete icons are gone
});

test('customer pre-order list shows the status chain and no down payment text', function () {
    [$user, $customer] = chainCustomer();
    PreOrder::create([
        'customer_id' => $customer->id, 'customer_name' => $customer->name, 'part_name' => 'Fork Seal',
        'quantity' => 1, 'price' => 300, 'total' => 300, 'status' => 'pending', 'is_paid' => false,
        'is_stocked' => false, 'sourced_at' => now(), 'down_payment' => 0,
    ]);

    $html = $this->actingAs($user)->get(route('customer.dashboard'))->assertOk()->getContent();

    expect($html)->toContain('Sourced / Awaiting Arrival')
        ->and($html)->toContain('Pending Sourcing')
        ->and($html)->toContain('Ready for Pickup')
        ->and($html)->toContain('Cash payment at pickup')
        ->and($html)->not->toContain('Paid DP');
});
