<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\RewardPointsLedger;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{User, RoomType, Carbon, Carbon}
     */
    private function scenario(int $firstRate = 200, int $secondRate = 250, int $rooms = 5): array
    {
        $user = User::factory()->create();
        $hotel = Hotel::factory()->create();
        $roomType = RoomType::factory()->for($hotel)->create(['max_occupancy' => 4]);

        $checkIn = now()->addDay()->startOfDay();
        $checkOut = $checkIn->copy()->addDays(2); // two nights

        Availability::factory()->for($roomType)->create([
            'date' => $checkIn->toDateString(), 'rooms_available' => $rooms, 'rate' => $firstRate,
        ]);
        Availability::factory()->for($roomType)->create([
            'date' => $checkIn->copy()->addDay()->toDateString(), 'rooms_available' => $rooms, 'rate' => $secondRate,
        ]);

        return [$user, $roomType, $checkIn, $checkOut];
    }

    /** FR6 — one reservation_nights row per night, each at that night's rate. */
    public function test_booking_creates_one_reservation_night_per_night_at_the_correct_nightly_rate(): void
    {
        [$user, $roomType, $checkIn, $checkOut] = $this->scenario(firstRate: 200, secondRate: 250);

        $this->actingAs($user)->post(route('reservations.store'), [
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 2,
        ])->assertRedirect();

        $reservation = Reservation::firstOrFail();

        $this->assertSame('pending', $reservation->status);
        $this->assertSame(2, $reservation->nights()->count());
        $this->assertEqualsWithDelta(450.0, (float) $reservation->subtotal, 0.001);
        $this->assertEqualsWithDelta(450.0, (float) $reservation->total_amount, 0.001);
        $this->assertDatabaseHas('reservation_nights', [
            'reservation_id' => $reservation->id,
            'stay_date' => $checkIn->toDateString(),
            'rate' => 200.00,
        ]);
        $this->assertDatabaseHas('reservation_nights', [
            'reservation_id' => $reservation->id,
            'stay_date' => $checkIn->copy()->addDay()->toDateString(),
            'rate' => 250.00,
        ]);
    }

    /** FR6 — availability is decremented by one room on every booked night. */
    public function test_availability_is_decremented_when_a_booking_is_made(): void
    {
        [$user, $roomType, $checkIn, $checkOut] = $this->scenario(rooms: 5);

        $this->actingAs($user)->post(route('reservations.store'), [
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 2,
        ])->assertRedirect();

        $this->assertSame(4, Availability::where('room_type_id', $roomType->id)
            ->where('date', $checkIn->toDateString())->value('rooms_available'));
        $this->assertSame(4, Availability::where('room_type_id', $roomType->id)
            ->where('date', $checkIn->copy()->addDay()->toDateString())->value('rooms_available'));
    }

    /** FR13 — reward points can be redeemed for a discount on a booking. */
    public function test_reward_points_can_be_redeemed_for_a_discount(): void
    {
        $user = User::factory()->create();
        // A balance of 100 points, backed by the ledger.
        RewardPointsLedger::create(['user_id' => $user->id, 'reservation_id' => null, 'points' => 100, 'reason' => 'Seed']);
        $user->update(['reward_points_balance' => 100]);

        [, $roomType, $checkIn, $checkOut] = $this->scenario(firstRate: 200, secondRate: 250); // subtotal 450

        $this->actingAs($user)->post(route('reservations.store'), [
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 2,
            'redeem_points' => 50,
        ])->assertRedirect();

        $reservation = Reservation::firstOrFail();

        // 50 points at 1 unit each => 50 off, so 450 - 50 = 400.
        $this->assertEqualsWithDelta(50.0, (float) $reservation->discount_total, 0.001);
        $this->assertEqualsWithDelta(400.0, (float) $reservation->total_amount, 0.001);

        // Balance drops to 50, and balance still equals the ledger sum.
        $this->assertSame(50, (int) $user->fresh()->reward_points_balance);
        $this->assertSame(50, (int) RewardPointsLedger::where('user_id', $user->id)->sum('points'));
        $this->assertDatabaseHas('reward_points_ledger', [
            'reservation_id' => $reservation->id,
            'points' => -50,
        ]);
    }

    /** FR2 — booking is not permitted without authentication. */
    public function test_booking_is_rejected_when_not_authenticated(): void
    {
        [, $roomType, $checkIn, $checkOut] = $this->scenario();

        $this->post(route('reservations.store'), [
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 2,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('reservations', 0);
    }

    /** A sold-out night aborts the whole booking; the transaction rolls back. */
    public function test_booking_is_rejected_and_rolled_back_when_a_night_is_sold_out(): void
    {
        [$user, $roomType, $checkIn, $checkOut] = $this->scenario();

        // Sell out the second night.
        Availability::where('room_type_id', $roomType->id)
            ->where('date', $checkIn->copy()->addDay()->toDateString())
            ->update(['rooms_available' => 0]);

        $this->actingAs($user)->post(route('reservations.store'), [
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 2,
        ])->assertSessionHasErrors('availability');

        // Nothing persisted and the first night was not decremented.
        $this->assertDatabaseCount('reservations', 0);
        $this->assertSame(5, Availability::where('room_type_id', $roomType->id)
            ->where('date', $checkIn->toDateString())->value('rooms_available'));
    }
}
