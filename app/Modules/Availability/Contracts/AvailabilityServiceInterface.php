<?php

namespace App\Modules\Availability\Contracts;

use App\Exceptions\AvailabilityException;
use App\Models\Availability;
use App\Modules\Availability\Data\SearchCriteria;
use Illuminate\Support\Collection;

/**
 * Public interface of the Availability bounded module. Every other domain
 * (search UI, booking engine) talks to availability only through this contract,
 * never by querying the tables directly, so the module could be extracted into
 * a separate service without changing its callers.
 */
interface AvailabilityServiceInterface
{
    /**
     * Room types that have a room free on every night of the requested range,
     * after applying the destination, price, room-type and sustainability
     * filters. A room type is only returned if the whole range is available.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function search(SearchCriteria $criteria): Collection;

    /**
     * The rates-and-availability calendar for one hotel across a date range,
     * grouped by room type. Backs the JSON calendar endpoint.
     *
     * @return array<string, mixed>
     */
    public function calendar(int $hotelId, string $from, string $to): array;

    /**
     * Lock the availability rows for every night of the stay [check_in,
     * check_out), verify the whole range is present with capacity, decrement
     * each night by one room, and return the locked rows carrying that night's
     * rate. Must be called inside a database transaction; the row locks are how
     * two concurrent bookings for the last room are serialised (NFR5).
     *
     * @return Collection<int, Availability>
     *
     * @throws AvailabilityException when the range is incomplete or sold out.
     */
    public function reserveStay(int $roomTypeId, string $checkIn, string $checkOut): Collection;

    /**
     * Restore one room per night for a cancelled stay [check_in, check_out).
     * Must be called inside a database transaction.
     */
    public function releaseStay(int $roomTypeId, string $checkIn, string $checkOut): void;
}
