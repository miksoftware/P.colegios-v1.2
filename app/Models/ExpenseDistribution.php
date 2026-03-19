<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseDistribution extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'budget_id',
        'expense_code_id',
        'amount',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function getActivityModule(): string
    {
        return 'expenses';
    }

    protected function getLogDescription(): string
    {
        return $this->expenseCode?->name ?? 'Distribución #' . $this->id;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function expenseCode(): BelongsTo
    {
        return $this->belongsTo(ExpenseCode::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ExpenseExecution::class);
    }

    /**
     * Convocatorias generadas desde esta distribución
     */
    public function convocatorias(): HasMany
    {
        return $this->hasMany(Convocatoria::class);
    }
    /**
     * Distribuciones de convocatoria que usan esta distribución de gasto
     */
    public function convocatoriaDistributions(): HasMany
    {
        return $this->hasMany(ConvocatoriaDistribution::class);
    }

    /**
     * Líneas de órdenes de pago que usan esta distribución de gasto
     */
    public function paymentOrderLines(): HasMany
    {
        return $this->hasMany(PaymentOrderExpenseLine::class);
    }

    // Monto total ejecutado (ejecuciones directas legacy)
    public function getTotalExecutedAttribute(): float
    {
        return (float) $this->executions()->sum('amount');
    }

    /**
     * Monto total pagado a través de órdenes de pago (no anuladas).
     */
    public function getTotalPaidAttribute(): float
    {
        if ($this->relationLoaded('paymentOrderLines')) {
            return (float) $this->paymentOrderLines
                ->filter(fn($line) => $line->paymentOrder && in_array($line->paymentOrder->status, ['draft', 'approved', 'paid']))
                ->sum('total');
        }

        return (float) $this->paymentOrderLines()
            ->whereHas('paymentOrder', fn($q) => $q->whereIn('status', ['draft', 'approved', 'paid']))
            ->sum('total');
    }

    // Monto comprometido en convocatorias no canceladas
    public function getTotalCommittedAttribute(): float
    {
        if ($this->relationLoaded('convocatoriaDistributions')) {
            return (float) $this->convocatoriaDistributions
                ->filter(fn($cd) => $cd->convocatoria && $cd->convocatoria->status !== 'cancelled')
                ->sum('amount');
        }

        return (float) $this->convocatoriaDistributions()
            ->whereHas('convocatoria', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->sum('amount');
    }

    /**
     * Monto realmente bloqueado por convocatorias.
     * Para cada convocatoria:
     * - Si la convocatoria tiene contrato con pagos para esta distribución → bloqueo = lo pagado
     * - Si la convocatoria tiene contrato completado/pagado sin usar esta distribución → bloqueo = 0 (liberado)
     * - Si la convocatoria tiene contrato activo sin pagos de esta distribución → bloqueo = compromiso original
     * - Si la convocatoria NO tiene contrato → bloqueo = compromiso original (aún reservado)
     */
    public function getTotalLockedAttribute(): float
    {
        $locked = 0;

        // Usar relaciones cargadas si están disponibles
        $convDistributions = $this->relationLoaded('convocatoriaDistributions')
            ? $this->convocatoriaDistributions
            : $this->convocatoriaDistributions()->with('convocatoria.contract.paymentOrders')->get();

        $paymentLines = $this->relationLoaded('paymentOrderLines')
            ? $this->paymentOrderLines
            : $this->paymentOrderLines()->with('paymentOrder.contract')->get();

        // Filtrar líneas de pago válidas (no anuladas)
        $validPaymentLines = $paymentLines->filter(
            fn($line) => $line->paymentOrder && in_array($line->paymentOrder->status, ['draft', 'approved', 'paid'])
        );

        foreach ($convDistributions as $cd) {
            // Ignorar convocatorias canceladas
            if ($cd->convocatoria && $cd->convocatoria->status === 'cancelled') {
                continue;
            }

            // Buscar pagos de esta distribución vinculados a esta convocatoria
            $paidForThis = $validPaymentLines
                ->filter(fn($line) => $line->paymentOrder->contract && $line->paymentOrder->contract->convocatoria_id == $cd->convocatoria_id)
                ->sum('total');

            if ($paidForThis > 0) {
                // Convocatoria tiene pagos reales de esta distribución → bloqueo = lo pagado
                $locked += (float) $paidForThis;
            } else {
                // Sin pagos de esta distribución
                // Verificar si la convocatoria tiene contrato con órdenes de pago (usando otras distribuciones)
                $contract = $cd->convocatoria->contract;
                
                if ($contract) {
                    // Hay contrato: verificar si tiene alguna orden de pago no anulada
                    $hasAnyPayments = $contract->relationLoaded('paymentOrders')
                        ? $contract->paymentOrders->whereIn('status', ['draft', 'approved', 'paid'])->isNotEmpty()
                        : $contract->paymentOrders()->whereIn('status', ['draft', 'approved', 'paid'])->exists();
                    
                    if ($hasAnyPayments) {
                        // El contrato tiene pagos pero NO usó esta distribución → liberado
                        $locked += 0;
                    } else {
                        // Contrato sin pagos aún → compromiso original
                        $locked += (float) $cd->amount;
                    }
                } else {
                    // Sin contrato → compromiso original (aún en proceso precontractual)
                    $locked += (float) $cd->amount;
                }
            }
        }

        return $locked;
    }

    /**
     * Saldo disponible para nuevas convocatorias.
     * = monto distribuido - ejecutado directo - monto realmente bloqueado
     * Cuando una convocatoria tiene pagos, solo se bloquea lo pagado (no el compromiso original),
     * liberando la diferencia para nuevas convocatorias.
     */
    public function getAvailableBalanceAttribute(): float
    {
        return (float) $this->amount - $this->total_executed - $this->total_locked;
    }

    // Porcentaje ejecutado
    public function getExecutionPercentageAttribute(): float
    {
        if ($this->amount <= 0) {
            return 0;
        }
        return round(($this->total_executed / $this->amount) * 100, 2);
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForBudget($query, int $budgetId)
    {
        return $query->where('budget_id', $budgetId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
