<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBankAccount extends Model
{
    protected $fillable = [
        'supplier_id',
        'bank_name',
        'account_type',
        'account_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const ACCOUNT_TYPES = [
        'ahorros'   => 'Cuenta de Ahorros',
        'corriente' => 'Cuenta Corriente',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getAccountTypeNameAttribute(): string
    {
        return self::ACCOUNT_TYPES[$this->account_type] ?? $this->account_type;
    }

    public function getFormattedAttribute(): string
    {
        return $this->bank_name . ' - ' . $this->account_type_name . ' - ' . $this->account_number;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
