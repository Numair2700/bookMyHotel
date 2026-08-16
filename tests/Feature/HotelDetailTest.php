<?php

namespace Tests\Feature;

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
}
