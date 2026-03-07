<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'convocatoria_id',
        'contract_number',
        'fiscal_year',
        'contracting_modality',
        'execution_place',
        'start_date',
        'end_date',
        'original_end_date',
        'extension_days',
        'extension_document_path',
        'extension_date',
        'duration_days',
        'object',
        'justification',
        'supplier_id',
        'supervisor_id',
        'subtotal',
        'iva',
        'total',
        'original_total',
        'addition_amount',
        'addition_document_path',
        'addition_date',
        'payment_method',
        'status',
        'annulment_reason',
        'annulment_date',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'original_end_date' => 'date',
        'extension_date' => 'datetime',
        'addition_date' => 'datetime',
        'annulment_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'original_total' => 'decimal:2',
        'addition_amount' => 'decimal:2',
    ];

    // ── Constants ─────────────────────────────────────────────
    const MODALITIES = [
        'directa'             => 'Contratación Directa',
        'minima_cuantia'      => 'Mínima Cuantía',
        'seleccion_abreviada' => 'Selección Abreviada',
        'concurso_meritos'    => 'Concurso de Méritos',
        'licitacion'          => 'Licitación Pública',
        'especial'            => 'Regimen Especial',
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
        'annulled'     => 'Anulado',
        'suspended'    => 'Suspendido',
    ];

    // ── Activity Log ─────────────────────────────────────────
    protected static function getActivityModule(): string
    {
        return 'contractual';
    }

    protected function getLogDescription(): string
    {
        return 'Contrato #' . $this->formatted_number;
    }

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

    public function paymentOrders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class);
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
            'annulled'     => 'red',
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

    /**
     * Máximo que se puede adicionar: 50% del valor inicial contratado menos lo ya adicionado.
     */
    public function getMaxAdditionAttribute(): float
    {
        $initialTotal = (float) ($this->original_total ?? $this->total);
        $alreadyAdded = (float) $this->addition_amount;
        return max(0, ($initialTotal * 0.5) - $alreadyAdded);
    }

    /**
     * Verifica si el contrato tiene órdenes de pago no anuladas.
     */
    public function hasPaymentOrders(): bool
    {
        return $this->paymentOrders()
            ->where('status', '!=', 'cancelled')
            ->exists();
    }
}
