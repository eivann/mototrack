<?php

use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

beforeEach(function () {
    Session::start();
    Notification::fake();
});

test('checkout rejects empty carts and redirects backward', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/checkout', []);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Cart is empty!');
});

test('checkout processes multi-item cart as pending reservation without payment', function () {
    $user = User::factory()->create(['name' => 'Jane Rider']);
    $owner = User::factory()->create(['role' => 'owner']);

    // Arrange: Create inventory
    $item1 = InventoryItem::create(['part_name' => 'Tire', 'price' => 1000.00, 'quantity' => 10]);
    $item2 = InventoryItem::create(['part_name' => 'Helmet', 'price' => 2000.00, 'quantity' => 5]);

    // Arrange: Mock Session Cart
    Session::put('cart', [
        $item1->id => ['part_name' => 'Tire', 'model' => 'Michelin', 'price' => 1000.00, 'quantity' => 2], // Total: 2000
        $item2->id => ['part_name' => 'Helmet', 'model' => 'AGV', 'price' => 2000.00, 'quantity' => 1], // Total: 2000
    ]); // Grand Total: 4000

    // Act
    $response = $this->actingAs($user)->withSession(['otp_verified' => true])->post('/checkout', []);

    // Assert: Redirects to receipt
    $response->assertRedirect('/checkout/receipt');

    // Assert: Database State
    expect($item1->fresh()->quantity)->toBe(8); // 10 - 2
    expect($item2->fresh()->quantity)->toBe(4); // 5 - 1
    
    $sales = Sale::orderBy('id', 'asc')->get();
    expect($sales)->toHaveCount(2);

    $firstSale = $sales->firstWhere('inventory_item_id', $item1->id);
    expect($firstSale->total_price)->toEqual(2000.00)
        ->and($firstSale->amount_tendered)->toEqual(0.00)
        ->and($firstSale->change)->toEqual(0.00)
        ->and($firstSale->status)->toEqual('pending');
    
    $this->assertNotNull($firstSale->reserved_at);
    $this->assertNotNull($firstSale->expires_at);

    // Assert: Notifications
    Notification::assertSentTo($user, StatusUpdated::class);
    
    // Assert: Session cleared
    expect(Session::get('cart'))->toBeNull();
});


