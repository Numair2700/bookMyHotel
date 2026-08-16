<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePromotionRequest;
use App\Models\Hotel;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR14 — hotel managers create and withdraw promotional discounts.
 */
class PromotionController extends Controller
{
    public function index(): Response
    {
        $promotions = Promotion::query()
            ->with('hotel')
            ->latest()
            ->get()
            ->map(fn (Promotion $promotion): array => [
                'id' => $promotion->id,
                'hotel' => $promotion->hotel?->name,
                'code' => $promotion->code,
                'description' => $promotion->description,
                'discount_type' => $promotion->discount_type,
                'discount_value' => (float) $promotion->discount_value,
                'valid_from' => $promotion->valid_from->toDateString(),
                'valid_to' => $promotion->valid_to->toDateString(),
                'active' => $promotion->active,
            ]);

        $hotels = Hotel::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Hotel $hotel): array => ['id' => $hotel->id, 'name' => $hotel->name]);

        return Inertia::render('manager/promotions', [
            'promotions' => $promotions->all(),
            'hotels' => $hotels->all(),
        ]);
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        Promotion::create($request->validated());

        return back()->with('success', 'Promotion created.');
    }

    /** Withdraw a promotion. Reservations that used it keep their price (FK set null). */
    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return back()->with('success', 'Promotion withdrawn.');
    }
}
