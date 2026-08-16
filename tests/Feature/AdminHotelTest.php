<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\HotelChain;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHotelTest extends TestCase
{
    use RefreshDatabase;

    /** FR16 — an admin can add, edit and remove a hotel. */
    public function test_an_admin_can_create_edit_and_delete_a_hotel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $chain = HotelChain::factory()->create();

        $this->actingAs($admin)->post(route('admin.hotels.store'), [
            'chain_id' => $chain->id,
            'name' => 'New Grand',
            'city' => 'Dubai',
            'country' => 'UAE',
            'region' => 'asia',
            'address' => '1 Palm Jumeirah',
            'description' => 'A luxury property.',
            'star_rating' => 5,
            'wifi_speed_mbps' => 500,
            'has_workspace' => true,
            'sustainability_certified' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('hotels', ['name' => 'New Grand', 'region' => 'asia']);

        $hotel = Hotel::where('name', 'New Grand')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.hotels.update', $hotel), [
            'chain_id' => $chain->id,
            'name' => 'Renamed Grand',
            'city' => 'Dubai',
            'country' => 'UAE',
            'region' => 'asia',
            'address' => '1 Palm Jumeirah',
            'description' => 'A luxury property.',
            'star_rating' => 4,
        ])->assertRedirect();
        $this->assertDatabaseHas('hotels', ['id' => $hotel->id, 'name' => 'Renamed Grand', 'star_rating' => 4]);

        $this->actingAs($admin)->delete(route('admin.hotels.destroy', $hotel))->assertRedirect();
        $this->assertDatabaseMissing('hotels', ['id' => $hotel->id]);
    }

    /** Financial records are protected: a hotel with reservations is not deletable. */
    public function test_a_hotel_with_reservations_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hotel = Hotel::factory()->create();
        Reservation::factory()->for($hotel)->create();

        $this->actingAs($admin)->delete(route('admin.hotels.destroy', $hotel))
            ->assertSessionHasErrors('hotel');
        $this->assertDatabaseHas('hotels', ['id' => $hotel->id]);
    }

    /** Only admins reach hotel management (role middleware). */
    public function test_a_non_admin_cannot_manage_hotels(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manager']))
            ->get(route('admin.hotels.index'))->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'guest']))
            ->get(route('admin.hotels.index'))->assertForbidden();
    }
}
