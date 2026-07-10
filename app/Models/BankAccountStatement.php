<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccountStatement extends Model
{
    protected $fillable = [
        'school_id',
        'bank_account_id',
        'year',
        'month',
        'file_name',
        'file_path',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'file_size' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
