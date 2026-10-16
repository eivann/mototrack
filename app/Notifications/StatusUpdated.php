<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class StatusUpdated extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $link;

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
        // Delivered synchronously (not queued) so the in-app record exists as soon as
        // the request finishes, with no dependency on a queue worker. Realtime
        // broadcast is added only when a real broadcaster is configured.
        $channels = ['database'];

        if (! in_array(config('broadcasting.default'), [null, 'null', 'log'], true)) {
            $channels[] = 'broadcast';
        }

        return $channels;
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
