<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_notifications_center()
    {
        $response = $this->get(route('notifications.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_notifications_center()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('notifications.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
        $response->assertSee('No notifications yet');
    }

    public function test_notifications_are_paginated()
    {
        $user = User::factory()->create();

        // Create 20 mock notifications manually to bypass any job dependencies
        for ($i = 0; $i < 20; $i++) {
            DatabaseNotification::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\Notifications\OrderUpdate',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => [
                    'title' => "Notification $i",
                    'message' => 'Test message',
                    'link' => '#'
                ],
                'read_at' => null,
                'created_at' => now()->subMinutes($i),
                'updated_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertStatus(200);
        // Should only show 15 per page (as defined in controller)
        $response->assertSee('Notification 0');
        $response->assertDontSee('Notification 19'); // On page 2

        $notifications = $response->viewData('notifications');
        $this->assertSame(15, $notifications->perPage());
        $this->assertSame(20, $notifications->total());
    }
}
