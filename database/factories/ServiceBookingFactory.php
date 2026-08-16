<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Service;
use App\Models\ServiceBooking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceBooking>
 */
class ServiceBookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'service_id' => Service::factory(),
            'service_date' => now()->addDays(6)->toDateString(),
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price' => fake()->randomFloat(2, 30, 200),
        ];
    }
}
