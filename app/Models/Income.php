<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Income extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'funding_source_id',
        'name',
        'description',
        'amount',
        'date',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    protected static function getActivityModule(): string
    {
        return 'incomes';
    }

    protected function getLogDescription(): string
    {
        return "Ingreso: {$this->name} - " . number_format($this->amount, 2);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(FundingSource::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(IncomeBankAccount::class);
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('date', $year);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhereHas('fundingSource', function ($sub) use ($search) {
                  $sub->where('name', 'like', "%{$search}%");
              })
              ->orWhereHas('bankAccounts.bank', function ($sub) use ($search) {
                  $sub->where('name', 'like', "%{$search}%");
              });
        });
    }
}
