<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    // Champs à exclure des logs d'activité
    protected array $excludedLogFields = ['updated_at', 'created_at'];

    // Types d'activités à logger
    protected array $loggedActivityTypes = ['created', 'updated'];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'order_number',
        'client_id',
        'recipient_user_id',
        'courier_id',
        'zone_id',
        'status',
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
        'recipient_confirmation_code',
        'recipient_confirmed',
        'package_description',
        'package_size',
        'distance_km',
        'base_price',
        'distance_price',
        'total_price',
        'commission_amount',
        'courier_earnings',
        'assigned_at',
        'picked_up_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
        'client_rating',
        'client_review',
        'courier_rating',
        'courier_review',
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

    // ==================== RELATIONSHIPS ====================

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Le destinataire s'il a un compte dans l'app
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class)->orderBy('created_at', 'desc');
    }

    // ==================== SCOPES ====================

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::PENDING);
    }

    public function scopeAssigned(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::ASSIGNED);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereIn('status', [OrderStatus::ASSIGNED, OrderStatus::PICKED_UP]);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::DELIVERED);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::CANCELLED);
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForCourier(Builder $query, int $courierId): Builder
    {
        return $query->where('courier_id', $courierId);
    }

    public function scopeAvailableForCouriers(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::PENDING)
            ->whereNull('courier_id');
    }

    // ==================== STATUS TRANSITIONS ====================

    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return $this->status->canTransitionTo($newStatus);
    }

    public function transitionTo(OrderStatus $newStatus, ?int $changedBy = null, ?string $note = null, ?float $latitude = null, ?float $longitude = null): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $previousStatus = $this->status;

        $this->status = $newStatus;

        // Set timestamps based on status
        match ($newStatus) {
            OrderStatus::ASSIGNED => $this->assigned_at = now(),
            OrderStatus::PICKED_UP => $this->picked_up_at = now(),
            OrderStatus::DELIVERED => $this->delivered_at = now(),
            OrderStatus::CANCELLED => $this->cancelled_at = now(),
            default => null,
        };

        $this->save();

        // Log status change
        $this->statusHistories()->create([
            'status' => $newStatus,
            'previous_status' => $previousStatus,
            'changed_by' => $changedBy,
            'note' => $note,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        return true;
    }

    public function assign(User $courier, ?int $changedBy = null): bool
    {
        if (!$courier->canAcceptOrders()) {
            return false;
        }

        $this->courier_id = $courier->id;
        
        return $this->transitionTo(OrderStatus::ASSIGNED, $changedBy);
    }

    public function markAsPickedUp(?int $changedBy = null, ?float $latitude = null, ?float $longitude = null): bool
    {
        return $this->transitionTo(OrderStatus::PICKED_UP, $changedBy, null, $latitude, $longitude);
    }

    public function markAsDelivered(?int $changedBy = null, ?float $latitude = null, ?float $longitude = null): bool
    {
        $result = $this->transitionTo(OrderStatus::DELIVERED, $changedBy, null, $latitude, $longitude);

        if ($result && $this->courier) {
            $this->courier->incrementTotalOrders();
            $this->courier->addToWallet($this->courier_earnings);
        }

        return $result;
    }

    public function cancel(string $reason, ?int $changedBy = null): bool
    {
        $this->cancellation_reason = $reason;
        
        return $this->transitionTo(OrderStatus::CANCELLED, $changedBy, $reason);
    }

    // ==================== HELPERS ====================

    public static function generateOrderNumber(): string
    {
        $prefix = 'OC';
        $date = now()->format('ymd');
        $random = strtoupper(substr(uniqid(), -4));
        
        return "{$prefix}{$date}{$random}";
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::PENDING;
    }

    public function isAssigned(): bool
    {
        return $this->status === OrderStatus::ASSIGNED;
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, [OrderStatus::ASSIGNED, OrderStatus::PICKED_UP]);
    }

    public function isCompleted(): bool
    {
        return $this->status === OrderStatus::DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::CANCELLED;
    }

    public function rateClient(int $rating, ?string $review = null): void
    {
        $this->update([
            'client_rating' => $rating,
            'client_review' => $review,
        ]);

        if ($this->client) {
            $this->client->updateRating($rating);
        }
    }

    public function rateCourier(int $rating, ?string $review = null): void
    {
        $this->update([
            'courier_rating' => $rating,
            'courier_review' => $review,
        ]);

        if ($this->courier) {
            $this->courier->updateRating($rating);
        }
    }
}
