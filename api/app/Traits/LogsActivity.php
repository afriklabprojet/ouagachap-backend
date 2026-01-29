<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    /**
     * Boot the trait
     */
    public static function bootLogsActivity(): void
    {
        // Log lors de la création
        static::created(function (Model $model) {
            if ($model->shouldLogActivity('created')) {
                $model->logActivity('created', null, $model->toArray());
            }
        });

        // Log lors de la mise à jour
        static::updated(function (Model $model) {
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
        });

        // Log lors de la suppression
        static::deleted(function (Model $model) {
            if ($model->shouldLogActivity('deleted')) {
                $model->logActivity('deleted', $model->toArray(), null);
            }
        });
    }

    /**
     * Enregistrer une activité
     */
    public function logActivity(string $action, ?array $oldValues = null, ?array $newValues = null, ?string $description = null): ActivityLog
    {
        $user = Auth::user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'log_type' => $this->getActivityLogType(),
            'action' => $action,
            'subject_type' => get_class($this),
            'subject_id' => $this->getKey(),
            'description' => $description ?? $this->getActivityDescription($action),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'properties' => $this->getActivityMetadata(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Enregistrer une activité personnalisée
     */
    public function logCustomActivity(string $action, string $description, ?array $metadata = null): ActivityLog
    {
        $user = Auth::user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'log_type' => $this->getActivityLogType(),
            'action' => $action,
            'subject_type' => get_class($this),
            'subject_id' => $this->getKey(),
            'description' => $description,
            'properties' => array_merge($this->getActivityMetadata(), $metadata ?? []),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Obtenir le type de log pour ce modèle
     */
    protected function getActivityLogType(): string
    {
        return property_exists($this, 'activityLogType') 
            ? $this->activityLogType 
            : strtolower(class_basename($this));
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
            : [];

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
        if ($this->tracking_number ?? null) {
            return $this->tracking_number;
        }
        if ($this->name ?? null) {
            return $this->name;
        }
        if ($this->phone ?? null) {
            return $this->phone;
        }
        
        return "#{$this->getKey()}";
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
