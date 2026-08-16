<?php

namespace Tests\Feature;

use App\Exceptions\CancellationException;
use App\Models\Availability;
use App\Models\CancellationPolicy;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationNight;
use App\Models\RewardPointsLedger;
use App\Models\RoomType;
use App\Models\User;
use App\Services\CancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CancellationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{Reservation, RoomType, Carbon}
     */
    private function makeReservation(int $freeHours, float $penalty, int $checkInDaysAway, float $total = 400): array
    {
        $user = User::factory()->create();
        $hotel = Hotel::factory()->create();
        CancellationPolicy::factory()->for($hotel)->create([
            'free_cancellation_hours' => $freeHours,
            'penalty_percentage' => $penalty,
        ]);
        $roomType = RoomType::factory()->for($hotel)->create();

        $checkIn = now()->addDays($checkInDaysAway)->startOfDay();
        $checkOut = $checkIn->copy()->addDays(2); // two nights

        // Availability as it would stand after this booking decremented it.
        Availability::factory()->for($roomType)->create(['date' => $checkIn->toDateString(), 'rooms_available' => 4, 'rate' => 200]);
        Availability::factory()->for($roomType)->create(['date' => $checkIn->copy()->addDay()->toDateString(), 'rooms_available' => 4, 'rate' => 200]);

        $reservation = Reservation::factory()->for($user)->for($hotel)->create([
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'status' => 'confirmed',
            'subtotal' => $total,
            'total_amount' => $total,
        ]);
        ReservationNight::factory()->for($reservation)->for($roomType)->create(['stay_date' => $checkIn->toDateString(), 'rate' => 200]);
        ReservationNight::factory()->for($reservation)->for($roomType)->create(['stay_date' => $checkIn->copy()->addDay()->toDateString(), 'rate' => 200]);
        Payment::factory()->for($reservation)->create(['amount' => $total, 'status' => 'succeeded']);

        return [$reservation, $roomType, $checkIn];
    }

    /** FR7, FR12 — cancelling inside the free window refunds the full amount. */
    public function test_cancellation_inside_the_free_window_refunds_in_full(): void
    {
        // Free window 24h; check-in 5 days away, so well inside it.
        [$reservation] = $this->makeReservation(freeHours: 24, penalty: 50, checkInDaysAway: 5, total: 400);

        $updated = app(CancellationService::class)->cancel($reservation);

        $this->assertSame('cancelled', $updated->status);
        $this->assertDatabaseHas('refunds', ['amount' => 400.00, 'status' => 'processed']);
    }

    /** FR7, FR12 — cancelling outside the free window applies the penalty. */
    public function test_cancellation_outside_the_free_window_applies_the_penalty(): void
    {
        // Free window 72h; check-in 1 day away, so the 40% penalty applies.
        [$reservation] = $this->makeReservation(freeHours: 72, penalty: 40, checkInDaysAway: 1, total: 400);

        app(CancellationService::class)->cancel($reservation);

        // 100 - 40 = 60% of 400.
        $this->assertDatabaseHas('refunds', ['amount' => 240.00, 'status' => 'processed']);
    }

    /** FR7 — cancellation restores the availability the booking had taken. */
    public function test_availability_is_restored_on_cancellation(): void
    {
        [$reservation, $roomType, $checkIn] = $this->makeReservation(freeHours: 24, penalty: 0, checkInDaysAway: 5);

        app(CancellationService::class)->cancel($reservation);

        $this->assertSame(5, Availability::where('room_type_id', $roomType->id)
            ->where('date', $checkIn->toDateString())->value('rooms_available'));
        $this->assertSame(5, Availability::where('room_type_id', $roomType->id)
            ->where('date', $checkIn->copy()->addDay()->toDateString())->value('rooms_available'));
    }

    /** FR13 — reward points earned on a booking are reversed when it is cancelled. */
    public function test_reward_points_are_reversed_on_cancellation(): void
    {
        [$reservation] = $this->makeReservation(freeHours: 24, penalty: 0, checkInDaysAway: 5, total: 400);
        $user = $reservation->user;

        // Simulate points having been awarded on payment.
        RewardPointsLedger::create([
            'user_id' => $user->id,
            'reservation_id' => $reservation->id,
            'points' => 40,
            'reason' => 'Earned on stay',
        ]);
        $user->update(['reward_points_balance' => 40]);

        app(CancellationService::class)->cancel($reservation);

        $this->assertSame(0, (int) $user->fresh()->reward_points_balance);
        // Balance must always equal the sum of the ledger.
        $this->assertSame(0, (int) RewardPointsLedger::where('user_id', $user->id)->sum('points'));
    }

    /** A completed reservation cannot be cancelled. */
    public function test_a_completed_reservation_cannot_be_cancelled(): void
    {
        $reservation = Reservation::factory()->completed()->create();

        $this->expectException(CancellationException::class);

        app(CancellationService::class)->cancel($reservation);
    }
}
