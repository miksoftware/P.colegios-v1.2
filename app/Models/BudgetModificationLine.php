<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetModificationLine extends Model
{
    protected $fillable = [
        'budget_modification_id',
        'expense_distribution_id',
        'amount_before',
        'amount_after',
        'document_date',
    ];

    protected $casts = [
        'amount_before' => 'decimal:2',
        'amount_after'  => 'decimal:2',
        'document_date' => 'date',
    ];

    public function budgetModification(): BelongsTo
    {
        return $this->belongsTo(BudgetModification::class);
    }

    public function expenseDistribution(): BelongsTo
    {
        return $this->belongsTo(ExpenseDistribution::class);
    }

    public function getAmountChangeAttribute(): float
    {
        return (float) $this->amount_after - (float) $this->amount_before;
    }

    /**
     * Fecha efectiva de la línea:
     * - document_date propio si existe
     * - fallback al document_date de la modificación padre
     */
    public function getEffectiveDateAttribute()
    {
        return $this->document_date ?? $this->budgetModification?->document_date;
    }
}
