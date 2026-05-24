<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\CourierMatchingService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Surveille les commandes ASSIGNED ou ACCEPTED sans activité depuis plus de 10 minutes.
 * Tente une réassignation automatique ou remet la commande en PENDING.
 * Doit être planifié via le Scheduler toutes les 5 minutes.
 */
class ReassignStuckOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    /** Délai (en minutes) sans activité pour considérer une commande "bloquée" */
    private const STUCK_THRESHOLD_MINUTES = 10;

    public function handle(CourierMatchingService $courierService, NotificationService $notificationService): void
    {
        $cutoff = now()->subMinutes(self::STUCK_THRESHOLD_MINUTES);

        $stuckOrders = Order::whereIn('status', [OrderStatus::ASSIGNED, OrderStatus::ACCEPTED])
            ->where('updated_at', '<', $cutoff)
            ->with(['courier', 'client'])
            ->get();

        if ($stuckOrders->isEmpty()) {
            return;
        }

        Log::warning('ReassignStuckOrderJob: commandes bloquées détectées', [
            'count' => $stuckOrders->count(),
            'order_ids' => $stuckOrders->pluck('id')->toArray(),
        ]);

        $reassigned = 0;
        $reset = 0;

        foreach ($stuckOrders as $order) {
            try {
                $this->handleStuckOrder($order, $courierService, $notificationService, $reassigned, $reset);
            } catch (\Exception $e) {
                Log::error('ReassignStuckOrderJob: erreur sur commande', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('ReassignStuckOrderJob: traitement terminé', [
            'total' => $stuckOrders->count(),
            'reassigned' => $reassigned,
            'reset_to_pending' => $reset,
        ]);
    }

    private function handleStuckOrder(
        Order $order,
        CourierMatchingService $courierService,
        NotificationService $notificationService,
        int &$reassigned,
        int &$reset
    ): void {
        $currentCourier = $order->courier;
        $stuckMinutes = now()->diffInMinutes($order->updated_at);

        Log::warning('ReassignStuckOrderJob: commande bloquée', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'courier_id' => $currentCourier?->id,
            'stuck_minutes' => $stuckMinutes,
        ]);

        // Notifier les admins
        $this->notifyAdmins($order, $notificationService, $stuckMinutes);

        // Tenter une réassignation à un autre coursier
        $newCourier = $courierService->getBestCourierForOrder($order);

        if ($newCourier && $newCourier->id !== $currentCourier?->id) {
            $order->update([
                'courier_id' => $newCourier->id,
                'status' => OrderStatus::ASSIGNED,
            ]);
            $order->statusHistories()->create([
                'status' => OrderStatus::ASSIGNED,
                'previous_status' => $order->getOriginal('status'),
                'changed_by' => null,
                'note' => "Réassignation automatique après {$stuckMinutes} min d'inactivité (coursier précédent: #{$currentCourier?->id})",
            ]);

            // Notifier le nouveau coursier
            $notificationService->sendToUser(
                $newCourier,
                'Nouvelle commande assignée',
                "La commande #{$order->order_number} vous a été réassignée.",
                ['order_id' => $order->id, 'type' => 'order_reassigned']
            );

            $reassigned++;
            Log::info('ReassignStuckOrderJob: commande réassignée', [
                'order_id' => $order->id,
                'new_courier_id' => $newCourier->id,
            ]);
        } else {
            // Pas de coursier dispo → remettre en PENDING pour redistribution
            $order->update([
                'courier_id' => null,
                'status' => OrderStatus::PENDING,
            ]);
            $order->statusHistories()->create([
                'status' => OrderStatus::PENDING,
                'previous_status' => $order->getOriginal('status'),
                'changed_by' => null,
                'note' => "Remise en attente automatique après {$stuckMinutes} min d'inactivité (aucun coursier disponible)",
            ]);

            $reset++;
            Log::info('ReassignStuckOrderJob: commande remise en attente', [
                'order_id' => $order->id,
            ]);
        }
    }

    private function notifyAdmins(Order $order, NotificationService $notificationService, int $stuckMinutes): void
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            try {
                $notificationService->sendToUser(
                    $admin,
                    '⚠️ Commande bloquée',
                    "La commande #{$order->order_number} est bloquée depuis {$stuckMinutes} min (statut: {$order->status->value}).",
                    [
                        'order_id' => $order->id,
                        'type' => 'stuck_order_alert',
                    ]
                );
            } catch (\Exception $e) {
                Log::warning('ReassignStuckOrderJob: échec notification admin', [
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
