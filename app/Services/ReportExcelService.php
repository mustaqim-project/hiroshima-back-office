<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashBook;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExcelService
{
    /**
     * Convert 1-based column index to Excel column letters ('A', 'B', 'Z', 'AA', etc.)
     */
    public static function columnLetter(int $colIndex): string
    {
        $letter = '';
        while ($colIndex > 0) {
            $mod = ($colIndex - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $colIndex = intval(($colIndex - $mod) / 26);
        }
        return $letter;
    }

    /**
     * Export a single chosen report as a professionally styled 2-sheet workbook
     * (Sheet 1: Executive KPI Overview, Sheet 2: Detailed Report Ledger)
     */
    public function exportSingleReport(string $reportType, string $startDate, string $endDate, ?string $branchId = null): StreamedResponse
    {
        $branchName = $this->getBranchLabel($branchId);
        $overviewData = $this->getOverviewData($startDate, $endDate, $branchId);
        $reportData = $this->getReportDataset($reportType, $startDate, $endDate, $branchId);

        $sheets = [
            [
                'name' => 'Ringkasan & KPI',
                'headers' => ['Parameter / Indikator KPI', 'Keterangan', 'Nilai / Status'],
                'rows' => [
                    ['Jenis Laporan', 'Kode & Nama Laporan', "{$reportType}: {$reportData['title']}"],
                    ['Rentang Tanggal', 'Periode Pelaporan', "{$startDate} s/d {$endDate}"],
                    ['Cabang / Lingkup', 'Filter Entitas Bisnis', $branchName],
                    ['Waktu Ekspor', 'Waktu Pengunduhan Data (WIB)', now('Asia/Jakarta')->format('d/m/Y H:i:s')],
                    ['Total Omzet POS', 'Omzet transaksi berstatus selesai', $overviewData['total_sales']],
                    ['Total Transaksi POS', 'Jumlah struk pesanan lunas', $overviewData['total_orders']],
                    ['Total Uang Masuk Buku Kas', 'Penerimaan modal / setoran', $overviewData['total_cash_in']],
                    ['Total Uang Keluar Buku Kas', 'Belanja bumbu, sewa, gaji, gas', $overviewData['total_cash_out']],
                    ['Estimasi Arus Kas Bersih', 'Omzet POS dikurangi kas keluar', $overviewData['net_profit_estimate']],
                    ['Total Void / Pembatalan', 'Pesanan yang dibatalkan kasir/spv', $overviewData['total_void'] . ' transaksi (Rp ' . number_format($overviewData['total_void_amount'], 0, ',', '.') . ')'],
                ],
                'total' => null,
            ],
            [
                'name' => substr($reportData['short_name'], 0, 31),
                'headers' => $reportData['headers'],
                'rows' => $reportData['rows'],
                'total' => $reportData['total'] ?? null,
            ],
        ];

        $safeName = str_replace([' ', '/', '\\', ':', '*'], '_', $reportData['short_name']);
        $filename = "{$safeName}_{$startDate}_{$endDate}.xlsx";

        return $this->streamMultiSheetWorkbook($sheets, $filename);
    }

    /**
     * Export comprehensive multi-sheet full package workbook
     * (12 organized sheets spanning Financial, Sales, Inventory, Cash, Shifts, and Audit)
     */
    public function exportFullPackage(string $startDate, string $endDate, ?string $branchId = null): StreamedResponse
    {
        $branchName = $this->getBranchLabel($branchId);
        $overview = $this->getOverviewData($startDate, $endDate, $branchId);

        $pnl = $this->getProfitLossData($startDate, $endDate, $branchId);
        $menuSales = $this->getMenuSalesData($startDate, $endDate, $branchId);
        $categorySales = $this->getCategorySalesData($startDate, $endDate, $branchId);
        $payments = $this->getPaymentMethodsData($startDate, $endDate, $branchId);
        $orderTypes = $this->getOrderTypesData($startDate, $endDate, $branchId);
        $cashBook = $this->getCashBookData($startDate, $endDate, $branchId);
        $shifts = $this->getShiftsReconciliationData($startDate, $endDate, $branchId);
        $taxService = $this->getTaxServiceData($startDate, $endDate, $branchId);
        $discounts = $this->getDiscountsPromosData($startDate, $endDate, $branchId);
        $voids = $this->getVoidRefundData($startDate, $endDate, $branchId);
        $orders = $this->getPosOrdersData($startDate, $endDate, $branchId);

        $sheets = [
            [
                'name' => '1. Ringkasan Eksekutif',
                'headers' => ['Indikator Eksekutif', 'Keterangan', 'Nilai (Rp / Qty)'],
                'rows' => [
                    ['Nama Sistem & Bisnis', 'Platform POS & Back Office', 'HIROSHIMA Japanese Street Food'],
                    ['Periode Pelaporan', 'Rentang Tanggal', "{$startDate} s/d {$endDate}"],
                    ['Cabang Terpilih', 'Lingkup Outlet', $branchName],
                    ['Waktu Cetak Dokumen', 'Generated At (WIB)', now('Asia/Jakarta')->format('d/m/Y H:i:s')],
                    ['Total Omzet Penjualan POS', 'Total transaksi berstatus selesai', $overview['total_sales']],
                    ['Jumlah Transaksi POS Lunas', 'Volume struk tercetak', $overview['total_orders']],
                    ['Rata-rata Nilai Struk (AOV)', 'Average Order Value', $overview['total_orders'] > 0 ? (int)($overview['total_sales'] / $overview['total_orders']) : 0],
                    ['Total Uang Masuk Buku Kas', 'Suntikan modal & pendapatan lain', $overview['total_cash_in']],
                    ['Total Uang Keluar Buku Kas', 'Beban bumbu, gas, sewa, gaji', $overview['total_cash_out']],
                    ['Estimasi Arus Kas Bersih', 'Omzet POS dikurangi kas keluar', $overview['net_profit_estimate']],
                    ['Total Pajak Restoran (PB1)', 'Pajak daerah 10/11%', $overview['total_tax']],
                    ['Total Diskon Diberikan', 'Promo, voucher & potongan manual', $overview['total_discount']],
                    ['Total Pembatalan (Void)', 'Frekuensi pesanan dibatalkan', $overview['total_void']],
                    ['Nominal Void', 'Estimasi nilai pembatalan', $overview['total_void_amount']],
                ],
                'total' => null,
            ],
            [
                'name' => '2. Estimasi Laba Rugi',
                'headers' => $pnl['headers'],
                'rows' => $pnl['rows'],
                'total' => $pnl['total'] ?? null,
            ],
            [
                'name' => '3. Penjualan per Menu',
                'headers' => $menuSales['headers'],
                'rows' => $menuSales['rows'],
                'total' => $menuSales['total'] ?? null,
            ],
            [
                'name' => '4. Penjualan Kategori',
                'headers' => $categorySales['headers'],
                'rows' => $categorySales['rows'],
                'total' => $categorySales['total'] ?? null,
            ],
            [
                'name' => '5. Metode Pembayaran',
                'headers' => $payments['headers'],
                'rows' => $payments['rows'],
                'total' => $payments['total'] ?? null,
            ],
            [
                'name' => '6. Jenis Pesanan',
                'headers' => $orderTypes['headers'],
                'rows' => $orderTypes['rows'],
                'total' => $orderTypes['total'] ?? null,
            ],
            [
                'name' => '7. Buku Kas Arus Uang',
                'headers' => $cashBook['headers'],
                'rows' => $cashBook['rows'],
                'total' => $cashBook['total'] ?? null,
            ],
            [
                'name' => '8. Shift & Kasir',
                'headers' => $shifts['headers'],
                'rows' => $shifts['rows'],
                'total' => $shifts['total'] ?? null,
            ],
            [
                'name' => '9. Pajak & Service',
                'headers' => $taxService['headers'],
                'rows' => $taxService['rows'],
                'total' => $taxService['total'] ?? null,
            ],
            [
                'name' => '10. Diskon & Promosi',
                'headers' => $discounts['headers'],
                'rows' => $discounts['rows'],
                'total' => $discounts['total'] ?? null,
            ],
            [
                'name' => '11. Void & Pembatalan',
                'headers' => $voids['headers'],
                'rows' => $voids['rows'],
                'total' => $voids['total'] ?? null,
            ],
            [
                'name' => '12. Rincian Struk POS',
                'headers' => $orders['headers'],
                'rows' => $orders['rows'],
                'total' => $orders['total'] ?? null,
            ],
        ];

        $filename = "Laporan_Full_Package_Hiroshima_{$startDate}_{$endDate}.xlsx";
        return $this->streamMultiSheetWorkbook($sheets, $filename);
    }

    /**
     * Dispatcher to fetch specific report dataset by type
     */
    public function getReportDataset(string $type, string $start, string $end, ?string $branchId): array
    {
        return match ($type) {
            'RPT-FIN' => $this->getCashBookData($start, $end, $branchId),
            'RPT-01' => $this->getPosOrdersData($start, $end, $branchId),
            'RPT-02' => $this->getMenuSalesData($start, $end, $branchId),
            'RPT-03' => $this->getCategorySalesData($start, $end, $branchId),
            'RPT-04' => $this->getPaymentMethodsData($start, $end, $branchId),
            'RPT-05' => $this->getOrderTypesData($start, $end, $branchId),
            'RPT-06' => $this->getVoidRefundData($start, $end, $branchId),
            'RPT-07' => $this->getDiscountsPromosData($start, $end, $branchId),
            'RPT-08' => $this->getTaxServiceData($start, $end, $branchId),
            'RPT-09' => $this->getShiftsReconciliationData($start, $end, $branchId),
            'RPT-17' => $this->getCashierProductivityData($start, $end, $branchId),
            'RPT-21' => $this->getOperationalExpensesData($start, $end, $branchId),
            'RPT-22' => $this->getProfitLossData($start, $end, $branchId),
            'RPT-23' => $this->getAuditTrailData($start, $end, $branchId),
            default => $this->getPosOrdersData($start, $end, $branchId),
        };
    }

    /**
     * Executive Overview Summary Metrics
     */
    public function getOverviewData(string $startDate, string $endDate, ?string $branchId): array
    {
        $orderQuery = Order::query()->whereBetween('business_date', [$startDate, $endDate]);
        if ($branchId && $branchId !== 'all') {
            if ($branchId === 'global') {
                $orderQuery->whereNull('branch_id');
            } else {
                $orderQuery->where('branch_id', $branchId);
            }
        }

        $cashQuery = CashBook::query()
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);
        if ($branchId === 'global') {
            $cashQuery->whereNull('branch_id');
        } elseif ($branchId && $branchId !== 'all') {
            $cashQuery->where('branch_id', $branchId);
        }

        $totalSales = (int)(clone $orderQuery)->where('status', 'completed')->sum('total_amount');
        $totalOrders = (int)(clone $orderQuery)->where('status', 'completed')->count();
        $totalTax = (int)(clone $orderQuery)->where('status', 'completed')->sum('tax_amount');
        $totalService = (int)(clone $orderQuery)->where('status', 'completed')->sum('service_charge_amount');
        $totalDiscount = (int)(clone $orderQuery)->where('status', 'completed')->sum(DB::raw('promo_discount_amount + voucher_discount_amount + manual_discount_amount'));
        $totalVoid = (int)(clone $orderQuery)->where('status', 'voided')->count();
        $totalVoidAmount = (int)(clone $orderQuery)->where('status', 'voided')->sum('total_amount');

        $totalCashIn = (int)(clone $cashQuery)->where('type', 'in')->sum('amount');
        $totalCashOut = (int)(clone $cashQuery)->where('type', 'out')->sum('amount');

        return [
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'total_tax' => $totalTax,
            'total_service' => $totalService,
            'total_discount' => $totalDiscount,
            'total_void' => $totalVoid,
            'total_void_amount' => $totalVoidAmount,
            'total_cash_in' => $totalCashIn,
            'total_cash_out' => $totalCashOut,
            'net_profit_estimate' => $totalSales - $totalCashOut,
        ];
    }

    /**
     * 1. RPT-01: Ringkasan Penjualan & Finansial POS
     */
    public function getPosOrdersData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = Order::with(['branch', 'orderType', 'user'])
            ->whereBetween('business_date', [$startDate, $endDate]);
        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->orderBy('id', 'desc')->get();
        $rows = [];
        $sumSubtotal = 0;
        $sumDisc = 0;
        $sumTax = 0;
        $sumTotal = 0;

        foreach ($orders as $o) {
            $disc = $o->promo_discount_amount + $o->voucher_discount_amount + $o->manual_discount_amount;
            $sumSubtotal += $o->subtotal;
            $sumDisc += $disc;
            $sumTax += $o->tax_amount;
            $sumTotal += $o->total_amount;

            $rows[] = [
                $o->order_no,
                $o->business_date,
                $o->created_at ? $o->created_at->format('H:i') : '-',
                $o->branch->name ?? 'Pusat',
                $o->user->name ?? 'Kasir',
                $o->orderType->name ?? 'Dine In',
                $o->subtotal,
                $disc,
                $o->tax_amount,
                $o->total_amount,
                $o->status === 'completed' ? 'Lunas' : 'Void',
            ];
        }

        return [
            'title' => 'Ringkasan Penjualan & Finansial POS (RPT-01)',
            'short_name' => 'Penjualan POS',
            'headers' => ['No. Struk', 'Tanggal Bisnis', 'Jam', 'Cabang', 'Kasir', 'Jenis Order', 'Subtotal (Rp)', 'Diskon (Rp)', 'Pajak (Rp)', 'Total Bayar (Rp)', 'Status'],
            'rows' => $rows,
            'total' => ['TOTAL KESELURUHAN', '', '', '', '', '', $sumSubtotal, $sumDisc, $sumTax, $sumTotal, count($rows) . ' Transaksi'],
        ];
    }

    /**
     * 2. RPT-02: Penjualan per Menu
     */
    public function getMenuSalesData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.business_date', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $query->where('orders.branch_id', $branchId);
        }

        $items = $query->select(
            'order_items.product_name',
            DB::raw('COALESCE(categories.name, "Menu Lainnya") as category_name'),
            DB::raw('SUM(order_items.quantity) as total_qty'),
            DB::raw('SUM(order_items.subtotal) as total_sales'),
            DB::raw('SUM(order_items.quantity * COALESCE(order_items.cost_per_unit, products.manual_cost, 0)) as total_cost')
        )
            ->groupBy('order_items.product_name', 'categories.name')
            ->orderBy('total_qty', 'desc')
            ->get();

        $rows = [];
        $sumQty = 0;
        $sumSales = 0;
        $sumCost = 0;

        foreach ($items as $idx => $it) {
            $qty = (int)$it->total_qty;
            $sales = (int)$it->total_sales;
            $cost = (int)$it->total_cost;
            $profit = $sales - $cost;
            $margin = $sales > 0 ? round(($profit / $sales) * 100, 1) : 0;

            $sumQty += $qty;
            $sumSales += $sales;
            $sumCost += $cost;

            $rows[] = [
                $idx + 1,
                $it->product_name,
                $it->category_name,
                $qty,
                $sales,
                $cost,
                $profit,
                $margin . '%',
            ];
        }

        $totalProfit = $sumSales - $sumCost;
        $totalMargin = $sumSales > 0 ? round(($totalProfit / $sumSales) * 100, 1) : 0;

        return [
            'title' => 'Laporan Penjualan per Menu (RPT-02)',
            'short_name' => 'Penjualan per Menu',
            'headers' => ['No', 'Nama Menu / Item', 'Kategori Menu', 'Qty Terjual', 'Total Omzet (Rp)', 'Total HPP (Rp)', 'Laba Kotor (Rp)', 'Margin (%)'],
            'rows' => $rows,
            'total' => ['TOTAL PENJUALAN MENU', '', '', $sumQty, $sumSales, $sumCost, $totalProfit, $totalMargin . '%'],
        ];
    }

    /**
     * 3. RPT-03: Penjualan per Kategori
     */
    public function getCategorySalesData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.business_date', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $query->where('orders.branch_id', $branchId);
        }

        $cats = $query->select(
            DB::raw('COALESCE(categories.name, "Tanpa Kategori") as category_name'),
            DB::raw('SUM(order_items.quantity) as total_qty'),
            DB::raw('SUM(order_items.subtotal) as total_sales')
        )
            ->groupBy('categories.name')
            ->orderBy('total_sales', 'desc')
            ->get();

        $grandSales = (int)$cats->sum('total_sales');
        $sumQty = 0;
        $rows = [];

        foreach ($cats as $idx => $c) {
            $qty = (int)$c->total_qty;
            $sales = (int)$c->total_sales;
            $pct = $grandSales > 0 ? round(($sales / $grandSales) * 100, 1) : 0;
            $sumQty += $qty;

            $rows[] = [
                $idx + 1,
                $c->category_name,
                $qty,
                $sales,
                $pct . '%',
            ];
        }

        return [
            'title' => 'Laporan Penjualan per Kategori (RPT-03)',
            'short_name' => 'Penjualan Kategori',
            'headers' => ['No', 'Kategori Menu', 'Jumlah Item Terjual', 'Total Omzet (Rp)', 'Kontribusi (%)'],
            'rows' => $rows,
            'total' => ['TOTAL SELURUH KATEGORI', '', $sumQty, $grandSales, '100%'],
        ];
    }

    /**
     * 4. RPT-04: Metode Pembayaran
     */
    public function getPaymentMethodsData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = Payment::query()
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.business_date', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $query->where('orders.branch_id', $branchId);
        }

        $methods = $query->select(
            DB::raw('COALESCE(payments.payment_method_name_snapshot, "Tunai") as method_name'),
            DB::raw('COUNT(payments.id) as tx_count'),
            DB::raw('SUM(payments.amount) as total_amount')
        )
            ->groupBy('payments.payment_method_name_snapshot')
            ->orderBy('total_amount', 'desc')
            ->get();

        $sumTx = 0;
        $sumAmount = 0;
        $rows = [];
        $totalCollected = (int)$methods->sum('total_amount');

        foreach ($methods as $idx => $m) {
            $cnt = (int)$m->tx_count;
            $amt = (int)$m->total_amount;
            $pct = $totalCollected > 0 ? round(($amt / $totalCollected) * 100, 1) : 0;
            $sumTx += $cnt;
            $sumAmount += $amt;

            $rows[] = [
                $idx + 1,
                $m->method_name,
                $cnt,
                $amt,
                $pct . '%',
            ];
        }

        return [
            'title' => 'Laporan Metode Pembayaran (RPT-04)',
            'short_name' => 'Metode Pembayaran',
            'headers' => ['No', 'Metode Pembayaran', 'Jumlah Transaksi', 'Total Nominal Diterima (Rp)', 'Pangsa (%)'],
            'rows' => $rows,
            'total' => ['TOTAL PEMBAYARAN', '', $sumTx, $sumAmount, '100%'],
        ];
    }

    /**
     * 5. RPT-05: Penjualan per Jenis Order
     */
    public function getOrderTypesData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = Order::query()
            ->leftJoin('order_types', 'orders.order_type_id', '=', 'order_types.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.business_date', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $query->where('orders.branch_id', $branchId);
        }

        $types = $query->select(
            DB::raw('COALESCE(order_types.name, "Dine In") as type_name'),
            DB::raw('COUNT(orders.id) as order_count'),
            DB::raw('SUM(orders.total_amount) as total_sales')
        )
            ->groupBy('order_types.name')
            ->orderBy('total_sales', 'desc')
            ->get();

        $rows = [];
        $sumOrders = 0;
        $sumSales = 0;
        $grandSales = (int)$types->sum('total_sales');

        foreach ($types as $idx => $t) {
            $cnt = (int)$t->order_count;
            $sales = (int)$t->total_sales;
            $aov = $cnt > 0 ? (int)($sales / $cnt) : 0;
            $pct = $grandSales > 0 ? round(($sales / $grandSales) * 100, 1) : 0;

            $sumOrders += $cnt;
            $sumSales += $sales;

            $rows[] = [
                $idx + 1,
                $t->type_name,
                $cnt,
                $sales,
                $aov,
                $pct . '%',
            ];
        }

        $avgAov = $sumOrders > 0 ? (int)($sumSales / $sumOrders) : 0;

        return [
            'title' => 'Laporan Penjualan per Jenis Order (RPT-05)',
            'short_name' => 'Jenis Order',
            'headers' => ['No', 'Jenis Pesanan', 'Jumlah Pesanan', 'Total Omzet (Rp)', 'Rata-rata Nilai Order / AOV (Rp)', 'Kontribusi (%)'],
            'rows' => $rows,
            'total' => ['TOTAL PENJUALAN JENIS ORDER', '', $sumOrders, $sumSales, $avgAov, '100%'],
        ];
    }

    /**
     * 6. RPT-06: Laporan Void & Pembatalan
     */
    public function getVoidRefundData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = Order::with(['branch', 'user'])
            ->where('status', 'voided')
            ->whereBetween('business_date', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $voids = $query->orderBy('id', 'desc')->get();
        $rows = [];
        $sumAmount = 0;

        foreach ($voids as $idx => $v) {
            $amt = $v->total_amount;
            $sumAmount += $amt;

            $rows[] = [
                $idx + 1,
                $v->order_no,
                $v->business_date,
                $v->branch->name ?? 'Pusat',
                $v->user->name ?? 'Kasir',
                $v->void_reason ?? 'Kesalahan input / Permintaan tamu',
                $amt,
            ];
        }

        return [
            'title' => 'Laporan Void & Pembatalan Pesanan (RPT-06)',
            'short_name' => 'Void & Pembatalan',
            'headers' => ['No', 'No. Struk', 'Tanggal', 'Cabang', 'Petugas Kasir', 'Alasan Pembatalan (Void)', 'Nominal Batal (Rp)'],
            'rows' => $rows,
            'total' => ['TOTAL PEMBATALAN VOID', '', '', '', '', count($rows) . ' Struk Void', $sumAmount],
        ];
    }

    /**
     * 7. RPT-07: Diskon & Promosi
     */
    public function getDiscountsPromosData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = Order::with(['branch'])
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->where('promo_discount_amount', '>', 0)
                    ->orWhere('voucher_discount_amount', '>', 0)
                    ->orWhere('manual_discount_amount', '>', 0);
            })
            ->whereBetween('business_date', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->orderBy('id', 'desc')->get();
        $rows = [];
        $sumSub = 0;
        $sumPromo = 0;
        $sumVoucher = 0;
        $sumManual = 0;
        $sumTotalDisc = 0;

        foreach ($orders as $idx => $o) {
            $promo = (int)$o->promo_discount_amount;
            $voucher = (int)$o->voucher_discount_amount;
            $manual = (int)$o->manual_discount_amount;
            $totDisc = $promo + $voucher + $manual;

            $sumSub += $o->subtotal;
            $sumPromo += $promo;
            $sumVoucher += $voucher;
            $sumManual += $manual;
            $sumTotalDisc += $totDisc;

            $rows[] = [
                $idx + 1,
                $o->order_no,
                $o->business_date,
                $o->branch->name ?? 'Pusat',
                $o->subtotal,
                $promo,
                $voucher,
                $manual,
                $totDisc,
                $o->total_amount,
            ];
        }

        return [
            'title' => 'Laporan Diskon & Promosi (RPT-07)',
            'short_name' => 'Diskon & Promosi',
            'headers' => ['No', 'No. Struk', 'Tanggal', 'Cabang', 'Subtotal (Rp)', 'Promo Otomatis (Rp)', 'Voucher (Rp)', 'Diskon Manual (Rp)', 'Total Diskon (Rp)', 'Total Akhir (Rp)'],
            'rows' => $rows,
            'total' => ['TOTAL POTONGAN DISKON', '', '', '', $sumSub, $sumPromo, $sumVoucher, $sumManual, $sumTotalDisc, ''],
        ];
    }

    /**
     * 8. RPT-08: Pajak Restoran & Service Charge
     */
    public function getTaxServiceData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = Order::with(['branch'])
            ->where('status', 'completed')
            ->whereBetween('business_date', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->orderBy('id', 'desc')->get();
        $rows = [];
        $sumSub = 0;
        $sumTax = 0;
        $sumSvc = 0;
        $sumTotal = 0;

        foreach ($orders as $idx => $o) {
            $sumSub += $o->subtotal;
            $sumTax += $o->tax_amount;
            $sumSvc += $o->service_charge_amount;
            $sumTotal += $o->total_amount;

            $rows[] = [
                $idx + 1,
                $o->order_no,
                $o->business_date,
                $o->branch->name ?? 'Pusat',
                $o->subtotal,
                $o->tax_amount,
                $o->service_charge_amount,
                $o->total_amount,
            ];
        }

        return [
            'title' => 'Laporan Pajak Restoran (PB1) & Service Charge (RPT-08)',
            'short_name' => 'Pajak & Service',
            'headers' => ['No', 'No. Struk', 'Tanggal', 'Cabang', 'Dasar Pengenaan Pajak / DPP (Rp)', 'Pajak Restoran PB1 (Rp)', 'Service Charge (Rp)', 'Total Pembayaran (Rp)'],
            'rows' => $rows,
            'total' => ['TOTAL PAJAK & SERVICE', '', '', '', $sumSub, $sumTax, $sumSvc, $sumTotal],
        ];
    }

    /**
     * 9. RPT-09: Shift & Rekonsiliasi Kas Kasir
     */
    public function getShiftsReconciliationData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = Shift::with(['branch', 'user'])
            ->whereDate('opened_at', '>=', $startDate)
            ->whereDate('opened_at', '<=', $endDate);

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $shifts = $query->orderBy('id', 'desc')->get();
        $rows = [];
        $sumStart = 0;
        $sumExp = 0;
        $sumAct = 0;
        $sumDiff = 0;

        foreach ($shifts as $idx => $s) {
            $startCash = (int)$s->starting_cash;
            $expCash = (int)$s->expected_cash;
            $actCash = (int)$s->actual_cash;
            $diff = (int)$s->cash_difference;

            $sumStart += $startCash;
            $sumExp += $expCash;
            $sumAct += $actCash;
            $sumDiff += $diff;

            $rows[] = [
                $idx + 1,
                $s->id,
                $s->branch->name ?? 'Pusat',
                $s->user->name ?? 'Kasir',
                $s->opened_at ? $s->opened_at->format('d/m/Y H:i') : '-',
                $s->closed_at ? $s->closed_at->format('d/m/Y H:i') : 'Masih Buka',
                $startCash,
                $expCash,
                $actCash,
                $diff,
                $s->status === 'closed' ? 'Tutup & Rekonsiliasi' : 'Aktif',
                $s->reconciliation_notes ?? '-',
            ];
        }

        return [
            'title' => 'Laporan Shift & Rekonsiliasi Kasir (RPT-09)',
            'short_name' => 'Shift & Kasir',
            'headers' => ['No', 'ID Shift', 'Cabang', 'Petugas Kasir', 'Jam Buka', 'Jam Tutup', 'Modal Awal Kas (Rp)', 'Kas Diharapkan (Rp)', 'Kas Fisik Aktual (Rp)', 'Selisih Kas (Rp)', 'Status Shift', 'Catatan'],
            'rows' => $rows,
            'total' => ['TOTAL REKONSILIASI KASIR', '', '', '', '', '', $sumStart, $sumExp, $sumAct, $sumDiff, '', ''],
        ];
    }

    /**
     * 10. RPT-17: Produktivitas Kasir
     */
    public function getCashierProductivityData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = Order::query()
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.business_date', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $query->where('orders.branch_id', $branchId);
        }

        $cashiers = $query->select(
            DB::raw('COALESCE(users.name, "Kasir Umum") as cashier_name'),
            DB::raw('COUNT(orders.id) as order_count'),
            DB::raw('SUM(orders.total_amount) as total_sales')
        )
            ->groupBy('users.name')
            ->orderBy('total_sales', 'desc')
            ->get();

        $rows = [];
        $sumOrders = 0;
        $sumSales = 0;

        foreach ($cashiers as $idx => $c) {
            $cnt = (int)$c->order_count;
            $sales = (int)$c->total_sales;
            $aov = $cnt > 0 ? (int)($sales / $cnt) : 0;

            $sumOrders += $cnt;
            $sumSales += $sales;

            $rows[] = [
                $idx + 1,
                $c->cashier_name,
                $cnt,
                $sales,
                $aov,
            ];
        }

        $totalAov = $sumOrders > 0 ? (int)($sumSales / $sumOrders) : 0;

        return [
            'title' => 'Laporan Produktivitas Kasir (RPT-17)',
            'short_name' => 'Produktivitas Kasir',
            'headers' => ['No', 'Nama Kasir / Staff', 'Jumlah Transaksi Lunas', 'Total Omzet Penjualan (Rp)', 'Rata-rata Nilai Struk / AOV (Rp)'],
            'rows' => $rows,
            'total' => ['TOTAL PRODUKTIVITAS', '', $sumOrders, $sumSales, $totalAov],
        ];
    }

    /**
     * 11. RPT-21: Pengeluaran Biaya Operasional
     */
    public function getOperationalExpensesData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = CashBook::query()
            ->where('type', 'out')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        if ($branchId === 'global') {
            $query->whereNull('branch_id');
        } elseif ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $cats = $query->select(
            'category',
            DB::raw('COUNT(id) as count'),
            DB::raw('SUM(amount) as total_amount')
        )
            ->groupBy('category')
            ->orderBy('total_amount', 'desc')
            ->get();

        $rows = [];
        $sumTx = 0;
        $sumExpense = (int)$cats->sum('total_amount');

        foreach ($cats as $idx => $c) {
            $cnt = (int)$c->count;
            $amt = (int)$c->total_amount;
            $pct = $sumExpense > 0 ? round(($amt / $sumExpense) * 100, 1) : 0;
            $sumTx += $cnt;

            $rows[] = [
                $idx + 1,
                $c->category,
                $cnt,
                $amt,
                $pct . '%',
            ];
        }

        return [
            'title' => 'Laporan Pengeluaran Biaya Operasional (RPT-21)',
            'short_name' => 'Beban Operasional',
            'headers' => ['No', 'Kategori Pengeluaran', 'Frekuensi Transaksi', 'Total Beban (Rp)', 'Porsi Beban (%)'],
            'rows' => $rows,
            'total' => ['TOTAL PENGELUARAN BIAYA OPERASIONAL', '', $sumTx, $sumExpense, '100%'],
        ];
    }

    /**
     * 12. RPT-22: Estimasi Laba Rugi F&B
     */
    public function getProfitLossData(string $startDate, string $endDate, ?string $branchId): array
    {
        $orderQuery = Order::query()->where('status', 'completed')->whereBetween('business_date', [$startDate, $endDate]);
        if ($branchId && $branchId !== 'all') {
            $orderQuery->where('branch_id', $branchId);
        }

        $grossSales = (int)(clone $orderQuery)->sum('subtotal');
        $discounts = (int)(clone $orderQuery)->sum(DB::raw('promo_discount_amount + voucher_discount_amount + manual_discount_amount'));
        $netSales = $grossSales - $discounts;

        // Estimated COGS from order items
        $itemQuery = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.business_date', [$startDate, $endDate]);
        if ($branchId && $branchId !== 'all') {
            $itemQuery->where('orders.branch_id', $branchId);
        }
        $cogs = (int)$itemQuery->sum(DB::raw('order_items.quantity * COALESCE(order_items.cost_per_unit, products.manual_cost, 0)'));
        if ($cogs === 0 && $netSales > 0) {
            // Standard F&B benchmark: 35% if manual cost is not populated
            $cogs = (int)($netSales * 0.35);
        }

        $grossProfit = $netSales - $cogs;
        $grossMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 1) : 0;

        // Operational Expenses from Cash Book
        $expenseQuery = CashBook::query()->where('type', 'out')->whereDate('transaction_date', '>=', $startDate)->whereDate('transaction_date', '<=', $endDate);
        if ($branchId === 'global') {
            $expenseQuery->whereNull('branch_id');
        } elseif ($branchId && $branchId !== 'all') {
            $expenseQuery->where('branch_id', $branchId);
        }
        $expenses = (int)$expenseQuery->sum('amount');

        $netOperatingProfit = $grossProfit - $expenses;
        $netMargin = $netSales > 0 ? round(($netOperatingProfit / $netSales) * 100, 1) : 0;

        $rows = [
            ['I. PENDAPATAN OPERASIONAL', '', ''],
            ['', 'Penjualan Kotor (Gross Sales POS)', $grossSales],
            ['', 'Potongan Diskon & Promosi', -$discounts],
            ['', 'TOTAL PENDAPATAN BERSIH (NET SALES)', $netSales],
            ['', '', ''],
            ['II. HARGA POKOK PENJUALAN (HPP / COGS)', '', ''],
            ['', 'Biaya Bahan Baku Menu Terjual', -$cogs],
            ['', 'TOTAL HARGA POKOK PENJUALAN (COGS)', -$cogs],
            ['', '', ''],
            ['III. LABA KOTOR (GROSS PROFIT)', 'Margin: ' . $grossMargin . '%', $grossProfit],
            ['', '', ''],
            ['IV. BEBAN OPERASIONAL KAS (OPEX)', '', ''],
            ['', 'Total Biaya Operasional Toko (Buku Kas Keluar)', -$expenses],
            ['', '', ''],
            ['V. ESTIMASI LABA BERSIH OPERASIONAL (NET PROFIT)', 'Net Margin: ' . $netMargin . '%', $netOperatingProfit],
        ];

        return [
            'title' => 'Estimasi Laporan Laba Rugi F&B (RPT-22)',
            'short_name' => 'Laba Rugi',
            'headers' => ['Komponen Finansial Laba Rugi', 'Catatan / Margin', 'Nominal (Rp)'],
            'rows' => $rows,
            'total' => ['ESTIMASI LABA BERSIH BERJALAN', 'Margin Bersih: ' . $netMargin . '%', $netOperatingProfit],
        ];
    }

    /**
     * 13. RPT-23: Integritas Rantai Audit Log
     */
    public function getAuditTrailData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = AuditLog::with('user')
            ->whereDate('occurred_at', '>=', $startDate)
            ->whereDate('occurred_at', '<=', $endDate);

        if ($branchId && $branchId !== 'all' && $branchId !== 'global') {
            $query->where('branch_id', $branchId);
        }

        $logs = $query->orderBy('id', 'desc')->limit(1000)->get();
        $rows = [];

        foreach ($logs as $idx => $l) {
            $rows[] = [
                $idx + 1,
                $l->occurred_at ? $l->occurred_at->format('d/m/Y H:i:s') : '-',
                $l->action,
                $l->user->name ?? 'Sistem',
                $l->ip ?? '127.0.0.1',
                substr($l->hash ?? '-', 0, 16) . '...',
            ];
        }

        return [
            'title' => 'Laporan Integritas Rantai Audit Log (RPT-23)',
            'short_name' => 'Audit Trail',
            'headers' => ['No', 'Waktu (WIB)', 'Aksi / Peristiwa', 'Pelaku / User', 'IP Address', 'Kriptografi Hash'],
            'rows' => $rows,
            'total' => ['TOTAL LOG TERVERIFIKASI', '', '', count($rows) . ' Catatan', '', 'Rantai Aman'],
        ];
    }

    /**
     * 14. RPT-FIN: Buku Kas & Arus Keuangan
     */
    public function getCashBookData(string $startDate, string $endDate, ?string $branchId): array
    {
        $cashQuery = CashBook::with(['branch', 'creator'])
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        if ($branchId === 'global') {
            $cashQuery->whereNull('branch_id');
        } elseif ($branchId && $branchId !== 'all') {
            $cashQuery->where('branch_id', $branchId);
        }

        $entries = $cashQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get();
        $rows = [];
        $sumIn = 0;
        $sumOut = 0;

        foreach ($entries as $idx => $e) {
            $in = $e->type === 'in' ? $e->amount : 0;
            $out = $e->type === 'out' ? $e->amount : 0;
            $sumIn += $in;
            $sumOut += $out;

            $branchStr = $e->is_global ? 'Kantor Pusat (Global Pusat)' : ($e->branch->name ?? '-');

            $rows[] = [
                $idx + 1,
                $e->transaction_date ? $e->transaction_date->format('d/m/Y') : '-',
                $branchStr,
                $e->type === 'in' ? 'Uang Masuk' : 'Uang Keluar',
                $e->category,
                $e->description ?? '-',
                $in > 0 ? $in : '',
                $out > 0 ? $out : '',
                $e->creator->name ?? 'Staf',
            ];
        }

        $netCash = $sumIn - $sumOut;

        return [
            'title' => 'Laporan Buku Kas & Arus Keuangan (RPT-FIN)',
            'short_name' => 'Buku Kas Ledger',
            'headers' => ['No', 'Tanggal', 'Cabang', 'Jenis Arus', 'Kategori', 'Keterangan', 'Uang Masuk (Rp)', 'Uang Keluar (Rp)', 'Petugas'],
            'rows' => $rows,
            'total' => ['TOTAL KAS MUTASI', '', '', '', '', 'Saldo Bersih: Rp ' . number_format($netCash, 0, ',', '.'), $sumIn, $sumOut, ''],
        ];
    }

    /**
     * Human readable branch filter label
     */
    protected function getBranchLabel(?string $branchId): string
    {
        if ($branchId === 'global') return 'Global (Kantor Pusat)';
        if ($branchId && $branchId !== 'all') {
            $b = Branch::find($branchId);
            return $b ? "Cabang {$b->name} ({$b->code})" : 'Cabang Terpilih';
        }
        return 'Seluruh Cabang & Pusat';
    }

    /**
     * Stream multi-sheet workbook to browser with fallback to CSV if ZipArchive is missing
     */
    protected function streamMultiSheetWorkbook(array $sheets, string $filename): StreamedResponse
    {
        if (class_exists(\ZipArchive::class)) {
            $tempPath = $this->createMultiSheetOpenXml($sheets);

            return new StreamedResponse(function () use ($tempPath) {
                $stream = fopen($tempPath, 'rb');
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
                @unlink($tempPath);
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'max-age=0, no-cache, must-revalidate',
                'Pragma' => 'public',
            ]);
        }

        // CSV Fallback (first sheet with data)
        $targetSheet = $sheets[1] ?? $sheets[0];
        $csvFilename = str_replace('.xlsx', '.csv', $filename);
        return new StreamedResponse(function () use ($targetSheet) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($output, $targetSheet['headers']);
            foreach ($targetSheet['rows'] as $row) {
                fputcsv($output, $row);
            }
            if (!empty($targetSheet['total'])) {
                fputcsv($output, $targetSheet['total']);
            }
            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$csvFilename}\"",
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Pure PHP Zero-Dependency Multi-Sheet OpenXML (.xlsx) Builder
     */
    protected function createMultiSheetOpenXml(array $sheets): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'hrs_multirpt_') . '.xlsx';
        $zip = new \ZipArchive();
        if ($zip->open($tempPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat paket laporan spreadsheet.');
        }

        $sheetCount = count($sheets);

        // 1. [Content_Types].xml
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $ct .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
        $zip->addFromString('[Content_Types].xml', $ct);

        // 2. _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $wbRels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $wbRels .= '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>';
        foreach ($sheets as $idx => $s) {
            $sNum = $idx + 1;
            $safeName = htmlspecialchars(substr($s['name'], 0, 31), ENT_XML1, 'UTF-8');
            $wb .= '<sheet name="' . $safeName . '" sheetId="' . $sNum . '" r:id="rId' . $sNum . '"/>';
        }
        $wb .= '</sheets></workbook>';
        $zip->addFromString('xl/workbook.xml', $wb);

        // 5. xl/styles.xml (Professional Dark Navy Header, Soft Zebra, Bold Totals, Clean Borders)
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            . '<font><name val="Segoe UI"/><sz val="11"/></font>'
            . '<font><name val="Segoe UI"/><sz val="11"/><b/><color rgb="FFFFFFFF"/></font>'
            . '<font><name val="Segoe UI"/><sz val="11"/><b/><color rgb="FF0F172A"/></font>'
            . '<font><name val="Segoe UI"/><sz val="13"/><b/><color rgb="FF0F172A"/></font>'
            . '</fonts>'
            . '<fills count="5">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/></patternFill></fill>' // 2: Navy header
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>' // 3: Zebra light
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/></patternFill></fill>' // 4: Total row
            . '</fills>'
            . '<borders count="3">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFCBD5E1"/></left><right style="thin"><color rgb="FFCBD5E1"/></right><top style="thin"><color rgb="FFCBD5E1"/></top><bottom style="thin"><color rgb="FFCBD5E1"/></bottom></border>'
            . '<border><left style="thin"><color rgb="FF94A3B8"/></left><right style="thin"><color rgb="FF94A3B8"/></right><top style="thin"><color rgb="FF0F172A"/></top><bottom style="double"><color rgb="FF0F172A"/></bottom></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="8">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>' // 0: default
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 1: Header
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>' // 2: Data left
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' // 3: Data right (number)
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>' // 4: Zebra left
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' // 5: Zebra right
            . '<xf numFmtId="0" fontId="2" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>' // 6: Total label
            . '<xf numFmtId="0" fontId="2" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' // 7: Total num
            . '</cellXfs></styleSheet>';
        $zip->addFromString('xl/styles.xml', $stylesXml);

        // 6. Generate individual worksheets
        foreach ($sheets as $idx => $s) {
            $sNum = $idx + 1;
            $headers = $s['headers'];
            $rows = $s['rows'];
            $totalRow = $s['total'] ?? null;

            // Calculate adaptive column widths
            $colWidths = [];
            foreach ($headers as $cIdx => $h) {
                $maxLen = mb_strlen((string)$h, 'UTF-8');
                foreach ($rows as $r) {
                    $valLen = isset($r[$cIdx]) ? mb_strlen((string)$r[$cIdx], 'UTF-8') : 0;
                    if ($valLen > $maxLen) $maxLen = $valLen;
                }
                if ($totalRow && isset($totalRow[$cIdx])) {
                    $valLen = mb_strlen((string)$totalRow[$cIdx], 'UTF-8');
                    if ($valLen > $maxLen) $maxLen = $valLen;
                }
                $colWidths[$cIdx + 1] = max(14, min(50, $maxLen + 4));
            }

            $colsXml = '<cols>';
            foreach ($colWidths as $colNum => $width) {
                $colsXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>';
            }
            $colsXml .= '</cols>';

            $sheetData = '<sheetData>';
            // Header Row (Row 1, Height 28pt)
            $sheetData .= '<row r="1" ht="28" customHeight="1">';
            foreach ($headers as $cIdx => $h) {
                $ref = self::columnLetter($cIdx + 1) . '1';
                $sheetData .= '<c r="' . $ref . '" s="1" t="inlineStr"><is><t>' . htmlspecialchars((string)$h, ENT_XML1, 'UTF-8') . '</t></is></c>';
            }
            $sheetData .= '</row>';

            // Data Rows (Height 22pt with alternate zebra striping)
            $rNum = 2;
            foreach ($rows as $rIdx => $r) {
                $isZebra = ($rIdx % 2 === 1);
                $sheetData .= '<row r="' . $rNum . '" ht="22" customHeight="1">';
                foreach ($r as $cIdx => $val) {
                    $ref = self::columnLetter($cIdx + 1) . $rNum;
                    $isNum = (is_int($val) || (is_numeric($val) && !preg_match('/^0[0-9]+$/', (string)$val)));
                    $styleIdx = $isNum ? ($isZebra ? 5 : 3) : ($isZebra ? 4 : 2);

                    if (is_null($val) || $val === '') {
                        $sheetData .= '<c r="' . $ref . '" s="' . $styleIdx . '"/>';
                    } elseif ($isNum) {
                        $sheetData .= '<c r="' . $ref . '" s="' . $styleIdx . '"><v>' . $val . '</v></c>';
                    } else {
                        $sheetData .= '<c r="' . $ref . '" s="' . $styleIdx . '" t="inlineStr"><is><t>' . htmlspecialchars((string)$val, ENT_XML1, 'UTF-8') . '</t></is></c>';
                    }
                }
                $sheetData .= '</row>';
                $rNum++;
            }

            // Grand Total Row (Height 26pt, Bold Font, Double Bottom Border)
            if ($totalRow) {
                $sheetData .= '<row r="' . $rNum . '" ht="26" customHeight="1">';
                foreach ($totalRow as $cIdx => $val) {
                    $ref = self::columnLetter($cIdx + 1) . $rNum;
                    $isNum = (is_int($val) || (is_numeric($val) && !preg_match('/^0[0-9]+$/', (string)$val)));
                    $styleIdx = $isNum ? 7 : 6;

                    if (is_null($val) || $val === '') {
                        $sheetData .= '<c r="' . $ref . '" s="' . $styleIdx . '"/>';
                    } elseif ($isNum) {
                        $sheetData .= '<c r="' . $ref . '" s="' . $styleIdx . '"><v>' . $val . '</v></c>';
                    } else {
                        $sheetData .= '<c r="' . $ref . '" s="' . $styleIdx . '" t="inlineStr"><is><t>' . htmlspecialchars((string)$val, ENT_XML1, 'UTF-8') . '</t></is></c>';
                    }
                }
                $sheetData .= '</row>';
            }

            $sheetData .= '</sheetData>';
            $worksheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . $colsXml . $sheetData . '</worksheet>';
            $zip->addFromString('xl/worksheets/sheet' . $sNum . '.xml', $worksheetXml);
        }

        $zip->close();
        return $tempPath;
    }
}
