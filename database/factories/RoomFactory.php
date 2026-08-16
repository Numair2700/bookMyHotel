<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'room_number' => (string) fake()->numberBetween(100, 999),
            'floor' => (string) fake()->numberBetween(1, 20),
            'status' => 'available',
        ];
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'maintenance',
        ]);
    }
}
