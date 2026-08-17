<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\CancellationPolicy;
use App\Models\Hotel;
use App\Models\Promotion;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HotelDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * FR4 — the hotel property page exposes room types, the cancellation policy
     * and only in-date promotions. Expired promotions are not shown.
     */
    public function test_hotel_detail_page_shows_room_types_policies_and_in_date_promotions(): void
    {
        $hotel = Hotel::factory()->create(['name' => 'Test Grand Hotel']);
        RoomType::factory()->count(3)->for($hotel)->create();
        CancellationPolicy::factory()->for($hotel)->create(['free_cancellation_hours' => 48]);
        Promotion::factory()->for($hotel)->create(['code' => 'SAVE10']);
        Promotion::factory()->for($hotel)->expired()->create(['code' => 'OLDDEAL']);

        $response = $this->get(route('hotels.show', $hotel));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('hotels/show')
            ->where('hotel.name', 'Test Grand Hotel')
            ->has('room_types', 3)
            ->has('cancellation_policies', 1)
            ->has('promotions', 1)
            ->where('promotions.0.code', 'SAVE10')
            ->where('average_rating', null)
        );
    }

    /** A dated visit prices each room for those nights (matching the search card). */
    public function test_hotel_page_prices_a_room_for_the_searched_dates(): void
    {
        $hotel = Hotel::factory()->create();
        $roomType = RoomType::factory()->for($hotel)->create();

        $checkIn = now()->addDay()->startOfDay();
        $checkOut = $checkIn->copy()->addDays(2); // two nights

        Availability::factory()->for($roomType)->create([
            'date' => $checkIn->toDateString(), 'rooms_available' => 3, 'rate' => 200,
        ]);
        Availability::factory()->for($roomType)->create([
            'date' => $checkIn->copy()->addDay()->toDateString(), 'rooms_available' => 3, 'rate' => 300,
        ]);

        $this->get(route('hotels.show', [
            'hotel' => $hotel,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
        ]))->assertInertia(fn (Assert $page) => $page
            ->where('room_types.0.available_for_dates', true)
            ->where('room_types.0.nights', 2)
            ->where('room_types.0.nightly_rate', 250)
            ->where('room_types.0.total_price', 500)
        );
    }

    /** A room missing (or sold out on) any night of the stay is flagged unavailable. */
    public function test_a_room_without_full_availability_is_flagged_unavailable(): void
    {
        $hotel = Hotel::factory()->create();
        $roomType = RoomType::factory()->for($hotel)->create();

        $checkIn = now()->addDay()->startOfDay();
        $checkOut = $checkIn->copy()->addDays(2); // two nights

        // Only the first night is available; the second is sold out.
        Availability::factory()->for($roomType)->create([
            'date' => $checkIn->toDateString(), 'rooms_available' => 3, 'rate' => 200,
        ]);
        Availability::factory()->for($roomType)->soldOut()->create([
            'date' => $checkIn->copy()->addDay()->toDateString(), 'rate' => 300,
        ]);

        $this->get(route('hotels.show', [
            'hotel' => $hotel,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
        ]))->assertInertia(fn (Assert $page) => $page
            ->where('room_types.0.available_for_dates', false)
            ->where('room_types.0.nights', null)
            ->where('room_types.0.total_price', null)
        );
    }

    /** Browsing without dates leaves availability unknown and shows base prices. */
    public function test_browsing_without_dates_leaves_availability_null(): void
    {
        $hotel = Hotel::factory()->create();
        RoomType::factory()->for($hotel)->create(['base_rate' => 180]);

        $this->get(route('hotels.show', $hotel))
            ->assertInertia(fn (Assert $page) => $page
                ->where('room_types.0.available_for_dates', null)
                ->where('room_types.0.nights', null)
                ->where('room_types.0.nightly_rate', 180)
            );
    }
}
