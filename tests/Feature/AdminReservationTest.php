<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminReservationTest extends TestCase
{
    use RefreshDatabase;

    /** FR17 — an admin sees all reservations across guests and hotels. */
    public function test_an_admin_can_list_all_reservations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Reservation::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('admin.reservations.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reservations/index')
                ->has('reservations', 3));
    }

    /** FR17 — the list can be filtered by hotel. */
    public function test_reservations_can_be_filtered_by_hotel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hotelA = Hotel::factory()->create();
        $hotelB = Hotel::factory()->create();
        Reservation::factory()->for($hotelA)->count(2)->create();
        Reservation::factory()->for($hotelB)->create();

        $this->actingAs($admin)
            ->get(route('admin.reservations.index', ['hotel_id' => $hotelA->id]))
            ->assertInertia(fn (Assert $page) => $page->has('reservations', 2));
    }

    public function test_a_guest_cannot_view_all_reservations(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'guest']))
            ->get(route('admin.reservations.index'))->assertForbidden();
    }
}
