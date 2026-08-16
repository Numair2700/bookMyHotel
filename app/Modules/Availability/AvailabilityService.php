<?php

namespace App\Modules\Availability;

use App\Exceptions\AvailabilityException;
use App\Models\Availability;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Modules\Availability\Contracts\AvailabilityServiceInterface;
use App\Modules\Availability\Data\SearchCriteria;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AvailabilityService implements AvailabilityServiceInterface
{
    public function search(SearchCriteria $criteria): Collection
    {
        $expectedNights = $criteria->nights();

        $rows = DB::table('room_types')
            ->join('hotels', 'hotels.id', '=', 'room_types.hotel_id')
            ->join('availability', 'availability.room_type_id', '=', 'room_types.id')
            // Only nights that actually have a room free are counted. The range
            // is half-open [check_in, check_out): the check-out day is not a
            // booked night.
            ->where('availability.date', '>=', $criteria->checkIn)
            ->where('availability.date', '<', $criteria->checkOut)
            ->where('availability.rooms_available', '>=', 1)
            ->when($criteria->destination, function (Builder $q, string $destination): void {
                $q->where(function (Builder $inner) use ($destination): void {
                    $inner->where('hotels.city', 'like', "%{$destination}%")
                        ->orWhere('hotels.country', 'like', "%{$destination}%")
                        ->orWhere('hotels.name', 'like', "%{$destination}%");
                });
            })
            ->when($criteria->sustainableOnly, function (Builder $q): void {
                $q->where('hotels.sustainability_certified', true);
            })
            ->when($criteria->requiresWorkspace, function (Builder $q): void {
                $q->where('hotels.has_workspace', true);
            })
            ->when($criteria->roomType, function (Builder $q, string $roomType): void {
                $q->where('room_types.name', 'like', "%{$roomType}%");
            })
            ->when($criteria->guests, function (Builder $q, int $guests): void {
                $q->where('room_types.max_occupancy', '>=', $guests);
            })
            ->groupBy('room_types.id', 'hotels.id')
            // The heart of FR3: a room type qualifies only if every night of the
            // range is present with capacity, so distinct available dates must
            // equal the number of nights requested.
            ->havingRaw('COUNT(DISTINCT availability.date) = ?', [$expectedNights])
            ->when($criteria->minPrice !== null, function (Builder $q) use ($criteria): void {
                $q->havingRaw('AVG(availability.rate) >= ?', [$criteria->minPrice]);
            })
            ->when($criteria->maxPrice !== null, function (Builder $q) use ($criteria): void {
                $q->havingRaw('AVG(availability.rate) <= ?', [$criteria->maxPrice]);
            })
            ->select([
                'room_types.id as room_type_id',
                'room_types.name as room_type_name',
                'room_types.description as room_type_description',
                'room_types.max_occupancy',
                'hotels.id as hotel_id',
                'hotels.name as hotel_name',
                'hotels.city',
                'hotels.country',
                'hotels.region',
                'hotels.star_rating',
                'hotels.sustainability_certified',
                'hotels.has_workspace',
                'hotels.wifi_speed_mbps',
            ])
            ->selectRaw('SUM(availability.rate) as total_price')
            ->selectRaw('AVG(availability.rate) as avg_nightly_rate')
            ->orderByRaw('SUM(availability.rate) asc')
            ->get();

        return $rows->map(fn (\stdClass $row) => $this->presentSearchRow($row, $expectedNights));
    }

    public function calendar(int $hotelId, string $from, string $to): array
    {
        $hotel = Hotel::findOrFail($hotelId);

        // Inclusive of the `to` date: compare against the day after it so a
        // stored time component on the date column does not exclude that day.
        $toExclusive = Carbon::parse($to)->addDay()->toDateString();

        $roomTypes = $hotel->roomTypes()
            ->with(['availability' => function ($q) use ($from, $toExclusive): void {
                $q->where('date', '>=', $from)
                    ->where('date', '<', $toExclusive)
                    ->orderBy('date');
            }])
            ->get();

        return [
            'hotel_id' => $hotel->id,
            'from' => $from,
            'to' => $to,
            'room_types' => $roomTypes->map(fn (RoomType $roomType) => $this->presentCalendarRoomType($roomType))
                ->values()
                ->all(),
        ];
    }

    public function reserveStay(int $roomTypeId, string $checkIn, string $checkOut): Collection
    {
        // Lock every night of the stay so a concurrent booking for the same
        // room type blocks here until this transaction commits or rolls back.
        $nights = Availability::query()
            ->where('room_type_id', $roomTypeId)
            ->where('date', '>=', $checkIn)
            ->where('date', '<', $checkOut)
            ->orderBy('date')
            ->lockForUpdate()
            ->get();

        $expectedNights = (int) Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));

        if ($nights->count() !== $expectedNights) {
            throw new AvailabilityException('The availability calendar is incomplete for these dates.');
        }

        if ($nights->contains(fn (Availability $night): bool => $night->rooms_available < 1)) {
            throw new AvailabilityException('No rooms remaining for these dates.');
        }

        $nights->each(fn (Availability $night) => $night->decrement('rooms_available'));

        return $nights;
    }

    public function releaseStay(int $roomTypeId, string $checkIn, string $checkOut): void
    {
        Availability::query()
            ->where('room_type_id', $roomTypeId)
            ->where('date', '>=', $checkIn)
            ->where('date', '<', $checkOut)
            ->lockForUpdate()
            ->get()
            ->each(fn (Availability $night) => $night->increment('rooms_available'));
    }

    /**
     * Shape a single search result row from the aggregated query.
     *
     * @return array<string, mixed>
     */
    private function presentSearchRow(\stdClass $row, int $nights): array
    {
        return [
            'hotel' => [
                'id' => (int) $row->hotel_id,
                'name' => $row->hotel_name,
                'city' => $row->city,
                'country' => $row->country,
                'region' => $row->region,
                'star_rating' => (int) $row->star_rating,
                'sustainability_certified' => (bool) $row->sustainability_certified,
                'has_workspace' => (bool) $row->has_workspace,
                'wifi_speed_mbps' => $row->wifi_speed_mbps !== null ? (int) $row->wifi_speed_mbps : null,
            ],
            'room_type' => [
                'id' => (int) $row->room_type_id,
                'name' => $row->room_type_name,
                'description' => $row->room_type_description,
                'max_occupancy' => (int) $row->max_occupancy,
            ],
            'nights' => $nights,
            'avg_nightly_rate' => round((float) $row->avg_nightly_rate, 2),
            'total_price' => round((float) $row->total_price, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentCalendarRoomType(RoomType $roomType): array
    {
        return [
            'id' => $roomType->id,
            'name' => $roomType->name,
            'base_rate' => (float) $roomType->base_rate,
            'nights' => $roomType->availability
                ->map(fn (Availability $night) => $this->presentCalendarNight($night))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentCalendarNight(Availability $night): array
    {
        return [
            'date' => $night->date->toDateString(),
            'rooms_available' => (int) $night->rooms_available,
            'rate' => (float) $night->rate,
        ];
    }
}
