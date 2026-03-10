<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Income;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class IncomePdfController extends Controller
{
    /**
     * Generar PDF de un ingreso individual
     */
    public function single(Request $request, int $id)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('incomes.view'), 403);

        $income = Income::where('school_id', $schoolId)
            ->with([
                'fundingSource.budgetItem',
                'creator',
                'bankAccounts.bank',
                'bankAccounts.bankAccount',
            ])
            ->findOrFail($id);

        $school = School::findOrFail($schoolId);

        $pdf = Pdf::loadView('pdf.income-receipt', [
            'income' => $income,
            'school' => $school,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("ingreso-{$income->id}.pdf");
    }

    /**
     * Generar PDF con todos los ingresos de un presupuesto (rubro + fuente)
     */
    public function byBudget(Request $request, int $budgetId)
    {
        $schoolId = (int) session('selected_school_id');
        abort_if(!$schoolId, 403);
        abort_if(!auth()->user()->can('incomes.view'), 403);

        $budget = Budget::where('school_id', $schoolId)
            ->with(['budgetItem', 'fundingSource'])
            ->findOrFail($budgetId);

        $incomes = Income::where('school_id', $schoolId)
            ->where('funding_source_id', $budget->funding_source_id)
            ->with([
                'fundingSource.budgetItem',
                'creator',
                'bankAccounts.bank',
                'bankAccounts.bankAccount',
            ])
            ->orderBy('date', 'asc')
            ->get();

        $totalCollected = $incomes->sum('amount');

        $school = School::findOrFail($schoolId);

        $pdf = Pdf::loadView('pdf.income-report', [
            'budget' => $budget,
            'incomes' => $incomes,
            'totalCollected' => $totalCollected,
            'school' => $school,
            'user' => auth()->user(),
        ]);

        $pdf->setPaper('letter');

        return $pdf->stream("ingresos-rubro-{$budget->budgetItem->code}.pdf");
    }
}
