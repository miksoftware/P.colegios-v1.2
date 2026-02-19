<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeBankAccount extends Model
{
    protected $fillable = [
        'income_id',
        'bank_id',
        'bank_account_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // ─── Relationships ──────────────────────────────────

    public function income(): BelongsTo
    {
        return $this->belongsTo(Income::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
