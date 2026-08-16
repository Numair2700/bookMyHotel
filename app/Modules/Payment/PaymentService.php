<?php

namespace App\Modules\Payment;

use App\Events\PaymentSucceeded;
use App\Exceptions\PaymentException;
use App\Models\Payment;
use App\Models\Reservation;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Contracts\PaymentServiceInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    public function pay(Reservation $reservation, string $method, string $token): Payment
    {
        if ($reservation->status !== 'pending') {
            throw new PaymentException('This reservation is not awaiting payment.');
        }

        // Recorded outside the transaction so a declined attempt is kept for
        // audit rather than rolled back. The charge is an external call, so it
        // must not run while a database transaction is held open.
        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => $reservation->total_amount,
            'method' => $method,
            'gateway_reference' => null,
            'status' => 'pending',
            'paid_at' => null,
        ]);

        $result = $this->gateway->charge($token, (float) $reservation->total_amount);

        if (! $result->successful) {
            $payment->update(['status' => 'failed', 'gateway_reference' => $result->reference]);

            throw new PaymentException($result->failureReason ?? 'The payment was declined.');
        }

        // Success side effects are atomic: mark paid and let listeners confirm
        // the reservation and award points within the same transaction.
        DB::transaction(function () use ($payment, $result): void {
            $payment->update([
                'status' => 'succeeded',
                'gateway_reference' => $result->reference,
                'paid_at' => Date::now(),
            ]);

            PaymentSucceeded::dispatch($payment);
        });

        return $payment;
    }
}
