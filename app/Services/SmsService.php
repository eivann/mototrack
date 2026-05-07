<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS using Semaphore API
     */
    public static function send($message)
    {
        $apiKey = config('services.semaphore.api_key');
        $number = config('services.semaphore.notify_number');

        // If keys are missing, fallback to MOCK MODE
        if (!$apiKey || !$number) {
            $mockNumber = $number ?: 'MOCK_NUMBER';
            Log::channel('single')->info("📱 [MOCK SMS SENT to {$mockNumber}]: {$message}");
            
            // Also flash it to the session so the user can see it in the UI immediately
            session()->flash('mock_sms', "📱 Mock SMS Sent: {$message}");
            
            return true;
        }

        try {
            $response = Http::post('https://api.semaphore.co/api/v4/messages', [
                'apikey' => $apiKey,
                'number' => $number,
                'message' => $message
            ]);

            if ($response->successful()) {
                Log::info('Semaphore SMS sent successfully to ' . $number);
                return true;
            } else {
                Log::error('Semaphore SMS failed: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Semaphore SMS exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if an item is low stock and send alert if needed
     */
    public static function checkLowStockAlert($item)
    {
        if ($item->quantity <= $item->low_stock_threshold && !$item->low_stock_notified) {
            $message = "⚠️ MotoTrack Alert: '{$item->part_name}' (ID: {$item->id}) is running low on stock. Only {$item->quantity} left! Please restock soon.";
            
            // Try to send SMS and only mark as notified if successful
            if (self::send($message)) {
                $item->low_stock_notified = true;
                $item->save();
            }
        }
    }
}
