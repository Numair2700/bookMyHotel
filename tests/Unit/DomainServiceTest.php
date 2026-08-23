<?php

namespace Tests\Unit;

use App\Exceptions\AvailabilityException;
use App\Models\Availability;
use App\Models\CancellationPolicy;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\ReservationNight;
use App\Models\RoomType;
use App\Models\User;
use App\Modules\Availability\AvailabilityService;
use App\Services\AnalyticsService;
use App\Services\CancellationService;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Unit tests for the domain service methods. Each test exercises a single
 * service method directly, off the HTTP path, as evidence for Section 6.
 */
class DomainServiceTest extends TestCase
{
    use RefreshDatabase;

    /** UT-01 (FR13) — reward points convert to a discount at 1 point = 1 unit. */
    public function test_reward_points_convert_to_a_discount_value(): void
    {
        $loyalty = new LoyaltyService;

        $this->assertSame(150.0, $loyalty->discountValue(150));
        $this->assertSame(0.0, $loyalty->discountValue(0));
        $this->assertSame(0.0, $loyalty->discountValue(-25));
    }

    /** UT-02 (FR13) — points are earned only on a sustainable stay, at floor(total / 10). */
    public function test_points_are_earned_only_on_a_sustainable_stay(): void
    {
        $loyalty = new LoyaltyService;
        $user = User::factory()->create(['reward_points_balance' => 0]);

        $sustainable = Reservation::factory()->for($user)->create([
            'is_sustainable' => true,
            'total_amount' => 250,
        ]);
        $loyalty->awardForReservation($sustainable);
        $this->assertSame(25, $user->fresh()->reward_points_balance);

        $standard = Reservation::factory()->for($user)->create([
            'is_sustainable' => false,
            'total_amount' => 400,
        ]);
        $loyalty->awardForReservation($standard);
        $this->assertSame(25, $user->fresh()->reward_points_balance);
    }

    /** UT-03 (FR7, NFR8) — full refund inside the free window, penalty applied after it. */
    public function test_refund_quote_respects_the_free_cancellation_window(): void
    {
        $cancellation = app(CancellationService::class);
        $hotel = Hotel::factory()->create();
        CancellationPolicy::factory()->for($hotel)->create([
            'free_cancellation_hours' => 48,
            'penalty_percentage' => 30,
        ]);

        $inWindow = Reservation::factory()->for($hotel)->create([
            'total_amount' => 1000,
            'check_in' => Carbon::now()->addDays(10),
        ]);
        $quoteIn = $cancellation->quote($inWindow);
        $this->assertTrue($quoteIn->withinFreeWindow);
        $this->assertSame(100.0, $quoteIn->percent);
        $this->assertSame(1000.0, $quoteIn->amount);

        $lateCancel = Reservation::factory()->for($hotel)->create([
            'total_amount' => 1000,
            'check_in' => Carbon::now()->addHours(12),
        ]);
        $quoteLate = $cancellation->quote($lateCancel);
        $this->assertFalse($quoteLate->withinFreeWindow);
        $this->assertSame(70.0, $quoteLate->percent);
        $this->assertSame(700.0, $quoteLate->amount);
    }

    /** UT-04 (FR18) — ADR is room revenue divided by room nights. */
    public function test_average_daily_rate_is_revenue_over_room_nights(): void
    {
        $analytics = new AnalyticsService;
        $reservation = Reservation::factory()->create(['status' => 'confirmed']);

        ReservationNight::factory()->for($reservation)->create([
            'stay_date' => '2026-09-01',
            'rate' => 200,
        ]);
        ReservationNight::factory()->for($reservation)->create([
            'stay_date' => '2026-09-02',
            'rate' => 300,
        ]);

        $summary = $analytics->summary('2026-09-01', '2026-09-30');

        $this->assertSame(2, $summary['room_nights']);
        $this->assertSame(500.0, $summary['room_revenue']);
        $this->assertSame(250.0, $summary['average_daily_rate']);
    }

    /** UT-05 (FR5, NFR5) — reserving a stay decrements every available night. */
    public function test_reserve_stay_decrements_each_available_night(): void
    {
        $service = app(AvailabilityService::class);
        $roomType = RoomType::factory()->create();

        Availability::factory()->for($roomType)->create([
            'date' => '2026-09-01',
            'rooms_available' => 3,
            'rate' => 200,
        ]);
        Availability::factory()->for($roomType)->create([
            'date' => '2026-09-02',
            'rooms_available' => 3,
            'rate' => 200,
        ]);

        $nights = $service->reserveStay($roomType->id, '2026-09-01', '2026-09-03');

        $this->assertCount(2, $nights);
        $this->assertSame(2, Availability::where('rooms_available', 2)->count());
    }

    /** UT-06 (FR5, NFR5) — a sold-out night makes the whole stay unavailable. */
    public function test_reserve_stay_rejects_a_sold_out_night(): void
    {
        $service = app(AvailabilityService::class);
        $roomType = RoomType::factory()->create();

        Availability::factory()->for($roomType)->create([
            'date' => '2026-09-01',
            'rooms_available' => 3,
            'rate' => 200,
        ]);
        Availability::factory()->for($roomType)->create([
            'date' => '2026-09-02',
            'rooms_available' => 0,
            'rate' => 200,
        ]);

        $this->expectException(AvailabilityException::class);
        $service->reserveStay($roomType->id, '2026-09-01', '2026-09-03');
    }
}
