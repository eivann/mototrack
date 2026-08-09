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
        if ($item->quantity <= $item->low_stock_threshold && !$item->low_stock_notified) {
            $message = "⚠️ MotoTrack Alert: '{$item->part_name}' (ID: {$item->id}) is running low on stock. Only {$item->quantity} left! Please restock soon.";
            
            if (self::send($message, null, $gateway)) {
                $item->low_stock_notified = true;
                $item->save();
            }
        }
    }
}
