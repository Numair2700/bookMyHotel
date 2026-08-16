<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAvailabilityRequest;
use App\Models\Availability;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR15 — hotel managers maintain the daily rate calendar and availability.
 */
class AvailabilityController extends Controller
{
    public function index(): Response
    {
        $roomTypes = RoomType::query()
            ->with('hotel')
            ->orderBy('hotel_id')
            ->get()
            ->map(fn (RoomType $roomType): array => [
                'id' => $roomType->id,
                'name' => $roomType->name,
                'hotel' => $roomType->hotel?->name,
                'base_rate' => (float) $roomType->base_rate,
                'total_rooms' => $roomType->total_rooms,
            ]);

        return Inertia::render('manager/availability', [
            'room_types' => $roomTypes->all(),
        ]);
    }

    /** Set the rate and rooms available for a room type on a given date. */
    public function update(UpdateAvailabilityRequest $request): RedirectResponse
    {
        Availability::updateOrCreate(
            [
                'room_type_id' => $request->integer('room_type_id'),
                'date' => (string) $request->input('date'),
            ],
            [
                'rooms_available' => $request->integer('rooms_available'),
                'rate' => (float) $request->input('rate'),
            ],
        );

        return back()->with('success', 'Availability updated.');
    }
}
