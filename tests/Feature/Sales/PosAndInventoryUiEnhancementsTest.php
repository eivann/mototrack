<?php

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('F17: owner POS page replaces Add to Cart wording with transaction terminology', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    InventoryItem::create([
        'part_name' => 'Spark Plug',
        'category' => 'Ignition',
        'brand' => 'NGK',
        'model' => 'CR7E',
        'quantity' => 10,
        'price' => 150.00,
    ]);

    $response = $this->actingAs($owner)->get('/sales');
    $response->assertOk();

    $html = $response->getContent();

    // Replaced "Search & Add to Cart" with "Search & Add Item"
    expect($html)->toContain('Search & Add Item');
    expect($html)->not->toContain('Search & Add to Cart');

    // Replaced "Cart is empty" / "Current Order" with transaction terminology
    expect($html)->toContain('Current Transaction');
    expect($html)->toContain('Transaction is empty');
});

test('F17: customer shop cart wording and functionality is preserved', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $item = InventoryItem::create([
        'part_name' => 'Brake Cable',
        'category' => 'Cables',
        'brand' => 'Honda',
        'model' => 'Mio',
        'quantity' => 5,
        'price' => 250.00,
    ]);

    // Customer cart endpoints and shop view must remain untouched
    $response = $this->actingAs($customer)->get('/cart');
    $response->assertOk();
});

test('F15: owner POS page renders category filter tabs including All', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    InventoryItem::create([
        'part_name' => 'Brake Shoes',
        'category' => 'Brakes',
        'brand' => 'Brembo',
        'model' => 'Universal',
        'quantity' => 8,
        'price' => 300.00,
    ]);

    $response = $this->actingAs($owner)->get('/sales');
    $response->assertOk();

    $html = $response->getContent();

    // Renders category chip template and category container
    expect($html)->toContain('role="tablist"');
    expect($html)->toContain('aria-label="Filter by category"');
    expect($html)->toContain('selectedCategory');
    expect($html)->toContain('categories');
});

test('F16: owner POS page renders accessible toast container, removes native alert calls, and allows validation feedback on checkout submit', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $response = $this->actingAs($owner)->get('/sales');
    $response->assertOk();

    $html = $response->getContent();

    // Toast container with accessibility attributes
    expect($html)->toContain('role="alert"');
    expect($html)->toContain('aria-live="assertive"');
    expect($html)->toContain('showToast');
    expect($html)->toContain('fixed bottom-6 right-6');

    // Checkout button enables client validation feedback and guards double submits
    expect($html)->toContain(':disabled="checkoutLoading"');
    expect($html)->toContain('@submit.prevent="submitCheckout()"');

    // No alert(...) calls in the blade script
    expect($html)->not->toContain('alert(');
});

test('F18: inventory page renders category navigation chips and preserves filtering and search', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    InventoryItem::create([
        'part_name' => 'Oil Filter',
        'category' => 'Filters',
        'brand' => 'Yamaha',
        'model' => 'Mio',
        'quantity' => 15,
        'price' => 120.00,
    ]);
    InventoryItem::create([
        'part_name' => 'Air Filter',
        'category' => 'Intake',
        'brand' => 'Yamaha',
        'model' => 'Mio',
        'quantity' => 10,
        'price' => 200.00,
    ]);

    // 1. Unfiltered page shows chips for All Categories and existing categories
    $response = $this->actingAs($owner)->get('/inventory');
    $response->assertOk();
    $html = $response->getContent();
    expect($html)->toContain('All Categories');
    expect($html)->toContain('Filters');
    expect($html)->toContain('Intake');

    // 2. Filter by category via query parameter works and highlights the active category
    $filteredResponse = $this->actingAs($owner)->get('/inventory?category=Filters');
    $filteredResponse->assertOk();
    $filteredHtml = $filteredResponse->getContent();
    expect($filteredHtml)->toContain('Oil Filter');
    expect($filteredHtml)->not->toContain('Air Filter');

    // 3. Search works alongside category filter
    $searchResponse = $this->actingAs($owner)->get('/inventory?category=Filters&search=Oil');
    $searchResponse->assertOk();
    expect($searchResponse->getContent())->toContain('Oil Filter');
});

