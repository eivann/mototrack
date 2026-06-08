<?php

namespace App\Services\Sms;

use App\Services\SmsGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SemaphoreSmsGateway implements SmsGatewayInterface
{
    /**
     * Create a new SemaphoreSmsGateway instance
     */
    public function __construct(
        protected string $apiKey
    ) {}

    /**
     * Send SMS via Semaphore API
     *
     * @param string $to
     * @param string $message
     * @return bool
     */
    public function send(string $to, string $message): bool
    {
        try {
            $response = Http::post('https://semaphore.co/api/v4/messages', [
                'apikey'  => $this->apiKey,
                'number'  => $to,
                'message' => $message
            ]);

            if ($response->successful()) {
                Log::info("Semaphore SMS sent successfully to {$to}");
                return true;
            }

            Log::error("Semaphore SMS failed: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("Semaphore SMS exception: " . $e->getMessage());
            return false;
        }
    }
}
