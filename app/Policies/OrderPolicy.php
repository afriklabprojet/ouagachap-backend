<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
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
     * View order list (own orders only)
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View single order
     */
    public function view(User $user, Order $order): bool
    {
        return $this->ownsOrder($user, $order);
    }

    /**
     * Create order (clients only)
     */
    public function create(User $user): bool
    {
        return $user->isClient();
    }

    /**
     * Update order status (courier assigned to order only)
     */
    public function update(User $user, Order $order): bool
    {
        // Seul le coursier assigné peut mettre à jour (pas le client)
        return $user->isCourier() && $order->courier_id === $user->id;
    }

    /**
     * Delete order (admin only via before())
     */
    public function delete(User $user, Order $order): bool
    {
        // Only admins can delete (handled by before())
        return false;
    }

    /**
     * Cancel order
     */
    public function cancel(User $user, Order $order): bool
    {
        if (!$this->ownsOrder($user, $order)) {
            return false;
        }

        return in_array($order->status, [
            OrderStatus::PENDING,
            OrderStatus::ASSIGNED,
        ]);
    }

    /**
     * Accept order (courier only)
     */
    public function accept(User $user, Order $order): bool
    {
        return $user->isCourier() 
            && $user->canAcceptOrders()
            && $order->isPending()
            && $order->courier_id === null;
    }

    /**
     * Update order status (courier assigned to order)
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->isCourier() && $order->courier_id === $user->id;
    }

    /**
     * Rate courier (client only, after delivery)
     */
    public function rateCourier(User $user, Order $order): bool
    {
        return $order->client_id === $user->id
            && $order->isCompleted()
            && $order->courier_rating === null;
    }

    /**
     * Rate client (courier only, after delivery)
     */
    public function rateClient(User $user, Order $order): bool
    {
        return $order->courier_id === $user->id
            && $order->isCompleted()
            && $order->client_rating === null;
    }

    /**
     * Track order (owner or recipient)
     */
    public function track(User $user, Order $order): bool
    {
        return $this->ownsOrder($user, $order);
    }

    /**
     * Check if user owns the order (client or assigned courier)
     */
    private function ownsOrder(User $user, Order $order): bool
    {
        return $order->client_id === $user->id 
            || $order->courier_id === $user->id;
    }
}
