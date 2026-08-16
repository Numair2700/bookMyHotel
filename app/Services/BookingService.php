<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\ReservationNight;
use App\Models\RoomType;
use App\Models\User;
use App\Modules\Availability\Contracts\AvailabilityServiceInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private readonly AvailabilityServiceInterface $availability,
        private readonly LoyaltyService $loyalty,
    ) {}

    /**
     * Create a reservation for a room type over [check_in, check_out). The whole
     * operation runs in one transaction: availability is locked and decremented
     * through the Availability module, then the reservation and one
     * reservation_nights row per night (at that night's rate) are written.
     */
    public function book(
        User $user,
        int $roomTypeId,
        string $checkIn,
        string $checkOut,
        int $guests,
        ?string $promotionCode = null,
        int $redeemPoints = 0,
    ): Reservation {
        return DB::transaction(function () use ($user, $roomTypeId, $checkIn, $checkOut, $guests, $promotionCode, $redeemPoints): Reservation {
            $roomType = RoomType::with('hotel')->findOrFail($roomTypeId);

            // Locks the nights, verifies capacity and decrements them (NFR5).
            $nights = $this->availability->reserveStay($roomTypeId, $checkIn, $checkOut);

            // Subtotal is the sum of the nightly rates actually charged (6.2),
            // not room_types.base_rate, so a mid-stay rate change is honoured.
            $subtotal = (float) $nights->sum(fn ($night): float => (float) $night->rate);

            [$promotion, $promotionDiscount] = $this->resolvePromotion($promotionCode, $roomType->hotel_id, $subtotal);

            // Redeem reward points against what is left after any promotion,
            // capped by the balance and by the remaining total (FR13).
            $afterPromotion = $subtotal - $promotionDiscount;
            $pointsToRedeem = max(0, min(
                $redeemPoints,
                $user->reward_points_balance,
                (int) floor($afterPromotion / LoyaltyService::POINT_VALUE),
            ));
            $pointsDiscount = $this->loyalty->discountValue($pointsToRedeem);

            $discountTotal = round($promotionDiscount + $pointsDiscount, 2);

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'hotel_id' => $roomType->hotel_id,
                'reference' => $this->generateReference(),
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => $guests,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'total_amount' => $subtotal - $discountTotal,
                'promotion_id' => $promotion?->id,
                'is_sustainable' => $roomType->hotel->sustainability_certified,
            ]);

            foreach ($nights as $night) {
                ReservationNight::create([
                    'reservation_id' => $reservation->id,
                    'room_type_id' => $roomTypeId,
                    'stay_date' => $night->date->toDateString(),
                    'rate' => $night->rate,
                ]);
            }

            if ($pointsToRedeem > 0) {
                $this->loyalty->redeem($user->id, $reservation->id, $pointsToRedeem);
            }

            return $reservation;
        });
    }

    /**
     * Resolve a promotion code for the hotel and compute the discount. An
     * invalid or out-of-date code yields no discount rather than an error.
     *
     * @return array{0: Promotion|null, 1: float}
     */
    private function resolvePromotion(?string $code, int $hotelId, float $subtotal): array
    {
        if ($code === null || $code === '') {
            return [null, 0.0];
        }

        $today = Date::today();

        $promotion = Promotion::query()
            ->where('hotel_id', $hotelId)
            ->where('code', $code)
            ->where('active', true)
            ->whereDate('valid_from', '<=', $today)
            ->whereDate('valid_to', '>=', $today)
            ->first();

        if ($promotion === null) {
            return [null, 0.0];
        }

        $discount = $promotion->discount_type === 'percentage'
            ? $subtotal * ((float) $promotion->discount_value / 100)
            : (float) $promotion->discount_value;

        // Never discount below zero.
        $discount = round(min($discount, $subtotal), 2);

        return [$promotion, $discount];
    }

    /**
     * A unique, human-readable reference in the form BMH-XXXXXXXX.
     */
    private function generateReference(): string
    {
        do {
            $reference = 'BMH-'.Str::upper(Str::random(8));
        } while (Reservation::where('reference', $reference)->exists());

        return $reference;
    }
}
