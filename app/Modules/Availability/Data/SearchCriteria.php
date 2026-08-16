<?php

namespace App\Modules\Availability\Data;

use Illuminate\Support\Carbon;

/**
 * Immutable value object describing a search request. Keeping the criteria in
 * one typed object means the Availability module's public interface does not
 * depend on the HTTP layer, so the module can be extracted later.
 */
final class SearchCriteria
{
    public function __construct(
        public readonly string $checkIn,   // Y-m-d
        public readonly string $checkOut,  // Y-m-d, exclusive (check-out day is not a night)
        public readonly ?string $destination = null,
        public readonly ?int $guests = null,
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly ?string $roomType = null,
        public readonly bool $sustainableOnly = false,
        public readonly bool $requiresWorkspace = false,
    ) {}

    /**
     * Number of booked nights in the range. A 12th-15th stay is three nights,
     * because the check-out day is not itself a booked night.
     */
    public function nights(): int
    {
        return (int) Carbon::parse($this->checkIn)->diffInDays(Carbon::parse($this->checkOut));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'destination' => $this->destination,
            'guests' => $this->guests,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'room_type' => $this->roomType,
            'sustainable_only' => $this->sustainableOnly,
            'has_workspace' => $this->requiresWorkspace,
            'nights' => $this->nights(),
        ];
    }
}
