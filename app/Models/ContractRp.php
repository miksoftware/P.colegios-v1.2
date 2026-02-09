<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractRp extends Model
{
    protected $table = 'contract_rps';

    protected $fillable = [
        'contract_id',
        'cdp_id',
        'rp_number',
        'fiscal_year',
        'total_amount',
        'status',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    const STATUSES = [
        'active'    => 'Activo',
        'cancelled' => 'Anulado',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function cdp(): BelongsTo
    {
        return $this->belongsTo(Cdp::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fundingSources(): HasMany
    {
        return $this->hasMany(RpFundingSource::class);
    }

    // ── Accessors ─────────────────────────────────────────────
    public function getFormattedNumberAttribute(): string
    {
        return str_pad($this->rp_number, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    // ── Static helpers ────────────────────────────────────────
    public static function getNextRpNumber(int $schoolId, int $year): int
    {
        $max = static::whereHas('contract', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })->where('fiscal_year', $year)->max('rp_number');

        return ($max ?? 0) + 1;
    }
}
