<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    use LogsActivity;

    protected $fillable = [
        'bank_id',
        'account_number',
        'account_type',
        'holder_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const ACCOUNT_TYPES = [
        'ahorros' => 'Ahorros',
        'corriente' => 'Corriente',
    ];

    // ─── Relationships ──────────────────────────────────

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    // ─── Accessors ──────────────────────────────────────

    public function getAccountTypeNameAttribute(): string
    {
        return self::ACCOUNT_TYPES[$this->account_type] ?? $this->account_type;
    }

    public function getFormattedAccountAttribute(): string
    {
        return $this->account_type_name . ' - ' . $this->account_number;
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
