<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\RewardPointsLedger;
use App\Models\User;

class LoyaltyService
{
    /**
     * How much one reward point is worth as booking discount. Earning is 1
     * point per 10 currency units spent (see awardForReservation); this single
     * constant sets the redemption side and can be tuned in one place.
     */
    public const POINT_VALUE = 1.0;

    /** The discount value of a number of points. */
    public function discountValue(int $points): float
    {
        return round(max(0, $points) * self::POINT_VALUE, 2);
    }

    /**
     * Redeem points against a reservation (FR13): write a negative ledger row
     * and reduce the balance by the same amount, so balance == sum(ledger).
     * The caller is responsible for capping points to the available balance and
     * the reservation total.
     */
    public function redeem(int $userId, int $reservationId, int $points): void
    {
        if ($points <= 0) {
            return;
        }

        RewardPointsLedger::create([
            'user_id' => $userId,
            'reservation_id' => $reservationId,
            'points' => -$points,
            'reason' => 'Redeemed against booking',
        ]);

        User::whereKey($userId)->decrement('reward_points_balance', $points);
    }

    /**
     * Award reward points for a paid reservation (6.5). Points are earned only
     * on sustainability-certified hotels (FR13), at 1 point per 10 currency
     * units of the total. A ledger row is written and the balance moved by the
     * same amount, so balance always equals the sum of the ledger.
     */
    public function awardForReservation(Reservation $reservation): void
    {
        if (! $reservation->is_sustainable) {
            return;
        }

        $points = (int) floor((float) $reservation->total_amount / 10);

        if ($points <= 0) {
            return;
        }

        RewardPointsLedger::create([
            'user_id' => $reservation->user_id,
            'reservation_id' => $reservation->id,
            'points' => $points,
            'reason' => 'Earned on stay '.$reservation->reference,
        ]);

        User::whereKey($reservation->user_id)->increment('reward_points_balance', $points);
    }
}
