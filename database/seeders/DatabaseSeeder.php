<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database in dependency order:
     * catalogue -> availability calendar -> users -> demo reservations.
     */
    public function run(): void
    {
        $this->call([
            CatalogSeeder::class,
            AvailabilitySeeder::class,
            UserSeeder::class,
            DemoReservationSeeder::class,
        ]);
    }
}
