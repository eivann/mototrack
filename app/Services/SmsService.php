<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS using the active driver (Twilio or Semaphore)
     *
     * @param string $message
     * @param string|null $recipientNumber
     * @return bool
     */
    public static function send($message, $recipientNumber = null)
    {
        // 1. Check Twilio Config
        $twilioSid = config('services.twilio.sid');
        $twilioAuthToken = config('services.twilio.auth_token');
        $twilioNumber = config('services.twilio.number');

        // 2. Check Semaphore Config
        $semaphoreApiKey = config('services.semaphore.api_key');
        $semaphoreDefaultNumber = config('services.semaphore.notify_number');

        // 3. Check Movider Config
        $moviderApiKey = config('services.movider.api_key');
        $moviderApiSecret = config('services.movider.api_secret');
        $moviderDefaultNumber = config('services.movider.notify_number');

        // Determine destination number
        $number = $recipientNumber ?: ($twilioSid ? null : ($semaphoreApiKey ? $semaphoreDefaultNumber : $moviderDefaultNumber));

        // If it's Twilio and no recipient was passed, default to SMS_NOTIFY_NUMBER or Twilio number
        if (!$number && $twilioSid) {
            $number = env('SMS_NOTIFY_NUMBER') ?: $twilioNumber;
        }

        // If no credentials, trigger Mock Mode
        if ((!$twilioSid || !$twilioAuthToken || !$twilioNumber) && 
            (!$semaphoreApiKey || !$semaphoreDefaultNumber) && 
            (!$moviderApiKey || !$moviderApiSecret)) {
            $target = $number ?: 'MOCK_NUMBER';
            Log::channel('single')->info("📱 [MOCK SMS SENT to {$target}]: {$message}");
            session()->flash('mock_sms', "📱 Mock SMS Sent: {$message}");
            return true;
        }

        // Send via Twilio if Twilio credentials exist (Priority 1)
        if ($twilioSid && $twilioAuthToken && $twilioNumber) {
            return self::sendViaTwilio($number, $message, $twilioSid, $twilioAuthToken, $twilioNumber);
        }

        // Send via Semaphore if Semaphore credentials exist (Priority 2)
        if ($semaphoreApiKey) {
            return self::sendViaSemaphore($number, $message, $semaphoreApiKey);
        }

        // Otherwise, send via Movider (Priority 3)
        return self::sendViaMovider($number, $message, $moviderApiKey, $moviderApiSecret);
    }

    /**
     * Send SMS via Twilio using standard Laravel Http client
     */
    protected static function sendViaTwilio($to, $message, $sid, $token, $from)
    {
        // Format Philippine phone numbers to international format (+63) for Twilio
        $formattedTo = self::formatToInternational($to);

        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
            
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post($url, [
                    'To'   => $formattedTo,
                    'From' => $from,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info("Twilio SMS sent successfully to {$formattedTo}");
                return true;
            } else {
                Log::error("Twilio SMS failed: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Twilio SMS exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS via Semaphore
     */
    protected static function sendViaSemaphore($to, $message, $apiKey)
    {
        try {
            $response = Http::post('https://semaphore.co/api/v4/messages', [
                'apikey'  => $apiKey,
                'number'  => $to,
                'message' => $message
            ]);

            if ($response->successful()) {
                Log::info("Semaphore SMS sent successfully to {$to}");
                return true;
            } else {
                Log::error("Semaphore SMS failed: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Semaphore SMS exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS via Movider
     */
    protected static function sendViaMovider($to, $message, $apiKey, $apiSecret)
    {
        // Format recipient to international format (+63) for Movider
        $formattedTo = self::formatToInternational($to);

        try {
            $response = Http::asForm()->post('https://api.movider.co/v1/sms', [
                'api_key'    => $apiKey,
                'api_secret' => $apiSecret,
                'to'         => $formattedTo,
                'text'       => $message
            ]);

            if ($response->successful()) {
                Log::info("Movider SMS response for {$formattedTo}: " . $response->body());
                return true;
            } else {
                Log::error("Movider SMS failed: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Movider SMS exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format PH local phone number format (0917...) into international format (+63917...) for Twilio
     */
    protected static function formatToInternational($number)
    {
        $number = trim($number);
        
        if (str_starts_with($number, '+')) {
            return $number;
        }

        if (str_starts_with($number, '09') && strlen($number) === 11) {
            return '+63' . substr($number, 1);
        }

        if (str_starts_with($number, '9') && strlen($number) === 10) {
            return '+63' . $number;
        }

        return $number;
    }

    /**
     * Check if an item is low stock and send alert if needed
     */
    public static function checkLowStockAlert($item)
    {
        if ($item->quantity <= $item->low_stock_threshold && !$item->low_stock_notified) {
            $message = "⚠️ MotoTrack Alert: '{$item->part_name}' (ID: {$item->id}) is running low on stock. Only {$item->quantity} left! Please restock soon.";
            
            if (self::send($message)) {
                $item->low_stock_notified = true;
                $item->save();
            }
        }
    }
}
