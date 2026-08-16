<?php

namespace App\Http\Controllers;

use App\Exceptions\ReviewException;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Reservation;
use App\Services\ReviewService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ReviewService $reviews,
    ) {}

    /** FR10 — submit a review for a completed, owned reservation. */
    public function store(StoreReviewRequest $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('review', $reservation);

        try {
            $this->reviews->submit(
                $reservation,
                (int) $request->input('rating'),
                (string) $request->input('comment'),
            );
        } catch (ReviewException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return back()->with('success', 'Thanks for your review. It will appear once approved.');
    }
}
