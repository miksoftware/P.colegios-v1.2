<?php

namespace App\Console\Commands;

use App\Models\Cdp;
use App\Models\Contract;
use Illuminate\Console\Command;

class FixOrphanedCdps extends Command
{
    protected $signature = 'fix:orphaned-cdps {--dry-run : Solo mostrar lo que se haría sin ejecutar}';
    protected $description = 'Anula CDPs activos de convocatorias canceladas y contratos anulados';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('=== MODO DRY-RUN (no se harán cambios) ===');
        }

        $fixed = 0;

        // 1. CDPs activos en convocatorias canceladas
        $orphanedFromConv = Cdp::where('status', 'active')
            ->whereHas('convocatoria', fn($q) => $q->where('status', 'cancelled'))
            ->with('convocatoria')
            ->get();

        foreach ($orphanedFromConv as $cdp) {
            $this->line("CDP #{$cdp->formatted_number} (ID:{$cdp->id}) - Convocatoria #{$cdp->convocatoria->formatted_number} cancelada");
            if (!$dryRun) {
                $cdp->update(['status' => 'cancelled']);
            }
            $fixed++;
        }

        // 2. CDPs activos/used en convocatorias con contrato anulado
        $orphanedFromContract = Cdp::whereIn('status', ['active', 'used'])
            ->whereHas('convocatoria', function ($q) {
                $q->whereHas('contract', fn($cq) => $cq->where('status', 'annulled'));
            })
            ->with('convocatoria.contract')
            ->get();

        foreach ($orphanedFromContract as $cdp) {
            // Evitar duplicados con el paso anterior
            if ($orphanedFromConv->contains('id', $cdp->id)) continue;

            $contract = $cdp->convocatoria->contract;
            $this->line("CDP #{$cdp->formatted_number} (ID:{$cdp->id}) - Contrato #{$contract->formatted_number} anulado");
            if (!$dryRun) {
                $cdp->update(['status' => 'cancelled']);
                // También cancelar la convocatoria si sigue adjudicada
                if ($cdp->convocatoria->status === 'awarded') {
                    $cdp->convocatoria->update(['status' => 'cancelled']);
                    $this->line("  → Convocatoria #{$cdp->convocatoria->formatted_number} marcada como cancelada");
                }
            }
            $fixed++;
        }

        if ($fixed === 0) {
            $this->info('No se encontraron CDPs huérfanos. Todo está en orden.');
        } else {
            $action = $dryRun ? 'se corregirían' : 'corregidos';
            $this->info("{$fixed} CDPs {$action}.");
        }

        return self::SUCCESS;
    }
}
