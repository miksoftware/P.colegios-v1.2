<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'budget_item_id',
        'type',
        'initial_amount',
        'current_amount',
        'fiscal_year',
        'description',
        'is_active',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'fiscal_year' => 'integer',
        'is_active' => 'boolean',
    ];

    public const TYPES = [
        'income' => 'Ingreso',
        'expense' => 'Gasto',
    ];

    protected static function getActivityModule(): string
    {
        return 'budgets';
    }

    protected function getLogDescription(): string
    {
        return "{$this->budgetItem->code} - {$this->budgetItem->name}";
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function modifications(): HasMany
    {
        return $this->hasMany(BudgetModification::class)->orderBy('modification_number');
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type === 'income' 
            ? 'bg-green-100 text-green-700' 
            : 'bg-red-100 text-red-700';
    }

    public function getTotalAdditionsAttribute(): float
    {
        return $this->modifications()->where('type', 'addition')->sum('amount');
    }

    public function getTotalReductionsAttribute(): float
    {
        return $this->modifications()->where('type', 'reduction')->sum('amount');
    }

    public function getNextModificationNumber(): int
    {
        return ($this->modifications()->max('modification_number') ?? 0) + 1;
    }

    public function recalculateCurrentAmount(): void
    {
        $this->current_amount = $this->initial_amount + $this->total_additions - $this->total_reductions;
        $this->save();
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->whereHas('budgetItem', function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        });
    }
}
