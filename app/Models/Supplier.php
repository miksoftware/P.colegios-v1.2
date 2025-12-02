<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'document_type',
        'document_number',
        'dv',
        'first_name',
        'second_name',
        'first_surname',
        'second_surname',
        'person_type',
        'tax_regime',
        'address',
        'city',
        'department',
        'phone',
        'mobile',
        'email',
        'bank_name',
        'account_type',
        'account_number',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Tipos de documento disponibles
     */
    public const DOCUMENT_TYPES = [
        'CC' => 'Cédula de Ciudadanía',
        'CE' => 'Cédula de Extranjería',
        'NIT' => 'NIT',
        'TI' => 'Tarjeta de Identidad',
        'PA' => 'Pasaporte',
        'RC' => 'Registro Civil',
        'NUIP' => 'NUIP',
    ];

    /**
     * Tipos de persona
     */
    public const PERSON_TYPES = [
        'natural' => 'Persona Natural',
        'juridica' => 'Persona Jurídica',
    ];

    /**
     * Regímenes tributarios
     */
    public const TAX_REGIMES = [
        'simplificado' => 'Régimen Simplificado',
        'comun' => 'Régimen Común',
        'gran_contribuyente' => 'Gran Contribuyente',
        'no_responsable' => 'No Responsable de IVA',
    ];

    /**
     * Tipos de cuenta bancaria
     */
    public const ACCOUNT_TYPES = [
        'ahorros' => 'Cuenta de Ahorros',
        'corriente' => 'Cuenta Corriente',
    ];

    /**
     * Módulo para el log de actividad
     */
    protected static function getActivityModule(): string
    {
        return 'suppliers';
    }

    /**
     * Descripción para el log
     */
    protected function getLogDescription(): string
    {
        return $this->full_name;
    }

    /**
     * Colegio al que pertenece
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Obtener nombre completo o razón social
     */
    public function getFullNameAttribute(): string
    {
        if ($this->person_type === 'juridica' || $this->document_type === 'NIT') {
            return $this->first_surname;
        }

        $parts = array_filter([
            $this->first_name,
            $this->second_name,
            $this->first_surname,
            $this->second_surname,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Obtener documento completo con DV si aplica
     */
    public function getFullDocumentAttribute(): string
    {
        $doc = "{$this->document_type} {$this->document_number}";
        
        if ($this->document_type === 'NIT' && $this->dv) {
            $doc .= "-{$this->dv}";
        }

        return $doc;
    }

    /**
     * Obtener nombre del tipo de documento
     */
    public function getDocumentTypeNameAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }

    /**
     * Obtener nombre del tipo de persona
     */
    public function getPersonTypeNameAttribute(): string
    {
        return self::PERSON_TYPES[$this->person_type] ?? $this->person_type;
    }

    /**
     * Obtener nombre del régimen tributario
     */
    public function getTaxRegimeNameAttribute(): string
    {
        return self::TAX_REGIMES[$this->tax_regime] ?? $this->tax_regime;
    }

    /**
     * Verificar si requiere DV
     */
    public function requiresDv(): bool
    {
        return $this->document_type === 'NIT';
    }

    /**
     * Scope para filtrar por colegio
     */
    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope para proveedores activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para buscar por nombre o documento
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('document_number', 'like', "%{$search}%")
              ->orWhere('first_surname', 'like', "%{$search}%")
              ->orWhere('first_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Calcular dígito de verificación para NIT
     */
    public static function calculateDv(string $nit): string
    {
        $vpri = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        $nit = str_pad($nit, 15, '0', STR_PAD_LEFT);
        $sum = 0;

        for ($i = 0; $i < 15; $i++) {
            $sum += intval($nit[$i]) * $vpri[$i];
        }

        $residue = $sum % 11;

        if ($residue > 1) {
            return (string)(11 - $residue);
        }

        return (string)$residue;
    }
}
