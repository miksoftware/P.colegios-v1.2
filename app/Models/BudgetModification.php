<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetModification extends Model
{
    use LogsActivity;

    protected $fillable = [
        'budget_id',
        'modification_number',
        'type',
        'amount',
        'previous_amount',
        'new_amount',
        'reason',
        'document_number',
        'document_date',
        'created_by',
    ];

    protected $casts = [
        'modification_number' => 'integer',
        'amount' => 'decimal:2',
        'previous_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'document_date' => 'date',
    ];

    public const TYPES = [
        'addition' => 'Adición',
        'reduction' => 'Reducción',
    ];

    protected static function getActivityModule(): string
    {
        return 'budget_modifications';
    }

    protected function getLogDescription(): string
    {
        $typeName = self::TYPES[$this->type] ?? $this->type;
        return "Mod. #{$this->modification_number} - {$typeName}";
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type === 'addition' 
            ? 'bg-green-100 text-green-700' 
            : 'bg-orange-100 text-orange-700';
    }

    public function getFormattedNumberAttribute(): string
    {
        return str_pad($this->modification_number, 3, '0', STR_PAD_LEFT);
    }
}
