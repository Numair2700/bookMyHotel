<?php

namespace App\Http\Controllers;

use App\Exceptions\AvailabilityException;
use App\Exceptions\CancellationException;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CancellationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly BookingService $booking,
        private readonly CancellationService $cancellation,
    ) {}

    /** FR8 — the guest's own bookings only. */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $reservations = $user->reservations()
            ->with('hotel')
            ->latest()
            ->get()
            ->map(fn (Reservation $reservation): array => [
                'id' => $reservation->id,
                'hotel_id' => $reservation->hotel_id,
                'reference' => $reservation->reference,
                'hotel' => $reservation->hotel->name,
                'check_in' => $reservation->check_in->toDateString(),
                'check_out' => $reservation->check_out->toDateString(),
                'status' => $reservation->status,
                'total_amount' => (float) $reservation->total_amount,
            ]);

        return Inertia::render('reservations/index', [
            'reservations' => $reservations->all(),
        ]);
    }

    /** FR6/NFR2 — a reservation, only if the viewer owns it (or is admin). */
    public function show(Reservation $reservation): Response
    {
        $this->authorize('view', $reservation);

        $reservation->load(['hotel', 'nights', 'payments', 'review']);

        // The refund the guest would get if they cancelled now (NFR8).
        $refundQuote = in_array($reservation->status, ['pending', 'confirmed'], true)
            ? $this->cancellation->quote($reservation)->toArray()
            : null;

        return Inertia::render('reservations/show', [
            'reservation' => [
                'id' => $reservation->id,
                'hotel_id' => $reservation->hotel_id,
                'reference' => $reservation->reference,
                'hotel' => $reservation->hotel->name,
                'check_in' => $reservation->check_in->toDateString(),
                'check_out' => $reservation->check_out->toDateString(),
                'guests' => $reservation->guests,
                'status' => $reservation->status,
                'subtotal' => (float) $reservation->subtotal,
                'discount_total' => (float) $reservation->discount_total,
                'total_amount' => (float) $reservation->total_amount,
                'is_sustainable' => $reservation->is_sustainable,
                'nights' => $reservation->nights->map(fn ($night): array => [
                    'stay_date' => $night->stay_date->toDateString(),
                    'rate' => (float) $night->rate,
                ])->all(),
            ],
            'refund_quote' => $refundQuote,
            'can_review' => $reservation->status === 'completed' && $reservation->review === null,
            'review' => $reservation->review !== null ? [
                'rating' => $reservation->review->rating,
                'comment' => $reservation->review->comment,
                'approved' => $reservation->review->approved,
            ] : null,
        ]);
    }

    /**
     * FR6 — create a reservation. The booking rules live in BookingService; the
     * controller only maps the request and turns a domain failure into a
     * validation error the UI can show.
     */
    public function store(StoreReservationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $data = $request->bookingData();

        try {
            $reservation = $this->booking->book(
                $user,
                $data['room_type_id'],
                $data['check_in'],
                $data['check_out'],
                $data['guests'],
                $data['promotion_code'],
                $data['redeem_points'],
            );
        } catch (AvailabilityException $e) {
            return back()->withErrors(['availability' => $e->getMessage()]);
        }

        // Continue the flow to the reservation page, where the guest pays.
        return redirect()
            ->route('reservations.show', $reservation)
            ->with('success', "Reservation {$reservation->reference} created — complete payment to confirm.");
    }

    /** FR7 — cancel a reservation the viewer owns. */
    public function cancel(Reservation $reservation): RedirectResponse
    {
        $this->authorize('cancel', $reservation);

        try {
            $this->cancellation->cancel($reservation);
        } catch (CancellationException $e) {
            return back()->withErrors(['cancellation' => $e->getMessage()]);
        }

        return back()->with('success', 'Reservation cancelled.');
    }
}
