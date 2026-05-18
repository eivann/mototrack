<?php

use App\Models\InventoryItem;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

// Strictly isolate the database per test
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cartService = new CartService();
    Session::start(); // Ensure a clean session for every isolated test
});

// 1. Add Item to Cart & Session Persistence
test('cart service can add items and persist to session', function () {
    $item = InventoryItem::create([
        'part_name' => 'Chain Lube',
        'price' => 250.00,
        'quantity' => 10,
    ]);

    $this->cartService->addItem($item->id, 2);

    $cart = Session::get('cart');
    expect($cart)->toHaveKey($item->id)
        ->and($cart[$item->id]['quantity'])->toBe(2)
        ->and($cart[$item->id]['part_name'])->toBe('Chain Lube');
});

// 2. Duplicate Item Merge Behavior
test('cart service aggregates quantity if same item added twice', function () {
    $item = InventoryItem::create([
        'part_name' => 'Helmet Visor',
        'price' => 800.00,
        'quantity' => 5,
    ]);

    $this->cartService->addItem($item->id, 2);
    $this->cartService->addItem($item->id, 1);

    $cart = $this->cartService->getCart();
    expect($cart[$item->id]['quantity'])->toBe(3);
});

// 3. Stock Limit Enforcement
test('cart service blocks adding more than available stock on existing items', function () {
    $item = InventoryItem::create([
        'part_name' => 'Exhaust Pipe',
        'price' => 5000.00,
        'quantity' => 2,
    ]);

    // Add once to trigger the existing constraint behavior
    $this->cartService->addItem($item->id, 1);
    
    expect(fn () => $this->cartService->addItem($item->id, 2))
        ->toThrow(\Exception::class, 'Cannot add more. Stock limit reached.');
});

// 4. Update Quantity
test('cart service can update the quantity of an existing item', function () {
    $item = InventoryItem::create([
        'part_name' => 'Spark Plug',
        'price' => 100.00,
        'quantity' => 10,
    ]);

    $this->cartService->addItem($item->id, 2);
    $this->cartService->updateQuantity($item->id, 5);

    $cart = $this->cartService->getCart();
    expect($cart[$item->id]['quantity'])->toBe(5);
});

// 5. Update Quantity - Stock Limit Enforcement
test('cart service blocks updating quantity beyond available stock', function () {
    $item = InventoryItem::create([
        'part_name' => 'Air Filter',
        'price' => 200.00,
        'quantity' => 3,
    ]);

    $this->cartService->addItem($item->id, 1);

    expect(fn () => $this->cartService->updateQuantity($item->id, 5))
        ->toThrow(\Exception::class, 'Cannot exceed available stock for Air Filter');
});

// 6. Invalid Quantity Handling (Negative or Zero)
test('cart service forcefully defaults invalid quantities to 1', function () {
    $item = InventoryItem::create([
        'part_name' => 'Grip Tape',
        'price' => 50.00,
        'quantity' => 10,
    ]);

    // Adding negative quantity
    $this->cartService->addItem($item->id, -5);
    $cart = $this->cartService->getCart();
    expect($cart[$item->id]['quantity'])->toBe(1); // Asserts max(1, $quantity) logic

    // Updating to zero
    $this->cartService->updateQuantity($item->id, 0);
    $cart = $this->cartService->getCart();
    expect($cart[$item->id]['quantity'])->toBe(1); // Asserts max(1, $quantity) logic
});

// 7. Remove Item
test('cart service can remove a specific item', function () {
    $item1 = InventoryItem::create(['part_name' => 'Item A', 'price' => 10, 'quantity' => 5]);
    $item2 = InventoryItem::create(['part_name' => 'Item B', 'price' => 20, 'quantity' => 5]);

    $this->cartService->addItem($item1->id, 1);
    $this->cartService->addItem($item2->id, 1);

    $this->cartService->removeItem($item1->id);

    $cart = $this->cartService->getCart();
    expect($cart)->not->toHaveKey($item1->id)
        ->and($cart)->toHaveKey($item2->id);
});

// 8. Clear Cart
test('cart service can completely clear the cart', function () {
    $item = InventoryItem::create(['part_name' => 'Tire', 'price' => 1000, 'quantity' => 5]);
    
    $this->cartService->addItem($item->id, 2);
    expect($this->cartService->getCart())->not->toBeEmpty();

    $this->cartService->clear();
    expect($this->cartService->getCart())->toBeEmpty();
});
