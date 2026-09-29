<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class StatusUpdated extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $title;
    protected $message;
    protected $link;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $link = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->link = $link;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link ?? '/customer/dashboard',
            'created_at' => now()->diffForHumans(),
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link ?? '/customer/dashboard',
        ];
    }
}
