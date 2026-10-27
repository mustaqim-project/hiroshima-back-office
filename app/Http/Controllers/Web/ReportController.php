<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashBook;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * @req RPT-01, RPT-02, RPT-03, RPT-04, RPT-05, RPT-06, RPT-07, RPT-08, RPT-09, RPT-10, RPT-11, RPT-12, RPT-13, RPT-14, RPT-15, RPT-16, RPT-17, RPT-18, RPT-19, RPT-20, RPT-21, RPT-22, RPT-23, FR-WEB-04, FSD-15
 */
class ReportController extends Controller
{
    public function index(Request $request): View|Response
    {
        $reportType = $request->query('type', 'RPT-01');
        $branchId = $request->query('branch_id');
        $startDate = $request->query('start_date', now('Asia/Jakarta')->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now('Asia/Jakarta')->toDateString());

        $branches = Branch::where('is_active', true)->get();

        // 1. Cash Book Data
        $cashQuery = CashBook::with(['branch', 'creator'])
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        if ($branchId === 'global') {
            $cashQuery->whereNull('branch_id');
        } elseif ($branchId && $branchId !== 'all') {
            $cashQuery->where('branch_id', $branchId);
        }

        $cashSummary = [
            'total_in' => (clone $cashQuery)->where('type', 'in')->sum('amount'),
            'total_out' => (clone $cashQuery)->where('type', 'out')->sum('amount'),
        ];
        $cashSummary['net_cash'] = $cashSummary['total_in'] - $cashSummary['total_out'];

        $cashBreakdown = (clone $cashQuery)
            ->selectRaw('type, category, SUM(amount) as total_amount, COUNT(*) as count')
            ->groupBy('type', 'category')
            ->orderBy('total_amount', 'desc')
            ->get();

        $cashBookEntries = (clone $cashQuery)->orderBy('transaction_date', 'desc')->paginate(25);

        // Handle CSV Export for Cash Book
        if ($request->query('export') === 'csv' && $reportType === 'RPT-FIN') {
            return $this->exportCashBookCsv($cashQuery->get(), $startDate, $endDate);
        }

        // 2. POS Orders Data
        $query = Order::query()
            ->whereBetween('business_date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $summary = [
            'total_sales' => (clone $query)->where('status', 'completed')->sum('total_amount'),
            'total_orders' => (clone $query)->where('status', 'completed')->count(),
            'total_tax' => (clone $query)->where('status', 'completed')->sum('tax_amount'),
            'total_discount' => (clone $query)->where('status', 'completed')->sum('manual_discount_amount'),
            'total_void' => (clone $query)->where('status', 'voided')->count(),
            'total_void_amount' => (clone $query)->where('status', 'voided')->sum('total_amount'),
            'total_cash_in' => $cashSummary['total_in'],
            'total_cash_out' => $cashSummary['total_out'],
            'net_profit_estimate' => (clone $query)->where('status', 'completed')->sum('total_amount') - $cashSummary['total_out'],
        ];

        $orders = (clone $query)->with(['items', 'orderType', 'branch'])->latest('id')->paginate(20);

        return view('backoffice.reports.index', compact(
            'reportType',
            'branches',
            'startDate',
            'endDate',
            'summary',
            'orders',
            'cashSummary',
            'cashBreakdown',
            'cashBookEntries'
        ));
    }

    protected function exportCashBookCsv($entries, string $start, string $end): Response
    {
        $filename = "Laporan_Buku_Kas_{$start}_{$end}.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        $output .= "ID,Tanggal,Cabang,Jenis Arus,Kategori,Keterangan,Nominal (Rp),Petugas\n";

        foreach ($entries as $e) {
            $type = $e->type === 'in' ? 'Uang Masuk' : 'Uang Keluar';
            $branch = $e->branch ? $e->branch->name : 'Global (Kantor Pusat)';
            $desc = '"' . str_replace('"', '""', $e->description ?? '') . '"';
            $creator = $e->creator->name ?? '-';
            $output .= "{$e->id},{$e->transaction_date->format('Y-m-d')},{$branch},{$type},{$e->category},{$desc},{$e->amount},{$creator}\n";
        }

        return response($output, 200, $headers);
    }
}
