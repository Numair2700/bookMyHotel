<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * 30 users: 1 admin, 4 managers, 25 guests. All share the demo password
     * "password" so the README can document one credential per role.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // Stable demo accounts referenced by the README, one per role.
        User::create([
            'name' => 'Site Administrator',
            'email' => 'admin@bookmyhotel.test',
            'password' => $password,
            'phone' => fake()->phoneNumber(),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Hotel Manager',
            'email' => 'manager@bookmyhotel.test',
            'password' => $password,
            'phone' => fake()->phoneNumber(),
            'role' => 'manager',
        ]);

        User::create([
            'name' => 'Guest User',
            'email' => 'guest@bookmyhotel.test',
            'password' => $password,
            'phone' => fake()->phoneNumber(),
            'role' => 'guest',
        ]);

        // Remaining 3 managers.
        for ($i = 2; $i <= 4; $i++) {
            User::create([
                'name' => fake()->name(),
                'email' => "manager{$i}@bookmyhotel.test",
                'password' => $password,
                'phone' => fake()->phoneNumber(),
                'role' => 'manager',
            ]);
        }

        // Remaining 24 guests.
        for ($i = 2; $i <= 25; $i++) {
            User::create([
                'name' => fake()->name(),
                'email' => "guest{$i}@bookmyhotel.test",
                'password' => $password,
                'phone' => fake()->phoneNumber(),
                'role' => 'guest',
            ]);
        }
    }
}
