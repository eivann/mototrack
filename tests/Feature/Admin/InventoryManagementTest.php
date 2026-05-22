<?php

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owners can create new inventory items', function () {
    // Arrange: Create a user with the 'owner' role
    $owner = User::factory()->create(['role' => 'owner']);

    // Act: Attempt to create a new part via the admin endpoint
    $response = $this->actingAs($owner)->post('/inventory', [
        'category' => 'Brakes',
        'part_name' => 'Brembo Brake Pads',
        'brand' => 'Brembo',
        'model' => 'M50',
        'cost_price' => 1500,
        'price' => 2500,
        'quantity' => 10,
        'minimum_stock' => 2,
    ]);

    // Assert: Handled successfully (usually redirects back to index)
    $response->assertRedirect('/inventory');
    
    // Assert: Database correctly saved the new item
    $this->assertDatabaseHas('inventory_items', [
        'part_name' => 'Brembo Brake Pads',
        'price' => 2500,
        'quantity' => 10,
    ]);
});

test('non-owners are blocked from creating inventory', function () {
    // Arrange: Create a standard customer user
    $user = User::factory()->create(['role' => 'customer']);

    // Act: Attempt to create a new part
    $response = $this->actingAs($user)->post('/inventory', [
        'part_name' => 'Brembo Brake Pads',
        'price' => 2500,
        'quantity' => 10,
    ]);

    // Assert: Access is forbidden (403) by the OwnerMiddleware
    $response->assertStatus(403);
});

test('owners can view the low stock dashboard', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $response = $this->actingAs($owner)->get('/low-stock');

    $response->assertStatus(200);
});
