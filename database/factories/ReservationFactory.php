<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 200, 1200);

        return [
            'user_id' => User::factory(),
            'hotel_id' => Hotel::factory(),
            'reference' => 'BMH-'.Str::upper(Str::random(8)),
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'guests' => fake()->numberBetween(1, 4),
            'status' => 'confirmed',
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'total_amount' => $subtotal,
            'promotion_id' => null,
            'is_sustainable' => false,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'pending']);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'completed']);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'cancelled']);
    }

    public function sustainable(): static
    {
        return $this->state(fn (array $attributes): array => ['is_sustainable' => true]);
    }
}
