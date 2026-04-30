<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransfer extends Model
{
    protected $fillable = [
        'school_id',
        'consecutive',
        'transfer_date',
        'from_name',
        'from_document',
        'from_location',
        'to_name',
        'to_document',
        'to_location',
        'observations',
        'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryTransferItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForSchool($query, $schoolId)
    {
        if (!$schoolId) return $query;
        return $query->where('school_id', $schoolId);
    }
}
