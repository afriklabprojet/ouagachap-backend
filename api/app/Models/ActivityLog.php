<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    // Log types
    const TYPE_AUTH = 'auth';
    const TYPE_ORDER = 'order';
    const TYPE_PAYMENT = 'payment';
    const TYPE_ADMIN = 'admin';
    const TYPE_SYSTEM = 'system';
    const TYPE_COURIER = 'courier';
    const TYPE_USER = 'user';

    // Actions
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_VIEW = 'view';
    const ACTION_EXPORT = 'export';
    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';
    const ACTION_STATUS_CHANGE = 'status_change';

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

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // ==================== SCOPES ====================

    public function scopeOfType($query, string $type)
    {
        return $query->where('log_type', $type);
    }

    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->getKey());
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Create a log entry for the current authenticated user.
     */
    public static function log(
        string $logType,
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        array $oldValues = [],
        array $newValues = []
    ): self {
        return self::create([
            'log_type' => $logType,
            'action' => $action,
            'description' => $description,
            'user_id' => auth()->id(),
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a user login.
     */
    public static function logLogin(User $user): self
    {
        return self::log(
            self::TYPE_AUTH,
            self::ACTION_LOGIN,
            "Connexion de {$user->name}",
            $user
        );
    }

    /**
     * Log a user logout.
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
     * Log a model creation.
     */
    public static function logCreate(Model $subject, string $description, array $properties = []): self
    {
        return self::log(
            self::resolveLogType($subject),
            self::ACTION_CREATE,
            $description,
            $subject,
            $properties
        );
    }

    /**
     * Log a model update.
     */
    public static function logUpdate(Model $subject, string $description, array $oldValues = [], array $newValues = []): self
    {
        return self::log(
            self::resolveLogType($subject),
            self::ACTION_UPDATE,
            $description,
            $subject,
            [],
            $oldValues,
            $newValues
        );
    }

    /**
     * Log a model deletion.
     */
    public static function logDelete(Model $subject, string $description, array $properties = []): self
    {
        return self::log(
            self::resolveLogType($subject),
            self::ACTION_DELETE,
            $description,
            $subject,
            $properties
        );
    }

    /**
     * Log an admin action.
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
     * Resolve the log type from a model class.
     */
    protected static function resolveLogType(Model $subject): string
    {
        return match (true) {
            $subject instanceof Order => self::TYPE_ORDER,
            $subject instanceof Payment => self::TYPE_PAYMENT,
            $subject instanceof User => self::TYPE_USER,
            default => self::TYPE_SYSTEM,
        };
    }
}
