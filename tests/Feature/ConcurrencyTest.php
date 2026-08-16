<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Modules\Availability\Contracts\AvailabilityServiceInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    // Not RefreshDatabase: that wraps each test in a transaction on the default
    // connection, which would collide with the two real transactions this test
    // drives to force a race. DatabaseMigrations rebuilds the schema instead.
    use DatabaseMigrations;

    /**
     * NFR5 — two overlapping transactions competing for the last room. The row
     * lock taken by the first must block the second, so exactly one can consume
     * the room and the availability count can never go negative.
     */
    public function test_two_concurrent_bookings_for_the_last_room_only_one_succeeds(): void
    {
        $hotel = Hotel::factory()->create();
        $roomType = RoomType::factory()->for($hotel)->create();

        $checkIn = now()->addDay()->toDateString();
        $checkOut = now()->addDays(2)->toDateString(); // one night

        // The last room on that night.
        Availability::factory()->for($roomType)->create([
            'date' => $checkIn, 'rooms_available' => 1, 'rate' => 200,
        ]);

        // A second, independent connection to the same database, configured to
        // fail fast rather than wait when it hits a held row lock.
        config(['database.connections.race' => config('database.connections.mysql')]);
        DB::purge('race');
        DB::connection('race')->statement('SET SESSION innodb_lock_wait_timeout = 1');

        /** @var AvailabilityServiceInterface $availability */
        $availability = app(AvailabilityServiceInterface::class);

        $secondTransactionFailed = false;

        // Transaction A: lock and decrement the last room, then hold the lock.
        DB::beginTransaction();

        try {
            $availability->reserveStay($roomType->id, $checkIn, $checkOut);

            // Transaction B (other connection) tries to grab the same row while A
            // still holds it. It cannot, and times out on the lock wait.
            try {
                DB::connection('race')
                    ->table('availability')
                    ->where('room_type_id', $roomType->id)
                    ->where('date', '>=', $checkIn)
                    ->where('date', '<', $checkOut)
                    ->lockForUpdate()
                    ->get();
            } catch (QueryException $e) {
                $secondTransactionFailed = true;
            }
        } finally {
            // A wins: commit its booking of the last room.
            DB::commit();
        }

        $this->assertTrue(
            $secondTransactionFailed,
            'The second transaction should have been blocked by the first transaction\'s row lock.'
        );

        // Exactly one room was consumed; the count is zero, never negative.
        $this->assertSame(0, (int) DB::table('availability')
            ->where('room_type_id', $roomType->id)
            ->where('date', $checkIn)
            ->value('rooms_available'));
    }
}
