<?php

namespace App\Models;

use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\OrderStatus;
use App\Traits\LogsActivity;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property UserRole   $role
 * @property UserStatus $status
 * @property KycStatus  $kyc_status
 */
class User extends Authenticatable implements FilamentUser // NOSONAR php:S1448
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, LogsActivity, HasRoles;

    // Champs sensibles à exclure des logs
    protected array $excludedLogFields = [
        'password', 'remember_token', 'fcm_token', 'updated_at',
        'current_latitude', 'current_longitude', 'latitude', 'longitude', 'location_updated_at',
        'identity_document_url', 'selfie_url',
    ];

    /**
     * Accesseurs à inclure dans la sérialisation JSON
     */
    protected $appends = ['avatar_url'];

    protected $fillable = [
        'phone',
        'firebase_uid',
        'device_fingerprint',
        'name',
        'email',
        'avatar',
        'password',
        'role',
        'status',
        'vehicle_type',
        'vehicle_plate',
        'vehicle_model',
        'is_available',
        'current_latitude',   // position live — seul champ à utiliser pour la géolocalisation
        'current_longitude',
        'location_updated_at',
        'battery_level',
        'battery_updated_at',
        'fcm_token',
        'device_type',
        'fcm_token_updated_at',
        'notification_preferences',
        'last_seen_at',
        'identity_document_url',
        'selfie_url',
        'documents_submitted_at',
        'documents_verified_at',
        'kyc_status',
        'kyc_rejection_reason',
        'phone_verified_at',
        'referral_code',
        'referred_by_user_id',
    ];

    /**
     * Champs calculés protégés contre le mass assignment.
     * Utiliser les méthodes dédiées : updateRating(), addToWallet(), incrementTotalOrders()
     */
    protected $guarded = [
        'id',
        'total_orders',
        'average_rating',
        'total_ratings',
        'wallet_balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'fcm_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'is_available' => 'boolean',
            'current_latitude' => 'decimal:8',
            'current_longitude' => 'decimal:8',
            'location_updated_at' => 'datetime',
            'battery_updated_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'fcm_token_updated_at' => 'datetime',
            'average_rating' => 'decimal:2',
            'wallet_balance' => 'decimal:2',
            'notification_preferences' => 'array',
            'kyc_status' => KycStatus::class,
            'documents_submitted_at' => 'datetime',
            'documents_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function clientOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    public function courierOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'courier_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->whereNull('cancelled_at')->where('ends_at', '>', now());
    }

    public function questProgress(): HasMany
    {
        return $this->hasMany(CourierQuestProgress::class, 'courier_id');
    }

    /**
     * L'utilisateur qui a parrainé ce user.
     */
    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    /**
     * Les utilisateurs parrainés par ce user.
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    // ==================== SCOPES ====================

    public function scopeClients(Builder $query): Builder
    {
        return $query->where('role', UserRole::CLIENT);
    }

    public function scopeCouriers(Builder $query): Builder
    {
        return $query->where('role', UserRole::COURIER);
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', UserRole::ADMIN);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true)
            ->where('wallet_balance', '>=', \App\Services\CourierService::MINIMUM_WALLET_BALANCE)
            ->where('kyc_status', \App\Enums\KycStatus::APPROVED);
    }

    // ==================== HELPERS ====================

    public function isClient(): bool
    {
        return $this->role === UserRole::CLIENT;
    }

    public function isCourier(): bool
    {
        return $this->role === UserRole::COURIER;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    public function canAcceptOrders(): bool
    {
        if (!$this->isCourier() || !$this->isActive() || !$this->is_available) {
            return false;
        }

        if ($this->kyc_status !== \App\Enums\KycStatus::APPROVED) {
            return false;
        }

        if ((float) $this->wallet_balance < \App\Services\CourierService::MINIMUM_WALLET_BALANCE) {
            return false;
        }

        // Vérifier si le coursier a déjà une livraison active
        $hasActiveDelivery = $this->courierOrders()
            ->whereIn('status', OrderStatus::activeStatuses())
            ->exists();

        return !$hasActiveDelivery;
    }

    /**
     * Check if courier has an active delivery
     */
    public function hasActiveDelivery(): bool
    {
        return $this->courierOrders()
            ->whereIn('status', OrderStatus::activeStatuses())
            ->exists();
    }

    public function updateLocation(float $latitude, float $longitude): void
    {
        $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'location_updated_at' => now(),
        ]);
    }

    /**
     * Met à jour la note moyenne de l'utilisateur de manière thread-safe.
     * Utilise un lock en base pour éviter les race conditions.
     */
    public function updateRating(int $newRating): void
    {
        DB::transaction(function () use ($newRating) {
            $user = self::lockForUpdate()->find($this->id);
            $totalRatings = $user->total_ratings + 1;
            $averageRating = (($user->average_rating * $user->total_ratings) + $newRating) / $totalRatings;

            $user->average_rating = round($averageRating, 2);
            $user->total_ratings = $totalRatings;
            $user->saveQuietly();
        });

        $this->refresh();
    }

    public function incrementTotalOrders(): void
    {
        $this->increment('total_orders');
    }

    /**
     * Créditer le wallet du coursier via WalletService (source unique de vérité).
     */
    public function addToWallet(float $amount): void
    {
        $walletService = app(\App\Services\WalletService::class);
        $walletService->creditCourierForDelivery($this, $amount);
    }

    /**
     * Synchronize wallet_balance from Wallet model (source of truth)
     */
    public function syncWalletBalance(): void
    {
        $wallet = \App\Models\Wallet::where('user_id', $this->id)->first();
        if ($wallet) {
            $this->forceFill(['wallet_balance' => $wallet->balance])->save();
        }
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        // URL complète déjà stockée (CDN ou autre) — retourner tel quel
        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        // Chemin relatif — résoudre via CdnService (local ou R2 selon l'env)
        return app(\App\Services\CdnService::class)->url($this->avatar);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() && $this->isActive();
    }
}
