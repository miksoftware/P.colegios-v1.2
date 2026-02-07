<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cdp extends Model
{
    use LogsActivity;

    protected $table = 'cdps';

    protected $fillable = [
        'school_id',
        'convocatoria_id',
        'cdp_number',
        'fiscal_year',
        'budget_item_id',
        'total_amount',
        'status',
        'created_by',
    ];

    protected $casts = [
        'cdp_number' => 'integer',
        'fiscal_year' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    const STATUSES = [
        'active' => 'Activo',
        'used' => 'Utilizado',
        'cancelled' => 'Anulado',
    ];

    const STATUS_COLORS = [
        'active' => 'bg-green-100 text-green-700',
        'used' => 'bg-blue-100 text-blue-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];

    protected static function getActivityModule(): string
    {
        return 'precontractual';
    }

    protected function getLogDescription(): string
    {
        return 'CDP #' . str_pad($this->cdp_number, 3, '0', STR_PAD_LEFT);
    }

    // Relaciones

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function convocatoria(): BelongsTo
    {
        return $this->belongsTo(Convocatoria::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fundingSources(): HasMany
    {
        return $this->hasMany(CdpFundingSource::class);
    }

    // Accessors

    public function getFormattedNumberAttribute(): string
    {
        return str_pad($this->cdp_number, 3, '0', STR_PAD_LEFT);
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-gray-100 text-gray-700';
    }

    // Scopes

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
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('cdp_number', 'like', "%{$search}%");
    }

    // Métodos estáticos

    public static function getNextCdpNumber(int $schoolId, int $fiscalYear): int
    {
        $max = static::where('school_id', $schoolId)
            ->where('fiscal_year', $fiscalYear)
            ->max('cdp_number');

        return ($max ?? 0) + 1;
    }

    /**
     * Calcula el total reservado por CDPs activos para una fuente de financiación específica
     * en un año fiscal dado.
     */
    public static function getTotalReservedForFundingSource(int $fundingSourceId, int $fiscalYear): float
    {
        return (float) CdpFundingSource::whereHas('cdp', function ($q) use ($fiscalYear) {
            $q->where('fiscal_year', $fiscalYear)
              ->where('status', 'active');
        })
        ->where('funding_source_id', $fundingSourceId)
        ->sum('amount');
    }
}
