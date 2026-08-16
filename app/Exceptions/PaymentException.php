<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a payment cannot be taken — the reservation is not awaiting
 * payment, or the gateway declined the charge. Rendered as HTTP 422.
 */
class PaymentException extends RuntimeException
{
    public function __construct(string $message = 'The payment could not be completed.')
    {
        parent::__construct($message);
    }
}
