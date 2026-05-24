<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Concerns\OrderHelpers;
use App\Models\Concerns\OrderRelationships;
use App\Models\Concerns\OrderScopes;
use App\Models\Concerns\OrderStateTransitions;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property OrderStatus $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $assigned_at
 * @property \Illuminate\Support\Carbon|null $picked_up_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 */
class Order extends Model
{
    use HasFactory, HasUuids, LogsActivity, OrderHelpers, OrderRelationships, OrderScopes, OrderStateTransitions, SoftDeletes;

    // Champs à exclure des logs d'activité
    protected array $excludedLogFields = ['updated_at', 'created_at'];

    // Types d'activités à logger
    protected array $loggedActivityTypes = ['created', 'updated'];

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Champs modifiables par l'utilisateur final (PUT/PATCH depuis les contrôleurs).
     * Les champs financiers et d'état sont exclusivement mis à jour via les méthodes
     * du modèle (transitionTo, assign, markAsDelivered…) ou forceFill() dans les services.
     */
    protected $fillable = [
        // Informations colis — modifiables avant dispatch
        'package_description',
        'package_size',
        'payment_method',

        // Adresses — saisie client
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_contact_name',
        'pickup_contact_phone',
        'pickup_instructions',
        'dropoff_address',
        'dropoff_latitude',
        'dropoff_longitude',
        'dropoff_contact_name',
        'dropoff_contact_phone',
        'dropoff_instructions',

        // Confirmation destinataire (mise à jour ciblée par le service)
        'recipient_confirmed',
        'delivery_photo_url',

        // Notes et évaluations post-livraison
        'cancellation_reason',
        'client_rating',
        'client_review',
        'courier_rating',
        'courier_review',
    ];

    /**
     * Champs protégés contre le mass assignment — uniquement via forceFill() ou setAttribute().
     * Toute tentative de modifier ces champs via create()/update() avec données utilisateur sera ignorée.
     */
    protected $guarded = [
        'id',
        'order_number',
        'client_id',
        'recipient_user_id',
        'courier_id',
        'zone_id',
        'status',
        'distance_km',
        'base_price',
        'distance_price',
        'total_price',
        'subscription_discount',
        'commission_amount',
        'courier_earnings',
        'recipient_confirmation_code',
        'assigned_at',
        'picked_up_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'pickup_latitude' => 'decimal:8',
            'pickup_longitude' => 'decimal:8',
            'dropoff_latitude' => 'decimal:8',
            'dropoff_longitude' => 'decimal:8',
            'distance_km' => 'decimal:2',
            'base_price' => 'decimal:2',
            'distance_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'courier_earnings' => 'decimal:2',
            'subscription_discount' => 'decimal:2',
            'assigned_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }
}
