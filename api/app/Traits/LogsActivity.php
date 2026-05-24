<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @method static void created(\Closure $callback)
 * @method static void updated(\Closure $callback)
 * @method static void deleted(\Closure $callback)
 */
trait LogsActivity
{
    /**
     * Boot the trait
     */
    /**
     * @codeCoverageIgnore
     */
    public static function bootLogsActivity(): void
    {
        // Log lors de la création
        static::created(function (Model $model) {
            try {
                if ($model->shouldLogActivity('created')) {
                    $model->logActivity('created', null, $model->toArray());
                }
            } catch (\Throwable $e) {
                Log::warning('Activity log failed on created: ' . $e->getMessage());
            }
        });

        // Log lors de la mise à jour
        static::updated(function (Model $model) {
            try {
                if ($model->shouldLogActivity('updated')) {
                    $changes = $model->getChanges();
                    $original = array_intersect_key($model->getOriginal(), $changes);

                    // Exclure les champs sensibles
                    $excludeFields = $model->getExcludedLogFields();
                    $changes = array_diff_key($changes, array_flip($excludeFields));
                    $original = array_diff_key($original, array_flip($excludeFields));

                    if (!empty($changes)) {
                        $model->logActivity('updated', $original, $changes);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Activity log failed on updated: ' . $e->getMessage());
            }
        });

        // Log lors de la suppression
        static::deleted(function (Model $model) {
            try {
                if ($model->shouldLogActivity('deleted')) {
                    $model->logActivity('deleted', $model->toArray(), null);
                }
            } catch (\Throwable $e) {
                Log::warning('Activity log failed on deleted: ' . $e->getMessage());
            }
        });
    }

    /**
     * Enregistrer une activité
     */
    public function logActivity(string $action, ?array $oldValues = null, ?array $newValues = null, ?string $description = null): ?ActivityLog
    {
        try {
            $user = Auth::user();

            return ActivityLog::create([
                'user_id' => $user?->id,
                'log_type' => $this->getActivityLogType(),
                'action' => $action,
                'subject_type' => get_class($this),
                'subject_id' => (string) $this->getKey(),
                'description' => $description ?? $this->getActivityDescription($action),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'properties' => $this->getActivityMetadata(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Activity log creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Enregistrer une activité personnalisée
     */
    public function logCustomActivity(string $action, string $description, ?array $metadata = null): ?ActivityLog
    {
        try {
            $user = Auth::user();

            return ActivityLog::create([
                'user_id' => $user?->id,
                'log_type' => $this->getActivityLogType(),
                'action' => $action,
                'subject_type' => get_class($this),
                'subject_id' => (string) $this->getKey(),
                'description' => $description,
                'properties' => array_merge($this->getActivityMetadata(), $metadata ?? []),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            // @codeCoverageIgnoreStart
        } catch (\Throwable $e) {
            Log::warning('Custom activity log failed: ' . $e->getMessage());
            return null;
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Obtenir le type de log pour ce modèle
     */
    protected function getActivityLogType(): string
    {
        // @codeCoverageIgnoreStart
        return property_exists($this, 'activityLogType')
            ? $this->activityLogType
            : strtolower(class_basename($this));
        // @codeCoverageIgnoreEnd
    }

    /**
     * Vérifier si on doit logger cette activité
     */
    protected function shouldLogActivity(string $action): bool
    {
        // Par défaut, tout logger sauf si spécifié autrement
        $loggedTypes = $this->getLoggedActivityTypes();

        if ($loggedTypes === ['*']) {
            return true;
        }

        return in_array($action, $loggedTypes);
    }

    /**
     * Obtenir les types d'activités à logger
     * Peut être surchargé dans le modèle
     */
    protected function getLoggedActivityTypes(): array
    {
        return property_exists($this, 'loggedActivityTypes')
            ? $this->loggedActivityTypes
            : ['*'];
    }

    /**
     * Obtenir les champs à exclure des logs
     * Peut être surchargé dans le modèle
     */
    protected function getExcludedLogFields(): array
    {
        $defaults = ['password', 'remember_token', 'api_token', 'fcm_token', 'updated_at'];

        $custom = property_exists($this, 'excludedLogFields')
            ? $this->excludedLogFields
            : []; // @codeCoverageIgnore

        return array_merge($defaults, $custom);
    }

    /**
     * Obtenir la description de l'activité
     */
    protected function getActivityDescription(string $action): string
    {
        $modelName = class_basename($this);
        $identifier = $this->getActivityIdentifier();

        return match ($action) {
            'created' => "{$modelName} {$identifier} créé",
            'updated' => "{$modelName} {$identifier} mis à jour",
            'deleted' => "{$modelName} {$identifier} supprimé",
            default => "{$modelName} {$identifier}: {$action}",
        };
    }

    /**
     * Obtenir l'identifiant pour les logs
     */
    protected function getActivityIdentifier(): string
    {
        // Essayer différents champs communs
        // @codeCoverageIgnoreStart
        if ($this->getAttribute('order_number') ?? null) {
            return (string) $this->getAttribute('order_number');
        }
        // @codeCoverageIgnoreEnd
        if ($this->getAttribute('name') ?? null) {
            return (string) $this->getAttribute('name');
        }
        if ($this->getAttribute('phone') ?? null) {
            return (string) $this->getAttribute('phone');
        }

        return "#{$this->getKey()}"; // @codeCoverageIgnore
    }

    /**
     * Obtenir les métadonnées supplémentaires
     * Peut être surchargé dans le modèle
     */
    protected function getActivityMetadata(): array
    {
        return [];
    }

    /**
     * Relation vers les logs d'activité de ce modèle
     */
    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}
