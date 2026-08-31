<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HotelRequest;
use App\Models\Hotel;
use App\Models\HotelChain;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR16 — administrators add, edit and remove hotels.
 */
class HotelController extends Controller
{
    public function index(): Response
    {
        $hotels = Hotel::query()
            ->with('chain')
            ->withCount('reservations')
            ->orderBy('name')
            ->get()
            ->map(fn (Hotel $hotel): array => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'chain' => $hotel->chain?->name,
                'chain_id' => $hotel->chain_id,
                'city' => $hotel->city,
                'country' => $hotel->country,
                'region' => $hotel->region,
                'address' => $hotel->address,
                'description' => $hotel->description,
                'star_rating' => $hotel->star_rating,
                'wifi_speed_mbps' => $hotel->wifi_speed_mbps,
                'has_workspace' => $hotel->has_workspace,
                'sustainability_certified' => $hotel->sustainability_certified,
                'reservations_count' => $hotel->reservations_count,
            ]);

        return Inertia::render('admin/hotels/index', [
            'hotels' => $hotels->all(),
            'chains' => HotelChain::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (HotelChain $chain): array => [
                    'id' => $chain->id,
                    'name' => $chain->name,
                ])->all(),
        ]);
    }

    public function store(HotelRequest $request): RedirectResponse
    {
        Hotel::create($request->validated());

        return back()->with('success', 'Hotel created.');
    }

    public function update(HotelRequest $request, Hotel $hotel): RedirectResponse
    {
        $hotel->update($request->validated());

        return back()->with('success', 'Hotel updated.');
    }

    public function destroy(Hotel $hotel): RedirectResponse
    {
        // Reservations restrict deletion (financial records must be kept).
        if ($hotel->reservations()->exists()) {
            return back()->withErrors(['hotel' => 'This hotel has reservations and cannot be deleted.']);
        }

        $hotel->delete();

        return back()->with('success', 'Hotel deleted.');
    }
}
