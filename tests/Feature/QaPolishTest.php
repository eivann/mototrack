<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\User;

test('browse keeps category and sort filters together', function () {
    InventoryItem::create([
        'category' => 'Brakes',
        'part_name' => 'Brake Pad',
        'brand' => 'Honda',
        'model' => 'Click',
        'quantity' => 5,
        'price' => 250,
    ]);

    InventoryItem::create([
        'category' => 'Engine',
        'part_name' => 'Oil Filter',
        'brand' => 'Yamaha',
        'model' => 'Mio',
        'quantity' => 5,
        'price' => 100,
    ]);

    $response = $this->get(route('browse', [
        'category' => 'Brakes',
        'sort' => 'high_low',
    ]));

    $response->assertOk();
    $response->assertSee('Brake Pad');
    $response->assertDontSee('Oil Filter');
    $response->assertSee('value="high_low" selected', false);
    $response->assertSee('value="Brakes" selected', false);
});

test('inventory suggestions are scoped to the requested field', function () {
    InventoryItem::create([
        'category' => 'Brakes',
        'part_name' => 'Brake Pad',
        'brand' => 'Honda',
        'model' => 'Click',
        'quantity' => 5,
        'price' => 250,
    ]);

    InventoryItem::create([
        'category' => 'Bearings',
        'part_name' => 'Wheel Bearing',
        'brand' => 'Koyo',
        'model' => 'Universal',
        'quantity' => 5,
        'price' => 180,
    ]);

    $response = $this->getJson(route('inventory.suggestions', [
        'field' => 'category',
        'q' => 'Bra',
    ]));

    $response->assertOk();
    expect($response->json())->toContain([
        'value' => 'Brakes',
        'label' => 'Brakes',
        'field' => 'category',
    ]);

    expect(collect($response->json())->pluck('value'))->not->toContain('Brake Pad');
});

test('customer login ignores cart intended url and lands on customer dashboard', function () {
    $user = User::factory()->create(['role' => 'customer']);
    Customer::create([
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ]);

    $response = $this
        ->withSession(['url.intended' => url('/cart')])
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $response->assertRedirect('/otp/verify');
});

test('customer login can still continue to checkout when that was intentional', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $response = $this
        ->withSession(['url.intended' => url('/checkout')])
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $response->assertRedirect('/otp/verify');
});

test('qa affected pages render for guest customer and owner roles', function () {
    InventoryItem::create([
        'category' => 'Brakes',
        'part_name' => 'Brake Pad',
        'brand' => 'Honda',
        'model' => 'Click',
        'quantity' => 5,
        'price' => 250,
    ]);

    $owner = User::factory()->create(['role' => 'owner']);
    $customer = User::factory()->create(['role' => 'customer']);
    Customer::create([
        'user_id' => $customer->id,
        'name' => $customer->name,
        'email' => $customer->email,
    ]);

    $this->get('/')->assertOk();
    $this->get(route('browse', ['category' => 'Brakes', 'sort' => 'high_low']))->assertOk();


    $this->actingAs($customer)->get(route('customer.dashboard'))->assertOk();
    $this->actingAs($owner)->get(route('dashboard'))->assertOk();
    $this->actingAs($owner)->get(route('inventory.index'))->assertOk();
});

test('pos search prompt uses manual search wording instead of scanner language', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $response = $this->actingAs($owner)->get(route('sales.index'));

    $response->assertOk();
    $response->assertSee('Search products manually by SKU, part name, brand, or model...');
    $response->assertDontSee('barcode', false);
    $response->assertDontSee('scan', false);
});

test('compatibility page can be rendered by guest and authenticated users with typed text inputs', function () {
    $response = $this->get(route('compatibility'));
    $response->assertOk();
    $response->assertSee('Compatibility Checker');
    $response->assertSee('input type="text" id="makeInput"', false);
    $response->assertSee('input type="text" id="modelInput"', false);
    $response->assertSee('input type="text" id="yearInput"', false);
});

test('browse page uses typed text input for model and does not have sticky filter panel', function () {
    $response = $this->get(route('browse'));
    $response->assertOk();
    $response->assertSee('id="filter-model"', false);
    $response->assertSee('name="model"', false);
    $response->assertDontSee('id="filter-panel" class="lg:col-span-3 lg:sticky', false);
});

test('inventory item resolves catalog image url to public images', function () {
    $item = new InventoryItem([
        'part_name' => 'Brake Cable',
        'image_path' => 'products/imageforBrake Cable - Takasago - STX.jpg',
    ]);

    expect($item->image_url)->toContain('images/products/imageforBrake Cable - Takasago - STX.jpg');
});

