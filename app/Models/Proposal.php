<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    use LogsActivity;

    protected $fillable = [
        'convocatoria_id',
        'supplier_id',
        'proposal_number',
        'subtotal',
        'iva',
        'total',
        'score',
        'is_selected',
    ];

    protected $casts = [
        'proposal_number' => 'integer',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'score' => 'decimal:2',
        'is_selected' => 'boolean',
    ];

    protected static function getActivityModule(): string
    {
        return 'precontractual';
    }

    protected function getLogDescription(): string
    {
        return 'Propuesta #' . $this->proposal_number . ' - ' . ($this->supplier?->full_name ?? 'Sin proveedor');
    }

    // Relaciones

    public function convocatoria(): BelongsTo
    {
        return $this->belongsTo(Convocatoria::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // Accessors

    public function getSupplierDocumentAttribute(): string
    {
        return $this->supplier?->full_document ?? '';
    }

    public function getSupplierAddressAttribute(): string
    {
        return $this->supplier?->address ?? '';
    }

    public function getSupplierPhoneAttribute(): string
    {
        return $this->supplier?->phone ?? $this->supplier?->mobile ?? '';
    }
}
