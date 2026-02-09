<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RpFundingSource extends Model
{
    protected $fillable = [
        'contract_rp_id',
        'funding_source_id',
        'budget_id',
        'amount',
        'bank_account_number',
        'bank_name',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function contractRp(): BelongsTo
    {
        return $this->belongsTo(ContractRp::class);
    }

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(FundingSource::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
