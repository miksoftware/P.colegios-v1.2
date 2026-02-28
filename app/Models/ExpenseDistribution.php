<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseDistribution extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'budget_id',
        'expense_code_id',
        'amount',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function getActivityModule(): string
    {
        return 'expenses';
    }

    protected function getLogDescription(): string
    {
        return $this->expenseCode?->name ?? 'Distribución #' . $this->id;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function expenseCode(): BelongsTo
    {
        return $this->belongsTo(ExpenseCode::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ExpenseExecution::class);
    }

    /**
     * Convocatorias generadas desde esta distribución
     */
    public function convocatorias(): HasMany
    {
        return $this->hasMany(Convocatoria::class);
    }
    /**
     * Distribuciones de convocatoria que usan esta distribución de gasto
     */
    public function convocatoriaDistributions(): HasMany
    {
        return $this->hasMany(ConvocatoriaDistribution::class);
    }

    // Monto total ejecutado
    public function getTotalExecutedAttribute(): float
    {
        return (float) $this->executions()->sum('amount');
    }

    // Monto comprometido en convocatorias no canceladas
    public function getTotalCommittedAttribute(): float
    {
        return (float) $this->convocatoriaDistributions()
            ->whereHas('convocatoria', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->sum('amount');
    }

    // Saldo disponible (resta ejecutado + comprometido en convocatorias)
    public function getAvailableBalanceAttribute(): float
    {
        return (float) $this->amount - $this->total_executed - $this->total_committed;
    }

    // Porcentaje ejecutado
    public function getExecutionPercentageAttribute(): float
    {
        if ($this->amount <= 0) {
            return 0;
        }
        return round(($this->total_executed / $this->amount) * 100, 2);
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForBudget($query, int $budgetId)
    {
        return $query->where('budget_id', $budgetId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
