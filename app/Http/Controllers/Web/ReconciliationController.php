<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
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

    public function index(Request $request): View
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $selectedBranchId = $request->query('branch_id');
        $selectedStatus = $request->query('status', 'all');

        $query = Shift::with(['user', 'closer', 'branch'])
            ->withCount([
                'orders as completed_orders_count' => fn ($q) => $q->where('status', 'completed'),
                'orders as voided_orders_count' => fn ($q) => $q->where('status', 'voided'),
            ])
            ->withSum([
                'orders as live_net_sales' => fn ($q) => $q->where('status', 'completed'),
            ], 'total_amount');

        if ($selectedBranchId) {
            $query->where('branch_id', (int)$selectedBranchId);
        }

        if ($selectedStatus && $selectedStatus !== 'all') {
            $query->where('status', $selectedStatus);
        } else {
            // Realtime monitoring: include open shifts alongside closed/auto_closed/reconciled
            $query->whereIn('status', ['open', 'closed', 'auto_closed', 'reconciled']);
        }

        $shifts = $query->latest('id')->paginate(15)->withQueryString();

        // Calculate live expected cash for open shifts
        foreach ($shifts as $s) {
            if ($s->status === 'open') {
                $s->expected_cash = $this->shiftService->calculateExpectedCash($s);
            }
        }

        // Active open shifts collection for realtime monitoring alert cards
        $activeShifts = Shift::with(['user', 'branch'])
            ->where('status', 'open')
            ->when($selectedBranchId, fn ($q) => $q->where('branch_id', (int)$selectedBranchId))
            ->get();

        foreach ($activeShifts as $as) {
            $as->live_expected_cash = $this->shiftService->calculateExpectedCash($as);
            $as->live_completed_tx = $as->orders()->where('status', 'completed')->count();
            $as->live_sales = (int)$as->orders()->where('status', 'completed')->sum('total_amount');
        }

        return view('backoffice.reconciliation.index', compact('shifts', 'branches', 'selectedBranchId', 'selectedStatus', 'activeShifts'));
    }

    public function report(Shift $shift): View
    {
        $report = $this->shiftService->generateCloseShiftReport($shift);

        return view('backoffice.reconciliation.report', compact('shift', 'report'));
    }

    public function reconcile(Request $request, Shift $shift): RedirectResponse
    {
        if ($shift->status === 'open') {
            return redirect()->back()->with('error', "Shift #{$shift->id} masih berjalan dan belum ditutup oleh kasir. Rekonsiliasi kas fisik hanya dapat disetujui setelah shift ditutup.");
        }

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
