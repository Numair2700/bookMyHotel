<?php

namespace App\Services;

use App\Exceptions\CancellationException;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\RewardPointsLedger;
use App\Models\User;
use App\Modules\Availability\Contracts\AvailabilityServiceInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class CancellationService
{
    public function __construct(
        private readonly AvailabilityServiceInterface $availability,
    ) {}

    /**
     * The refund the guest would receive for cancelling now (6.4, NFR8).
     * Inside the free window the refund is 100%, otherwise 100 − penalty%.
     */
    public function quote(Reservation $reservation): RefundQuote
    {
        $policy = $reservation->hotel->cancellationPolicies()->first();

        $hoursUntilCheckIn = Date::now()->diffInHours($reservation->check_in, false);

        $withinFreeWindow = $policy === null || $hoursUntilCheckIn >= $policy->free_cancellation_hours;
        $penalty = $policy !== null ? (float) $policy->penalty_percentage : 0.0;

        $percent = $withinFreeWindow ? 100.0 : max(0.0, 100.0 - $penalty);
        $amount = round((float) $reservation->total_amount * $percent / 100, 2);

        return new RefundQuote(
            amount: $amount,
            percent: $percent,
            withinFreeWindow: $withinFreeWindow,
            policyName: $policy?->name,
            freeCancellationHours: $policy?->free_cancellation_hours,
            penaltyPercentage: $penalty,
        );
    }

    /**
     * Cancel a reservation: restore availability, record the refund against the
     * paid payment, reverse any reward points, and mark it cancelled — all in
     * one transaction (6.4).
     */
    public function cancel(Reservation $reservation): Reservation
    {
        if (! in_array($reservation->status, ['pending', 'confirmed'], true)) {
            throw new CancellationException;
        }

        return DB::transaction(function () use ($reservation): Reservation {
            $quote = $this->quote($reservation);

            // Give the room nights back to the calendar.
            $roomTypeId = (int) $reservation->nights()->value('room_type_id');
            $this->availability->releaseStay(
                $roomTypeId,
                $reservation->check_in->toDateString(),
                $reservation->check_out->toDateString(),
            );

            $reservation->update(['status' => 'cancelled']);

            // Record a refund against the successful payment, if one exists.
            $payment = $reservation->payments()->where('status', 'succeeded')->latest()->first();
            if ($payment !== null) {
                Refund::create([
                    'payment_id' => $payment->id,
                    'amount' => $quote->amount,
                    'reason' => $quote->withinFreeWindow ? 'Free cancellation' : 'Cancellation with penalty',
                    'status' => 'processed',
                    'processed_at' => Date::now(),
                ]);
            }

            $this->reverseRewardPoints($reservation);

            return $reservation->refresh();
        });
    }

    /**
     * Write a compensating ledger row for any points earned on this reservation
     * and reduce the balance, keeping balance == sum(ledger) (6.5).
     */
    private function reverseRewardPoints(Reservation $reservation): void
    {
        $awarded = (int) RewardPointsLedger::where('reservation_id', $reservation->id)->sum('points');

        if ($awarded === 0) {
            return;
        }

        RewardPointsLedger::create([
            'user_id' => $reservation->user_id,
            'reservation_id' => $reservation->id,
            'points' => -$awarded,
            'reason' => 'Reversal on cancellation of '.$reservation->reference,
        ]);

        User::whereKey($reservation->user_id)->decrement('reward_points_balance', $awarded);
    }
}
