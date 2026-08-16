<?php

namespace Database\Factories;

use App\Models\Availability;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'date' => fake()->dateTimeBetween('now', '+180 days')->format('Y-m-d'),
            'rooms_available' => fake()->numberBetween(1, 12),
            'rate' => fake()->randomFloat(2, 150, 600),
        ];
    }

    /** No rooms free on this date. */
    public function soldOut(): static
    {
        return $this->state(fn (array $attributes): array => [
            'rooms_available' => 0,
        ]);
    }
}
