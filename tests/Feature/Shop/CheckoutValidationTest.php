<?php

use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    Session::start();
});

test('checkout rolls back database transaction if item is out of stock', function () {
    $user = User::factory()->create();

    // Arrange: Create item with only 1 in stock
    $item = InventoryItem::create([
        'part_name' => 'Limited Edition Exhaust',
        'price' => 3000.00,
        'quantity' => 1,
    ]);

    // Arrange: Simulate user added 2 to cart earlier when stock was 2
    Session::put('cart', [
        $item->id => [
            'part_name' => 'Limited Edition Exhaust',
            'model' => 'Akrapovic',
            'price' => 3000.00,
            'quantity' => 2,
        ]
    ]);

    // Act: Attempt checkout
    $response = $this->actingAs($user)->post('/checkout', [
        'payment_method' => 'gcash',
    ]);

    // Assert: Redirects backward with the specific stock issue error
    $response->assertRedirect();
    $response->assertSessionHas('error', 'Stock issue detected for: Limited Edition Exhaust');

    // Assert: Database Transaction successfully rolled back
    expect(Sale::count())->toBe(0); // No sale was persisted
    expect($item->fresh()->quantity)->toBe(1); // Stock was not decremented
});

test('checkout preserves order and redirects to receipt if gateway crashes', function () {
    $user = User::factory()->create();

    $item = InventoryItem::create([
        'part_name' => 'Side Mirror',
        'price' => 500.00,
        'quantity' => 10,
    ]);

    Session::put('cart', [
        $item->id => [
            'part_name' => 'Side Mirror',
            'model' => 'Generic',
            'price' => 500.00,
            'quantity' => 2,
        ]
    ]);

    // Arrange: Mock Payment Gateway to Throw Exception (Simulating Server Down)
    $this->mock(PaymentService::class, function (MockInterface $mock) {
        $mock->shouldReceive('createInvoice')
             ->once()
             ->andThrow(new \Exception('Xendit Connection Timeout 504'));
    });

    // Act
    $response = $this->actingAs($user)->post('/checkout', [
        'payment_method' => 'gcash',
    ]);

    // Assert: Handled gracefully without 500 error screen
    $response->assertRedirect('/checkout/receipt');
    $response->assertSessionHas('warning');
    $warningMsg = session('warning');
    expect($warningMsg)->toContain('has been saved, but the payment gateway is temporarily unavailable');

    // Assert: Verify intentional design - Order IS saved even if gateway fails
    expect(Sale::count())->toBe(1);
    expect($item->fresh()->quantity)->toBe(8); // Stock successfully decremented
});
