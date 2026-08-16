<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\ReservationNight;
use App\Models\Review;
use App\Models\RewardPointsLedger;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoReservationSeeder extends Seeder
{
    /**
     * ~40 reservations spread across the past 60 days with mixed statuses, so
     * the analytics dashboard shows real room nights, revenue and ADR on first
     * load. Each reservation stores one reservation_nights row per night at that
     * night's rate (weekend +20%), mirroring the real booking engine.
     */
    public function run(): void
    {
        $guests = User::where('role', 'guest')->get();
        $hotels = Hotel::with('roomTypes')->get();

        for ($n = 0; $n < 40; $n++) {
            $guest = $guests->random();
            $hotel = $hotels->random();
            $roomType = $hotel->roomTypes->random();

            $checkIn = now()->copy()->subDays(fake()->numberBetween(1, 60))->startOfDay();
            $nightCount = fake()->numberBetween(1, 5);
            $checkOut = $checkIn->copy()->addDays($nightCount);

            // Weighted status mix. Only confirmed/completed count towards analytics.
            $status = fake()->randomElement([
                'completed', 'completed', 'completed', 'completed',
                'confirmed', 'confirmed', 'confirmed',
                'cancelled', 'cancelled',
                'pending',
            ]);

            // Build the nightly rows first so the reservation total is their sum.
            // Check-out day is not a booked night.
            $nights = [];
            $subtotal = 0.0;
            foreach (CarbonPeriod::create($checkIn, $checkOut->copy()->subDay()) as $date) {
                $rate = $date->isWeekend()
                    ? round((float) $roomType->base_rate * 1.20, 2)
                    : (float) $roomType->base_rate;
                $nights[] = ['stay_date' => $date->toDateString(), 'rate' => $rate];
                $subtotal += $rate;
            }

            $reservation = Reservation::create([
                'user_id' => $guest->id,
                'hotel_id' => $hotel->id,
                'reference' => 'BMH-'.Str::upper(Str::random(8)),
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => fake()->numberBetween(1, $roomType->max_occupancy),
                'status' => $status,
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'total_amount' => $subtotal,
                'is_sustainable' => $hotel->sustainability_certified,
            ]);

            foreach ($nights as $night) {
                ReservationNight::create([
                    'reservation_id' => $reservation->id,
                    'room_type_id' => $roomType->id,
                    'stay_date' => $night['stay_date'],
                    'rate' => $night['rate'],
                ]);
            }

            // Payment: succeeded for anything that went through, pending otherwise.
            if (in_array($status, ['confirmed', 'completed', 'cancelled'], true)) {
                $payment = Payment::create([
                    'reservation_id' => $reservation->id,
                    'amount' => $reservation->total_amount,
                    'method' => fake()->randomElement(['card', 'paypal', 'bank_transfer']),
                    'gateway_reference' => 'test_'.Str::lower(Str::random(16)),
                    'status' => 'succeeded',
                    'paid_at' => $checkIn->copy()->subDays(fake()->numberBetween(1, 10)),
                ]);

                // Cancelled bookings carry a refund record.
                if ($status === 'cancelled') {
                    Refund::create([
                        'payment_id' => $payment->id,
                        'amount' => $reservation->total_amount,
                        'reason' => 'Guest cancellation within free window',
                        'status' => 'processed',
                        'processed_at' => $checkIn->copy()->subDays(fake()->numberBetween(0, 3)),
                    ]);
                }
            } else {
                Payment::create([
                    'reservation_id' => $reservation->id,
                    'amount' => $reservation->total_amount,
                    'method' => fake()->randomElement(['card', 'paypal', 'bank_transfer']),
                    'gateway_reference' => null,
                    'status' => 'pending',
                    'paid_at' => null,
                ]);
            }

            // Reward points: earned only on sustainability-certified hotels, and
            // only for bookings that stuck (confirmed/completed). 1 point / 10 units.
            if ($hotel->sustainability_certified && in_array($status, ['confirmed', 'completed'], true)) {
                RewardPointsLedger::create([
                    'user_id' => $guest->id,
                    'reservation_id' => $reservation->id,
                    'points' => (int) floor($reservation->total_amount / 10),
                    'reason' => 'Earned on sustainable stay '.$reservation->reference,
                ]);
            }

            // Approved reviews on most completed stays (one per reservation).
            if ($status === 'completed' && fake()->boolean(70)) {
                Review::create([
                    'reservation_id' => $reservation->id,
                    'user_id' => $guest->id,
                    'hotel_id' => $hotel->id,
                    'rating' => fake()->numberBetween(6, 10),
                    'comment' => fake()->sentence(12),
                    'approved' => true,
                ]);
            }
        }

        // The balance must always equal the sum of the ledger. Recompute from
        // the ledger so the invariant holds exactly for every user.
        foreach (User::all() as $user) {
            $balance = (int) RewardPointsLedger::where('user_id', $user->id)->sum('points');
            if ($balance !== $user->reward_points_balance) {
                $user->update(['reward_points_balance' => $balance]);
            }
        }
    }
}
