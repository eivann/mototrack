<?php

use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Support\Facades\Notification;

test('users can mark their own notifications as read via api', function () {
    // Arrange
    $user = User::factory()->create();
    
    // Create a dummy notification
    $user->notify(new StatusUpdated('Test Title', 'Test Message', '/'));
    
    $notificationId = $user->unreadNotifications->first()->id;

    // Act
    $response = $this->actingAs($user)->postJson("/notifications/{$notificationId}/read");

    // Assert
    $response->assertRedirect(); // Asserts a 302 redirect to back()
    expect($user->fresh()->unreadNotifications->count())->toBe(0);
});

test('users can mark all notifications as read via web endpoint', function () {
    // Arrange
    $user = User::factory()->create();
    
    // Create multiple dummy notifications
    $user->notify(new StatusUpdated('Test 1', 'Message 1', '/'));
    $user->notify(new StatusUpdated('Test 2', 'Message 2', '/'));

    expect($user->unreadNotifications->count())->toBe(2);

    // Act
    $response = $this->actingAs($user)->post("/notifications/read-all");

    // Assert
    $response->assertRedirect(); // Asserts a 302 redirect to back()
    expect($user->fresh()->unreadNotifications->count())->toBe(0);
});
