<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\HotelChain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hotel>
 */
class HotelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chain_id' => HotelChain::factory(),
            'name' => fake()->company().' Hotel',
            'city' => fake()->city(),
            'country' => fake()->country(),
            'region' => fake()->randomElement(['asia', 'europe']),
            'address' => fake()->streetAddress(),
            'description' => fake()->paragraph(),
            'star_rating' => 5,
            'wifi_speed_mbps' => fake()->randomElement([100, 250, 500, 1000]),
            'has_workspace' => fake()->boolean(),
            'sustainability_certified' => fake()->boolean(),
        ];
    }

    public function sustainable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sustainability_certified' => true,
        ]);
    }

    public function notSustainable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sustainability_certified' => false,
        ]);
    }
}
