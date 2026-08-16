<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
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
}
