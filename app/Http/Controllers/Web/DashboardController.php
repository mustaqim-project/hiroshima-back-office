<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashBook;
use App\Models\FraudAlert;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Shift;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FR-WEB-01, FR-WEB-03, FR-WEB-04, FSD-15
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $now = now('Asia/Jakarta');
        $today = $now->toDateString();

        // 1. Resolve Filter Presets & Date Range
        $preset = $request->query('preset');
        $branchId = $request->query('branch_id', 'all');

        $inputStartDate = $request->query('start_date');
        $inputEndDate = $request->query('end_date');

        if ($preset) {
            switch ($preset) {
                case 'today':
                    $startDate = $today;
                    $endDate = $today;
                    break;
                case 'yesterday':
                    $yesterday = $now->copy()->subDay()->toDateString();
                    $startDate = $yesterday;
                    $endDate = $yesterday;
                    break;
                case 'this_week':
                    $startDate = $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                    $endDate = $today;
                    break;
                case 'this_month':
                    $startDate = $now->copy()->startOfMonth()->toDateString();
                    $endDate = $today;
                    break;
                case 'last_month':
                    $startDate = $now->copy()->subMonth()->startOfMonth()->toDateString();
                    $endDate = $now->copy()->subMonth()->endOfMonth()->toDateString();
                    break;
                case 'this_year':
                    $startDate = $now->copy()->startOfYear()->toDateString();
                    $endDate = $today;
                    break;
                case 'custom':
                default:
                    $startDate = $inputStartDate ?: $today;
                    $endDate = $inputEndDate ?: $today;
                    break;
            }
        } elseif ($inputStartDate && $inputEndDate) {
            $preset = 'custom';
            $startDate = $inputStartDate;
            $endDate = $inputEndDate;
        } else {
            // Default preset is Today
            $preset = 'today';
            $startDate = $today;
            $endDate = $today;
        }

        // Clamp dates to prevent memory crashes on excessive ranges
        if ($startDate > $endDate) {
            $tmp = $startDate;
            $startDate = $endDate;
            $endDate = $tmp;
        }

        $branches = Branch::where('is_active', true)->orderBy('id')->get();
        $selectedBranch = ($branchId !== 'all') ? $branches->firstWhere('id', (int) $branchId) : null;

        // 2. Base Order Query
        $orderQuery = Order::query()
            ->whereBetween('business_date', [$startDate, $endDate]);

        if ($selectedBranch) {
            $orderQuery->where('branch_id', $selectedBranch->id);
        }

        $completedOrdersQuery = (clone $orderQuery)->where('status', 'completed');
        $voidOrdersQuery = (clone $orderQuery)->where('status', 'voided');

        // Core Sales & Transaction Metrics
        $totalSales = (int) (clone $completedOrdersQuery)->sum('total_amount');
        $totalOrders = (int) (clone $completedOrdersQuery)->count();
        $totalVoid = (int) (clone $voidOrdersQuery)->count();
        $totalVoidAmount = (int) (clone $voidOrdersQuery)->sum('total_amount');
        $voidRate = ($totalOrders + $totalVoid) > 0 
            ? round(($totalVoid / ($totalOrders + $totalVoid)) * 100, 1) 
            : 0;

        $avgOrderValue = $totalOrders > 0 ? (int) round($totalSales / $totalOrders) : 0;

        // 3. Cash Book Finance Metrics
        $cashQuery = CashBook::query()
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($selectedBranch) {
            $cashQuery->where('branch_id', $selectedBranch->id);
        }

        $totalCashIn = (int) (clone $cashQuery)->where('type', 'in')->sum('amount');
        $totalCashOut = (int) (clone $cashQuery)->where('type', 'out')->sum('amount');
        $netCash = $totalCashIn - $totalCashOut;
        $netProfitEstimate = $totalSales - $totalCashOut;

        // 4. Multi-Branch Overview (Performance per Cabang)
        $branchOverview = [];
        $totalSalesAllBranches = (int) Order::whereBetween('business_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('total_amount');

        foreach ($branches as $b) {
            $bOrders = Order::where('branch_id', $b->id)
                ->whereBetween('business_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->get(['id', 'total_amount', 'client_created_at']);

            $bSales = (int) $bOrders->sum('total_amount');
            $bCount = $bOrders->count();
            $bAov = $bCount > 0 ? (int) round($bSales / $bCount) : 0;

            $bCashOut = (int) CashBook::where('branch_id', $b->id)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->where('type', 'out')
                ->sum('amount');

            $contributionPct = $totalSalesAllBranches > 0 
                ? round(($bSales / $totalSalesAllBranches) * 100, 1) 
                : 0;

            // Find peak hour for this branch
            $hourlyCounts = [];
            foreach ($bOrders as $bo) {
                if ($bo->client_created_at) {
                    $h = (int) $bo->client_created_at->setTimezone('Asia/Jakarta')->format('G');
                    $hourlyCounts[$h] = ($hourlyCounts[$h] ?? 0) + 1;
                }
            }
            arsort($hourlyCounts);
            $topHour = !empty($hourlyCounts) ? array_key_first($hourlyCounts) : null;
            $peakHourStr = $topHour !== null ? sprintf('%02d:00 - %02d:00', $topHour, $topHour + 1) : '-';

            $activeShift = Shift::where('branch_id', $b->id)->where('status', 'open')->first();

            $branchOverview[] = [
                'branch' => $b,
                'sales' => $bSales,
                'orders_count' => $bCount,
                'aov' => $bAov,
                'cash_out' => $bCashOut,
                'contribution_pct' => $contributionPct,
                'peak_hour' => $peakHourStr,
                'has_active_shift' => (bool) $activeShift,
                'active_shift_cashier' => $activeShift?->user?->name,
            ];
        }

        // Sort branches by sales descending
        usort($branchOverview, fn ($a, $b) => $b['sales'] <=> $a['sales']);

        // 5. Traffic Analysis: Hourly Footfall & Distribution
        $allPeriodOrders = (clone $completedOrdersQuery)
            ->with(['orderType'])
            ->get(['id', 'order_type_id', 'branch_id', 'total_amount', 'client_created_at', 'business_date']);

        $hourlyTraffic = [];
        // Hours from 10:00 to 23:00 (F&B Street Food hours)
        for ($h = 10; $h <= 23; $h++) {
            $label = sprintf('%02d:00', $h);
            $hourlyTraffic[$label] = [
                'hour' => $h,
                'label' => $label,
                'orders_count' => 0,
                'sales_amount' => 0,
            ];
        }

        foreach ($allPeriodOrders as $ord) {
            if ($ord->client_created_at) {
                $h = (int) $ord->client_created_at->setTimezone('Asia/Jakarta')->format('G');
                $label = sprintf('%02d:00', $h);
                if (isset($hourlyTraffic[$label])) {
                    $hourlyTraffic[$label]['orders_count']++;
                    $hourlyTraffic[$label]['sales_amount'] += (int) $ord->total_amount;
                }
            }
        }

        // Determine Global Peak Hour
        $maxTrafficHour = null;
        $maxTrafficCount = -1;
        foreach ($hourlyTraffic as $ht) {
            if ($ht['orders_count'] > $maxTrafficCount) {
                $maxTrafficCount = $ht['orders_count'];
                $maxTrafficHour = $ht;
            }
        }
        $peakTrafficDisplay = $maxTrafficHour && $maxTrafficHour['orders_count'] > 0
            ? sprintf('%02d:00 - %02d:00 (%s Transaksi)', $maxTrafficHour['hour'], $maxTrafficHour['hour'] + 1, number_format($maxTrafficHour['orders_count']))
            : 'Belum ada data';

        // 6. Timeline Trend: Daily Sales & Orders
        $dateDiff = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        $dailyTrend = [];

        // If range <= 60 days, display daily points.
        if ($dateDiff <= 60) {
            $period = CarbonPeriod::create($startDate, $endDate);
            foreach ($period as $dt) {
                $dStr = $dt->toDateString();
                $dailyTrend[$dStr] = [
                    'date' => $dStr,
                    'label' => $dt->translatedFormat('d M'),
                    'sales' => 0,
                    'orders' => 0,
                ];
            }

            foreach ($allPeriodOrders as $ord) {
                $d = $ord->business_date;
                if (isset($dailyTrend[$d])) {
                    $dailyTrend[$d]['sales'] += (int) $ord->total_amount;
                    $dailyTrend[$d]['orders']++;
                }
            }
        } else {
            // Group by week or month if range is long
            $period = CarbonPeriod::create($startDate, '1 month', $endDate);
            foreach ($period as $dt) {
                $mKey = $dt->format('Y-m');
                $dailyTrend[$mKey] = [
                    'date' => $mKey,
                    'label' => $dt->translatedFormat('M Y'),
                    'sales' => 0,
                    'orders' => 0,
                ];
            }
            foreach ($allPeriodOrders as $ord) {
                $mKey = Carbon::parse($ord->business_date)->format('Y-m');
                if (isset($dailyTrend[$mKey])) {
                    $dailyTrend[$mKey]['sales'] += (int) $ord->total_amount;
                    $dailyTrend[$mKey]['orders']++;
                }
            }
        }

        // 7. Order Type Distribution (Dine In vs Take Away vs Delivery)
        $orderTypes = OrderType::where('is_active', true)->get();
        $orderTypeStats = [];
        foreach ($orderTypes as $ot) {
            $matched = $allPeriodOrders->where('order_type_id', $ot->id);
            $otSales = (int) $matched->sum('total_amount');
            $otCount = $matched->count();
            $orderTypeStats[] = [
                'id' => $ot->id,
                'name' => $ot->name,
                'orders_count' => $otCount,
                'sales' => $otSales,
                'pct' => $totalOrders > 0 ? round(($otCount / $totalOrders) * 100, 1) : 0,
            ];
        }

        // 8. Payment Methods Distribution (Display all active payment methods configured)
        $completedOrderIds = $allPeriodOrders->pluck('id')->toArray();
        $activePaymentMethods = PaymentMethod::where('is_active', true)->orderBy('id')->get();
        $allPaymentsInPeriod = !empty($completedOrderIds)
            ? Payment::whereIn('order_id', $completedOrderIds)->get()
            : collect();

        $totalPaymentSum = (int) $allPaymentsInPeriod->sum('amount');
        $paymentStats = [];

        foreach ($activePaymentMethods as $pm) {
            $matched = $allPaymentsInPeriod->filter(function ($p) use ($pm) {
                return $p->payment_method_id == $pm->id 
                    || strcasecmp(trim($p->payment_method_name_snapshot ?? ''), trim($pm->name)) === 0;
            });

            $pmAmount = (int) $matched->sum('amount');
            $pmCount = $matched->count();

            $paymentStats[] = [
                'id' => $pm->id,
                'name' => $pm->name,
                'code' => $pm->code,
                'type' => $pm->type,
                'amount' => $pmAmount,
                'count' => $pmCount,
                'pct' => $totalPaymentSum > 0 ? round(($pmAmount / $totalPaymentSum) * 100, 1) : 0,
            ];
        }

        // Include any payments using inactive or legacy methods
        $matchedIds = $paymentStats ? array_column($paymentStats, 'id') : [];
        $unmatched = $allPaymentsInPeriod->filter(function ($p) use ($matchedIds, $activePaymentMethods) {
            $matchedById = in_array($p->payment_method_id, $matchedIds);
            $matchedByName = $activePaymentMethods->contains(fn($pm) => strcasecmp(trim($pm->name), trim($p->payment_method_name_snapshot ?? '')) === 0);
            return !$matchedById && !$matchedByName;
        });

        if ($unmatched->isNotEmpty()) {
            $otherAmount = (int) $unmatched->sum('amount');
            $paymentStats[] = [
                'id' => null,
                'name' => 'Metode Lainnya',
                'code' => 'other',
                'type' => 'other',
                'amount' => $otherAmount,
                'count' => $unmatched->count(),
                'pct' => $totalPaymentSum > 0 ? round(($otherAmount / $totalPaymentSum) * 100, 1) : 0,
            ];
        }

        // 9. Top 5 Menu Terlaris (Best Seller Products)
        $topProducts = [];
        if (!empty($completedOrderIds)) {
            $topProducts = OrderItem::whereIn('order_id', $completedOrderIds)
                ->selectRaw('product_name, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
                ->groupBy('product_name')
                ->orderByDesc('total_qty')
                ->take(6)
                ->get();
        }

        // 10. Operational Status & Alerts
        $activeShiftsCount = Shift::where('status', 'open')->count();
        $newFraudAlertsCount = FraudAlert::where('status', 'new')->count();

        // 11. Recent Feeds
        $recentOrders = (clone $orderQuery)
            ->with(['orderType', 'branch', 'user'])
            ->latest('id')
            ->take(8)
            ->get();

        $recentCashTransactions = (clone $cashQuery)
            ->with(['branch', 'creator'])
            ->latest('id')
            ->take(6)
            ->get();

        // Prepare Chart.js Payloads
        $chartTrendLabels = array_column($dailyTrend, 'label');
        $chartTrendSales = array_column($dailyTrend, 'sales');
        $chartTrendOrders = array_column($dailyTrend, 'orders');

        $chartHourlyLabels = array_column($hourlyTraffic, 'label');
        $chartHourlyOrders = array_column($hourlyTraffic, 'orders_count');
        $chartHourlySales = array_column($hourlyTraffic, 'sales_amount');

        $chartBranchLabels = array_map(fn ($b) => $b['branch']->name, $branchOverview);
        $chartBranchSales = array_column($branchOverview, 'sales');

        $chartOrderTypeLabels = array_column($orderTypeStats, 'name');
        $chartOrderTypeCounts = array_column($orderTypeStats, 'orders_count');

        $chartPaymentLabels = array_column($paymentStats, 'name');
        $chartPaymentAmounts = array_column($paymentStats, 'amount');

        return view('backoffice.dashboard', compact(
            'preset',
            'startDate',
            'endDate',
            'branchId',
            'selectedBranch',
            'branches',
            'totalSales',
            'totalOrders',
            'avgOrderValue',
            'totalVoid',
            'totalVoidAmount',
            'voidRate',
            'totalCashIn',
            'totalCashOut',
            'netCash',
            'netProfitEstimate',
            'branchOverview',
            'peakTrafficDisplay',
            'activeShiftsCount',
            'newFraudAlertsCount',
            'recentOrders',
            'recentCashTransactions',
            'orderTypeStats',
            'paymentStats',
            'topProducts',
            'chartTrendLabels',
            'chartTrendSales',
            'chartTrendOrders',
            'chartHourlyLabels',
            'chartHourlyOrders',
            'chartHourlySales',
            'chartBranchLabels',
            'chartBranchSales',
            'chartOrderTypeLabels',
            'chartOrderTypeCounts',
            'chartPaymentLabels',
            'chartPaymentAmounts'
        ));
    }
}
