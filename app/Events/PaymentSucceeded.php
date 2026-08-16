<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised by the Payment module when a charge succeeds. Other domains react to
 * this (confirming the reservation, awarding reward points) via listeners, so
 * the Payment module never calls into them directly.
 */
class PaymentSucceeded
{
    use Dispatchable;

    public function __construct(
        public readonly Payment $payment,
    ) {}
}
