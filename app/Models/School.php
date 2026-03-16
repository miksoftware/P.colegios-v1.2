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
}
