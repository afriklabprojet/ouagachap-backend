<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Admin bypass : les admins ont accès à toutes les actions.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Tout utilisateur authentifié peut lister les commandes (filtrage dans la requête).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Le client propriétaire, le coursier assigné ou le destinataire peuvent voir la commande.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->client_id
            || $user->id === $order->courier_id
            || $user->id === $order->recipient_user_id;
    }

    /**
     * Seuls les clients peuvent créer des commandes.
     */
    public function create(User $user): bool
    {
        return $user->isClient();
    }

    /**
     * Seul le coursier assigné peut modifier la commande.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->isCourier() && $user->id === $order->courier_id;
    }

    /**
     * Suppression réservée aux admins (via before).
     */
    public function delete(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Annulation :
     *  - Client propriétaire : statuts pending / assigned / accepted uniquement
     *  - Coursier assigné : statut assigned uniquement
     */
    public function cancel(User $user, Order $order): bool
    {
        $cancellableByClient = [OrderStatus::PENDING, OrderStatus::ASSIGNED, OrderStatus::ACCEPTED];

        if ($user->isClient() && $user->id === $order->client_id) {
            return in_array($order->status, $cancellableByClient);
        }

        if ($user->isCourier() && $user->id === $order->courier_id) {
            return $order->status === OrderStatus::ASSIGNED;
        }

        return false;
    }

    /**
     * Un coursier disponible peut accepter une commande pending sans coursier assigné.
     */
    public function accept(User $user, Order $order): bool
    {
        return $user->isCourier()
            && $user->is_available
            && $order->status === OrderStatus::PENDING
            && $order->courier_id === null;
    }

    /**
     * Seul le coursier assigné peut changer le statut de la commande.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->isCourier() && $user->id === $order->courier_id;
    }

    /**
     * Le client propriétaire peut noter le coursier une seule fois après livraison.
     * (courier_rating = note que le client donne au coursier)
     */
    public function rateCourier(User $user, Order $order): bool
    {
        return $user->isClient()
            && $user->id === $order->client_id
            && $order->status === OrderStatus::DELIVERED
            && $order->courier_id !== null
            && $order->courier_rating === null;
    }

    /**
     * Le coursier assigné peut noter le client une seule fois après livraison.
     * (client_rating = note que le coursier donne au client)
     */
    public function rateClient(User $user, Order $order): bool
    {
        return $user->isCourier()
            && $user->id === $order->courier_id
            && $order->status === OrderStatus::DELIVERED
            && $order->client_rating === null;
    }

    /**
     * Le client propriétaire ou le coursier assigné peuvent suivre la commande.
     */
    public function track(User $user, Order $order): bool
    {
        return $user->id === $order->client_id
            || $user->id === $order->courier_id;
    }
}
