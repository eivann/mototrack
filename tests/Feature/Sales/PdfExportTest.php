<?php

use App\Models\Sale;
use App\Models\User;
use App\Models\InventoryItem;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated owner can download a pdf receipt', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    
    $item = InventoryItem::create([
        'part_name' => 'Tire',
        'price' => 1500,
        'quantity' => 10,
    ]);

    $sale = Sale::create([
        'inventory_item_id' => $item->id,
        'quantity' => 1,
        'price' => 1500,
        'total_price' => 1500,
        'transaction_reference' => 'TRX-123456',
    ]);

    $response = $this->actingAs($owner)->get("/sales/TRX-123456/download");
    
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
    $this->assertStringContainsString('attachment; filename=Receipt_TRX-123456.pdf', $response->headers->get('content-disposition'));
});

test('unauthenticated user cannot download pdf receipt', function () {
    $response = $this->get("/sales/TRX-123456/download");
    $response->assertRedirect('/login');
});

test('customer cannot download another customers pdf receipt via checkout route', function () {
    $customerUser = User::factory()->create(['role' => 'customer']);
    $otherCustomerUser = User::factory()->create(['role' => 'customer']);
    
    $customer1 = Customer::create(['user_id' => $customerUser->id, 'name' => 'Test 1', 'email' => 't1@test.com', 'phone' => '123']);
    $customer2 = Customer::create(['user_id' => $otherCustomerUser->id, 'name' => 'Test 2', 'email' => 't2@test.com', 'phone' => '123']);

    $item = InventoryItem::create(['part_name' => 'Tire', 'price' => 1500, 'quantity' => 10]);

    Sale::create([
        'inventory_item_id' => $item->id,
        'customer_id' => $customer1->id,
        'quantity' => 1,
        'price' => 1500,
        'total_price' => 1500,
        'transaction_reference' => 'TRX-999',
    ]);

    $response = $this->actingAs($otherCustomerUser)->get("/checkout/receipt/TRX-999/download");
    $response->assertStatus(404);
});

test('customer can download their own pdf receipt via checkout route', function () {
    $customerUser = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $customerUser->id, 'name' => 'Test 1', 'email' => 't1@test.com', 'phone' => '123']);
    
    $item = InventoryItem::create(['part_name' => 'Tire', 'price' => 1500, 'quantity' => 10]);

    Sale::create([
        'inventory_item_id' => $item->id,
        'customer_id' => $customer->id,
        'quantity' => 1,
        'price' => 1500,
        'total_price' => 1500,
        'transaction_reference' => 'TRX-888',
    ]);

    $response = $this->actingAs($customerUser)->get("/checkout/receipt/TRX-888/download");
    $response->assertStatus(200);
});

test('downloading non-existent receipt returns 404', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $response = $this->actingAs($owner)->get("/sales/TRX-NON-EXISTENT/download");
    $response->assertStatus(404);
});
