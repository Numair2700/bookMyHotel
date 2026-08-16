<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * FR3 — a room type is only offered when it has a room free on every night
     * of the requested range. A missing night or a sold-out night excludes it.
     */
    public function test_search_returns_only_room_types_with_availability_across_the_full_range(): void
    {
        $hotel = Hotel::factory()->create();

        $checkIn = now()->addDay()->startOfDay();
        $checkOut = $checkIn->copy()->addDays(3); // three nights
        $nights = [
            $checkIn->toDateString(),
            $checkIn->copy()->addDay()->toDateString(),
            $checkIn->copy()->addDays(2)->toDateString(),
        ];

        // Available every night — should be returned.
        $full = RoomType::factory()->for($hotel)->create(['name' => 'Deluxe King']);
        foreach ($nights as $date) {
            Availability::factory()->for($full)->create(['date' => $date, 'rooms_available' => 5, 'rate' => 200]);
        }

        // Missing the middle night — should be excluded.
        $gap = RoomType::factory()->for($hotel)->create(['name' => 'Executive Suite']);
        Availability::factory()->for($gap)->create(['date' => $nights[0], 'rooms_available' => 5, 'rate' => 200]);
        Availability::factory()->for($gap)->create(['date' => $nights[2], 'rooms_available' => 5, 'rate' => 200]);

        // One night sold out — should be excluded.
        $soldOut = RoomType::factory()->for($hotel)->create(['name' => 'Family Room']);
        Availability::factory()->for($soldOut)->create(['date' => $nights[0], 'rooms_available' => 5, 'rate' => 200]);
        Availability::factory()->for($soldOut)->create(['date' => $nights[1], 'rooms_available' => 0, 'rate' => 200]);
        Availability::factory()->for($soldOut)->create(['date' => $nights[2], 'rooms_available' => 5, 'rate' => 200]);

        $response = $this->get(route('search', [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('search/results')
            ->where('count', 1)
            ->has('results', 1)
            ->where('results.0.room_type.id', $full->id)
            ->where('results.0.nights', 3)
            ->where('results.0.total_price', fn ($value) => (float) $value === 600.0)
        );
    }

    /**
     * FR3 — the price, room-type and sustainability filters each narrow the
     * result set correctly.
     */
    public function test_search_filters_by_price_room_type_and_sustainability(): void
    {
        $checkIn = now()->addDay()->startOfDay();
        $checkOut = $checkIn->copy()->addDay(); // one night
        $date = $checkIn->toDateString();

        $green = Hotel::factory()->sustainable()->create();
        $plain = Hotel::factory()->notSustainable()->create();

        $cheapGreen = RoomType::factory()->for($green)->create(['name' => 'Deluxe King']);
        Availability::factory()->for($cheapGreen)->create(['date' => $date, 'rooms_available' => 5, 'rate' => 100]);

        $luxeGreen = RoomType::factory()->for($green)->create(['name' => 'Executive Suite']);
        Availability::factory()->for($luxeGreen)->create(['date' => $date, 'rooms_available' => 5, 'rate' => 500]);

        $cheapPlain = RoomType::factory()->for($plain)->create(['name' => 'Deluxe King']);
        Availability::factory()->for($cheapPlain)->create(['date' => $date, 'rooms_available' => 5, 'rate' => 100]);

        $base = ['check_in' => $checkIn->toDateString(), 'check_out' => $checkOut->toDateString()];

        // Sustainability filter: only the two room types in the certified hotel.
        $this->get(route('search', $base + ['sustainable_only' => 1]))
            ->assertInertia(fn (Assert $p) => $p->component('search/results')->where('count', 2));

        // Price filter on top of sustainability: excludes the 500-a-night suite.
        $this->get(route('search', $base + ['sustainable_only' => 1, 'max_price' => 200]))
            ->assertInertia(fn (Assert $p) => $p->where('count', 1)->where('results.0.room_type.id', $cheapGreen->id));

        // Room-type filter: only the Executive Suite matches by name.
        $this->get(route('search', $base + ['room_type' => 'Executive']))
            ->assertInertia(fn (Assert $p) => $p->where('count', 1)->where('results.0.room_type.id', $luxeGreen->id));
    }

    /** FR3 — the facilities filter narrows to hotels with a workspace. */
    public function test_search_filters_by_facilities(): void
    {
        $checkIn = now()->addDay()->startOfDay();
        $checkOut = $checkIn->copy()->addDay();
        $date = $checkIn->toDateString();

        $withWorkspace = Hotel::factory()->create(['has_workspace' => true]);
        $withoutWorkspace = Hotel::factory()->create(['has_workspace' => false]);

        $roomA = RoomType::factory()->for($withWorkspace)->create();
        Availability::factory()->for($roomA)->create(['date' => $date, 'rooms_available' => 5, 'rate' => 200]);
        $roomB = RoomType::factory()->for($withoutWorkspace)->create();
        Availability::factory()->for($roomB)->create(['date' => $date, 'rooms_available' => 5, 'rate' => 200]);

        $this->get(route('search', [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'has_workspace' => 1,
        ]))->assertInertia(fn (Assert $page) => $page
            ->component('search/results')
            ->where('count', 1)
            ->where('results.0.room_type.id', $roomA->id));
    }

    public function test_search_requires_a_valid_date_range(): void
    {
        $this->get(route('search', ['check_in' => 'not-a-date']))
            ->assertSessionHasErrors(['check_in', 'check_out']);
    }
}
