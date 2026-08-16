<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'name' => fake()->randomElement(['Signature Restaurant', 'Spa & Wellness', 'Airport Chauffeur', 'City Heritage Tour']),
            'category' => fake()->randomElement(['dining', 'spa', 'vehicle_hire', 'tour']),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 30, 200),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['active' => false]);
    }
}
