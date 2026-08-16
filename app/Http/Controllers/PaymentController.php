<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentException;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Modules\Payment\Contracts\PaymentServiceInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PaymentServiceInterface $payments,
    ) {}

    /** FR12 — take payment for a reservation through the Payment module. */
    public function store(StorePaymentRequest $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('pay', $reservation);

        try {
            $this->payments->pay(
                $reservation,
                (string) $request->input('method'),
                (string) $request->input('token'),
            );
        } catch (PaymentException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()
            ->route('reservations.show', $reservation)
            ->with('success', 'Payment successful. Your booking is confirmed.');
    }

    public function show(Payment $payment): Response
    {
        $this->authorize('view', $payment);

        $payment->load('reservation');

        return Inertia::render('payments/show', [
            'payment' => [
                'id' => $payment->id,
                'reservation_reference' => $payment->reservation?->reference,
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
                'status' => $payment->status,
                'gateway_reference' => $payment->gateway_reference,
                'paid_at' => $payment->paid_at?->toDateTimeString(),
            ],
        ]);
    }
}
