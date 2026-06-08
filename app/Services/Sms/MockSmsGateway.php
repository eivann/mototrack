<?php

namespace App\Services\Sms;

use App\Services\SmsGatewayInterface;
use Illuminate\Support\Facades\Log;

class MockSmsGateway implements SmsGatewayInterface
{
    /**
     * Send mock SMS via standard Laravel logger
     *
     * @param string $to
     * @param string $message
     * @return bool
     */
    public function send(string $to, string $message): bool
    {
        $target = $to ?: 'MOCK_NUMBER';
        Log::channel('single')->info("📱 [MOCK SMS SENT to {$target}]: {$message}");
        return true;
    }
}
