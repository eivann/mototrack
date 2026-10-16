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
     * Legacy entry point (kept for compatibility and tests).
     *
     * Delegates to LowStockAlertService, which owns the alert state: the in-app
     * owner notification is recorded synchronously first, then SMS is sent
     * inline with the resolved gateway on a best-effort basis.
     *
     * @param mixed $item
     * @param SmsGatewayInterface|null $gateway
     * @return void
     */
    public static function checkLowStockAlert($item, ?SmsGatewayInterface $gateway = null)
    {
        app(LowStockAlertService::class)->evaluate((int) $item->id, $gateway ?: app(SmsGatewayInterface::class));
    }

    /**
     * Send the low-stock SMS for an item. Best effort: returns false (and logs)
     * instead of throwing when the gateway fails.
     */
    public static function sendLowStock($item, ?SmsGatewayInterface $gateway = null): bool
    {
        $message = "⚠️ MotoTrack Alert: '{$item->part_name}' (ID: {$item->id}) is running low on stock. Only {$item->quantity} left! Please restock soon.";

        try {
            return (bool) self::send($message, null, $gateway);
        } catch (\Throwable $e) {
            Log::warning("Low-stock SMS for item {$item->id} failed: " . $e->getMessage());

            return false;
        }
    }
}
