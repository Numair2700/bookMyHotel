<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'code' => strtoupper(fake()->unique()->bothify('????##')),
            'description' => fake()->sentence(4),
            'discount_type' => fake()->randomElement(['percentage', 'fixed']),
            'discount_value' => fake()->randomFloat(2, 5, 50),
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_to' => now()->addMonths(3)->toDateString(),
            'active' => true,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'valid_from' => now()->subMonths(3)->toDateString(),
            'valid_to' => now()->subMonth()->toDateString(),
        ]);
    }
}
