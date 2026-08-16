<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'user_id' => User::factory(),
            'hotel_id' => Hotel::factory(),
            'rating' => fake()->numberBetween(1, 10),
            'comment' => fake()->sentence(12),
            'approved' => false,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => ['approved' => true]);
    }
}
