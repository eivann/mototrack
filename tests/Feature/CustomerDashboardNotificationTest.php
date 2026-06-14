<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class CustomerDashboardNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_dismiss_notification_and_load_dashboard()
    {
        $this->withoutExceptionHandling();
        
        $user = User::factory()->create();
        Customer::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '1234567890',
            'address' => 'Test Address'
        ]);

        $notification = DatabaseNotification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\StatusUpdated',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [
                'title' => "Notification",
                'message' => 'Test message',
                'link' => '#'
            ],
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->post(route('notifications.read', $notification->id));
        $response->assertRedirect();

        $response2 = $this->actingAs($user)->get('/customer/dashboard');
        $response2->assertStatus(200);
    }
}
