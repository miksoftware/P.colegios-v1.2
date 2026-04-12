<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PrecontractualPdfController extends Controller
{
    /**
     * Generar PDF de Estudios Previos de la Contratación
     */
    public function estudiosPrevios(Request $request, int $convocatoriaId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('precontractual.view'), 403);

        $convocatoria = Convocatoria::where('school_id', $schoolId)
            ->with([
                'school',
                'contract.supplier',
                'selectedProposal.supplier',
                'cdps.budgetItem',
                'cdps.fundingSources.fundingSource',
                'distributionDetails.expenseDistribution.expenseCode',
                'distributionDetails.expenseDistribution.budget.budgetItem',
                'distributionDetails.expenseDistribution.budget.fundingSource',
                'creator',
            ])
            ->findOrFail($convocatoriaId);

        $school = School::findOrFail($schoolId);

        // Datos del contrato (si existe)
        $contract = $convocatoria->contract;

        // Proveedor seleccionado
        $supplier = $contract?->supplier ?? $convocatoria->selectedProposal?->supplier;

        // Duración del contrato
        $durationDays = $contract?->duration_days ?? null;
        if (!$durationDays && $convocatoria->start_date && $convocatoria->end_date) {
            $durationDays = $convocatoria->start_date->diffInDays($convocatoria->end_date);
        }

        // Monto en letras
        $amount = (float) ($contract?->total ?? $convocatoria->assigned_budget);
        $amountInWords = self::amountToWords($amount);

        // CDPs activos
        $activeCdps = $convocatoria->cdps->where('status', '!=', 'cancelled');

        // Códigos de gasto desde las distribuciones de la convocatoria
        $expenseCodeRows = [];
        foreach ($convocatoria->distributionDetails as $dd) {
            $expenseCode = $dd->expenseDistribution?->expenseCode;
            if ($expenseCode) {
                $expenseCodeRows[] = [
                    'code' => $expenseCode->code ?? '',
                    'name' => $expenseCode->name ?? '',
                    'amount' => (float) $dd->amount,
                ];
            }
        }

        // Construir tabla de CDPs con códigos de gasto
        $cdpRows = [];
        foreach ($activeCdps as $cdp) {
            $cdpRows[] = [
                'cdp_number' => $cdp->formatted_number,
                'total_amount' => (float) $cdp->total_amount,
            ];
        }

        $pdf = Pdf::loadView('pdf.estudios-previos', [
            'convocatoria' => $convocatoria,
            'school' => $school,
            'contract' => $contract,
            'supplier' => $supplier,
            'durationDays' => $durationDays,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'cdpRows' => $cdpRows,
            'expenseCodeRows' => $expenseCodeRows,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("estudios-previos-conv-{$convocatoria->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Disponibilidad Presupuestal
     */
    public function disponibilidadPresupuestal(Request $request, int $convocatoriaId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('precontractual.view'), 403);

        $convocatoria = Convocatoria::where('school_id', $schoolId)
            ->with([
                'school',
                'cdps.budgetItem',
                'cdps.fundingSources.fundingSource',
                'cdps.fundingSources.budget',
                'distributionDetails.expenseDistribution.expenseCode',
                'distributionDetails.expenseDistribution.budget.budgetItem',
                'creator',
            ])
            ->findOrFail($convocatoriaId);

        $school = School::findOrFail($schoolId);

        // Filas de rubros: código de gasto + nombre + valor (del CDP/distribución)
        $rubroRows = [];
        foreach ($convocatoria->distributionDetails as $dd) {
            $expenseCode = $dd->expenseDistribution?->expenseCode;
            if ($expenseCode) {
                $rubroRows[] = [
                    'code' => $expenseCode->code ?? '',
                    'name' => $expenseCode->name ?? '',
                    'amount' => (float) $dd->amount,
                ];
            }
        }

        $totalAmount = collect($rubroRows)->sum('amount');
        $amountInWords = self::amountToWords($totalAmount);

        $pdf = Pdf::loadView('pdf.disponibilidad-presupuestal', [
            'convocatoria' => $convocatoria,
            'school' => $school,
            'rubroRows' => $rubroRows,
            'totalAmount' => $totalAmount,
            'amountInWords' => $amountInWords,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("disponibilidad-presupuestal-conv-{$convocatoria->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Requisición de Necesidades
     */
    public function requisicionNecesidades(Request $request, int $convocatoriaId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('precontractual.view'), 403);

        $convocatoria = Convocatoria::where('school_id', $schoolId)
            ->with(['school', 'creator'])
            ->findOrFail($convocatoriaId);

        $school = School::findOrFail($schoolId);

        $amount = (float) $convocatoria->assigned_budget;
        $amountInWords = self::amountToWords($amount);

        $pdf = Pdf::loadView('pdf.requisicion-necesidades', [
            'convocatoria' => $convocatoria,
            'school' => $school,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("requisicion-necesidades-conv-{$convocatoria->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Certificado de Bienes y Servicios Incorporados en el Plan de Compras
     */
    public function certificadoPlanCompras(Request $request, int $convocatoriaId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('precontractual.view'), 403);

        $convocatoria = Convocatoria::where('school_id', $schoolId)
            ->with([
                'school',
                'distributionDetails.expenseDistribution.expenseCode',
                'creator',
            ])
            ->findOrFail($convocatoriaId);

        $school = School::findOrFail($schoolId);

        // Filas: código de gasto + nombre + valor
        $rows = [];
        foreach ($convocatoria->distributionDetails as $dd) {
            $expenseCode = $dd->expenseDistribution?->expenseCode;
            if ($expenseCode) {
                $rows[] = [
                    'code' => $expenseCode->code ?? '',
                    'name' => $expenseCode->name ?? '',
                    'amount' => (float) $dd->amount,
                ];
            }
        }

        // Si no hay distribuciones, usar el objeto como fila única
        if (empty($rows)) {
            $rows[] = [
                'code' => '',
                'name' => $convocatoria->object,
                'amount' => (float) $convocatoria->assigned_budget,
            ];
        }

        $totalAmount = collect($rows)->sum('amount');

        $pdf = Pdf::loadView('pdf.certificado-plan-compras', [
            'convocatoria' => $convocatoria,
            'school' => $school,
            'rows' => $rows,
            'totalAmount' => $totalAmount,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("certificado-plan-compras-conv-{$convocatoria->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Convocatoria a Veedurías Ciudadanas
     */
    public function convocatoriaVeedurias(Request $request, int $convocatoriaId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('precontractual.view'), 403);

        $convocatoria = Convocatoria::where('school_id', $schoolId)
            ->with(['school', 'creator'])
            ->findOrFail($convocatoriaId);

        $school = School::findOrFail($schoolId);

        $amount = (float) $convocatoria->assigned_budget;
        $amountInWords = self::amountToWords($amount);

        $pdf = Pdf::loadView('pdf.convocatoria-veedurias', [
            'convocatoria' => $convocatoria,
            'school' => $school,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("convocatoria-veedurias-conv-{$convocatoria->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Invitación a Cotizar
     */
    public function invitacionCotizar(Request $request, int $convocatoriaId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('precontractual.view'), 403);

        $convocatoria = Convocatoria::where('school_id', $schoolId)
            ->with(['school', 'creator'])
            ->findOrFail($convocatoriaId);

        $school = School::findOrFail($schoolId);

        $amount = (float) $convocatoria->assigned_budget;
        $amountInWords = self::amountToWords($amount);

        $pdf = Pdf::loadView('pdf.invitacion-cotizar', [
            'convocatoria' => $convocatoria,
            'school' => $school,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("invitacion-cotizar-conv-{$convocatoria->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Acta de Evaluación
     */
    public function actaEvaluacion(Request $request, int $convocatoriaId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('precontractual.view'), 403);

        $convocatoria = Convocatoria::where('school_id', $schoolId)
            ->with([
                'school',
                'proposals.supplier',
                'selectedProposal.supplier',
                'cdps.budgetItem',
                'cdps.fundingSources.fundingSource',
                'distributionDetails.expenseDistribution.expenseCode',
                'creator',
            ])
            ->findOrFail($convocatoriaId);

        abort_if($convocatoria->proposals->isEmpty(), 404, 'No hay propuestas para evaluar.');

        $school = School::findOrFail($schoolId);
        $proposals = $convocatoria->proposals->sortByDesc('score');
        $selectedProposal = $convocatoria->selectedProposal;

        // CDPs activos con rubros
        $activeCdps = $convocatoria->cdps->where('status', '!=', 'cancelled');
        $cdpRows = [];
        foreach ($activeCdps as $cdp) {
            foreach ($cdp->fundingSources as $cdpFs) {
                $cdpRows[] = [
                    'cdp_number' => $cdp->formatted_number,
                    'funding_source_code' => $cdpFs->fundingSource?->code ?? '',
                    'funding_source_name' => $cdpFs->fundingSource?->name ?? '',
                    'amount' => (float) $cdpFs->amount,
                ];
            }
        }

        // Códigos de gasto
        $expenseCodeRows = [];
        foreach ($convocatoria->distributionDetails as $dd) {
            $ec = $dd->expenseDistribution?->expenseCode;
            if ($ec) {
                $expenseCodeRows[] = [
                    'code' => $ec->code ?? '',
                    'name' => $ec->name ?? '',
                ];
            }
        }

        $pdf = Pdf::loadView('pdf.acta-evaluacion', [
            'convocatoria' => $convocatoria,
            'school' => $school,
            'proposals' => $proposals,
            'selectedProposal' => $selectedProposal,
            'cdpRows' => $cdpRows,
            'expenseCodeRows' => $expenseCodeRows,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("acta-evaluacion-conv-{$convocatoria->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Aceptación de Propuesta
     */
    public function aceptacionPropuesta(Request $request, int $convocatoriaId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('precontractual.view'), 403);

        $convocatoria = Convocatoria::where('school_id', $schoolId)
            ->with([
                'school',
                'selectedProposal.supplier.municipality',
                'contract',
                'cdps.budgetItem',
                'cdps.fundingSources.fundingSource',
                'distributionDetails.expenseDistribution.expenseCode',
                'creator',
            ])
            ->findOrFail($convocatoriaId);

        $selectedProposal = $convocatoria->selectedProposal;
        abort_if(!$selectedProposal, 404, 'No hay propuesta ganadora.');

        $school = School::findOrFail($schoolId);
        $supplier = $selectedProposal->supplier;
        $contract = $convocatoria->contract;

        $amount = (float) $selectedProposal->total;
        $amountInWords = self::amountToWords($amount);

        // Duración
        $durationDays = $contract?->duration_days ?? null;
        if (!$durationDays && $convocatoria->start_date && $convocatoria->end_date) {
            $durationDays = $convocatoria->start_date->diffInDays($convocatoria->end_date);
        }

        // CDPs
        $activeCdps = $convocatoria->cdps->where('status', '!=', 'cancelled');
        $cdpNumbers = $activeCdps->map(fn($c) => $c->formatted_number)->implode(', ');

        // Códigos de gasto
        $expenseCodeRows = [];
        foreach ($convocatoria->distributionDetails as $dd) {
            $ec = $dd->expenseDistribution?->expenseCode;
            if ($ec) {
                $expenseCodeRows[] = $ec->code . ' - ' . $ec->name;
            }
        }

        $pdf = Pdf::loadView('pdf.aceptacion-propuesta', [
            'convocatoria' => $convocatoria,
            'school' => $school,
            'selectedProposal' => $selectedProposal,
            'supplier' => $supplier,
            'contract' => $contract,
            'amount' => $amount,
            'amountInWords' => $amountInWords,
            'durationDays' => $durationDays,
            'cdpNumbers' => $cdpNumbers,
            'expenseCodeRows' => $expenseCodeRows,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("aceptacion-propuesta-conv-{$convocatoria->formatted_number}.pdf");
    }

    /**
     * Generar PDF de Certificado de Disponibilidad Presupuestal (CDP formal)
     */
    public function certificadoDisponibilidad(Request $request, int $convocatoriaId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('precontractual.view'), 403);

        $convocatoria = Convocatoria::where('school_id', $schoolId)
            ->with([
                'school',
                'cdps.budgetItem',
                'cdps.fundingSources.fundingSource',
                'cdps.fundingSources.budget',
                'creator',
            ])
            ->findOrFail($convocatoriaId);

        $activeCdps = $convocatoria->cdps->where('status', '!=', 'cancelled');
        abort_if($activeCdps->isEmpty(), 404, 'No hay CDPs asignados.');

        $school = School::findOrFail($schoolId);

        // Construir filas: código rubro, nombre rubro, fuentes con montos, valor total
        $cdpRows = [];
        foreach ($activeCdps as $cdp) {
            $sources = [];
            foreach ($cdp->fundingSources as $cdpFs) {
                $sources[] = [
                    'name' => $cdpFs->fundingSource?->name ?? '',
                    'amount' => (float) $cdpFs->amount,
                ];
            }
            $cdpRows[] = [
                'cdp_number' => $cdp->formatted_number,
                'budget_item_code' => $cdp->budgetItem?->code ?? '',
                'budget_item_name' => $cdp->budgetItem?->name ?? '',
                'sources' => $sources,
                'total_amount' => (float) $cdp->total_amount,
            ];
        }

        $grandTotal = collect($cdpRows)->sum('total_amount');

        // Número del CDP principal (el primero)
        $cdpNumber = $activeCdps->first()->formatted_number ?? '';

        $pdf = Pdf::loadView('pdf.certificado-disponibilidad', [
            'convocatoria' => $convocatoria,
            'school' => $school,
            'cdpRows' => $cdpRows,
            'cdpNumber' => $cdpNumber,
            'grandTotal' => $grandTotal,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("certificado-disponibilidad-conv-{$convocatoria->formatted_number}.pdf");
    }

    /**
     * Convertir monto a palabras en español
     */
    public static function amountToWords(float $amount): string
    {
        $entero = (int) floor($amount);
        $decimales = (int) round(($amount - $entero) * 100);

        $palabras = self::numberToWords($entero);

        if ($decimales > 0) {
            return strtoupper($palabras) . ' PESOS CON ' . strtoupper(self::numberToWords($decimales)) . ' CENTAVOS M/CTE';
        }

        return strtoupper($palabras) . ' PESOS M/CTE';
    }

    private static function numberToWords(int $number): string
    {
        if ($number == 0) return 'cero';

        $unidades = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
        $decenas = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $especiales = [11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce', 15 => 'quince',
                       16 => 'dieciséis', 17 => 'diecisiete', 18 => 'dieciocho', 19 => 'diecinueve',
                       21 => 'veintiún', 22 => 'veintidós', 23 => 'veintitrés', 24 => 'veinticuatro',
                       25 => 'veinticinco', 26 => 'veintiséis', 27 => 'veintisiete', 28 => 'veintiocho', 29 => 'veintinueve'];
        $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos',
                     'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        if ($number < 0) return 'menos ' . self::numberToWords(abs($number));

        $result = '';

        if ($number >= 1000000) {
            $millones = (int) floor($number / 1000000);
            if ($millones == 1) {
                $result .= 'un millón ';
            } else {
                $result .= self::numberToWords($millones) . ' millones ';
            }
            $number %= 1000000;
        }

        if ($number >= 1000) {
            $miles = (int) floor($number / 1000);
            if ($miles == 1) {
                $result .= 'mil ';
            } else {
                $result .= self::numberToWords($miles) . ' mil ';
            }
            $number %= 1000;
        }

        if ($number >= 100) {
            if ($number == 100) {
                $result .= 'cien';
                return trim($result);
            }
            $result .= $centenas[(int) floor($number / 100)] . ' ';
            $number %= 100;
        }

        if ($number > 0) {
            if (isset($especiales[$number])) {
                $result .= $especiales[$number];
            } elseif ($number < 10) {
                $result .= $unidades[$number];
            } else {
                $d = (int) floor($number / 10);
                $u = $number % 10;
                $result .= $decenas[$d];
                if ($u > 0) {
                    $result .= ' y ' . $unidades[$u];
                }
            }
        }

        return trim($result);
    }
}
