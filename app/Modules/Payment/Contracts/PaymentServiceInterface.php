<?php

namespace App\Modules\Payment\Contracts;

use App\Exceptions\PaymentException;
use App\Models\Payment;
use App\Models\Reservation;

/**
 * Public interface of the Payment bounded module. Other domains take payment
 * only through this contract. On success the module records the payment and
 * raises a PaymentSucceeded event; it never calls into other domains itself, so
 * it stays isolated and extractable.
 */
interface PaymentServiceInterface
{
    /**
     * Charge a tokenised payment for a pending reservation.
     *
     * @throws PaymentException when the reservation is not payable or the
     *                          gateway declines the charge.
     */
    public function pay(Reservation $reservation, string $method, string $token): Payment;
}
