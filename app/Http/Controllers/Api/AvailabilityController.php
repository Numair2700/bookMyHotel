<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailabilityCalendarRequest;
use App\Models\Hotel;
use App\Modules\Availability\Contracts\AvailabilityServiceInterface;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityServiceInterface $availability,
    ) {}

    /**
     * [JSON] GET /api/hotels/{hotel}/availability?from=&to=
     * A partial-update endpoint for the hotel detail calendar. Returns raw JSON
     * ({ data }) rather than an Inertia response.
     */
    public function show(Hotel $hotel, AvailabilityCalendarRequest $request): JsonResponse
    {
        $data = $this->availability->calendar(
            $hotel->id,
            $request->date('from')->toDateString(),
            $request->date('to')->toDateString(),
        );

        return response()->json(['data' => $data]);
    }
}
