<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo_path',
        'nit',
        'dane_code',
        'municipality',
        'rector_name',
        'rector_document',
        'pagador_name',
        'address',
        'email',
        'phone',
        'website',
        'budget_agreement_number',
        'budget_approval_date',
        'current_validity',
        'contracting_manual_approval_number',
        'contracting_manual_approval_date',
        'dian_resolution_1',
        'dian_resolution_2',
        'dian_range_1',
        'dian_range_2',
        'dian_expiration_1',
        'dian_expiration_2',
    ];

    /**
     * Get the public URL for the school logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Get the absolute path for the school logo (for PDFs).
     */
    public function getLogoAbsolutePathAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return Storage::disk('public')->path($this->logo_path);
    }

    /**
     * The users that belong to the school.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Get the user with "Rector" role assigned to this school.
     */
    public function getRectorUserAttribute(): ?User
    {
        return $this->users()
            ->whereHas('roles', fn($q) => $q->whereRaw('LOWER(name) = ?', ['rector']))
            ->first();
    }

    /**
     * Get the rector display name: first from user with Rector role, then from rector_name field.
     */
    public function getRectorDisplayNameAttribute(): string
    {
        $rectorUser = $this->rector_user;

        if ($rectorUser) {
            return strtoupper(trim($rectorUser->name . ' ' . $rectorUser->surname));
        }

        return $this->rector_name ?? 'Rector(a)';
    }

    /**
     * Get the rector document: first from user with Rector role, then from rector_document field.
     */
    public function getRectorDisplayDocumentAttribute(): ?string
    {
        $rectorUser = $this->rector_user;

        if ($rectorUser) {
            return $rectorUser->identification_number;
        }

        return $this->rector_document;
    }

    /**
     * Get the pagador display name from the pagador_name field.
     */
    public function getPagadorDisplayNameAttribute(): string
    {
        return $this->pagador_name ?? 'Pagador(a)';
    }

    /**
     * Get the user with "Auxiliar" role assigned to this school.
     * Matches roles containing "auxiliar" (case-insensitive).
     */
    public function getAuxiliarUserAttribute(): ?User
    {
        return $this->users()
            ->whereHas('roles', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%auxiliar%']))
            ->first();
    }

    /**
     * Get the Auxiliar Administrativo display name.
     * Priority: user with Auxiliar role > pagador_name field.
     */
    public function getAuxiliarDisplayNameAttribute(): string
    {
        $auxiliarUser = $this->auxiliar_user;

        if ($auxiliarUser) {
            return strtoupper(trim($auxiliarUser->name . ' ' . $auxiliarUser->surname));
        }

        return $this->pagador_name ?? 'Auxiliar Administrativo';
    }

    /**
     * Get the user with "Ordenador del Gasto" role assigned to this school.
     * Matches roles containing "ordenador" (case-insensitive).
     * Falls back to Rector role, then to rector_name field.
     */
    public function getOrdenadorGastoUserAttribute(): ?User
    {
        $ordenador = $this->users()
            ->whereHas('roles', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%ordenador%']))
            ->first();

        if ($ordenador) {
            return $ordenador;
        }

        return $this->rector_user;
    }

    /**
     * Get the Ordenador del Gasto display name.
     */
    public function getOrdenadorGastoDisplayNameAttribute(): string
    {
        $user = $this->ordenador_gasto_user;

        if ($user) {
            return strtoupper(trim($user->name . ' ' . $user->surname));
        }

        return $this->rector_name ?? 'Ordenador(a) del Gasto';
    }

    /**
     * Get the Ordenador del Gasto document number.
     */
    public function getOrdenadorGastoDisplayDocumentAttribute(): ?string
    {
        $user = $this->ordenador_gasto_user;

        if ($user) {
            return $user->identification_number;
        }

        return $this->rector_document;
    }
}
