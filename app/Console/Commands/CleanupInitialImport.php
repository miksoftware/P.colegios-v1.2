<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryEntry;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\InventoryAccountingAccount;
use Carbon\Carbon;

class CleanupInitialImport extends Command
{
    protected $signature = 'app:cleanup-import {--school= : School ID to scope cleanup (optional)}';
    protected $description = 'Clean up garbage from the initial import';

    public function handle()
    {
        $this->info("Starting cleanup...");

        // ── 1. Eliminar cuentas contables con código inválido (no numérico/puntual) ──
        // Estas fueron creadas cuando el import usaba la descripción del artículo como código.
        // Un código válido luce como: 1655, 165504, 1.6.55.04, etc.
        $invalidAccounts = InventoryAccountingAccount::where('name', 'like', 'Cuenta Autogenerada %')
            ->get()
            ->filter(fn($a) => !preg_match('/^\d[\d\.]+\d$/', $a->code));

        $invalidIds = $invalidAccounts->pluck('id');

        if ($invalidIds->isNotEmpty()) {
            // Eliminar los artículos vinculados a esas cuentas inválidas
            $itemsDeleted = InventoryItem::whereIn('inventory_accounting_account_id', $invalidIds)->delete();
            $this->info("Deleted {$itemsDeleted} inventory items with invalid account codes.");

            // Eliminar las cuentas inválidas
            $accountsDeleted = InventoryAccountingAccount::whereIn('id', $invalidIds)->delete();
            $this->info("Deleted {$accountsDeleted} invalid accounting accounts (descriptions used as codes).");
        } else {
            $this->info("No invalid accounting accounts found.");
        }

        // ── 2. Eliminar proveedores autogenerados sin artículos asociados ──
        $autoSupplierIds = Supplier::where('document_number', 'like', '999%')->pluck('id');
        if ($autoSupplierIds->isNotEmpty()) {
            $usedSupplierIds = InventoryItem::whereIn('supplier_id', $autoSupplierIds)->pluck('supplier_id')->unique();
            $orphanIds = $autoSupplierIds->diff($usedSupplierIds);
            $orphanSuppliers = Supplier::whereIn('id', $orphanIds)->delete();
            $this->info("Deleted {$orphanSuppliers} orphan auto-generated suppliers.");
        } else {
            $this->info("No auto-generated suppliers found.");
        }

        // ── 3. Limpiar cuentas autogeneradas cuyo código SÍ es válido pero no tienen artículos ──
        $validAutoIds = InventoryAccountingAccount::where('name', 'like', 'Cuenta Autogenerada %')->pluck('id');
        if ($validAutoIds->isNotEmpty()) {
            $usedAccountIds = InventoryItem::whereIn('inventory_accounting_account_id', $validAutoIds)->pluck('inventory_accounting_account_id')->unique();
            $unusedIds = $validAutoIds->diff($usedAccountIds);
            $emptyAutoAccounts = InventoryAccountingAccount::whereIn('id', $unusedIds)->delete();
            $this->info("Deleted {$emptyAutoAccounts} empty valid auto-generated accounts.");
        } else {
            $this->info("No empty auto-generated accounts found.");
        }

        $this->info("Cleanup finished.");
    }
}
