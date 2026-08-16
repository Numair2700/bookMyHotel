<?php

namespace Database\Seeders;

use App\Models\CancellationPolicy;
use App\Models\Hotel;
use App\Models\HotelChain;
use App\Models\Promotion;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * The static hotel catalogue: 4 chains, 2 hotels each (one Asian, one
     * European), 3 room types per hotel, physical rooms, services, one
     * cancellation policy each, and two active promotions.
     */
    public function run(): void
    {
        // chain name => [ [hotel], [hotel] ]. sustainability_certified is set
        // on at least three hotels (here four) per the brief.
        $catalogue = [
            'Marriott' => [
                ['name' => 'Marriott Tokyo Bay', 'city' => 'Tokyo', 'country' => 'Japan', 'region' => 'asia', 'sustainable' => false],
                ['name' => 'Marriott Paris Opéra', 'city' => 'Paris', 'country' => 'France', 'region' => 'europe', 'sustainable' => true],
            ],
            'Hilton' => [
                ['name' => 'Hilton Singapore Orchard', 'city' => 'Singapore', 'country' => 'Singapore', 'region' => 'asia', 'sustainable' => true],
                ['name' => 'Hilton London Park Lane', 'city' => 'London', 'country' => 'United Kingdom', 'region' => 'europe', 'sustainable' => false],
            ],
            'Hyatt' => [
                ['name' => 'Grand Hyatt Bangkok', 'city' => 'Bangkok', 'country' => 'Thailand', 'region' => 'asia', 'sustainable' => false],
                ['name' => 'Park Hyatt Rome', 'city' => 'Rome', 'country' => 'Italy', 'region' => 'europe', 'sustainable' => true],
            ],
            'Four Seasons' => [
                ['name' => 'Four Seasons Hong Kong', 'city' => 'Hong Kong', 'country' => 'China', 'region' => 'asia', 'sustainable' => true],
                ['name' => 'Four Seasons Barcelona', 'city' => 'Barcelona', 'country' => 'Spain', 'region' => 'europe', 'sustainable' => false],
            ],
        ];

        // Three room types every hotel offers. base_rate is scaled per hotel below.
        $roomTypeTemplates = [
            ['name' => 'Deluxe King', 'max_occupancy' => 2, 'rate_factor' => 1.0, 'total_rooms' => 12],
            ['name' => 'Executive Suite', 'max_occupancy' => 3, 'rate_factor' => 1.8, 'total_rooms' => 8],
            ['name' => 'Family Room', 'max_occupancy' => 4, 'rate_factor' => 1.5, 'total_rooms' => 6],
        ];

        // One service in each of the four categories (covers all categories).
        $serviceTemplates = [
            ['name' => 'Signature Restaurant', 'category' => 'dining', 'price' => 85.00],
            ['name' => 'Spa & Wellness', 'category' => 'spa', 'price' => 140.00],
            ['name' => 'Airport Chauffeur', 'category' => 'vehicle_hire', 'price' => 60.00],
            ['name' => 'City Heritage Tour', 'category' => 'tour', 'price' => 45.00],
        ];

        $hotelIndex = 0;
        $promotionTargets = [];

        foreach ($catalogue as $chainName => $hotels) {
            $chain = HotelChain::create(['name' => $chainName]);

            foreach ($hotels as $data) {
                // Base nightly price band varies by hotel so the calendar looks real.
                $hotelBaseRate = 220 + ($hotelIndex * 35);

                $hotel = Hotel::create([
                    'chain_id' => $chain->id,
                    'name' => $data['name'],
                    'city' => $data['city'],
                    'country' => $data['country'],
                    'region' => $data['region'],
                    'address' => fake()->streetAddress().', '.$data['city'],
                    'description' => 'A five-star '.$chainName.' property in the heart of '.$data['city'].'.',
                    'star_rating' => 5,
                    'wifi_speed_mbps' => fake()->randomElement([100, 250, 500, 1000]),
                    'has_workspace' => fake()->boolean(70),
                    'sustainability_certified' => $data['sustainable'],
                ]);

                foreach ($roomTypeTemplates as $template) {
                    $baseRate = round($hotelBaseRate * $template['rate_factor'], 2);

                    $roomType = RoomType::create([
                        'hotel_id' => $hotel->id,
                        'name' => $template['name'],
                        'description' => $template['name'].' at '.$hotel->name.'.',
                        'max_occupancy' => $template['max_occupancy'],
                        'base_rate' => $baseRate,
                        'total_rooms' => $template['total_rooms'],
                    ]);

                    // Physical inventory matching total_rooms (housekeeping/front desk).
                    for ($i = 1; $i <= $template['total_rooms']; $i++) {
                        $floor = (int) ceil($i / 4) + 1;
                        Room::create([
                            'room_type_id' => $roomType->id,
                            'room_number' => $floor.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                            'floor' => (string) $floor,
                            'status' => fake()->boolean(90) ? 'available' : 'maintenance',
                        ]);
                    }
                }

                foreach ($serviceTemplates as $template) {
                    Service::create([
                        'hotel_id' => $hotel->id,
                        'name' => $template['name'],
                        'category' => $template['category'],
                        'description' => $template['name'].' at '.$hotel->name.'.',
                        'price' => $template['price'],
                        'active' => true,
                    ]);
                }

                // One cancellation policy per hotel, free window varying 24-72h so
                // the refund logic is visibly different across hotels (NFR8).
                $freeHours = [24, 48, 72][$hotelIndex % 3];
                CancellationPolicy::create([
                    'hotel_id' => $hotel->id,
                    'name' => $freeHours.'-hour free cancellation',
                    'free_cancellation_hours' => $freeHours,
                    'penalty_percentage' => [25.00, 40.00, 50.00][$hotelIndex % 3],
                ]);

                $promotionTargets[] = $hotel;
                $hotelIndex++;
            }
        }

        // Two active promotions attached to the first two hotels.
        Promotion::create([
            'hotel_id' => $promotionTargets[0]->id,
            'code' => 'SUMMER10',
            'description' => '10% off your summer stay',
            'discount_type' => 'percentage',
            'discount_value' => 10.00,
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_to' => now()->addMonths(3)->toDateString(),
            'active' => true,
        ]);

        Promotion::create([
            'hotel_id' => $promotionTargets[1]->id,
            'code' => 'STAY50',
            'description' => 'AED 50 off bookings over three nights',
            'discount_type' => 'fixed',
            'discount_value' => 50.00,
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_to' => now()->addMonths(3)->toDateString(),
            'active' => true,
        ]);
    }
}
