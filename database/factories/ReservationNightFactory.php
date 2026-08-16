<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\ReservationNight;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationNight>
 */
class ReservationNightFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'room_type_id' => RoomType::factory(),
            'stay_date' => now()->addDays(5)->toDateString(),
            'rate' => fake()->randomFloat(2, 150, 600),
        ];
    }
}
