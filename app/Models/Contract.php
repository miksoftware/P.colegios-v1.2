<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $fillable = [
        'school_id',
        'convocatoria_id',
        'contract_number',
        'fiscal_year',
        'contracting_modality',
        'execution_place',
        'start_date',
        'end_date',
        'duration_days',
        'object',
        'justification',
        'supplier_id',
        'supervisor_id',
        'subtotal',
        'iva',
        'total',
        'payment_method',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // ── Constants ─────────────────────────────────────────────
    const MODALITIES = [
        'directa'             => 'Contratación Directa',
        'minima_cuantia'      => 'Mínima Cuantía',
        'seleccion_abreviada' => 'Selección Abreviada',
        'concurso_meritos'    => 'Concurso de Méritos',
        'licitacion'          => 'Licitación Pública',
    ];

    const PAYMENT_METHODS = [
        'single'  => 'Pago único al finalizar el contrato a satisfacción',
        'partial' => 'Pagos parciales según porcentaje de ejecución',
    ];

    const STATUSES = [
        'draft'        => 'Borrador',
        'active'       => 'Activo',
        'in_execution' => 'En Ejecución',
        'completed'    => 'Finalizado',
        'terminated'   => 'Terminado',
        'suspended'    => 'Suspendido',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function convocatoria(): BelongsTo
    {
        return $this->belongsTo(Convocatoria::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rps(): HasMany
    {
        return $this->hasMany(ContractRp::class);
    }

    // ── Accessors ─────────────────────────────────────────────
    public function getFormattedNumberAttribute(): string
    {
        return str_pad($this->contract_number, 4, '0', STR_PAD_LEFT);
    }

    public function getModalityNameAttribute(): string
    {
        return self::MODALITIES[$this->contracting_modality] ?? $this->contracting_modality;
    }

    public function getPaymentMethodNameAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft'        => 'gray',
            'active'       => 'blue',
            'in_execution' => 'yellow',
            'completed'    => 'green',
            'terminated'   => 'red',
            'suspended'    => 'orange',
            default        => 'gray',
        };
    }

    // ── Scopes ────────────────────────────────────────────────
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('contract_number', 'like', "%{$search}%")
              ->orWhere('object', 'like', "%{$search}%")
              ->orWhere('execution_place', 'like', "%{$search}%")
              ->orWhereHas('supplier', function ($sq) use ($search) {
                  $sq->where('first_name', 'like', "%{$search}%")
                     ->orWhere('first_surname', 'like', "%{$search}%")
                     ->orWhere('document_number', 'like', "%{$search}%");
              });
        });
    }

    // ── Static helpers ────────────────────────────────────────
    public static function getNextContractNumber(int $schoolId, int $year): int
    {
        $max = static::where('school_id', $schoolId)
            ->where('fiscal_year', $year)
            ->max('contract_number');

        return ($max ?? 0) + 1;
    }
}
