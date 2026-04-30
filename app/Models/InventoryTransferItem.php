<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransferItem extends Model
{
    protected $fillable = [
        'inventory_transfer_id',
        'inventory_item_id',
        'old_location',
        'new_location',
        'old_account_id',
        'new_account_id',
    ];

    public function transfer()
    {
        return $this->belongsTo(InventoryTransfer::class, 'inventory_transfer_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function oldAccount()
    {
        return $this->belongsTo(InventoryAccountingAccount::class, 'old_account_id');
    }

    public function newAccount()
    {
        return $this->belongsTo(InventoryAccountingAccount::class, 'new_account_id');
    }
}
