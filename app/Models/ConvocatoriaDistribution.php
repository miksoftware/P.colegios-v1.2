<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvocatoriaDistribution extends Model
{
    protected $fillable = [
        'convocatoria_id',
        'expense_distribution_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function convocatoria(): BelongsTo
    {
        return $this->belongsTo(Convocatoria::class);
    }

    public function expenseDistribution(): BelongsTo
    {
        return $this->belongsTo(ExpenseDistribution::class);
    }
}
