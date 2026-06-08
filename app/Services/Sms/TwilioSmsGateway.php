<?php

namespace App\Services\Sms;

use App\Services\SmsGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioSmsGateway implements SmsGatewayInterface
{
    /**
     * Create a new TwilioSmsGateway instance
     */
    public function __construct(
        protected string $sid,
        protected string $token,
        protected string $from
    ) {}

    /**
     * Send SMS via Twilio using standard Laravel Http client
     *
     * @param string $to
     * @param string $message
     * @return bool
     */
    public function send(string $to, string $message): bool
    {
        $formattedTo = $this->formatToInternational($to);

        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";
            
            $response = Http::withBasicAuth($this->sid, $this->token)
                ->asForm()
                ->post($url, [
                    'To'   => $formattedTo,
                    'From' => $this->from,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info("Twilio SMS sent successfully to {$formattedTo}");
                return true;
            }

            Log::error("Twilio SMS failed: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("Twilio SMS exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format PH local phone number format (0917...) into international format (+63917...)
     */
    protected function formatToInternational(string $number): string
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
}
