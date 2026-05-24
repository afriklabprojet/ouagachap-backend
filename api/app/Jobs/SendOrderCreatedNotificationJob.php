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
 * Job asynchrone pour envoyer notification de nouvelle commande.
 * 
 * Déclenché lors de :
 * - Création d'une nouvelle commande (POST /api/v1/orders)
 * 
 * Envoyé à :
 * - Client : "Commande créée avec succès"
 * - Coursiers disponibles dans la zone : "Nouvelle commande disponible"
 * 
 * Impact : -200ms response time sur création commande
 * Queue : 'notifications' (prioritaire)
 */
class SendOrderCreatedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * Nombre de tentatives avant échec.
     */
    public int $tries = 3;
    
    /**
     * Timeout en secondes.
     */
    public int $timeout = 30;
    
    /**
     * @param Order $order Commande créée
     */
    public function __construct(
        public Order $order
    ) {
        // Utiliser la queue 'notifications' (prioritaire)
        $this->onQueue('notifications');
    }
    
    /**
     * Exécute le job.
     */
    public function handle(PushNotificationService $pushService): void
    {
        try {
            // Charger les relations nécessaires
            $this->order->load(['client', 'zone']);
            
            // Notification au client
            if ($this->order->client) {
                $pushService->sendToUser(
                    $this->order->client,
                    'Commande créée',
                    "Votre commande #{$this->order->id} a été créée avec succès.",
                    [
                        'type' => 'order_created',
                        'order_id' => $this->order->id,
                        'status' => $this->order->status,
                    ]
                );
            }
            
            // Notification aux coursiers disponibles dans la zone
            if ($this->order->zone) {
                $availableCouriers = \App\Models\User::where('role', 'courier')
                    ->where('is_active', true)
                    ->where('is_available', true)
                    ->whereHas('zones', fn($q) => $q->where('zones.id', $this->order->zone_id))
                    ->get();
                
                $pushService->sendToUsers(
                    $availableCouriers->all(),
                    'Nouvelle commande',
                    "Une nouvelle commande est disponible dans votre zone.",
                    [
                        'type' => 'new_order_available',
                        'order_id' => $this->order->id,
                        'zone_id' => $this->order->zone_id,
                        'pickup_address' => $this->order->pickup_address,
                    ]
                );
            }
            
            Log::info("Order created notifications sent", [
                'order_id' => $this->order->id,
                'client_id' => $this->order->client_id,
                'zone_id' => $this->order->zone_id,
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to send order created notifications", [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);
            
            // Relancer le job si échec (max $tries fois)
            throw $e;
        }
    }
    
    /**
     * Appelé si le job échoue après toutes les tentatives.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Order created notification job failed permanently", [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
