<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Withdrawal;

class WithdrawalPolicy
{
    /**
     * Admin bypass.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Seuls les coursiers peuvent créer une demande de retrait.
     */
    public function create(User $user): bool
    {
        return $user->isCourier();
    }

    /**
     * Un coursier peut voir uniquement ses propres retraits.
     */
    public function view(User $user, Withdrawal $withdrawal): bool
    {
        return $user->id === $withdrawal->user_id;
    }

    /**
     * Un coursier peut voir la liste de ses propres retraits.
     */
    public function viewAny(User $user): bool
    {
        return $user->isCourier();
    }
}
