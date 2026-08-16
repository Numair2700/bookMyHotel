<?php

namespace App\Services;

use App\Exceptions\ReviewException;
use App\Models\Reservation;
use App\Models\Review;

class ReviewService
{
    /**
     * Submit a review for a stay (6.6). Only a completed reservation may be
     * reviewed, and only once. Reviews start unapproved; an admin approves them
     * before they count towards the hotel rating. Ownership is enforced by the
     * caller's policy check.
     *
     * @throws ReviewException
     */
    public function submit(Reservation $reservation, int $rating, string $comment): Review
    {
        if ($reservation->status !== 'completed') {
            throw new ReviewException('You can only review a completed stay.');
        }

        if ($reservation->review()->exists()) {
            throw new ReviewException('This stay has already been reviewed.');
        }

        return Review::create([
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'hotel_id' => $reservation->hotel_id,
            'rating' => $rating,
            'comment' => $comment,
            'approved' => false,
        ]);
    }
}
