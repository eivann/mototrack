<?php

use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('unpaid reservations are strictly excluded from collected revenue', function () {
    $item = InventoryItem::create([
        'part_name' => 'Brake Fluid',
        'category' => 'Fluids',
        'brand' => 'Brembo',
        'model' => 'DOT 4',
        'quantity' => 10,
        'price' => 450.00,
    ]);

    // Unpaid reservation
    $reservation = Sale::create([
        'inventory_item_id' => $item->id,
        'quantity' => 2,
        'price' => 450.00,
        'total_price' => 900.00,
        'amount_tendered' => 0.00,
        'change' => 0.00,
        'status' => 'pending',
        'reserved_at' => now(),
        'expires_at' => now()->addHours(24),
        'payment_confirmed_at' => null,
    ]);

    // Query matching DashboardController revenue logic
    $todayRevenue = Sale::whereDate('payment_confirmed_at', now()->today())
        ->where(function($q) { $q->where('status', 'completed')->orWhere('amount_tendered', '>', 0); })
        ->where('status', '!=', OrderStatus::SALE_REFUNDED)
        ->whereNotNull('payment_confirmed_at')
        ->sum('total_price');

    expect((float)$todayRevenue)->toBe(0.00);
});

test('paid sales and completed reservations are counted in revenue while refunded sales are excluded', function () {
    $item = InventoryItem::create([
        'part_name' => 'Chain',
        'category' => 'Drive',
        'brand' => 'DID',
        'model' => '520',
        'quantity' => 10,
        'price' => 1500.00,
    ]);

    // Completed cash sale: 1500
    Sale::create([
        'inventory_item_id' => $item->id,
        'quantity' => 1,
        'price' => 1500.00,
        'total_price' => 1500.00,
        'amount_tendered' => 1500.00,
        'change' => 0.00,
        'status' => 'completed',
        'payment_confirmed_at' => now(),
    ]);

    // Picked-up reservation: 3000
    Sale::create([
        'inventory_item_id' => $item->id,
        'quantity' => 2,
        'price' => 1500.00,
        'total_price' => 3000.00,
        'amount_tendered' => 3000.00,
        'change' => 0.00,
        'status' => 'completed',
        'reserved_at' => now()->subHours(2),
        'picked_up_at' => now(),
        'payment_confirmed_at' => now(),
    ]);

    // Refunded sale: 1500 (Must be excluded)
    Sale::create([
        'inventory_item_id' => $item->id,
        'quantity' => 1,
        'price' => 1500.00,
        'total_price' => 1500.00,
        'amount_tendered' => 1500.00,
        'change' => 0.00,
        'status' => OrderStatus::SALE_REFUNDED,
        'payment_confirmed_at' => now(),
    ]);

    $todayRevenue = Sale::whereDate('payment_confirmed_at', now()->today())
        ->where(function($q) { $q->where('status', 'completed')->orWhere('amount_tendered', '>', 0); })
        ->where('status', '!=', OrderStatus::SALE_REFUNDED)
        ->whereNotNull('payment_confirmed_at')
        ->sum('total_price');

    // Expected: 1500 + 3000 = 4500.00 (refunded 1500 excluded)
    expect((float)$todayRevenue)->toBe(4500.00);
});
