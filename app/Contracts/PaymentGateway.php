<?php

namespace App\Contracts;

/**
 * Audit #2: payment abstraction so controllers depend on an interface rather
 * than concrete implementations. Additional providers can implement this
 * contract and be swapped via the container binding in AppServiceProvider
 * (keyed off config('services.payment.provider')).
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

}
