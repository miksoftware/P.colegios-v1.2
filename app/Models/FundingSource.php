<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundingSource extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'budget_item_id',
        'name',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const TYPES = [
        'internal' => 'Interna',
        'external' => 'Externa',
    ];

    protected static function getActivityModule(): string
    {
        return 'funding_sources';
    }

    protected function getLogDescription(): string
    {
        return $this->name;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type === 'internal'
            ? 'bg-blue-100 text-blue-700'
            : 'bg-purple-100 text-purple-700';
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
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
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhereHas('budgetItem', function ($q) use ($search) {
                  $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
              });
        });
    }
}
