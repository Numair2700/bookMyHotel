<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Mail\ReservationConfirmedMail;
use App\Models\Reservation;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Support\Facades\Mail;

/**
 * Reacts to a successful payment: confirms the reservation, awards reward
 * points and queues the confirmation email. This lives outside the Payment
 * module so that module has no outbound dependency on the reservation, loyalty
 * or mail domains.
 */
class HandleSuccessfulPayment
{
    public function __construct(
        private readonly LoyaltyService $loyalty,
    ) {}

    public function handle(PaymentSucceeded $event): void
    {
        $reservation = Reservation::findOrFail($event->payment->reservation_id);

        $reservation->update(['status' => 'confirmed']);

        $this->loyalty->awardForReservation($reservation);

        // Queued (ShouldQueue) so it never blocks the payment response (NFR3).
        $guest = User::findOrFail($reservation->user_id);
        Mail::to($guest->email)->send(new ReservationConfirmedMail($reservation));
    }
}
