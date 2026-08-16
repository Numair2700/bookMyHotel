<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    /** FR15 — a manager can change the rate and rooms available for a date. */
    public function test_a_manager_can_update_the_rate_and_availability_for_a_date(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $roomType = RoomType::factory()->for(Hotel::factory())->create();
        $date = now()->addDays(10)->toDateString();
        Availability::factory()->for($roomType)->create(['date' => $date, 'rooms_available' => 5, 'rate' => 200]);

        $this->actingAs($manager)->put(route('manager.availability.update'), [
            'room_type_id' => $roomType->id,
            'date' => $date,
            'rooms_available' => 8,
            'rate' => 275,
        ])->assertRedirect();

        $this->assertDatabaseHas('availability', [
            'room_type_id' => $roomType->id,
            'date' => $date,
            'rooms_available' => 8,
            'rate' => 275.00,
        ]);
    }

    /** A date with no existing row is created (updateOrCreate). */
    public function test_updating_availability_creates_a_row_when_none_exists(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $roomType = RoomType::factory()->for(Hotel::factory())->create();
        $date = now()->addDays(200)->toDateString(); // beyond the seeded window

        $this->actingAs($manager)->put(route('manager.availability.update'), [
            'room_type_id' => $roomType->id,
            'date' => $date,
            'rooms_available' => 3,
            'rate' => 150,
        ])->assertRedirect();

        $this->assertDatabaseHas('availability', [
            'room_type_id' => $roomType->id,
            'date' => $date,
            'rooms_available' => 3,
        ]);
    }

    public function test_a_guest_cannot_edit_availability(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'guest']))
            ->put(route('manager.availability.update'), [])->assertForbidden();
    }
}
