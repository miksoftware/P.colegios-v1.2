<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrderTaxLine extends Model
{
    protected $fillable = [
        'payment_order_id',
        'tax_type',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }

    /**
     * Nombre legible del tipo de impuesto.
     */
    public function getTaxTypeNameAttribute(): string
    {
        return PaymentOrder::ACCOUNTING_CODES[$this->tax_type] ?? $this->tax_type;
    }
}
