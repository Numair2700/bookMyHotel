<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'name' => fake()->randomElement(['Deluxe King', 'Executive Suite', 'Family Room']),
            'description' => fake()->sentence(),
            'max_occupancy' => fake()->numberBetween(2, 4),
            'base_rate' => fake()->randomFloat(2, 150, 600),
            'total_rooms' => fake()->numberBetween(5, 15),
        ];
    }
}
