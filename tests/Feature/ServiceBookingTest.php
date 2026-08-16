<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceBookingTest extends TestCase
{
    use RefreshDatabase;

    /** FR9 — the owner can add one of the hotel's services to their booking. */
    public function test_owner_can_add_a_service_to_their_reservation(): void
    {
        $owner = User::factory()->create();
        $hotel = Hotel::factory()->create();
        $reservation = Reservation::factory()->for($owner)->for($hotel)->create(['status' => 'confirmed']);
        $service = Service::factory()->for($hotel)->create(['price' => 85]);

        $this->actingAs($owner)
            ->post(route('reservations.services.store', $reservation), [
                'service_id' => $service->id,
                'service_date' => now()->addDays(6)->toDateString(),
                'quantity' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('service_bookings', [
            'reservation_id' => $reservation->id,
            'service_id' => $service->id,
            'quantity' => 2,
            'unit_price' => 85.00, // snapshotted from the service price
        ]);
    }

    public function test_a_stranger_cannot_add_a_service_to_someone_elses_reservation(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $hotel = Hotel::factory()->create();
        $reservation = Reservation::factory()->for($owner)->for($hotel)->create();
        $service = Service::factory()->for($hotel)->create();

        $this->actingAs($stranger)
            ->post(route('reservations.services.store', $reservation), [
                'service_id' => $service->id,
                'service_date' => now()->addDays(6)->toDateString(),
                'quantity' => 1,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('service_bookings', 0);
    }

    public function test_a_service_from_a_different_hotel_is_rejected(): void
    {
        $owner = User::factory()->create();
        $hotel = Hotel::factory()->create();
        $otherHotel = Hotel::factory()->create();
        $reservation = Reservation::factory()->for($owner)->for($hotel)->create();
        $service = Service::factory()->for($otherHotel)->create();

        $this->actingAs($owner)
            ->post(route('reservations.services.store', $reservation), [
                'service_id' => $service->id,
                'service_date' => now()->addDays(6)->toDateString(),
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('service');

        $this->assertDatabaseCount('service_bookings', 0);
    }
}
