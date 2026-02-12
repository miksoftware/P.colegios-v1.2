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
        'total_retentions',
        'net_payment',
        'observations',
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
        'compras'        => 'Compras',
        'servicios'      => 'Servicios',
        'honorarios'     => 'Honorarios',
        'arrendamiento'  => 'Arrendamiento',
    ];

    /**
     * Porcentajes de retención en la fuente según concepto y si declara renta.
     * [concepto => [no_declara, declara]]
     */
    const RETENTION_RATES = [
        'compras'        => [3.5, 2.5],
        'servicios'      => [6.0, 4.0],
        'honorarios'     => [11.0, 10.0],
        'arrendamiento'  => [3.5, 3.5],
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
        return self::RETENTION_CONCEPTS[$this->retention_concept] ?? $this->retention_concept ?? 'N/A';
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
     * Total pagado para un contrato.
     */
    public static function getTotalPaidForContract(int $contractId): float
    {
        return (float) static::where('contract_id', $contractId)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('total');
    }
}
