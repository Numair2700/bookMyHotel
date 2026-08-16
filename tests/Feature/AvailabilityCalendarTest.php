<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityCalendarTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The [JSON] calendar endpoint returns per-night rooms and rates for each
     * room type in the requested range, wrapped in { data }.
     */
    public function test_availability_calendar_returns_json_rates_for_a_date_range(): void
    {
        $hotel = Hotel::factory()->create();
        $roomType = RoomType::factory()->for($hotel)->create(['name' => 'Deluxe King', 'base_rate' => 200]);

        $d1 = now()->addDay()->toDateString();
        $d2 = now()->addDays(2)->toDateString();
        Availability::factory()->for($roomType)->create(['date' => $d1, 'rooms_available' => 4, 'rate' => 200]);
        Availability::factory()->for($roomType)->create(['date' => $d2, 'rooms_available' => 2, 'rate' => 240]);

        $response = $this->getJson(route('api.hotels.availability', [
            'hotel' => $hotel->id,
            'from' => $d1,
            'to' => $d2,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.hotel_id', $hotel->id)
            ->assertJsonPath('data.room_types.0.name', 'Deluxe King')
            ->assertJsonCount(2, 'data.room_types.0.nights')
            ->assertJsonPath('data.room_types.0.nights.0.rooms_available', 4)
            ->assertJsonPath('data.room_types.0.nights.1.rooms_available', 2);
    }

    public function test_availability_calendar_validates_the_date_range(): void
    {
        $hotel = Hotel::factory()->create();

        $this->getJson(route('api.hotels.availability', [
            'hotel' => $hotel->id,
            'from' => now()->addDays(5)->toDateString(),
            'to' => now()->addDay()->toDateString(), // to before from
        ]))->assertStatus(422);
    }
}
