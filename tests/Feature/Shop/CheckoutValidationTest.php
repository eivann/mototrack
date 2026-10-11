<?php

use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    Session::start();
    config(['app.debug' => false]);
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
    $response = $this->actingAs($user)->withSession(['otp_verified' => true])->post('/checkout', []);

    // Assert: Redirects backward with the specific stock issue error
    $response->assertRedirect();
    $response->assertSessionHas('error', 'Checkout failed. Your cart has not been charged. Please try again.');

    // Assert: Database Transaction successfully rolled back
    expect(Sale::count())->toBe(0); // No sale was persisted
    expect($item->fresh()->quantity)->toBe(1); // Stock was not decremented
});

test('checkout rejects invalid negative quantities or string IDs', function () {
    $user = User::factory()->create();

    Session::put('cart', [
        'invalid_id' => [
            'quantity' => -5,
        ]
    ]);

    $response = $this->actingAs($user)->withSession(['otp_verified' => true])->post('/checkout', []);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Checkout failed. Your cart has not been charged. Please try again.');
    expect(Sale::count())->toBe(0);
});
