<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can access the main admin dashboard', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $response = $this->actingAs($owner)->get('/dashboard');

    $response->assertStatus(200);
    // Ensure it renders the admin dashboard view (assuming the view is 'admin.dashboard')
    $response->assertViewIs('admin.dashboard');
});

test('customer cannot access the admin dashboard and is redirected', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $response = $this->actingAs($customer)->get('/dashboard');

    // Customer should be redirected to their own dashboard
    $response->assertRedirect(route('customer.dashboard'));
});

test('guest is redirected to login when trying to access admin dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});
