<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job asynchrone pour envoyer notification de changement de status.
 * 
 * Déclenché lors de :
 * - Acceptation commande (pending → accepted)
 * - Pickup marchandise (accepted → delivering)
 * - Livraison complétée (delivering → completed)
 * - Annulation (any → cancelled)
 * 
 * Envoyé à :
 * - Client : notification de progression
 * - Coursier : confirmation de changement
 * 
 * Impact : Notifications temps réel sans bloquer l'API
 * Queue : 'notifications' (prioritaire)
 */
class SendOrderStatusChangedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public int $timeout = 30;
    
    /**
     * @param Order $order Commande modifiée
     * @param string $previousStatus Status précédent
     * @param string $newStatus Nouveau status
     */
    public function __construct(
        public Order $order,
        public string $previousStatus,
        public string $newStatus
    ) {
        $this->onQueue('notifications');
    }
    
    /**
     * Exécute le job.
     */
    public function handle(PushNotificationService $pushService): void
    {
        try {
            // Charger les relations nécessaires
            $this->order->load(['client', 'courier']);
            
            // Messages selon le changement de status
            $messages = $this->getNotificationMessages();
            
            // Notification au client
            if ($this->order->client) {
                $pushService->sendToUser(
                    $this->order->client,
                    $messages['client_title'],
                    $messages['client_body'],
                    [
                        'type' => 'order_status_changed',
                        'order_id' => $this->order->id,
                        'previous_status' => $this->previousStatus,
                        'new_status' => $this->newStatus,
                    ]
                );
            }
            
            // Notification au coursier (si assigné)
            if ($this->order->courier) {
                $pushService->sendToUser(
                    $this->order->courier,
                    $messages['courier_title'],
                    $messages['courier_body'],
                    [
                        'type' => 'order_status_changed',
                        'order_id' => $this->order->id,
                        'previous_status' => $this->previousStatus,
                        'new_status' => $this->newStatus,
                    ]
                );
            }
            
            Log::info("Order status changed notifications sent", [
                'order_id' => $this->order->id,
                'status_change' => "{$this->previousStatus} → {$this->newStatus}",
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to send status changed notifications", [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Messages de notification selon le changement de status.
     */
    private function getNotificationMessages(): array
    {
        return match ($this->newStatus) {
            'accepted' => [
                'client_title' => 'Commande acceptée',
                'client_body' => "Un coursier a accepté votre commande #{$this->order->id}.",
                'courier_title' => 'Commande acceptée',
                'courier_body' => "Vous avez accepté la commande #{$this->order->id}.",
            ],
            'delivering' => [
                'client_title' => 'En cours de livraison',
                'client_body' => "Votre commande #{$this->order->id} est en cours de livraison.",
                'courier_title' => 'Livraison en cours',
                'courier_body' => "Vous avez récupéré la commande #{$this->order->id}.",
            ],
            'completed' => [
                'client_title' => 'Livraison terminée',
                'client_body' => "Votre commande #{$this->order->id} a été livrée avec succès.",
                'courier_title' => 'Livraison terminée',
                'courier_body' => "Livraison de la commande #{$this->order->id} terminée.",
            ],
            'cancelled' => [
                'client_title' => 'Commande annulée',
                'client_body' => "Votre commande #{$this->order->id} a été annulée.",
                'courier_title' => 'Commande annulée',
                'courier_body' => "La commande #{$this->order->id} a été annulée.",
            ],
            default => [
                'client_title' => 'Mise à jour commande',
                'client_body' => "Votre commande #{$this->order->id} a été mise à jour.",
                'courier_title' => 'Mise à jour commande',
                'courier_body' => "La commande #{$this->order->id} a été mise à jour.",
            ],
        };
    }
    
    /**
     * Appelé si le job échoue après toutes les tentatives.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Order status changed notification job failed permanently", [
            'order_id' => $this->order->id,
            'status_change' => "{$this->previousStatus} → {$this->newStatus}",
            'error' => $exception->getMessage(),
        ]);
    }
}
