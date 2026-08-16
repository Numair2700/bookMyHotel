<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\ReservationNight;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    /** FR18 — the admin dashboard shows the headline figures and per-hotel table. */
    public function test_an_admin_can_view_the_analytics_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hotel = Hotel::factory()->create();
        $roomType = RoomType::factory()->for($hotel)->create();
        $reservation = Reservation::factory()->for($hotel)->create(['status' => 'completed']);
        ReservationNight::factory()->for($reservation)->for($roomType)->create([
            'stay_date' => now()->subDays(5)->toDateString(), 'rate' => 300,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/analytics')
                ->where('summary.room_nights', 1)
                ->where('summary.average_daily_rate', 300)
                ->has('per_hotel', 1));
    }

    /** FR18 — the [JSON] refresh endpoint returns the figures as JSON. */
    public function test_the_analytics_refresh_endpoint_returns_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('api.admin.analytics.refresh', [
                'from' => now()->subDays(30)->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('data.summary.room_nights', 0)
            ->assertJsonPath('data.summary.average_daily_rate', null);
    }

    public function test_a_non_admin_cannot_view_analytics(): void
    {
        $guest = User::factory()->create(['role' => 'guest']);

        $this->actingAs($guest)->get(route('admin.analytics.index'))->assertForbidden();
        $this->actingAs($guest)->getJson(route('api.admin.analytics.refresh'))->assertForbidden();
    }
}
