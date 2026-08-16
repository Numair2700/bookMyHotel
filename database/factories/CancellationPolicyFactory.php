<?php

namespace Database\Factories;

use App\Models\CancellationPolicy;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CancellationPolicy>
 */
class CancellationPolicyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $freeHours = fake()->randomElement([24, 48, 72]);

        return [
            'hotel_id' => Hotel::factory(),
            'name' => $freeHours.'-hour free cancellation',
            'free_cancellation_hours' => $freeHours,
            'penalty_percentage' => fake()->randomElement([25.00, 40.00, 50.00]),
        ];
    }
}
