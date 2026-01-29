<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_type',
        'action',
        'description',
        'user_id',
        'subject_type',
        'subject_id',
        'properties',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Types de logs
    const TYPE_AUTH = 'auth';
    const TYPE_ORDER = 'order';
    const TYPE_PAYMENT = 'payment';
    const TYPE_ADMIN = 'admin';
    const TYPE_SYSTEM = 'system';
    const TYPE_COURIER = 'courier';
    const TYPE_USER = 'user';

    // Actions communes
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_VIEW = 'view';
    const ACTION_EXPORT = 'export';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Logger une activité
     */
    public static function log(
        string $type,
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return self::create([
            'log_type' => $type,
            'action' => $action,
            'description' => $description,
            'user_id' => auth()->id(),
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'properties' => $properties ?: null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Logger une connexion
     */
    public static function logLogin(User $user): self
    {
        return self::log(
            self::TYPE_AUTH,
            self::ACTION_LOGIN,
            "Connexion de {$user->name}",
            $user,
            ['role' => $user->role]
        );
    }

    /**
     * Logger une déconnexion
     */
    public static function logLogout(User $user): self
    {
        return self::log(
            self::TYPE_AUTH,
            self::ACTION_LOGOUT,
            "Déconnexion de {$user->name}",
            $user
        );
    }

    /**
     * Logger une création
     */
    public static function logCreate(Model $model, string $description): self
    {
        $type = self::getTypeFromModel($model);
        
        return self::log(
            $type,
            self::ACTION_CREATE,
            $description,
            $model,
            [],
            null,
            $model->toArray()
        );
    }

    /**
     * Logger une mise à jour
     */
    public static function logUpdate(Model $model, string $description, array $oldValues, array $newValues): self
    {
        $type = self::getTypeFromModel($model);
        
        return self::log(
            $type,
            self::ACTION_UPDATE,
            $description,
            $model,
            [],
            $oldValues,
            $newValues
        );
    }

    /**
     * Logger une suppression
     */
    public static function logDelete(Model $model, string $description): self
    {
        $type = self::getTypeFromModel($model);
        
        return self::log(
            $type,
            self::ACTION_DELETE,
            $description,
            $model,
            $model->toArray()
        );
    }

    /**
     * Logger une action admin
     */
    public static function logAdminAction(string $action, string $description, ?Model $subject = null, array $properties = []): self
    {
        return self::log(
            self::TYPE_ADMIN,
            $action,
            $description,
            $subject,
            $properties
        );
    }

    /**
     * Déterminer le type de log à partir du modèle
     */
    private static function getTypeFromModel(Model $model): string
    {
        return match(get_class($model)) {
            Order::class => self::TYPE_ORDER,
            Payment::class => self::TYPE_PAYMENT,
            User::class => self::TYPE_USER,
            default => self::TYPE_SYSTEM,
        };
    }

    // Scopes
    public function scopeOfType($query, string $type)
    {
        return $query->where('log_type', $type);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
