<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Delivers in-app notifications synchronously and reports what was durably recorded.
 *
 * - The database row is written inside the request, so it never depends on a
 *   queue worker being alive.
 * - Each recipient is isolated: a failure (e.g. realtime broadcast down) for one
 *   user never prevents the others from being notified.
 * - The return value counts recipients whose database notification was written,
 *   so callers can start a deadline only after durable recording.
 */
class NotificationDispatcher
{
    /**
     * @param  User|iterable<User>|null  $recipients
     * @return int Number of recipients with a persisted notification row.
     */
    public function send($recipients, StatusUpdated $template): int
    {
        if ($recipients === null) {
            return 0;
        }

        $recipients = $recipients instanceof User ? [$recipients] : $recipients;
        $recorded = 0;

        foreach ($recipients as $user) {
            $notification = clone $template;
            $notification->id = (string) Str::uuid();

            // 1) Durable in-app record. If this succeeds the notification exists.
            try {
                $user->notifyNow($notification, ['database']);
                $recorded++;
            } catch (\Throwable $e) {
                Log::warning("Notification '{$template->title}' could not be recorded for user {$user->id}: " . $e->getMessage());
                continue;
            }

            // 2) Optional realtime push. Best effort: never affects the record above.
            if (in_array('broadcast', $notification->via($user), true)) {
                try {
                    $user->notifyNow($notification, ['broadcast']);
                } catch (\Throwable $e) {
                    Log::notice("Realtime broadcast failed for user {$user->id} (in-app record kept): " . $e->getMessage());
                }
            }
        }

        return $recorded;
    }
}
