<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Convocatoria extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'expense_distribution_id',
        'convocatoria_number',
        'fiscal_year',
        'start_date',
        'end_date',
        'object',
        'justification',
        'assigned_budget',
        'requires_multiple_cdps',
        'status',
        'evaluation_date',
        'proposals_count',
        'created_by',
    ];

    protected $casts = [
        'convocatoria_number' => 'integer',
        'fiscal_year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'assigned_budget' => 'decimal:2',
        'requires_multiple_cdps' => 'boolean',
        'evaluation_date' => 'date',
        'proposals_count' => 'integer',
    ];

    const STATUSES = [
        'draft' => 'Borrador',
        'open' => 'Abierta',
        'evaluation' => 'En Evaluación',
        'awarded' => 'Adjudicada',
        'cancelled' => 'Cancelada',
    ];

    const STATUS_COLORS = [
        'draft' => 'bg-gray-100 text-gray-700',
        'open' => 'bg-blue-100 text-blue-700',
        'evaluation' => 'bg-yellow-100 text-yellow-700',
        'awarded' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];

    protected static function getActivityModule(): string
    {
        return 'precontractual';
    }

    protected function getLogDescription(): string
    {
        return 'Convocatoria #' . str_pad($this->convocatoria_number, 3, '0', STR_PAD_LEFT);
    }

    // Relaciones

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function expenseDistribution(): BelongsTo
    {
        return $this->belongsTo(ExpenseDistribution::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cdps(): HasMany
    {
        return $this->hasMany(Cdp::class)->orderBy('cdp_number');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class)->orderBy('proposal_number');
    }

    public function selectedProposal(): HasOne
    {
        return $this->hasOne(Proposal::class)->where('is_selected', true);
    }

    // Accessors

    public function getFormattedNumberAttribute(): string
    {
        return str_pad($this->convocatoria_number, 3, '0', STR_PAD_LEFT);
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-gray-100 text-gray-700';
    }

    public function getTotalCdpAmountAttribute(): float
    {
        return (float) $this->cdps()->sum('total_amount');
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

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('object', 'like', "%{$search}%")
              ->orWhere('justification', 'like', "%{$search}%")
              ->orWhere('convocatoria_number', 'like', "%{$search}%");
        });
    }

    // Métodos estáticos

    public static function getNextConvocatoriaNumber(int $schoolId, int $fiscalYear): int
    {
        $max = static::where('school_id', $schoolId)
            ->where('fiscal_year', $fiscalYear)
            ->max('convocatoria_number');

        return ($max ?? 0) + 1;
    }
}
