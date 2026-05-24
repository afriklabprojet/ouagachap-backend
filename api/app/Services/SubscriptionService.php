<?php

namespace App\Services;

use App\Enums\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\SubscriptionPlanConfig;
use App\Models\User;

class SubscriptionService
{
    /**
     * Souscrire ou renouveler un plan CHAP Pass pour un client.
     *
     * Si le client a déjà un abonnement actif, il est remplacé (annulé immédiatement)
     * par le nouveau plan. Cela permet les upgrades/downgrades sans payer deux fois.
     */
    public function subscribe(User $user, SubscriptionPlan $plan): Subscription
    {
        // Annuler tout abonnement actif existant
        $this->cancelActiveSubscription($user);

        // Lire les montants depuis la DB (avec cache), fallback sur l'Enum
        $config = SubscriptionPlanConfig::getConfig($plan);

        return Subscription::create([
            'user_id'           => $user->id,
            'plan'              => $plan,
            'price_xof'         => $config['price_xof'],
            'discount_xof'      => $config['discount_xof'],
            'priority_dispatch' => $config['priority_dispatch'],
            'starts_at'         => now(),
            'ends_at'           => now()->addDays(30),
            'cancelled_at'      => null,
        ]);
    }

    /**
     * Retourner l'abonnement actif de l'utilisateur, ou null.
     */
    public function getActiveSubscription(User $user): ?Subscription
    {
        return Subscription::where('user_id', $user->id)
            ->active()
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Retourner la remise par livraison de l'utilisateur (0 si pas d'abonnement actif).
     */
    public function getDiscountForUser(User $user): int
    {
        $sub = $this->getActiveSubscription($user);

        return $sub ? $sub->discount_xof : 0;
    }

    /**
     * Annuler l'abonnement actif de l'utilisateur.
     *
     * @return bool Vrai si un abonnement a été annulé, faux si aucun actif.
     */
    public function cancelActiveSubscription(User $user): bool
    {
        $sub = $this->getActiveSubscription($user);

        if ($sub === null) {
            return false;
        }

        $sub->update(['cancelled_at' => now()]);

        return true;
    }
}
