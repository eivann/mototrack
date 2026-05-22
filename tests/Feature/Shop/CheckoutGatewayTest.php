<?php

use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\StatusUpdated;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    Session::start();
    Notification::fake();
});

test('checkout rejects empty carts and redirects backward', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/checkout', [
        'payment_method' => 'gcash',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Cart is empty!');
});

test('checkout processes multi-item cart, locks stock, and assigns tendered amount only to first item', function () {
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

    // Arrange: Mock GCash Gateway
    $this->mock(PaymentService::class, function (MockInterface $mock) {
        $mock->shouldReceive('createInvoice')
             ->once()
             ->andReturn(['invoice_url' => 'https://mock-xendit.com/gcash-pay']);
    });

    // Act
    $response = $this->actingAs($user)->post('/checkout', [
        'payment_method' => 'gcash',
        'amount_tendered' => 5000.00 // User pays 5000 for a 4000 cart (Change: 1000)
    ]);

    // Assert: Redirects to Mock Gateway
    $response->assertRedirect('https://mock-xendit.com/gcash-pay');

    // Assert: Database State
    expect($item1->fresh()->quantity)->toBe(8); // 10 - 2
    expect($item2->fresh()->quantity)->toBe(4); // 5 - 1
    
    $sales = Sale::orderBy('id', 'asc')->get();
    expect($sales)->toHaveCount(2);

    // Verify intentional multi-item logic: First item gets the tendered amount and change
    $firstSale = $sales->firstWhere('inventory_item_id', $item1->id);
    expect($firstSale->total_price)->toEqual(2000.00)
        ->and($firstSale->amount_tendered)->toEqual(5000.00)
        // FIX VERIFIED: Change is correctly calculated against the final grand total (4000)
        ->and($firstSale->change)->toEqual(1000.00);

    // Second item gets 0 for tendered and change
    $secondSale = $sales->firstWhere('inventory_item_id', $item2->id);
    expect($secondSale->total_price)->toEqual(2000.00)
        ->and($secondSale->amount_tendered)->toEqual(0.00)
        ->and($secondSale->change)->toEqual(0.00);

    // Assert: Notifications
    Notification::assertSentTo($user, StatusUpdated::class);
    Notification::assertNotSentTo($owner, StatusUpdated::class); // Deferred for online payment
    
    // Assert: Session cleared
    expect(Session::get('cart'))->toBeNull();
});

test('checkout processes rcpay integration successfully without amount_tendered provided', function () {
    $user = User::factory()->create();
    $item = InventoryItem::create(['part_name' => 'Gloves', 'price' => 500.00, 'quantity' => 5]);

    Session::put('cart', [
        $item->id => ['part_name' => 'Gloves', 'model' => 'Fox', 'price' => 500.00, 'quantity' => 1]
    ]);

    // Mock RCPAY
    $this->mock(PaymentService::class, function (MockInterface $mock) {
        $mock->shouldReceive('createRcpayUrl')
             ->once()
             ->andReturn('https://mock-rcpay.com/pay');
    });

    // Act: No amount_tendered provided
    $response = $this->actingAs($user)->post('/checkout', [
        'payment_method' => 'rcpay',
        'bank_code' => 'QRPH'
    ]);

    // Assert
    $response->assertRedirect('https://mock-rcpay.com/pay');
    
    // Fallback logic: amount_tendered defaults to grandTotal if null
    $sale = Sale::first();
    expect($sale->amount_tendered)->toEqual(500.00)
        ->and($sale->change)->toEqual(0.00);
});
