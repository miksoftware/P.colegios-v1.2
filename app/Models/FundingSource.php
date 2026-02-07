<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundingSource extends Model
{
    use LogsActivity;

    protected $fillable = [
        'budget_item_id',
        'code',
        'name',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Tipos de fuente de financiación según el Ministerio
     */
    public const TYPES = [
        'sgp' => 'SGP - Sistema General de Participaciones',
        'rp' => 'RP - Recursos Propios',
        'rb' => 'RB - Recursos de Balance',
        'other' => 'Otros',
    ];

    /**
     * Códigos estándar del Ministerio de Educación
     */
    public const STANDARD_CODES = [
        '1' => 'RP - Recursos Propios',
        '2' => 'SGP - Sistema General de Participaciones',
        '33' => 'RB RP - Recursos de Balance (Recursos Propios)',
        '34' => 'RB SGP - Recursos de Balance (SGP)',
    ];

    protected static function getActivityModule(): string
    {
        return 'funding_sources';
    }

    protected function getLogDescription(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Rubro al que pertenece esta fuente de financiación
     */
    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    /**
     * Presupuestos asociados a esta fuente
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Ingresos registrados para esta fuente
     */
    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    /**
     * Detalle de CDPs que reservan de esta fuente
     */
    public function cdpFundingSources(): HasMany
    {
        return $this->hasMany(CdpFundingSource::class);
    }

    /**
     * Total reservado por CDPs activos para esta fuente en un año fiscal
     */
    public function getTotalReservedByCdps(int $year): float
    {
        return Cdp::getTotalReservedForFundingSource($this->id, $year);
    }

    /**
     * Obtener nombre del tipo
     */
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Color del badge según el tipo
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'sgp' => 'bg-blue-100 text-blue-700',
            'rp' => 'bg-green-100 text-green-700',
            'rb' => 'bg-yellow-100 text-yellow-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Código con nombre para mostrar en selects
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Total ejecutado (ingresos reales recibidos)
     */
    public function getTotalExecutedAttribute(): float
    {
        return $this->incomes()->sum('amount');
    }

    /**
     * Total presupuestado como ingreso para un año
     */
    public function getBudgetedIncomeForYear(int $year): float
    {
        return $this->budgets()
            ->where('type', 'income')
            ->where('fiscal_year', $year)
            ->sum('current_amount');
    }

    /**
     * Total presupuestado como gasto para un año
     */
    public function getBudgetedExpenseForYear(int $year): float
    {
        return $this->budgets()
            ->where('type', 'expense')
            ->where('fiscal_year', $year)
            ->sum('current_amount');
    }

    /**
     * Calcula el saldo disponible real de la fuente de financiación
     * Saldo = Ingresos - Transferencias Salientes + Transferencias Entrantes
     */
    public function getAvailableBalanceAttribute(): float
    {
        $totalIncomes = $this->incomes()->sum('amount');
        
        $totalOutgoing = BudgetTransfer::where('source_funding_source_id', $this->id)->sum('amount');
        $totalIncoming = BudgetTransfer::where('destination_funding_source_id', $this->id)->sum('amount');
        
        return $totalIncomes - $totalOutgoing + $totalIncoming;
    }

    /**
     * Calcula el saldo disponible para un año específico
     */
    public function getAvailableBalanceForYear(int $year): float
    {
        $totalIncomes = $this->incomes()->whereYear('date', $year)->sum('amount');
        
        $totalOutgoing = BudgetTransfer::where('source_funding_source_id', $this->id)
            ->where('fiscal_year', $year)
            ->sum('amount');
        $totalIncoming = BudgetTransfer::where('destination_funding_source_id', $this->id)
            ->where('fiscal_year', $year)
            ->sum('amount');
        
        return $totalIncomes - $totalOutgoing + $totalIncoming;
    }

    /**
     * Scope para filtrar por rubro
     */
    public function scopeForBudgetItem($query, int $budgetItemId)
    {
        return $query->where('budget_item_id', $budgetItemId);
    }

    /**
     * Scope para fuentes activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para buscar por código o nombre
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        });
    }
}
