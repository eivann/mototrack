<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived
{
    use Dispatchable, SerializesModels;

    /**
     * @param  string  $type      'preorder' | 'shop'
     * @param  string  $orderId   External order reference.
     * @param  float   $amount    Raw gateway amount.
     * @param  array   $context   Audit #13: pre-resolved display data so queued
     *                            listeners don't re-query. Keys: customer_user_id,
     *                            customer_name, display_amount, part_name.
     */
    public function __construct(
        public string $type,
        public string $orderId,
        public float $amount,
        public array $context = [],
    ) {
    }
}
