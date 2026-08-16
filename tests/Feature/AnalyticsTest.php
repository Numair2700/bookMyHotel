<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\ReservationNight;
use App\Models\RoomType;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /** FR18 — ADR equals room revenue divided by room nights. */
    public function test_adr_equals_room_revenue_divided_by_room_nights(): void
    {
        $hotel = Hotel::factory()->create();
        $roomType = RoomType::factory()->for($hotel)->create();
        $reservation = Reservation::factory()->for($hotel)->create(['status' => 'confirmed']);

        // Three nights at 200, 200 and 300 → revenue 700, nights 3, ADR 233.33.
        foreach ([[10, 200], [11, 200], [12, 300]] as [$offset, $rate]) {
            ReservationNight::factory()->for($reservation)->for($roomType)->create([
                'stay_date' => now()->addDays($offset)->toDateString(),
                'rate' => $rate,
            ]);
        }

        $summary = app(AnalyticsService::class)->summary(
            now()->addDays(9)->toDateString(),
            now()->addDays(13)->toDateString(),
        );

        $this->assertSame(3, $summary['room_nights']);
        $this->assertEqualsWithDelta(700.0, $summary['room_revenue'], 0.001);
        $this->assertEqualsWithDelta(233.33, $summary['average_daily_rate'], 0.001);
    }

    /** FR18 — ADR is null (not an error, not zero) when there are no room nights. */
    public function test_analytics_returns_null_adr_when_there_are_no_room_nights(): void
    {
        $summary = app(AnalyticsService::class)->summary(
            now()->toDateString(),
            now()->addDays(5)->toDateString(),
        );

        $this->assertSame(0, $summary['room_nights']);
        $this->assertSame(0.0, $summary['room_revenue']);
        $this->assertNull($summary['average_daily_rate']);
    }

    /** Only confirmed/completed reservations count towards analytics. */
    public function test_analytics_excludes_pending_and_cancelled_reservations(): void
    {
        $hotel = Hotel::factory()->create();
        $roomType = RoomType::factory()->for($hotel)->create();

        $cancelled = Reservation::factory()->for($hotel)->cancelled()->create();
        $pending = Reservation::factory()->for($hotel)->pending()->create();

        foreach ([$cancelled, $pending] as $reservation) {
            ReservationNight::factory()->for($reservation)->for($roomType)->create([
                'stay_date' => now()->addDays(10)->toDateString(),
                'rate' => 500,
            ]);
        }

        $summary = app(AnalyticsService::class)->summary(
            now()->addDays(9)->toDateString(),
            now()->addDays(11)->toDateString(),
        );

        $this->assertSame(0, $summary['room_nights']);
        $this->assertNull($summary['average_daily_rate']);
    }

    /** The per-hotel breakdown reports the same metrics grouped by hotel. */
    public function test_per_hotel_breakdown_reports_metrics_for_each_hotel(): void
    {
        $hotelA = Hotel::factory()->create(['name' => 'Alpha Hotel']);
        $roomA = RoomType::factory()->for($hotelA)->create();
        $resA = Reservation::factory()->for($hotelA)->create(['status' => 'completed']);
        ReservationNight::factory()->for($resA)->for($roomA)->create([
            'stay_date' => now()->addDays(10)->toDateString(), 'rate' => 400,
        ]);

        $hotelB = Hotel::factory()->create(['name' => 'Beta Hotel']);
        $roomB = RoomType::factory()->for($hotelB)->create();
        $resB = Reservation::factory()->for($hotelB)->create(['status' => 'confirmed']);
        ReservationNight::factory()->for($resB)->for($roomB)->create([
            'stay_date' => now()->addDays(10)->toDateString(), 'rate' => 250,
        ]);

        $breakdown = app(AnalyticsService::class)->perHotel(
            now()->addDays(9)->toDateString(),
            now()->addDays(11)->toDateString(),
        );

        $this->assertCount(2, $breakdown);
        // Ordered by revenue descending, so Alpha (400) comes first.
        $this->assertSame('Alpha Hotel', $breakdown[0]['hotel']);
        $this->assertEqualsWithDelta(400.0, $breakdown[0]['average_daily_rate'], 0.001);
    }
}
