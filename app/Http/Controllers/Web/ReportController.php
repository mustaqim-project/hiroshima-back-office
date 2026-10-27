<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashBook;
use App\Services\ReportExcelService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @req RPT-FIN, RPT-01, RPT-02, RPT-03, RPT-04, RPT-05, RPT-06, RPT-07, RPT-08, RPT-09, RPT-10, RPT-11, RPT-12, RPT-17, RPT-21, RPT-22, RPT-23, RPT-24, FR-WEB-04, FSD-15
 */
class ReportController extends Controller
{
    public function __construct(
        protected ReportExcelService $reportExcelService
    ) {}

    public function index(Request $request): View|StreamedResponse
    {
        $availableReports = ReportExcelService::getAvailableReports();
        $reportType = $request->query('type', 'RPT-01');
        if (!isset($availableReports[$reportType])) {
            $reportType = 'RPT-01';
        }

        $branchId = $request->query('branch_id');
        $user = $request->user();
        if ($user && $user->branch_id && !in_array($user->role, ['owner', 'admin'])) {
            $branchId = (string) $user->branch_id;
        }

        $startDate = $request->query('start_date', now('Asia/Jakarta')->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now('Asia/Jakarta')->toDateString());

        // Handle inline query exports (?export=package or ?export=single)
        $exportMode = $request->query('export');
        if ($exportMode === 'package') {
            return $this->reportExcelService->exportFullPackage($startDate, $endDate, $branchId);
        }

        if (in_array($exportMode, ['single', 'excel', 'xlsx', 'csv'])) {
            return $this->reportExcelService->exportSingleReport($reportType, $startDate, $endDate, $branchId);
        }

        $branches = Branch::where('is_active', true)->get();
        $summary = $this->reportExcelService->getOverviewData($startDate, $endDate, $branchId);
        $activeReportData = $this->reportExcelService->getReportDataset($reportType, $startDate, $endDate, $branchId);

        // Cash category breakdown for RPT-FIN
        $cashBreakdown = [];
        if ($reportType === 'RPT-FIN') {
            $cashQuery = CashBook::query()
                ->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate);
            if ($branchId === 'global') {
                $cashQuery->whereNull('branch_id');
            } elseif ($branchId && $branchId !== 'all') {
                $cashQuery->where('branch_id', $branchId);
            }

            $cashBreakdown = $cashQuery
                ->selectRaw('type, category, SUM(amount) as total_amount, COUNT(*) as count')
                ->groupBy('type', 'category')
                ->orderBy('total_amount', 'desc')
                ->get();
        }

        return view('backoffice.reports.index', compact(
            'availableReports',
            'reportType',
            'branches',
            'startDate',
            'endDate',
            'branchId',
            'summary',
            'activeReportData',
            'cashBreakdown'
        ));
    }

    /**
     * Export active single report as 2-sheet formatted Excel workbook
     */
    public function exportSingle(Request $request): StreamedResponse
    {
        $availableReports = ReportExcelService::getAvailableReports();
        $reportType = $request->query('type', 'RPT-01');
        if (!isset($availableReports[$reportType])) {
            $reportType = 'RPT-01';
        }

        $branchId = $request->query('branch_id');
        $user = $request->user();
        if ($user && $user->branch_id && !in_array($user->role, ['owner', 'admin'])) {
            $branchId = (string) $user->branch_id;
        }

        $startDate = $request->query('start_date', now('Asia/Jakarta')->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now('Asia/Jakarta')->toDateString());

        return $this->reportExcelService->exportSingleReport($reportType, $startDate, $endDate, $branchId);
    }

    /**
     * Export all reports as a comprehensive multi-sheet Excel package
     */
    public function exportPackage(Request $request): StreamedResponse
    {
        $branchId = $request->query('branch_id');
        $user = $request->user();
        if ($user && $user->branch_id && !in_array($user->role, ['owner', 'admin'])) {
            $branchId = (string) $user->branch_id;
        }

        $startDate = $request->query('start_date', now('Asia/Jakarta')->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now('Asia/Jakarta')->toDateString());

        return $this->reportExcelService->exportFullPackage($startDate, $endDate, $branchId);
    }
}
