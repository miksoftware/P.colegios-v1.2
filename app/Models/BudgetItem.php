<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'accounting_account_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Módulo para el log de actividad
     */
    protected static function getActivityModule(): string
    {
        return 'budget_items';
    }

    /**
     * Descripción para el log
     */
    protected function getLogDescription(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Cuenta contable auxiliar asociada
     */
    public function accountingAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class);
    }

    /**
     * Fuentes de financiación asociadas
     */
    public function fundingSources(): HasMany
    {
        return $this->hasMany(FundingSource::class);
    }

    /**
     * Presupuestos asociados a este rubro
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * CDPs asociados a este rubro
     */
    public function cdps(): HasMany
    {
        return $this->hasMany(Cdp::class);
    }

    /**
     * Obtener código completo (cuenta + rubro)
     */
    public function getFullCodeAttribute(): string
    {
        $accountCode = $this->accountingAccount?->code ?? '';
        return $accountCode ? "{$accountCode}-{$this->code}" : $this->code;
    }

    /**
     * Scope para rubros activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para buscar por nombre o código
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        });
    }
}
