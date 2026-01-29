<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Admin can do anything
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * View payment list
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View single payment
     */
    public function view(User $user, Payment $payment): bool
    {
        return $payment->user_id === $user->id;
    }

    /**
     * Initiate payment (only for own orders)
     */
    public function create(User $user): bool
    {
        return $user->isClient();
    }

    /**
     * Check payment status
     */
    public function checkStatus(User $user, Payment $payment): bool
    {
        return $payment->user_id === $user->id;
    }
}
