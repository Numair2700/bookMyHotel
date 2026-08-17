<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * After login every role lands on "dashboard"; send each to the area that
     * is actually useful to them rather than an empty placeholder screen.
     */
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User && $user->isAdmin()) {
            return redirect()->route('admin.analytics.index');
        }

        if ($user instanceof User && $user->isManager()) {
            return redirect()->route('manager.promotions.index');
        }

        // Guests land on their own bookings.
        return redirect()->route('reservations.index');
    }
}
