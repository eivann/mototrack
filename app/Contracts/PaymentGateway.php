<?php

namespace App\Contracts;

/**
 * Audit #2: payment abstraction so controllers depend on an interface rather
 * than the concrete Xendit/RCPAY implementation. Additional providers can
 * implement this contract and be swapped via the container binding in
 * AppServiceProvider (keyed off config('services.payment.provider')).
 *
 * Note: provider choice (GCash/Xendit vs RCPAY) is made per-transaction at
 * request time, so both provider methods live on one gateway rather than
 * behind separate single-provider classes.
 */
interface PaymentGateway
{
    /**
     * Create a GCash/Xendit hosted invoice. Returns the provider invoice object.
     */
    public function createInvoice($externalId, $amount, $description, $successUrl, $failureUrl);

    /**
     * Fetch the status of a previously created invoice.
     */
    public function getInvoiceStatus($invoiceId);

    /**
     * Build an RCPAY PLUS hosted-payment redirect URL.
     */
    public function createRcpayUrl($orderId, $amount, $bankCode = 'PMP');

    /**
     * Verify an RCPAY PLUS webhook signature. Returns true when valid.
     */
    public function verifyRcpayWebhook(array $payload);
}
