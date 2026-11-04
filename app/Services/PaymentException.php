<?php

namespace App\Services;

/** A payment rule was not met; the message is safe to show to the user. */
class PaymentException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $httpStatus = 422)
    {
        parent::__construct($message);
    }
}
