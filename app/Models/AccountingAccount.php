<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingAccount extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'description',
        'level',
        'parent_id',
        'nature',
        'allows_movement',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'allows_movement' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Nombres de los niveles
     */
    public const LEVELS = [
        1 => 'Clase',
        2 => 'Grupo',
        3 => 'Cuenta',
        4 => 'Subcuenta',
        5 => 'Auxiliar',
    ];

    /**
     * Colores para cada nivel (Tailwind)
     */
    public const LEVEL_COLORS = [
        1 => 'bg-purple-100 text-purple-800',
        2 => 'bg-blue-100 text-blue-800',
        3 => 'bg-teal-100 text-teal-800',
        4 => 'bg-orange-100 text-orange-800',
        5 => 'bg-gray-100 text-gray-800',
    ];

    /**
     * Módulo para el log de actividad
     */
    protected static function getActivityModule(): string
    {
        return 'accounting_accounts';
    }

    /**
     * Descripción para el log
     */
    protected function getLogDescription(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Cuenta padre
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'parent_id');
    }

    /**
     * Cuentas hijas
     */
    public function children(): HasMany
    {
        return $this->hasMany(AccountingAccount::class, 'parent_id')->orderBy('code');
    }

    /**
     * Cuentas hijas recursivas
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * Obtener nombre del nivel
     */
    public function getLevelNameAttribute(): string
    {
        return self::LEVELS[$this->level] ?? 'Desconocido';
    }

    /**
     * Obtener color del nivel
     */
    public function getLevelColorAttribute(): string
    {
        return self::LEVEL_COLORS[$this->level] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Obtener naturaleza legible
     */
    public function getNatureNameAttribute(): string
    {
        return $this->nature === 'D' ? 'Débito' : 'Crédito';
    }

    /**
     * Obtener la ruta completa de la cuenta (jerarquía)
     */
    public function getFullPathAttribute(): string
    {
        $path = collect([$this->name]);
        $parent = $this->parent;

        while ($parent) {
            $path->prepend($parent->name);
            $parent = $parent->parent;
        }

        return $path->implode(' → ');
    }

    /**
     * Verificar si tiene hijos
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Obtener siguiente código sugerido para un hijo
     */
    public function getNextChildCode(): string
    {
        $lastChild = $this->children()->orderByDesc('code')->first();

        if (!$lastChild) {
            // Primer hijo: añadir 01 o 0001 según el nivel
            $suffix = $this->level < 3 ? '01' : '0001';
            return $this->code . $suffix;
        }

        // Incrementar el último código
        $lastCode = $lastChild->code;
        $parentCode = $this->code;
        $childPart = substr($lastCode, strlen($parentCode));
        $nextNumber = intval($childPart) + 1;
        
        $padLength = strlen($childPart);
        return $parentCode . str_pad($nextNumber, $padLength, '0', STR_PAD_LEFT);
    }

    /**
     * Obtener siguiente código para una clase (nivel 1)
     */
    public static function getNextClassCode(): string
    {
        $lastClass = self::where('level', 1)
            ->orderByDesc('code')
            ->first();

        if (!$lastClass) {
            return '1';
        }

        return (string)(intval($lastClass->code) + 1);
    }

    /**
     * Scope para cuentas raíz (clases)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orderBy('code');
    }

    /**
     * Scope para cuentas activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
