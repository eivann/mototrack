<?php

namespace App\Services;

use App\Services\Sms\TwilioSmsGateway;
use App\Services\Sms\MoviderSmsGateway;
use App\Services\Sms\MockSmsGateway;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS using the active gateway driver
     *
     * @param string $message
     * @param string|null $recipientNumber
     * @param SmsGatewayInterface|null $gateway
     * @return bool
     */
    public static function send($message, $recipientNumber = null, ?SmsGatewayInterface $gateway = null)
    {
        $gateway = $gateway ?: app(SmsGatewayInterface::class);

        // Determine destination number
        $number = $recipientNumber;

        // If no recipient number was passed, fall back to configured default numbers based on gateway type
        if (!$number) {
            if ($gateway instanceof TwilioSmsGateway) {
                $number = config('services.movider.notify_number') ?: config('services.twilio.number');
            } elseif ($gateway instanceof MoviderSmsGateway) {
                $number = config('services.movider.notify_number');
            } else {
                $number = 'MOCK_NUMBER';
            }
        }

        return $gateway->send($number, $message);
    }

    /**
     * Check if an item is low stock and send alert if needed
     *
     * @param mixed $item
     * @param SmsGatewayInterface|null $gateway
     * @return void
     */
    public static function checkLowStockAlert($item, ?SmsGatewayInterface $gateway = null)
    {
        // The operator-facing dashboard notification is independent of SMS
        // delivery: a gateway outage must not leave the owner with no visible
        // signal that stock is critical.
        if ($item->quantity <= $item->low_stock_threshold && !$item->low_stock_notified) {
            self::notifyLowStockOwners($item);

            $message = "⚠️ MotoTrack Alert: '{$item->part_name}' (ID: {$item->id}) is running low on stock. Only {$item->quantity} left! Please restock soon.";

            if (self::send($message, null, $gateway)) {
                $item->low_stock_notified = true;
                $item->save();
            }
        }
    }

    /**
     * Record a dashboard notification for owners when an item crosses its
     * threshold. Guarded so a repeatedly-swept item notifies only once.
     */
    private static function notifyLowStockOwners($item): void
    {
        try {
            $owners = \App\Models\User::owners();

            if ($owners->isEmpty()) {
                return;
            }

            $title = 'Low Stock Alert';
            $message = "{$item->part_name} is down to {$item->quantity} unit(s) — at or below the alert threshold of {$item->low_stock_threshold}.";
            $link = route('low-stock.index', ['highlight' => $item->id]);

            $alreadySent = \Illuminate\Support\Facades\DB::table('notifications')
                ->whereIn('notifiable_id', $owners->pluck('id'))
                ->where('type', \App\Notifications\StatusUpdated::class)
                ->where('data', 'like', '%"title":"' . $title . '"%')
                ->where('data', 'like', '%"link":"' . $link . '"%')
                ->exists();

            if ($alreadySent) {
                return;
            }

            \Illuminate\Support\Facades\Notification::send($owners, new \App\Notifications\StatusUpdated(
                $title,
                $message,
                $link
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Low-stock notification for item {$item->id} failed: " . $e->getMessage());
        }
    }
}
