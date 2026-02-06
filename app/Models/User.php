<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Traits\LogsActivity;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, LogsActivity, HasRoles;

    // Champs sensibles à exclure des logs
    protected array $excludedLogFields = [
        'password', 'remember_token', 'fcm_token', 'updated_at',
        'current_latitude', 'current_longitude', 'location_updated_at'
    ];

    protected $fillable = [
        'phone',
        'firebase_uid',
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
        'current_latitude',
        'current_longitude',
        'location_updated_at',
        'total_orders',
        'average_rating',
        'total_ratings',
        'wallet_balance',
        'fcm_token',
        'device_type',
        'fcm_token_updated_at',
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
            'average_rating' => 'decimal:2',
            'wallet_balance' => 'decimal:2',
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

    // ==================== SCOPES ====================

    public function scopeClients($query)
    {
        return $query->where('role', UserRole::CLIENT);
    }

    public function scopeCouriers($query)
    {
        return $query->where('role', UserRole::COURIER);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', UserRole::ADMIN);
    }

    public function scopeActive($query)
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
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
        
        // Vérifier si le coursier a déjà une livraison active
        $hasActiveDelivery = $this->courierOrders()
            ->whereIn('status', ['assigned', 'accepted', 'picking_up', 'picked_up', 'in_transit'])
            ->exists();
        
        return !$hasActiveDelivery;
    }

    /**
     * Check if courier has an active delivery
     */
    public function hasActiveDelivery(): bool
    {
        return $this->courierOrders()
            ->whereIn('status', ['assigned', 'accepted', 'picking_up', 'picked_up', 'in_transit'])
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

    public function updateRating(int $newRating): void
    {
        $totalRatings = $this->total_ratings + 1;
        $averageRating = (($this->average_rating * $this->total_ratings) + $newRating) / $totalRatings;

        $this->update([
            'average_rating' => round($averageRating, 2),
            'total_ratings' => $totalRatings,
        ]);
    }

    public function incrementTotalOrders(): void
    {
        $this->increment('total_orders');
    }

    public function addToWallet(float $amount): void
    {
        $this->increment('wallet_balance', $amount);
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }
        
        // Si c'est déjà une URL complète, retourner tel quel
        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }
        
        // Sinon, générer l'URL du storage
        return url('storage/' . $this->avatar);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() && $this->isActive();
    }
}
