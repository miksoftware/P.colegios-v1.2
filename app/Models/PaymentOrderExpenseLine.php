<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrderExpenseLine extends Model
{
    protected $fillable = [
        'payment_order_id',
        'expense_distribution_id',
        'expense_code_id',
        'subtotal',
        'iva',
        'total',
        'retention_concept',
        'supplier_declares_rent',
        'retention_percentage',
        'retefuente',
        'reteiva',
        'estampilla_produlto_mayor',
        'estampilla_procultura',
        'retencion_ica',
        'total_retentions',
        'net_payment',
    ];

    protected $casts = [
        'supplier_declares_rent' => 'boolean',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'retention_percentage' => 'decimal:2',
        'retefuente' => 'decimal:2',
        'reteiva' => 'decimal:2',
        'estampilla_produlto_mayor' => 'decimal:2',
        'estampilla_procultura' => 'decimal:2',
        'retencion_ica' => 'decimal:2',
        'total_retentions' => 'decimal:2',
        'net_payment' => 'decimal:2',
    ];

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }

    public function expenseDistribution(): BelongsTo
    {
        return $this->belongsTo(ExpenseDistribution::class);
    }

    public function expenseCode(): BelongsTo
    {
        return $this->belongsTo(ExpenseCode::class);
    }
}
