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
    protected $signature = 'app:cleanup-import';
    protected $description = 'Clean up garbage from the initial import';

    public function handle()
    {
        $this->info("Starting cleanup...");
        
        // Find the generic entry
        $entry = InventoryEntry::where('observations', 'like', '%Carga Inicial de Inventario por Excel%')->first();
        
        if ($entry) {
            $this->info("Found entry: " . $entry->id);
            $count = InventoryItem::where('inventory_entry_id', $entry->id)->count();
            $this->info("Deleting $count items associated with this entry.");
            
            // Disable foreign key checks temporarily if needed, but standard Eloquent delete is fine if cascading is not set
            // Let's delete items first
            InventoryItem::where('inventory_entry_id', $entry->id)->delete();
            
            // Delete the entry
            $entry->delete();
            $this->info("Deleted the InventoryEntry.");
        } else {
            $this->info("No generic entry found.");
        }

        // Delete suppliers created today with person_type = juridica and document_type = NIT that are clearly auto-generated
        // For safety, we delete any supplier with document_number starting with 999 and created today
        $suppliersCount = Supplier::where('document_number', 'like', '999%')
            ->whereDate('created_at', Carbon::today())
            ->delete();
        $this->info("Deleted $suppliersCount auto-generated suppliers.");

        // Delete auto-generated accounts created today
        $accountsCount = InventoryAccountingAccount::where('name', 'like', 'Cuenta Autogenerada %')
            ->whereDate('created_at', Carbon::today())
            ->delete();
        $this->info("Deleted $accountsCount auto-generated accounts.");

        // Delete any items that have very weird names (like from the instructions sheet) just in case they slipped through
        $weirdCount = InventoryItem::where('name', 'like', '%INSTRUCTIVO FORMATO%')->delete();
        $this->info("Deleted $weirdCount weird items.");

        $this->info("Cleanup finished.");
    }
}
