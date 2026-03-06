<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrder extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'contract_id',
        'payment_number',
        'fiscal_year',
        'invoice_number',
        'invoice_date',
        'payment_date',
        'is_full_payment',
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
        'other_taxes_total',
        'total_retentions',
        'net_payment',
        'observations',
        'supplier_bank_name',
        'supplier_account_type',
        'supplier_account_number',
        'status',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'payment_date' => 'date',
        'is_full_payment' => 'boolean',
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
        'other_taxes_total' => 'decimal:2',
        'total_retentions' => 'decimal:2',
        'net_payment' => 'decimal:2',
    ];

    // ── Constants ─────────────────────────────────────────────

    const STATUSES = [
        'draft'     => 'Borrador',
        'approved'  => 'Aprobada',
        'paid'      => 'Pagada',
        'cancelled' => 'Anulada',
    ];

    const STATUS_COLORS = [
        'draft'     => 'bg-gray-100 text-gray-700',
        'approved'  => 'bg-blue-100 text-blue-700',
        'paid'      => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];

    const RETENTION_CONCEPTS = [
        'compras'                    => 'Compras',
        'servicios'                  => 'Servicios',
        'honorarios'                 => 'Honorarios',
        'arrendamiento_sitios_web'   => 'Arrendamientos Sitios Web',
        'arrendamiento_inmuebles'    => 'Arrendamientos Bienes Inmuebles',
        'transporte_pasajeros'       => 'Servicio de Transporte de Pasajeros',
    ];

    /**
     * Porcentajes de retención en la fuente según concepto.
     * [concepto => [no_declara, declara]]
     */
    const RETENTION_RATES = [
        'compras'                    => [3.5, 2.5],
        'servicios'                  => [6.0, 4.0],
        'honorarios'                 => [10.0, 11.0],
        'arrendamiento_sitios_web'   => [11.0, 3.5],
        'arrendamiento_inmuebles'    => [3.5, 3.5],
        'transporte_pasajeros'       => [3.5, 3.5],
    ];

    /**
     * Base mínima (subtotal) a partir de la cual se aplica retención en la fuente.
     */
    const RETENTION_MIN_BASE = [
        'compras'                    => 524000,
        'servicios'                  => 105000,
        'honorarios'                 => 1,
        'arrendamiento_sitios_web'   => 1,
        'arrendamiento_inmuebles'    => 524000,
        'transporte_pasajeros'       => 524000,
    ];

    /**
     * Códigos contables para cada tipo de retención.
     */
    const ACCOUNTING_CODES = [
        'retefuente_servicios'       => '243605 - Retenciones de Servicios y Arrendamientos',
        'retefuente_compras'         => '243608 - Retención de Compras',
        'retefuente_honorarios'      => '243603 - Retención de Honorarios',
        'reteiva'                    => '243625 - ReteIVA',
        'estampilla_procultura'      => '24072202 - Estampilla Procultura',
        'estampilla_produlto_mayor'  => '24072204 - Estampilla Produlto Mayor',
        'retencion_ica'              => '24072209 - ReteICA',
    ];

    // ── Activity Log ──────────────────────────────────────────

    protected static function getActivityModule(): string
    {
        return 'postcontractual';
    }

    protected function getLogDescription(): string
    {
        return 'Orden de Pago #' . str_pad($this->payment_number, 4, '0', STR_PAD_LEFT);
    }

    // ── Relationships ─────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Accessors ─────────────────────────────────────────────

    public function getFormattedNumberAttribute(): string
    {
        return str_pad($this->payment_number, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-gray-100 text-gray-700';
    }

    public function getRetentionConceptNameAttribute(): string
    {
        return self::RETENTION_CONCEPTS[$this->retention_concept] ?? $this->retention_concept ?? 'Sin retención';
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('payment_number', 'like', "%{$search}%")
              ->orWhere('invoice_number', 'like', "%{$search}%")
              ->orWhereHas('contract', function ($sq) use ($search) {
                  $sq->where('contract_number', 'like', "%{$search}%")
                     ->orWhere('object', 'like', "%{$search}%");
              });
        });
    }

    // ── Static helpers ────────────────────────────────────────

    public static function getNextPaymentNumber(int $schoolId, int $year): int
    {
        $max = static::where('school_id', $schoolId)
            ->where('fiscal_year', $year)
            ->max('payment_number');

        return ($max ?? 0) + 1;
    }

    /**
     * Calcula el porcentaje de retención según concepto y si declara renta.
     */
    public static function getRetentionRate(string $concept, bool $declaresRent): float
    {
        $rates = self::RETENTION_RATES[$concept] ?? [0, 0];
        return $declaresRent ? $rates[1] : $rates[0];
    }

    /**
     * Verifica si el subtotal supera la base mínima para aplicar retención.
     */
    public static function meetsRetentionThreshold(string $concept, float $subtotal): bool
    {
        $minBase = self::RETENTION_MIN_BASE[$concept] ?? 0;
        return $subtotal >= $minBase;
    }

    /**
     * Total pagado para un contrato (no anuladas).
     * Filtra opcionalmente por school_id para aislamiento multi-tenant.
     */
    public static function getTotalPaidForContract(int $contractId, ?int $schoolId = null): float
    {
        $query = static::where('contract_id', $contractId)
            ->whereIn('status', ['draft', 'approved', 'paid']);
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        return (float) $query->sum('total');
    }
}
