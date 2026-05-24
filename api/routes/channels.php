<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Autorisation des canaux privés WebSocket (Reverb/Pusher).
| Chaque closure reçoit l'utilisateur authentifié et les paramètres du canal.
| Retourner true/false autorise ou refuse la souscription.
|
*/

/**
 * Canal commande privé — canal principal pour le suivi temps réel.
 *
 * Utilisé par :
 *   - OrderTrackingUpdate (tracking.update avec ETA)
 *   - CourierLocationUpdated (location.updated position brute)
 *
 * Accessible par : le client de la commande, le destinataire, le coursier assigné, les admins.
 */
Broadcast::channel('order.{orderId}', function (User $user, string $orderId) {
    $order = Order::find($orderId);

    if (! $order) {
        return false;
    }

    return $user->isAdmin()
        || $order->client_id === $user->id
        || $order->recipient_user_id === $user->id
        || ($order->courier_id !== null && $order->courier_id === $user->id);
});

/**
 * Canal historique des commandes (pluriel) — statuts, assignation.
 *
 * Utilisé par : OrderStatusChanged, OrderAssigned.
 */
Broadcast::channel('orders.{orderId}', function (User $user, string $orderId) {
    $order = Order::find($orderId);

    if (! $order) {
        return false;
    }

    return $user->isAdmin()
        || $order->client_id === $user->id
        || $order->recipient_user_id === $user->id
        || ($order->courier_id !== null && $order->courier_id === $user->id);
});

/**
 * Canal coursier — commandes disponibles et notifications push.
 *
 * Utilisé par : OrderAssigned, OrderStatusChanged (côté coursier).
 * Accessible uniquement par le coursier concerné.
 */
Broadcast::channel('courier.{courierId}.orders', function (User $user, string $courierId) {
    return $user->isCourier() && (string) $user->id === $courierId;
});

/**
 * Canal utilisateur personnel (modèle User) — notifications générales.
 *
 * Utilisé par Laravel Notifications (ShouldBroadcast).
 * Convention Laravel : App.Models.User.{id}
 */
Broadcast::channel('App.Models.User.{id}', function (User $user, string $id) {
    return (string) $user->id === $id;
});
