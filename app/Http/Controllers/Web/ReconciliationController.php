<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\AuditLogger;
use App\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FR-WEB-07, FSD-09, RPT-09
 */
class ReconciliationController extends Controller
{
    public function __construct(
        protected ShiftService $shiftService,
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $shifts = Shift::with(['user', 'branch'])
            ->whereIn('status', ['closed', 'auto_closed', 'reconciled'])
            ->latest('id')
            ->paginate(15);

        return view('backoffice.reconciliation.index', compact('shifts'));
    }

    public function report(Shift $shift): View
    {
        $report = $this->shiftService->generateCloseShiftReport($shift);

        return view('backoffice.reconciliation.report', compact('shift', 'report'));
    }

    public function reconcile(Request $request, Shift $shift): RedirectResponse
    {
        $request->validate([
            'notes' => 'required|string|min:3',
        ]);

        $this->shiftService->reconcile($shift, auth()->id() ?? 1, $request->notes);

        $this->auditLogger->record('shift.reconciled', $shift, ['status' => 'closed'], [
            'status' => 'reconciled',
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', "Shift #{$shift->id} berhasil direkonsiliasi.");
    }
}
