<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels - OUAGA CHAP
|--------------------------------------------------------------------------
|
| Canaux de diffusion WebSocket pour le temps réel
|
*/

/**
 * Canal privé utilisateur - Notifications personnelles
 */
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

/**
 * Canal privé pour suivre une commande spécifique
 * Le client et le coursier assigné peuvent écouter
 */
Broadcast::channel('orders.{orderId}', function (User $user, string $orderId) {
    $order = Order::find($orderId);
    
    if (!$order) {
        return false;
    }
    
    // Le client propriétaire ou le coursier assigné
    return $user->id === $order->client_id || $user->id === $order->courier_id;
});

/**
 * Canal privé pour le tracking GPS du coursier
 * Seul le client de la commande peut écouter
 */
Broadcast::channel('courier.{courierId}.location', function (User $user, int $courierId) {
    // Vérifier si l'utilisateur a une commande active avec ce coursier
    return Order::where('client_id', $user->id)
        ->where('courier_id', $courierId)
        ->whereIn('status', ['assigned', 'picked_up'])
        ->exists();
});

/**
 * Canal pour les coursiers disponibles dans une zone
 * Réservé aux admins
 */
Broadcast::channel('couriers.available', function (User $user) {
    return $user->isAdmin();
});

/**
 * Canal privé pour le dashboard coursier
 * Nouvelles commandes disponibles
 */
Broadcast::channel('courier.{courierId}.orders', function (User $user, int $courierId) {
    return $user->id === $courierId && $user->isCourier();
});
