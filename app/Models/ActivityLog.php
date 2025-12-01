<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'school_id',
        'action',
        'model_type',
        'model_id',
        'module',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Usuario que realizó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Colegio donde se realizó la acción
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Obtener el modelo relacionado
     */
    public function subject()
    {
        return $this->morphTo('model');
    }

    /**
     * Obtener nombre del módulo legible
     */
    public function getModuleDisplayNameAttribute(): string
    {
        $modules = [
            'accounting_accounts' => 'Cuentas Contables',
            'schools' => 'Colegios',
            'users' => 'Usuarios',
            'roles' => 'Roles',
        ];

        return $modules[$this->module] ?? $this->module ?? 'Sistema';
    }

    /**
     * Obtener acción legible
     */
    public function getActionDisplayNameAttribute(): string
    {
        $actions = [
            'created' => 'Creó',
            'updated' => 'Actualizó',
            'deleted' => 'Eliminó',
        ];

        return $actions[$this->action] ?? $this->action;
    }

    /**
     * Crear log de actividad
     */
    public static function log(
        string $action,
        Model $model,
        string $module,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'school_id' => session('selected_school_id'),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'module' => $module,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
