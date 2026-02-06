<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InAppNotification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'icon',
        'color',
        'data',
        'action_url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Types de notifications
    const TYPE_ORDER = 'order_status';
    const TYPE_PAYMENT = 'payment';
    const TYPE_PROMO = 'promo';
    const TYPE_SYSTEM = 'system';
    const TYPE_WALLET = 'wallet';

    // Icônes par type
    const ICONS = [
        'order_status' => 'package',
        'payment' => 'credit-card',
        'promo' => 'gift',
        'system' => 'bell',
        'wallet' => 'wallet',
    ];

    // Couleurs par type
    const COLORS = [
        'order_status' => 'blue',
        'payment' => 'green',
        'promo' => 'purple',
        'system' => 'gray',
        'wallet' => 'orange',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Créer une notification in-app
     */
    public static function notify(
        User $user,
        string $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null
    ): self {
        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => self::ICONS[$type] ?? 'bell',
            'color' => self::COLORS[$type] ?? 'gray',
            'data' => $data,
            'action_url' => $actionUrl,
        ]);
    }

    /**
     * Créer depuis un NotificationType enum
     */
    public static function fromNotificationType(
        User $user,
        NotificationType $notificationType,
        array $data = []
    ): self {
        $type = self::mapNotificationTypeToInApp($notificationType);
        
        return self::notify(
            $user,
            $type,
            $notificationType->getTitle(),
            $notificationType->getDefaultMessage($data),
            $data,
            $data['action_url'] ?? null
        );
    }

    /**
     * Mapper NotificationType vers type in-app
     */
    private static function mapNotificationTypeToInApp(NotificationType $type): string
    {
        return match(true) {
            str_starts_with($type->value, 'order') => self::TYPE_ORDER,
            str_starts_with($type->value, 'payment') => self::TYPE_PAYMENT,
            str_starts_with($type->value, 'withdrawal') || str_starts_with($type->value, 'wallet') => self::TYPE_WALLET,
            $type->value === 'promotional' => self::TYPE_PROMO,
            default => self::TYPE_SYSTEM,
        };
    }

    /**
     * Marquer comme lue
     */
    public function markAsRead(): self
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return $this;
    }

    /**
     * Marquer plusieurs comme lues
     */
    public static function markManyAsRead(array $ids): int
    {
        return self::whereIn('id', $ids)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Marquer toutes les notifications d'un utilisateur comme lues
     */
    public static function markAllAsReadForUser(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
