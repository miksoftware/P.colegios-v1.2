<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransfer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'transfer_number',
        'source_budget_id',
        'destination_budget_id',
        'amount',
        'source_previous_amount',
        'source_new_amount',
        'destination_previous_amount',
        'destination_new_amount',
        'reason',
        'document_number',
        'document_date',
        'fiscal_year',
        'created_by',
    ];

    protected $casts = [
        'transfer_number' => 'integer',
        'amount' => 'decimal:2',
        'source_previous_amount' => 'decimal:2',
        'source_new_amount' => 'decimal:2',
        'destination_previous_amount' => 'decimal:2',
        'destination_new_amount' => 'decimal:2',
        'document_date' => 'date',
        'fiscal_year' => 'integer',
    ];

    protected static function getActivityModule(): string
    {
        return 'budget_transfers';
    }

    protected function getLogDescription(): string
    {
        return "Traslado #{$this->formatted_number}";
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function sourceBudget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'source_budget_id');
    }

    public function destinationBudget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'destination_budget_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedNumberAttribute(): string
    {
        return str_pad($this->transfer_number, 3, '0', STR_PAD_LEFT);
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('transfer_number', 'like', "%{$search}%")
              ->orWhere('document_number', 'like', "%{$search}%")
              ->orWhere('reason', 'like', "%{$search}%")
              ->orWhereHas('sourceBudget.budgetItem', function ($sub) use ($search) {
                  $sub->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
              })
              ->orWhereHas('destinationBudget.budgetItem', function ($sub) use ($search) {
                  $sub->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
              });
        });
    }

    public static function getNextTransferNumber(int $schoolId, int $fiscalYear): int
    {
        return (static::forSchool($schoolId)->forYear($fiscalYear)->max('transfer_number') ?? 0) + 1;
    }
}
