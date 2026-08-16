<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->isAdmin()
            || ($payment->reservation !== null && $payment->reservation->user_id === $user->id);
    }
}
