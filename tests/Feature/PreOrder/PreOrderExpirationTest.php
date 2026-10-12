<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('stocking pre-order durably sets ready_notified_at, 24h pickup deadline, and sends notification', function () {
    Notification::fake();

    $owner    = User::factory()->create(['role' => 'owner']);
    $user     = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create([
        'user_id' => $user->id,
        'name'    => $user->name,
        'email'   => $user->email,
    ]);
    $item     = InventoryItem::create(['part_name' => 'Chain Sprocket', 'price' => 800, 'quantity' => 5]);

    $preorder = PreOrder::create([
        'customer_id'       => $customer->id,
        'inventory_item_id' => $item->id,
        'customer_name'     => $customer->name,
        'part_name'         => 'Chain Sprocket',
        'quantity'          => 2,
        'price'             => 800,
        'total'             => 1600,
        'status'            => 'pending',
        'is_paid'           => false,
        'is_stocked'        => false,
    ]);

    $response = $this->actingAs($owner)->post(route('preorders.stock', $preorder));
    $response->assertRedirect();

    $fresh = $preorder->fresh();
    expect($fresh->is_stocked)->toBeTrue();
    expect($fresh->ready_notified_at)->not->toBeNull();
    expect($fresh->pickup_expires_at)->not->toBeNull();
    expect($fresh->pickup_expires_at->gt(now()->addHours(23)))->toBeTrue();
    expect($item->fresh()->quantity)->toBe(3); // 5 - 2

    Notification::assertSentTo($user, StatusUpdated::class, function ($n) {
        return stripos($n->title, 'Ready for Pickup') !== false;
    });
});

test('preorders:expire command expires overdue preorders and safely restores catalog stock', function () {
    Notification::fake();

    $user     = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create([
        'user_id' => $user->id,
        'name'    => $user->name,
        'email'   => $user->email,
    ]);
    $item     = InventoryItem::create(['part_name' => 'Spark Plug', 'price' => 150, 'quantity' => 10]);

    // Preorder stocked 25 hours ago, pickup expired 1 hour ago
    $preorder = PreOrder::create([
        'customer_id'       => $customer->id,
        'inventory_item_id' => $item->id,
        'customer_name'     => $customer->name,
        'part_name'         => 'Spark Plug',
        'quantity'          => 4,
        'price'             => 150,
        'total'             => 600,
        'status'            => 'arrived',
        'is_paid'           => false,
        'is_stocked'        => true,
        'ready_notified_at' => now()->subHours(25),
        'pickup_expires_at' => now()->subHour(),
    ]);

    $this->artisan('preorders:expire')
        ->expectsOutputToContain('Expired 1 pre-orders.')
        ->assertSuccessful();

    $fresh = $preorder->fresh();
    expect($fresh->status)->toBe('cancelled');
    expect($fresh->is_stocked)->toBeFalse();
    expect($item->fresh()->quantity)->toBe(14); // 10 + 4 restored
});

test('custom preorders without inventory_item_id do not attempt to restore nonexistent inventory on expire', function () {
    Notification::fake();

    $user     = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create([
        'user_id' => $user->id,
        'name'    => $user->name,
        'email'   => $user->email,
    ]);

    $initialCount = InventoryItem::count();

    $customPreorder = PreOrder::create([
        'customer_id'       => $customer->id,
        'inventory_item_id' => null,
        'customer_name'     => $customer->name,
        'part_name'         => 'Custom Rare Exhaust',
        'quantity'          => 1,
        'price'             => 3500,
        'total'             => 3500,
        'status'            => 'arrived',
        'is_paid'           => false,
        'is_stocked'        => true,
        'ready_notified_at' => now()->subHours(25),
        'pickup_expires_at' => now()->subHour(),
    ]);

    $this->artisan('preorders:expire')->assertSuccessful();

    $fresh = $customPreorder->fresh();
    expect($fresh->status)->toBe('cancelled');
    expect($fresh->is_stocked)->toBeFalse();
    expect(InventoryItem::count())->toBe($initialCount);
});

test('resending readiness notification preserves established 24-hour pickup deadline without resetting', function () {
    Notification::fake();

    $owner    = User::factory()->create(['role' => 'owner']);
    $user     = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create([
        'user_id' => $user->id,
        'name'    => $user->name,
        'email'   => $user->email,
    ]);

    $originalDeadline = now()->addHours(12); // Already 12 hours into its 24h window
    $originalNotified = now()->subHours(12);

    $preorder = PreOrder::create([
        'customer_id'       => $customer->id,
        'inventory_item_id' => null,
        'customer_name'     => $customer->name,
        'part_name'         => 'Clutch Cable',
        'quantity'          => 1,
        'price'             => 250,
        'total'             => 250,
        'status'            => 'pending',
        'is_paid'           => false,
        'is_stocked'        => true,
        'ready_notified_at' => $originalNotified,
        'pickup_expires_at' => $originalDeadline,
    ]);

    $response = $this->actingAs($owner)->post(route('preorders.notify-ready', $preorder));
    $response->assertRedirect();
    $response->assertSessionHas('success', 'Readiness notification resent. Existing 24-hour pickup deadline preserved.');

    $fresh = $preorder->fresh();
    // Deadline must not have been reset to now() + 24 hours
    expect($fresh->pickup_expires_at->toDateTimeString())->toBe($originalDeadline->toDateTimeString());
    expect($fresh->ready_notified_at->toDateTimeString())->toBe($originalNotified->toDateTimeString());

    Notification::assertSentTo($user, StatusUpdated::class);
});

