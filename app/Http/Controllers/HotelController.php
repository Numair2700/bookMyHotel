<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HotelController extends Controller
{
    /**
     * FR4 — the hotel property page: description, room types, facilities, the
     * cancellation policy, in-date promotions and approved guest reviews.
     */
    public function show(Request $request, Hotel $hotel): Response
    {
        $hotel->load([
            'chain',
            'roomTypes',
            'cancellationPolicies',
            'promotions' => function ($q): void {
                $q->where('active', true)
                    ->whereDate('valid_from', '<=', Date::today())
                    ->whereDate('valid_to', '>=', Date::today());
            },
            'reviews' => function ($q): void {
                $q->where('approved', true)->with('user:id,name')->latest()->limit(20);
            },
            'services' => function ($q): void {
                $q->where('active', true);
            },
        ]);

        // Hotel rating is the average of approved reviews (spec 6.6).
        $averageRating = Review::query()
            ->where('hotel_id', $hotel->id)
            ->where('approved', true)
            ->avg('rating');

        // When the guest arrives from a search, price every room for those exact
        // dates so the figure on this page reconciles with the search card
        // (weekend nights are dearer, so the average differs from the base rate).
        $checkIn = $request->input('check_in');
        $checkOut = $request->input('check_out');
        $hasDates = $this->nightsInRange($checkIn, $checkOut) !== null;
        $pricing = $this->datedPricing($hotel, $checkIn, $checkOut);

        return Inertia::render('hotels/show', [
            'hotel' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'chain' => $hotel->chain?->name,
                'city' => $hotel->city,
                'country' => $hotel->country,
                'region' => $hotel->region,
                'address' => $hotel->address,
                'description' => $hotel->description,
                'star_rating' => $hotel->star_rating,
                'wifi_speed_mbps' => $hotel->wifi_speed_mbps,
                'has_workspace' => $hotel->has_workspace,
                'sustainability_certified' => $hotel->sustainability_certified,
            ],
            'room_types' => $hotel->roomTypes->map(fn ($rt): array => [
                'id' => $rt->id,
                'name' => $rt->name,
                'description' => $rt->description,
                'max_occupancy' => $rt->max_occupancy,
                'base_rate' => (float) $rt->base_rate,
                'total_rooms' => $rt->total_rooms,
                // Price for the searched dates (falls back to the base rate when
                // browsing without dates or the stay is not fully available).
                'nightly_rate' => $pricing[$rt->id]['avg'] ?? (float) $rt->base_rate,
                'nights' => $pricing[$rt->id]['nights'] ?? null,
                'total_price' => $pricing[$rt->id]['total'] ?? null,
                // null when browsing without dates; otherwise whether every night
                // of the requested stay is available for this room type.
                'available_for_dates' => $hasDates ? isset($pricing[$rt->id]) : null,
            ])->all(),
            'cancellation_policies' => $hotel->cancellationPolicies->map(fn ($p): array => [
                'id' => $p->id,
                'name' => $p->name,
                'free_cancellation_hours' => $p->free_cancellation_hours,
                'penalty_percentage' => (float) $p->penalty_percentage,
            ])->all(),
            'promotions' => $hotel->promotions->map(fn ($p): array => [
                'code' => $p->code,
                'description' => $p->description,
                'discount_type' => $p->discount_type,
                'discount_value' => (float) $p->discount_value,
            ])->all(),
            'reviews' => $hotel->reviews->map(fn ($r): array => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'guest' => $r->user?->name,
                'created_at' => $r->created_at?->toDateString(),
            ])->all(),
            'services' => $hotel->services->map(fn ($service): array => [
                'id' => $service->id,
                'name' => $service->name,
                'category' => $service->category,
                'description' => $service->description,
                'price' => (float) $service->price,
            ])->all(),
            'average_rating' => $averageRating !== null ? round((float) $averageRating, 1) : null,
            // Prefill the booking widget from the search that led here.
            'booking' => [
                'check_in' => $request->input('check_in'),
                'check_out' => $request->input('check_out'),
                'guests' => $request->filled('guests') ? $request->integer('guests') : null,
            ],
        ]);
    }

    /**
     * Average and total nightly rate per room type over a half-open date range,
     * read from the same availability rates the search uses. Keyed by room type
     * id; a room type only appears when every night of the stay is available.
     *
     * @return array<int, array{nights: int, avg: float, total: float}>
     */
    private function datedPricing(Hotel $hotel, ?string $checkIn, ?string $checkOut): array
    {
        $nights = $this->nightsInRange($checkIn, $checkOut);

        if ($nights === null) {
            return [];
        }

        $rows = DB::table('availability')
            ->join('room_types', 'room_types.id', '=', 'availability.room_type_id')
            ->where('room_types.hotel_id', $hotel->id)
            ->where('availability.date', '>=', Carbon::parse($checkIn)->toDateString())
            ->where('availability.date', '<', Carbon::parse($checkOut)->toDateString())
            // Match search semantics: only nights with a room free count as
            // available, so a sold-out night makes the stay unbookable.
            ->where('availability.rooms_available', '>=', 1)
            ->groupBy('availability.room_type_id')
            ->select('availability.room_type_id')
            ->selectRaw('AVG(availability.rate) as avg_rate')
            ->selectRaw('SUM(availability.rate) as total_rate')
            ->selectRaw('COUNT(DISTINCT availability.date) as night_count')
            ->get();

        $pricing = [];

        foreach ($rows as $row) {
            // Only quote a for-your-dates price when the whole stay is covered.
            if ((int) $row->night_count !== $nights) {
                continue;
            }

            $pricing[(int) $row->room_type_id] = [
                'nights' => $nights,
                'avg' => round((float) $row->avg_rate, 2),
                'total' => round((float) $row->total_rate, 2),
            ];
        }

        return $pricing;
    }

    /**
     * Number of nights in a half-open [check_in, check_out) range, or null when
     * the dates are missing, unparseable or not in order.
     */
    private function nightsInRange(?string $checkIn, ?string $checkOut): ?int
    {
        if ($checkIn === null || $checkOut === null) {
            return null;
        }

        try {
            $in = Carbon::parse($checkIn)->startOfDay();
            $out = Carbon::parse($checkOut)->startOfDay();
        } catch (\Exception) {
            return null;
        }

        if ($out->lessThanOrEqualTo($in)) {
            return null;
        }

        return (int) $in->diffInDays($out);
    }
}
