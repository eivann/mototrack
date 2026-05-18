<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Create a dummy route protected by the owner middleware
    Route::get('/_test_owner_protected', function () {
        return 'owner access granted';
    })->middleware(['web', 'auth', 'owner']);
});

test('it blocks non-owners with 403 unauthorized', function () {
    // Arrange: Create a standard user (not an owner)
    $user = User::factory()->create(['role' => 'customer']);

    // Act: Attempt to access the protected route
    $response = $this->actingAs($user)->get('/_test_owner_protected');

    // Assert: Must return 403 Forbidden
    $response->assertStatus(403);
});

test('it allows owners to access protected routes', function () {
    // Arrange: Create an owner user
    $owner = User::factory()->create(['role' => 'owner']);

    // Act: Attempt to access the protected route
    $response = $this->actingAs($owner)->get('/_test_owner_protected');

    // Assert: Must return 200 OK and the success string
    $response->assertOk();
    $response->assertSee('owner access granted');
});
