<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived
{
    use Dispatchable, SerializesModels;

    public string $type;
    public string $orderId;
    public float $amount;

    /**
     * Create a new event instance.
     */
    public function __construct(string $type, string $orderId, float $amount)
    {
        $this->type = $type;
        $this->orderId = $orderId;
        $this->amount = $amount;
    }
}
