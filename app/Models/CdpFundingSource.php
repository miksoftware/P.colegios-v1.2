<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CdpFundingSource extends Model
{
    protected $fillable = [
        'cdp_id',
        'funding_source_id',
        'budget_id',
        'amount',
        'available_balance_at_creation',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'available_balance_at_creation' => 'decimal:2',
    ];

    // Relaciones

    public function cdp(): BelongsTo
    {
        return $this->belongsTo(Cdp::class);
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
