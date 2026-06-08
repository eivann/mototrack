<?php

namespace App\Services\Sms;

use App\Services\SmsGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoviderSmsGateway implements SmsGatewayInterface
{
    /**
     * Create a new MoviderSmsGateway instance
     */
    public function __construct(
        protected string $apiKey,
        protected string $apiSecret
    ) {}

    /**
     * Send SMS via Movider API
     *
     * @param string $to
     * @param string $message
     * @return bool
     */
    public function send(string $to, string $message): bool
    {
        $formattedTo = $this->formatToInternational($to);

        try {
            $response = Http::asForm()->post('https://api.movider.co/v1/sms', [
                'api_key'    => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'to'         => $formattedTo,
                'text'       => $message
            ]);

            if ($response->successful()) {
                Log::info("Movider SMS response for {$formattedTo}: " . $response->body());
                return true;
            }

            Log::error("Movider SMS failed: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("Movider SMS exception: " . $e->getMessage());
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
