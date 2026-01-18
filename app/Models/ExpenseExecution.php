<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseExecution extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'expense_distribution_id',
        'accounting_account_id',
        'supplier_id',
        'amount',
        'execution_date',
        'document_number',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'execution_date' => 'date',
    ];

    protected static function getActivityModule(): string
    {
        return 'expenses';
    }

    protected function getLogDescription(): string
    {
        return 'Ejecución $' . number_format($this->amount, 2) . ' - ' . ($this->supplier?->full_name ?? 'Sin proveedor');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function expenseDistribution(): BelongsTo
    {
        return $this->belongsTo(ExpenseDistribution::class);
    }

    public function accountingAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForDistribution($query, int $distributionId)
    {
        return $query->where('expense_distribution_id', $distributionId);
    }
}
