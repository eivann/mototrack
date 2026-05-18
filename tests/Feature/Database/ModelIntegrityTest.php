<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\Sale;
use App\Models\User;

test('Sale model correctly mass-assigns and casts numerical values', function () {
    // Arrange: Create dependencies
    $user = User::factory()->create();
    $customer = Customer::create([
        'user_id' => $user->id,
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);

    $item = InventoryItem::create([
        'part_name' => 'Brake Pad',
        'price' => 1000.00,
        'quantity' => 10,
    ]);

    $trxRef = 'TRX-' . uniqid();

    // Act: Mass assign
    $sale = Sale::create([
        'inventory_item_id' => $item->id,
        'customer_id' => $customer->id,
        'quantity' => 2,
        'price' => 1000.00,
        'total_price' => 2000.00,
        'transaction_reference' => $trxRef,
    ]);

    // Assert: Database persistence and type casting
    expect($sale->id)->not->toBeNull()
        ->and($sale->total_price)->toEqual(2000.00)
        ->and($sale->transaction_reference)->toBe($trxRef);
});

test('PreOrder model correctly mass-assigns and casts boolean is_paid', function () {
    // Arrange
    $user = User::factory()->create();
    $customer = Customer::create([
        'user_id' => $user->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com'
    ]);

    $orderId = 'PRE-' . uniqid();

    // Act
    $preorder = PreOrder::create([
        'customer_id' => $customer->id,
        'customer_name' => 'Jane Doe',
        'part_name' => 'Exhaust Pipe',
        'model' => 'Yamaha R1',
        'quantity' => 1,
        'price' => 5000.00,
        'total' => 5000.00,
        'down_payment' => 500.00,
        'status' => 'Pending',
        'is_paid' => false,
    ]);

    // Assert
    expect($preorder->id)->not->toBeNull()
        ->and($preorder->is_paid)->toBeFalse() // Verifies the boolean cast works on SQLite
        ->and($preorder->down_payment)->toEqual(500.00);

    // Update state to paid
    $preorder->is_paid = true;
    $preorder->save();
    
    $preorder->refresh();
    expect($preorder->is_paid)->toBeTrue();
});
