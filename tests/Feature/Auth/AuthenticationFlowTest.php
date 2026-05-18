<?php

use App\Models\User;

test('guest users are redirected to login when accessing dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('standard authenticated users are redirected to customer dashboard', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('customer.dashboard'));
});
