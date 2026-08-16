<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\ReservationNight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReservationAccessTest extends TestCase
{
    use RefreshDatabase;

    /** NFR2 — a guest cannot read another guest's reservation by changing the id. */
    public function test_a_guest_cannot_read_another_guests_reservation_by_id(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $reservation = Reservation::factory()->for($owner)->create();

        $this->actingAs($other)
            ->get(route('reservations.show', $reservation))
            ->assertForbidden();
    }

    public function test_a_guest_can_view_their_own_reservation(): void
    {
        $owner = User::factory()->create();
        $reservation = Reservation::factory()->for($owner)->create();

        $this->actingAs($owner)
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reservations/show')
                ->where('reservation.id', $reservation->id));
    }

    public function test_my_bookings_lists_only_the_users_own_reservations(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Reservation::factory()->for($owner)->count(2)->create();
        Reservation::factory()->for($other)->create();

        $this->actingAs($owner)
            ->get(route('reservations.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('reservations/index')
                ->has('reservations', 2));
    }

    public function test_owner_can_cancel_their_reservation_but_a_stranger_cannot(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $reservation = Reservation::factory()->for($owner)->create(['status' => 'confirmed']);
        ReservationNight::factory()->for($reservation)->create();

        // A stranger is refused and nothing changes.
        $this->actingAs($other)
            ->post(route('reservations.cancel', $reservation))
            ->assertForbidden();
        $this->assertSame('confirmed', $reservation->fresh()->status);

        // The owner can cancel.
        $this->actingAs($owner)
            ->post(route('reservations.cancel', $reservation))
            ->assertRedirect();
        $this->assertSame('cancelled', $reservation->fresh()->status);
    }
}
