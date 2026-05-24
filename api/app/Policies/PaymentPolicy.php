<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
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
     * Tout utilisateur authentifié peut lister ses paiements.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Un utilisateur peut voir uniquement ses propres paiements.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id;
    }

    /**
     * Seuls les clients peuvent initier un paiement.
     */
    public function create(User $user): bool
    {
        return $user->isClient();
    }

    /**
     * Un utilisateur peut vérifier le statut de ses propres paiements seulement.
     */
    public function checkStatus(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id;
    }
}
