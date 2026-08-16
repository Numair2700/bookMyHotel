<?php

namespace Database\Seeders;

use App\Models\RoomType;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AvailabilitySeeder extends Seeder
{
    /**
     * 180 days of rates-and-availability from today for every room type.
     * Weekend nights (Sat/Sun) are priced 20% above the weekday base rate.
     */
    public function run(): void
    {
        $period = CarbonPeriod::create(now()->startOfDay(), now()->copy()->addDays(179)->startOfDay());
        $now = now();

        foreach (RoomType::all() as $roomType) {
            $rows = [];

            foreach ($period as $date) {
                $rate = $date->isWeekend()
                    ? round((float) $roomType->base_rate * 1.20, 2)
                    : (float) $roomType->base_rate;

                $rows[] = [
                    'room_type_id' => $roomType->id,
                    'date' => $date->toDateString(),
                    'rooms_available' => $roomType->total_rooms,
                    'rate' => $rate,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Bulk insert per room type keeps 4,320 rows fast to seed.
            DB::table('availability')->insert($rows);
        }
    }
}
