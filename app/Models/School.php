<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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
     * The users that belong to the school.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
