<?php

namespace Database\Factories;

use App\Models\HotelChain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HotelChain>
 */
class HotelChainFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
        ];
    }
}
