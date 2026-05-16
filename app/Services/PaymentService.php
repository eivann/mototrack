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
}
