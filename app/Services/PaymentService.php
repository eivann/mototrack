<?php

namespace App\Services;

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

class PaymentService
{
    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
    }

    /**
     * Create a GCash invoice via Xendit.
     */
    public function createInvoice($externalId, $amount, $description, $successUrl, $failureUrl)
    {
        $apiInstance = new InvoiceApi();
        
        $create_invoice_request = new CreateInvoiceRequest([
            'external_id' => $externalId,
            'description' => $description,
            'amount' => $amount,
            'invoice_duration' => 86400,
            'currency' => 'PHP',
            'payment_methods' => ['GCASH'],
            'success_redirect_url' => $successUrl,
            'failure_redirect_url' => $failureUrl,
        ]);

        return $apiInstance->createInvoice($create_invoice_request);
    }

    /**
     * Get invoice status.
     */
    public function getInvoiceStatus($invoiceId)
    {
        $apiInstance = new InvoiceApi();
        return $apiInstance->getInvoiceById($invoiceId);
    }

    /**
     * Create a payment redirect URL via RCPAY PLUS.
     * 
     * @param string $orderId The unique reference/order ID (e.g. TRX-XXXX or PRE-X)
     * @param float $amount The gross amount to charge in PHP
     * @param string|null $bankCode Specific channel ("PMP" for Maya or "QRPH"). Defaults to "PMP".
     * @return string The redirect URL
     */
    public function createRcpayUrl($orderId, $amount, $bankCode = 'PMP')
    {
        $merchantId = config('services.rcpay.merchant_id');
        $apiKey = config('services.rcpay.api_key');
        
        $params = [
            'merchant_id' => $merchantId,
            'api_key' => $apiKey,
            'product' => $orderId,
            'amount' => number_format($amount, 2, '.', ''),
            'bank_code' => $bankCode
        ];

        return 'https://rcpayplus.com/web/pay.php?' . http_build_query($params);
    }

    /**
     * Verify the RCPAY PLUS webhook signature.
     * 
     * @param array $payload The received JSON webhook payload
     * @return bool True if valid, false otherwise
     */
    public function verifyRcpayWebhook(array $payload)
    {
        if (!isset($payload['signature'])) {
            return false;
        }

        $receivedSignature = $payload['signature'];
        
        // Remove signature from payload array for signing
        unset($payload['signature']);

        // Sort alphabetically by key
        ksort($payload);

        // Rebuild string and append secret key
        $signStr = "";
        foreach ($payload as $key => $value) {
            $signStr .= $key . '=' . $value . '&';
        }
        
        $apiKey = config('services.rcpay.api_key');
        $signStr .= 'key=' . $apiKey;

        // Verify MD5 hash
        return md5($signStr) === $receivedSignature;
    }

    /**
     * Programmatically transfer funds (payout/withdrawal) via RCPAY PLUS.
     * 
     * @param float $amount The amount to transfer in PHP
     * @param string $bankCode Target institution ("GCASH" or "PMP")
     * @param string $accountNumber 11-digit mobile number or target account
     * @param string $accountName Registered name of target account holder
     * @return array Response JSON decoded array
     */
    public function payoutRcpay($amount, $bankCode, $accountNumber, $accountName)
    {
        $merchantId = config('services.rcpay.merchant_id');
        $apiKey = config('services.rcpay.api_key');

        $response = \Illuminate\Support\Facades\Http::timeout(10)->post('https://rcpayplus.com/web/transfer.php', [
            'merchant_id' => $merchantId,
            'api_key' => $apiKey,
            'amount' => floatval($amount),
            'bank_code' => $bankCode,
            'account_number' => $accountNumber,
            'account_name' => $accountName
        ]);

        return $response->json();
    }
}
