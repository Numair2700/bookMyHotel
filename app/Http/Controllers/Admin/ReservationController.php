<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR17 — administrators view all reservations, filtered by hotel or by a
 * specific date (a stay that covers that date).
 */
class ReservationController extends Controller
{
    public function index(Request $request): Response
    {
        $date = $request->input('date');
        $hotelId = $request->integer('hotel_id');

        $reservations = Reservation::query()
            ->with(['hotel', 'user'])
            ->when($date, function (Builder $q) use ($date): void {
                $q->whereDate('check_in', '<=', $date)->whereDate('check_out', '>', $date);
            })
            ->when($hotelId, function (Builder $q) use ($hotelId): void {
                $q->where('hotel_id', $hotelId);
            })
            ->latest('check_in')
            ->get()
            ->map(fn (Reservation $reservation): array => [
                'id' => $reservation->id,
                'reference' => $reservation->reference,
                'hotel' => $reservation->hotel->name,
                'guest' => $reservation->user->name,
                'check_in' => $reservation->check_in->toDateString(),
                'check_out' => $reservation->check_out->toDateString(),
                'status' => $reservation->status,
                'total_amount' => (float) $reservation->total_amount,
            ]);

        return Inertia::render('admin/reservations/index', [
            'reservations' => $reservations->all(),
            'filters' => ['date' => $date, 'hotel_id' => $hotelId ?: null],
        ]);
    }
}
