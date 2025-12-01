<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    /**
     * Nombre del módulo para el log (sobrescribir en el modelo)
     */
    protected static function getActivityModule(): string
    {
        return 'system';
    }

    /**
     * Campos a ignorar en el log
     */
    protected static function getIgnoredFields(): array
    {
        return ['created_at', 'updated_at', 'remember_token', 'password'];
    }

    /**
     * Boot del trait
     */
    protected static function bootLogsActivity(): void
    {
        // Log cuando se crea
        static::created(function ($model) {
            $model->logActivity('created', null, $model->getLoggableAttributes());
        });

        // Log cuando se actualiza
        static::updated(function ($model) {
            $original = collect($model->getOriginal())
                ->except(static::getIgnoredFields())
                ->toArray();
            
            $changes = collect($model->getChanges())
                ->except(static::getIgnoredFields())
                ->toArray();

            if (!empty($changes)) {
                $model->logActivity('updated', $original, $changes);
            }
        });

        // Log cuando se elimina
        static::deleted(function ($model) {
            $model->logActivity('deleted', $model->getLoggableAttributes(), null);
        });
    }

    /**
     * Obtener atributos para loggear
     */
    protected function getLoggableAttributes(): array
    {
        return collect($this->getAttributes())
            ->except(static::getIgnoredFields())
            ->toArray();
    }

    /**
     * Registrar actividad
     */
    protected function logActivity(string $action, ?array $oldValues, ?array $newValues): void
    {
        $descriptions = [
            'created' => 'Creó ' . $this->getLogDescription(),
            'updated' => 'Actualizó ' . $this->getLogDescription(),
            'deleted' => 'Eliminó ' . $this->getLogDescription(),
        ];

        ActivityLog::create([
            'user_id' => auth()->id(),
            'school_id' => session('selected_school_id') ?? $this->school_id ?? null,
            'action' => $action,
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'module' => static::getActivityModule(),
            'description' => $descriptions[$action] ?? null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Obtener descripción del registro para el log
     */
    protected function getLogDescription(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }
        if (isset($this->code) && isset($this->name)) {
            return "{$this->code} - {$this->name}";
        }
        return "registro #{$this->id}";
    }
}
