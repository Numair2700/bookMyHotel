<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceBookingRequest;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\ServiceBooking;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class ServiceBookingController extends Controller
{
    use AuthorizesRequests;

    /** FR9 — add an ancillary service to a reservation. */
    public function store(StoreServiceBookingRequest $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('addService', $reservation);

        if (! in_array($reservation->status, ['pending', 'confirmed'], true)) {
            return back()->withErrors(['service' => 'Services can only be added to an active booking.']);
        }

        // The service must belong to the booked hotel and still be on sale.
        $service = Service::query()
            ->whereKey($request->integer('service_id'))
            ->where('hotel_id', $reservation->hotel_id)
            ->where('active', true)
            ->first();

        if ($service === null) {
            return back()->withErrors(['service' => 'That service is not available for this hotel.']);
        }

        ServiceBooking::create([
            'reservation_id' => $reservation->id,
            'service_id' => $service->id,
            'service_date' => (string) $request->input('service_date'),
            'quantity' => $request->integer('quantity'),
            // Snapshot the price at time of booking.
            'unit_price' => $service->price,
        ]);

        return back()->with('success', 'Service added to your booking.');
    }
}
