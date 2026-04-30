<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'inventory_accounting_account_id',
        'inventory_entry_id',
        'inventory_discharge_id',
        'name',
        'initial_value',
        'acquisition_date',
        'supplier_id',
        'state',
        'current_tag',
        'location',
        'funding_source',
        'inventory_type',
        'is_active',
    ];

    protected $casts = [
        'initial_value' => 'decimal:2',
        'acquisition_date' => 'date',
        'is_active' => 'boolean',
    ];

    public const STATES = [
        'bueno' => 'Bueno',
        'regular' => 'Regular',
        'malo' => 'Malo',
    ];

    public const INVENTORY_TYPES = [
        'devolutivo' => 'Devolutivo',
        'consumo' => 'Consumo',
    ];

    protected static function getActivityModule(): string
    {
        return 'inventories';
    }

    protected function getLogDescription(): string
    {
        return $this->name . ($this->current_tag ? ' (' . $this->current_tag . ')' : '');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(InventoryAccountingAccount::class, 'inventory_accounting_account_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(InventoryEntry::class, 'inventory_entry_id');
    }

    public function discharge(): BelongsTo
    {
        return $this->belongsTo(InventoryDischarge::class, 'inventory_discharge_id');
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('current_tag', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%");
        });
    }

    public function getStateNameAttribute(): string
    {
        return self::STATES[$this->state] ?? ucfirst($this->state);
    }

    // --- CÁLCULOS DE DEPRECIACIÓN ---

    public function getMonthsInUse(\Carbon\Carbon $endDate = null): int
    {
        $endDate = $endDate ?? now();
        if (!$this->acquisition_date || $this->acquisition_date->gt($endDate)) {
            return 0;
        }
        return $this->acquisition_date->diffInMonths($endDate);
    }

    public function getMonthlyDepreciationAttribute(): float
    {
        $years = $this->account->depreciation_years ?? 0;
        if ($years <= 0 || $this->initial_value <= 0) {
            return 0;
        }
        return $this->initial_value / ($years * 12);
    }

    public function getAccumulatedDepreciation(\Carbon\Carbon $endDate = null): float
    {
        $months = $this->getMonthsInUse($endDate);
        $accumulated = $this->monthly_depreciation * $months;
        return min($accumulated, $this->initial_value); // No puede depreciarse más de su valor
    }

    public function getNetBookValue(\Carbon\Carbon $endDate = null): float
    {
        return $this->initial_value - $this->getAccumulatedDepreciation($endDate);
    }

    // --- INFORMACIÓN DE BAJA ---

    public function getDischargeInfoAttribute(): ?string
    {
        if (!$this->discharge) {
            return null;
        }
        
        $res = $this->discharge->resolution_number ?? str_pad($this->discharge->consecutive, 4, '0', STR_PAD_LEFT);
        $date = $this->discharge->date ? $this->discharge->date->format('d/m/Y') : '';
        return trim("{$res} {$date}");
    }
}
