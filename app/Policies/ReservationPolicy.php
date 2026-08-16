<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

/**
 * A guest may only see and act on their own reservations; an admin may act on
 * any. This is what stops a user reading another user's booking by changing the
 * id in the URL (NFR2).
 */
class ReservationPolicy
{
    public function view(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id || $user->isAdmin();
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id || $user->isAdmin();
    }

    public function pay(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id || $user->isAdmin();
    }

    /** Only the guest who made the booking may review it. */
    public function review(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id;
    }

    public function addService(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id || $user->isAdmin();
    }
}
