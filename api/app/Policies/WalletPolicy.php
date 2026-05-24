<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
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
     * Un coursier peut consulter son propre portefeuille.
     */
    public function view(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id;
    }
}
