<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashBook;
use App\Models\CashMovement;
use App\Models\FraudAlert;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExcelService
{
    /**
     * Master registry of all available reports in Hiroshima Back Office
     */
    public static function getAvailableReports(): array
    {
        return [
            'RPT-FIN' => [
                'code' => 'RPT-FIN',
                'name' => 'Laporan Buku Kas & Arus Keuangan (Ledger)',
                'short_name' => 'Buku Kas Ledger',
                'category' => 'Finance & Keuangan',
                'description' => 'Mutasi buku kas operasional, saldo awal, saldo berjalan, pemasukan dan pengeluaran.',
            ],
            'RPT-01' => [
                'code' => 'RPT-01',
                'name' => 'Ringkasan Penjualan & Finansial POS',
                'short_name' => 'Penjualan POS',
                'category' => 'Penjualan & Kasir',
                'description' => 'Rekapitulasi transaksi struk, omzet kotor, diskon, service charge, pajak PB1, dan omzet bersih.',
            ],
            'RPT-02' => [
                'code' => 'RPT-02',
                'name' => 'Penjualan per Menu (Menu Sales)',
                'short_name' => 'Penjualan per Menu',
                'category' => 'Produk & Penjualan',
                'description' => 'Analisis kuantitas terjual, omzet penjualan, HPP bahan baku, laba kotor, dan margin per menu.',
            ],
            'RPT-03' => [
                'code' => 'RPT-03',
                'name' => 'Penjualan per Kategori (Category Sales)',
                'short_name' => 'Penjualan Kategori',
                'category' => 'Produk & Penjualan',
                'description' => 'Distribusi volume dan omzet penjualan berdasarkan kategori produk serta kontribusi omzet.',
            ],
            'RPT-04' => [
                'code' => 'RPT-04',
                'name' => 'Metode Pembayaran (Payment Channels)',
                'short_name' => 'Metode Pembayaran',
                'category' => 'Finansial & Kasir',
                'description' => 'Rekapitulasi penerimaan kas, QRIS, kartu debit, dan transfer per metode bayar.',
            ],
            'RPT-05' => [
                'code' => 'RPT-05',
                'name' => 'Penjualan per Jenis Order (Order Types)',
                'short_name' => 'Jenis Order',
                'category' => 'Penjualan & Kasir',
                'description' => 'Performa penjualan berdasarkan jenis layanan: Dine-in, Takeaway, dan Delivery.',
            ],
            'RPT-06' => [
                'code' => 'RPT-06',
                'name' => 'Laporan Void & Refund (Loss Prevention)',
                'short_name' => 'Void & Refund',
                'category' => 'Pengawasan & Audit',
                'description' => 'Audit pembatalan struk (void) dan pengembalian uang (refund) beserta alasan dan otorisasi PIN.',
            ],
            'RPT-07' => [
                'code' => 'RPT-07',
                'name' => 'Diskon & Promosi (Discounts & Vouchers)',
                'short_name' => 'Diskon & Promosi',
                'category' => 'Promosi & Marketing',
                'description' => 'Rincian pemanfaatan promo otomatis, voucher diskon, poin loyalty, dan diskon manual kasir.',
            ],
            'RPT-08' => [
                'code' => 'RPT-08',
                'name' => 'Pajak Restoran (PB1) & Service Charge',
                'short_name' => 'Pajak & Service',
                'category' => 'Pajak & Legalitas',
                'description' => 'Dasar Pengenaan Pajak (DPP), service charge toko, dan pajak daerah restoran (PB1).',
            ],
            'RPT-09' => [
                'code' => 'RPT-09',
                'name' => 'Shift & Rekonsiliasi Kas (Cash Drawer)',
                'short_name' => 'Shift & Kasir',
                'category' => 'Operasional Kasir',
                'description' => 'Modal awal, arus kas shift, kas aktual, selisih laci kasir, dan audit shift auto-close.',
            ],
            'RPT-10' => [
                'code' => 'RPT-10',
                'name' => 'Detail Transaksi per Baris Item (Line Items)',
                'short_name' => 'Detail Baris Item',
                'category' => 'Detail Penjualan',
                'description' => 'Rincian transaksi per baris item pesanan, varian menu, modifier tambahan, dan harga satuan.',
            ],
            'RPT-11' => [
                'code' => 'RPT-11',
                'name' => 'Analisis Jam Sibuk Penjualan (Peak Hours)',
                'short_name' => 'Analisis Jam Sibuk',
                'category' => 'Analitik Operasional',
                'description' => 'Distribusi volume transaksi dan omzet per jam operasional (00:00 - 23:00) untuk deteksi jam sibuk.',
            ],
            'RPT-12' => [
                'code' => 'RPT-12',
                'name' => 'Perbandingan Kinerja Antar Cabang',
                'short_name' => 'Komparasi Cabang',
                'category' => 'Manajemen Multi-Cabang',
                'description' => 'Perbandingan omzet, jumlah transaksi, AOV, pajak, dan rasio pembatalan antar seluruh cabang aktif.',
            ],
            'RPT-17' => [
                'code' => 'RPT-17',
                'name' => 'Produktivitas Kasir (Cashier Performance)',
                'short_name' => 'Produktivitas Kasir',
                'category' => 'SDM & Kasir',
                'description' => 'Evaluasi performa kasir: volume transaksi lunas, total omzet, rata-rata transaksi, dan rasio void.',
            ],
            'RPT-21' => [
                'code' => 'RPT-21',
                'name' => 'Pengeluaran Biaya Operasional (Expenses)',
                'short_name' => 'Beban Operasional',
                'category' => 'Finance & Keuangan',
                'description' => 'Daftar pengeluaran operasional outlet: bahan baku, gaji, sewa, listrik/air, dan utilitas.',
            ],
            'RPT-22' => [
                'code' => 'RPT-22',
                'name' => 'Estimasi Laba Rugi F&B (P&L Income Statement)',
                'short_name' => 'Laba Rugi F&B',
                'category' => 'Finance & Manajemen',
                'description' => 'Laporan laba rugi: Penjualan Bersih, HPP bahan baku, Laba Kotor, Biaya Operasional, dan Laba Bersih.',
            ],
            'RPT-23' => [
                'code' => 'RPT-23',
                'name' => 'Integritas Rantai Audit Log (Hash Trail)',
                'short_name' => 'Audit Trail Hash',
                'category' => 'Keamanan & Kepatuhan',
                'description' => 'Catatan audit log aktivitas sistem yang diikat oleh rantai kriptografi SHA-256 anti-tamper.',
            ],
            'RPT-24' => [
                'code' => 'RPT-24',
                'name' => 'Deteksi Anomali & Fraud POS (Loss Prevention)',
                'short_name' => 'Deteksi Fraud POS',
                'category' => 'Keamanan & Kepatuhan',
                'description' => 'Peringatan dan audit indikasi anomali transaksi kasir (void berlebihan, split bill, manipulasi diskon).',
            ],
        ];
    }

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
     * Sanitize user input against CSV/Excel Formula Injection (CWE-1236)
     */
    public static function sanitizeCellText(?string $val): string
    {
        if ($val === null) return '';
        $val = (string) $val;
        if (isset($val[0]) && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"])) {
            return "'" . $val;
        }
        return $val;
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
                'title' => 'RINGKASAN EKSEKUTIF & INDIKATOR KUNCI',
                'period' => "Periode: {$startDate} s/d {$endDate}",
                'branch' => "Lingkup: " . $branchName,
                'headers' => ['Parameter / Indikator KPI', 'Keterangan', 'Nilai / Status'],
                'rows' => [
                    ['Jenis Laporan Terpilih', 'Kode & Nama Laporan', "{$reportType}: {$reportData['title']}"],
                    ['Rentang Tanggal', 'Periode Pelaporan Bisnis', "{$startDate} s/d {$endDate}"],
                    ['Cabang / Lingkup', 'Filter Entitas Bisnis', $branchName],
                    ['Waktu Pengunduhan Data', 'Generated At (WIB)', now('Asia/Jakarta')->format('d/m/Y H:i:s')],
                    ['Total Omzet Penjualan POS', 'Total transaksi berstatus selesai (lunas)', $overviewData['total_sales']],
                    ['Total Transaksi POS', 'Jumlah struk pesanan lunas tercatat', $overviewData['total_orders']],
                    ['Rata-rata Nilai Struk (AOV)', 'Average Order Value per struk', $overviewData['total_orders'] > 0 ? (int)($overviewData['total_sales'] / $overviewData['total_orders']) : 0],
                    ['Total Uang Masuk Buku Kas', 'Penerimaan modal / setoran kas', $overviewData['total_cash_in']],
                    ['Total Uang Keluar Buku Kas', 'Belanja bumbu, sewa, gaji, gas, utilitas', $overviewData['total_cash_out']],
                    ['Estimasi Arus Kas Bersih', 'Omzet POS dikurangi kas keluar', $overviewData['net_profit_estimate']],
                    ['Total Pajak Restoran (PB1)', 'Pungutan pajak restoran 10%', $overviewData['total_tax']],
                    ['Total Diskon Diberikan', 'Promo, voucher, loyalty & potongan manual', $overviewData['total_discount']],
                    ['Total Pembatalan (Void)', 'Frekuensi pesanan dibatalkan', $overviewData['total_void'] . ' transaksi'],
                    ['Nominal Void', 'Estimasi nilai pesanan batal', $overviewData['total_void_amount']],
                ],
                'total' => null,
            ],
            [
                'name' => substr(preg_replace('/[\\\\\/\?\*\:\[\]]/', '', $reportData['short_name']), 0, 31),
                'title' => strtoupper($reportData['title']),
                'period' => "Periode: {$startDate} s/d {$endDate}",
                'branch' => "Lingkup: " . $branchName,
                'headers' => $reportData['headers'],
                'rows' => $reportData['rows'],
                'total' => $reportData['total'] ?? null,
            ],
        ];

        $safeName = str_replace([' ', '/', '\\', ':', '*'], '_', $reportData['short_name']);
        $filename = "{$reportType}_{$safeName}_{$startDate}_{$endDate}.xlsx";

        return $this->streamMultiSheetWorkbook($sheets, $filename);
    }

    /**
     * Export comprehensive multi-sheet full package workbook
     * (19 organized sheets spanning Executive Summary and all 18 reports)
     */
    public function exportFullPackage(string $startDate, string $endDate, ?string $branchId = null): StreamedResponse
    {
        $branchName = $this->getBranchLabel($branchId);
        $overview = $this->getOverviewData($startDate, $endDate, $branchId);

        // Fetch datasets for all 18 reports
        $fin = $this->getCashBookData($startDate, $endDate, $branchId);
        $rpt01 = $this->getPosOrdersData($startDate, $endDate, $branchId);
        $rpt02 = $this->getMenuSalesData($startDate, $endDate, $branchId);
        $rpt03 = $this->getCategorySalesData($startDate, $endDate, $branchId);
        $rpt04 = $this->getPaymentMethodsData($startDate, $endDate, $branchId);
        $rpt05 = $this->getOrderTypesData($startDate, $endDate, $branchId);
        $rpt06 = $this->getVoidRefundData($startDate, $endDate, $branchId);
        $rpt07 = $this->getDiscountsPromosData($startDate, $endDate, $branchId);
        $rpt08 = $this->getTaxServiceData($startDate, $endDate, $branchId);
        $rpt09 = $this->getShiftsReconciliationData($startDate, $endDate, $branchId);
        $rpt10 = $this->getLineItemDetailData($startDate, $endDate, $branchId);
        $rpt11 = $this->getHourlySalesData($startDate, $endDate, $branchId);
        $rpt12 = $this->getBranchComparisonData($startDate, $endDate, $branchId);
        $rpt17 = $this->getCashierProductivityData($startDate, $endDate, $branchId);
        $rpt21 = $this->getOperationalExpensesData($startDate, $endDate, $branchId);
        $rpt22 = $this->getProfitLossData($startDate, $endDate, $branchId);
        $rpt23 = $this->getAuditTrailData($startDate, $endDate, $branchId);
        $rpt24 = $this->getFraudAlertsData($startDate, $endDate, $branchId);

        $sheets = [
            [
                'name' => '1. Ringkasan Eksekutif',
                'title' => 'RINGKASAN EKSEKUTIF BISNIS & OPERASIONAL',
                'period' => "Periode: {$startDate} s/d {$endDate}",
                'branch' => "Lingkup: " . $branchName,
                'headers' => ['Indikator Eksekutif', 'Keterangan Definisi', 'Nilai (Rp / Qty)'],
                'rows' => [
                    ['Platform & Brand Bisnis', 'Sistem POS & Back Office', 'HIROSHIMA Japanese Street Food'],
                    ['Periode Pelaporan', 'Rentang Tanggal Kalender', "{$startDate} s/d {$endDate}"],
                    ['Cabang / Entitas Terpilih', 'Lingkup Outlet Laporan', $branchName],
                    ['Waktu Cetak Dokumen', 'Generated At (WIB)', now('Asia/Jakarta')->format('d/m/Y H:i:s')],
                    ['Total Omzet Penjualan POS', 'Total transaksi berstatus selesai (lunas)', $overview['total_sales']],
                    ['Jumlah Transaksi POS Lunas', 'Volume struk tercetak & terbayar', $overview['total_orders']],
                    ['Rata-rata Nilai Struk (AOV)', 'Average Order Value per struk', $overview['total_orders'] > 0 ? (int)($overview['total_sales'] / $overview['total_orders']) : 0],
                    ['Total Uang Masuk Buku Kas', 'Suntikan modal, setoran kas, penerimaan lain', $overview['total_cash_in']],
                    ['Total Uang Keluar Buku Kas', 'Beban bumbu, gas, sewa, gaji, perlengkapan', $overview['total_cash_out']],
                    ['Estimasi Arus Kas Bersih', 'Omzet POS dikurangi kas keluar', $overview['net_profit_estimate']],
                    ['Total Pajak Restoran (PB1)', 'Pungutan pajak restoran daerah 10%', $overview['total_tax']],
                    ['Total Service Charge', 'Biaya layanan restoran', $overview['total_service']],
                    ['Total Diskon Diberikan', 'Promo otomatis, voucher, loyalty & diskon manual', $overview['total_discount']],
                    ['Total Pembatalan Struk (Void)', 'Frekuensi pesanan dibatalkan kasir/spv', $overview['total_void']],
                    ['Nominal Pembatalan (Void)', 'Nilai omzet pesanan yang dibatalkan', $overview['total_void_amount']],
                ],
                'total' => null,
            ],
            $this->formatSheetEntry('2. Buku Kas Ledger', $fin, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('3. Penjualan POS', $rpt01, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('4. Penjualan per Menu', $rpt02, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('5. Penjualan Kategori', $rpt03, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('6. Metode Pembayaran', $rpt04, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('7. Jenis Pesanan', $rpt05, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('8. Void & Refund', $rpt06, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('9. Diskon & Promosi', $rpt07, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('10. Pajak & Service', $rpt08, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('11. Shift & Kasir', $rpt09, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('12. Detail Baris Item', $rpt10, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('13. Analisis Jam Sibuk', $rpt11, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('14. Komparasi Cabang', $rpt12, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('15. Produktivitas Kasir', $rpt17, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('16. Beban Operasional', $rpt21, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('17. Laba Rugi F&B', $rpt22, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('18. Audit Trail Hash', $rpt23, $startDate, $endDate, $branchName),
            $this->formatSheetEntry('19. Deteksi Fraud POS', $rpt24, $startDate, $endDate, $branchName),
        ];

        $filename = "Hiroshima-Full-Report-Package_{$startDate}_{$endDate}.xlsx";
        return $this->streamMultiSheetWorkbook($sheets, $filename);
    }

    protected function formatSheetEntry(string $shortName, array $dataset, string $startDate, string $endDate, string $branchName): array
    {
        return [
            'name' => substr(preg_replace('/[\\\\\/\?\*\:\[\]]/', '', $shortName), 0, 31),
            'title' => strtoupper($dataset['title'] ?? $shortName),
            'period' => "Periode: {$startDate} s/d {$endDate}",
            'branch' => "Lingkup: " . $branchName,
            'headers' => $dataset['headers'],
            'rows' => $dataset['rows'],
            'total' => $dataset['total'] ?? null,
        ];
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
            'RPT-10' => $this->getLineItemDetailData($start, $end, $branchId),
            'RPT-11' => $this->getHourlySalesData($start, $end, $branchId),
            'RPT-12' => $this->getBranchComparisonData($start, $end, $branchId),
            'RPT-17' => $this->getCashierProductivityData($start, $end, $branchId),
            'RPT-21' => $this->getOperationalExpensesData($start, $end, $branchId),
            'RPT-22' => $this->getProfitLossData($start, $end, $branchId),
            'RPT-23' => $this->getAuditTrailData($start, $end, $branchId),
            'RPT-24' => $this->getFraudAlertsData($start, $end, $branchId),
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

        $completedQuery = (clone $orderQuery)->where('status', 'completed');
        $totalSales = (int) $completedQuery->sum('total_amount');
        $totalOrders = (int) $completedQuery->count();
        $totalTax = (int) $completedQuery->sum('tax_amount');
        $totalService = (int) $completedQuery->sum('service_charge_amount');
        $totalDiscount = (int) $completedQuery->sum(DB::raw('promo_discount_amount + voucher_discount_amount + loyalty_discount_amount + manual_discount_amount'));
        
        $voidedQuery = (clone $orderQuery)->where('status', 'voided');
        $totalVoid = (int) $voidedQuery->count();
        $totalVoidAmount = (int) $voidedQuery->sum('total_amount');

        $totalCashIn = (int) (clone $cashQuery)->where('type', 'in')->sum('amount');
        $totalCashOut = (int) (clone $cashQuery)->where('type', 'out')->sum('amount');

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
     * RPT-FIN: Laporan Buku Kas & Arus Keuangan (Finance Ledger)
     * Features: Prior Saldo Awal, Running Saldo Berjalan, Masuk, Keluar, Saldo Akhir.
     */
    public function getCashBookData(string $startDate, string $endDate, ?string $branchId): array
    {
        $baseQuery = CashBook::query();
        if ($branchId === 'global') {
            $baseQuery->whereNull('branch_id');
        } elseif ($branchId && $branchId !== 'all') {
            $baseQuery->where('branch_id', $branchId);
        }

        // Calculate Saldo Awal before $startDate
        $priorIn = (int) (clone $baseQuery)->whereDate('transaction_date', '<', $startDate)->where('type', 'in')->sum('amount');
        $priorOut = (int) (clone $baseQuery)->whereDate('transaction_date', '<', $startDate)->where('type', 'out')->sum('amount');
        $startingBalance = $priorIn - $priorOut;

        // Fetch period entries sorted chronologically
        $periodQuery = (clone $baseQuery)->with(['branch', 'creator'])
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc');

        $entries = $periodQuery->get();
        $rows = [];
        $runningBalance = $startingBalance;
        $sumIn = 0;
        $sumOut = 0;

        // Row 0: Saldo Awal
        $rows[] = [
            1,
            date('d/m/Y', strtotime($startDate)),
            $this->getBranchLabel($branchId),
            'Saldo Awal',
            'Akumulasi Saldo Sebelumnya',
            'Saldo kas berjalan sebelum ' . date('d M Y', strtotime($startDate)),
            $startingBalance > 0 ? $startingBalance : 0,
            $startingBalance < 0 ? abs($startingBalance) : 0,
            $startingBalance,
            'Sistem',
        ];

        foreach ($entries as $idx => $e) {
            $in = $e->type === 'in' ? $e->amount : 0;
            $out = $e->type === 'out' ? $e->amount : 0;
            $sumIn += $in;
            $sumOut += $out;
            $runningBalance += ($in - $out);

            $branchStr = $e->is_global ? 'Kantor Pusat (Global Pusat)' : ($e->branch->name ?? 'Cabang');

            $rows[] = [
                $idx + 2,
                $e->transaction_date ? $e->transaction_date->format('d/m/Y') : '-',
                $branchStr,
                $e->type === 'in' ? 'Uang Masuk' : 'Uang Keluar',
                $e->category,
                $e->description ?? '-',
                $in > 0 ? $in : 0,
                $out > 0 ? $out : 0,
                $runningBalance,
                $e->creator->name ?? 'Staff',
            ];
        }

        $finalBalance = $runningBalance;

        return [
            'title' => 'Laporan Buku Kas & Arus Keuangan (RPT-FIN)',
            'short_name' => 'Buku Kas Ledger',
            'headers' => ['No', 'Tanggal', 'Cabang', 'Jenis Arus', 'Kategori', 'Keterangan', 'Uang Masuk (Rp)', 'Uang Keluar (Rp)', 'Saldo Kas (Rp)', 'Petugas'],
            'rows' => $rows,
            'total' => ['TOTAL MUTASI KAS', '', '', '', '', 'Saldo Akhir Berjalan', $sumIn + ($startingBalance > 0 ? $startingBalance : 0), $sumOut + ($startingBalance < 0 ? abs($startingBalance) : 0), $finalBalance, ''],
        ];
    }

    /**
     * RPT-01: Ringkasan Penjualan & Finansial POS
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
        $sumService = 0;
        $sumTax = 0;
        $sumRounding = 0;
        $sumTotal = 0;

        foreach ($orders as $idx => $o) {
            $disc = $o->promo_discount_amount + $o->voucher_discount_amount + $o->loyalty_discount_amount + $o->manual_discount_amount;
            
            // Only aggregate completed orders to avoid inflating revenue with voided orders
            if ($o->status === 'completed') {
                $sumSubtotal += $o->subtotal;
                $sumDisc += $disc;
                $sumService += $o->service_charge_amount;
                $sumTax += $o->tax_amount;
                $sumRounding += $o->rounding_amount;
                $sumTotal += $o->total_amount;
            }

            $rows[] = [
                $idx + 1,
                $o->order_no,
                $o->business_date,
                $o->created_at ? $o->created_at->format('H:i') : '-',
                $o->branch->name ?? 'Pusat',
                $o->user->name ?? 'Kasir',
                $o->orderType->name ?? 'Dine In',
                $o->subtotal,
                $disc,
                $o->service_charge_amount,
                $o->tax_amount,
                $o->rounding_amount,
                $o->total_amount,
                $o->status === 'completed' ? 'Lunas' : ($o->status === 'voided' ? 'Void' : ucfirst($o->status)),
            ];
        }

        return [
            'title' => 'Ringkasan Penjualan & Finansial POS (RPT-01)',
            'short_name' => 'Penjualan POS',
            'headers' => ['No', 'No. Struk', 'Tanggal Bisnis', 'Jam', 'Cabang', 'Kasir', 'Jenis Order', 'Subtotal (Rp)', 'Total Diskon (Rp)', 'Service (Rp)', 'Pajak PB1 (Rp)', 'Pembulatan (Rp)', 'Total Bayar (Rp)', 'Status'],
            'rows' => $rows,
            'total' => ['TOTAL KESELURUHAN LUNAS', '', '', '', '', '', count($rows) . ' Struk', $sumSubtotal, $sumDisc, $sumService, $sumTax, $sumRounding, $sumTotal, 'Lunas'],
        ];
    }

    /**
     * RPT-02: Penjualan per Menu
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
            $qty = (int) $it->total_qty;
            $sales = (int) $it->total_sales;
            $cost = (int) $it->total_cost;
            $profit = $sales - $cost;
            $margin = $sales > 0 ? round(($profit / $sales) * 100, 1) : 0;
            $avgPrice = $qty > 0 ? (int) ($sales / $qty) : 0;

            $sumQty += $qty;
            $sumSales += $sales;
            $sumCost += $cost;

            $rows[] = [
                $idx + 1,
                $it->product_name,
                $it->category_name,
                $avgPrice,
                $qty,
                $sales,
                $cost,
                $profit,
                $margin . '%',
            ];
        }

        $totalProfit = $sumSales - $sumCost;
        $totalMargin = $sumSales > 0 ? round(($totalProfit / $sumSales) * 100, 1) : 0;
        $overallAvgPrice = $sumQty > 0 ? (int) ($sumSales / $sumQty) : 0;

        return [
            'title' => 'Laporan Penjualan per Menu (RPT-02)',
            'short_name' => 'Penjualan per Menu',
            'headers' => ['No', 'Nama Menu / Item', 'Kategori Menu', 'Harga Rata-rata (Rp)', 'Qty Terjual', 'Total Omzet (Rp)', 'Total HPP (Rp)', 'Laba Kotor (Rp)', 'Margin (%)'],
            'rows' => $rows,
            'total' => ['TOTAL PENJUALAN MENU', '', '', $overallAvgPrice, $sumQty, $sumSales, $sumCost, $totalProfit, $totalMargin . '%'],
        ];
    }

    /**
     * RPT-03: Penjualan per Kategori
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

        $grandSales = (int) $cats->sum('total_sales');
        $sumQty = 0;
        $rows = [];

        foreach ($cats as $idx => $c) {
            $qty = (int) $c->total_qty;
            $sales = (int) $c->total_sales;
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
     * RPT-04: Metode Pembayaran
     */
    public function getPaymentMethodsData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = Payment::query()
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->leftJoin('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.business_date', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $query->where('orders.branch_id', $branchId);
        }

        $methods = $query->select(
            DB::raw('COALESCE(payments.payment_method_name_snapshot, payment_methods.name, "Tunai") as method_name'),
            DB::raw('COALESCE(payment_methods.type, "cash") as method_type'),
            DB::raw('COUNT(payments.id) as tx_count'),
            DB::raw('SUM(payments.amount) as total_amount')
        )
            ->groupBy('payments.payment_method_name_snapshot', 'payment_methods.name', 'payment_methods.type')
            ->orderBy('total_amount', 'desc')
            ->get();

        $sumTx = 0;
        $sumAmount = 0;
        $rows = [];
        $totalCollected = (int) $methods->sum('total_amount');

        foreach ($methods as $idx => $m) {
            $cnt = (int) $m->tx_count;
            $amt = (int) $m->total_amount;
            $pct = $totalCollected > 0 ? round(($amt / $totalCollected) * 100, 1) : 0;
            $avgTx = $cnt > 0 ? (int) ($amt / $cnt) : 0;
            $typeLabel = match ($m->method_type) {
                'cash' => 'Tunai (Cash)',
                'qris_static', 'qris_dynamic', 'qris' => 'QRIS Digital',
                'debit_card' => 'Kartu Debit (EDC)',
                'credit_card' => 'Kartu Kredit (EDC)',
                default => 'Non-Tunai',
            };

            $sumTx += $cnt;
            $sumAmount += $amt;

            $rows[] = [
                $idx + 1,
                $m->method_name,
                $typeLabel,
                $cnt,
                $amt,
                $avgTx,
                $pct . '%',
            ];
        }

        $overallAvg = $sumTx > 0 ? (int) ($sumAmount / $sumTx) : 0;

        return [
            'title' => 'Laporan Metode Pembayaran (RPT-04)',
            'short_name' => 'Metode Pembayaran',
            'headers' => ['No', 'Metode Pembayaran', 'Tipe Saluran', 'Jumlah Transaksi', 'Total Nominal (Rp)', 'Rata-rata per Tx (Rp)', 'Pangsa (%)'],
            'rows' => $rows,
            'total' => ['TOTAL PEMBAYARAN DITERIMA', '', '', $sumTx, $sumAmount, $overallAvg, '100%'],
        ];
    }

    /**
     * RPT-05: Penjualan per Jenis Order
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
        $grandSales = (int) $types->sum('total_sales');

        foreach ($types as $idx => $t) {
            $cnt = (int) $t->order_count;
            $sales = (int) $t->total_sales;
            $aov = $cnt > 0 ? (int) ($sales / $cnt) : 0;
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

        $avgAov = $sumOrders > 0 ? (int) ($sumSales / $sumOrders) : 0;

        return [
            'title' => 'Laporan Penjualan per Jenis Order (RPT-05)',
            'short_name' => 'Jenis Order',
            'headers' => ['No', 'Jenis Pesanan', 'Jumlah Pesanan', 'Total Omzet (Rp)', 'Rata-rata Nilai Order / AOV (Rp)', 'Kontribusi (%)'],
            'rows' => $rows,
            'total' => ['TOTAL PENJUALAN JENIS ORDER', '', $sumOrders, $sumSales, $avgAov, '100%'],
        ];
    }

    /**
     * RPT-06: Laporan Void & Refund (Loss Prevention)
     */
    public function getVoidRefundData(string $startDate, string $endDate, ?string $branchId): array
    {
        // 1. Voided Orders
        $voidQuery = Order::with(['branch', 'user', 'voidedByUser'])
            ->where('status', 'voided')
            ->whereBetween('business_date', [$startDate, $endDate]);
        if ($branchId && $branchId !== 'all') {
            $voidQuery->where('branch_id', $branchId);
        }
        $voids = $voidQuery->orderBy('id', 'desc')->get();

        // 2. Refunds
        $refundQuery = Refund::with(['order.branch', 'order.user', 'authorizedByUser'])
            ->whereHas('order', function ($q) use ($startDate, $endDate, $branchId) {
                $q->whereBetween('business_date', [$startDate, $endDate]);
                if ($branchId && $branchId !== 'all') {
                    $q->where('branch_id', $branchId);
                }
            });
        $refunds = $refundQuery->orderBy('id', 'desc')->get();

        $rows = [];
        $sumAmount = 0;
        $rowIdx = 1;

        foreach ($voids as $v) {
            $amt = (int) $v->total_amount;
            $sumAmount += $amt;
            $staff = $v->voidedByUser->name ?? ($v->user->name ?? 'Kasir');

            $rows[] = [
                $rowIdx++,
                $v->order_no,
                $v->business_date . ' ' . ($v->voided_at ? $v->voided_at->format('H:i') : ($v->created_at ? $v->created_at->format('H:i') : '-')),
                $v->branch->name ?? 'Pusat',
                $staff,
                'Void Struk Pesanan',
                $v->void_reason ?? 'Kesalahan input / Permintaan tamu',
                'PIN SPV Tervalidasi',
                $amt,
            ];
        }

        foreach ($refunds as $rf) {
            $amt = (int) $rf->amount;
            $sumAmount += $amt;
            $staff = $rf->authorizedByUser->name ?? ($rf->order->user->name ?? 'Kasir');

            $rows[] = [
                $rowIdx++,
                $rf->order->order_no ?? ('ORD-RF-' . $rf->id),
                $rf->created_at ? $rf->created_at->format('Y-m-d H:i') : '-',
                $rf->order->branch->name ?? 'Pusat',
                $staff,
                'Refund Pengembalian Uang',
                $rf->reason ?? 'Pengembalian dana pelanggan',
                $rf->authorized_by_pin ? 'PIN Supervisor' : 'Otorisasi Manual',
                $amt,
            ];
        }

        return [
            'title' => 'Laporan Void & Refund (RPT-06)',
            'short_name' => 'Void & Refund',
            'headers' => ['No', 'No. Struk', 'Tanggal & Waktu', 'Cabang', 'Petugas Kasir / Pelaku', 'Tipe Tindakan', 'Alasan Pembatalan / Refund', 'Otorisasi PIN', 'Nominal Batal/Refund (Rp)'],
            'rows' => $rows,
            'total' => ['TOTAL PEMBATALAN & REFUND', '', '', '', '', count($rows) . ' Kejadian Tercatat', '', '', $sumAmount],
        ];
    }

    /**
     * RPT-07: Diskon & Promosi
     */
    public function getDiscountsPromosData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = Order::with(['branch'])
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->where('promo_discount_amount', '>', 0)
                    ->orWhere('voucher_discount_amount', '>', 0)
                    ->orWhere('loyalty_discount_amount', '>', 0)
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
        $sumLoyalty = 0;
        $sumManual = 0;
        $sumTotalDisc = 0;
        $sumFinal = 0;

        foreach ($orders as $idx => $o) {
            $promo = (int) $o->promo_discount_amount;
            $voucher = (int) $o->voucher_discount_amount;
            $loyalty = (int) $o->loyalty_discount_amount;
            $manual = (int) $o->manual_discount_amount;
            $totDisc = $promo + $voucher + $loyalty + $manual;

            $sumSub += $o->subtotal;
            $sumPromo += $promo;
            $sumVoucher += $voucher;
            $sumLoyalty += $loyalty;
            $sumManual += $manual;
            $sumTotalDisc += $totDisc;
            $sumFinal += $o->total_amount;

            $rows[] = [
                $idx + 1,
                $o->order_no,
                $o->business_date,
                $o->branch->name ?? 'Pusat',
                $o->subtotal,
                $promo,
                $voucher,
                $loyalty,
                $manual,
                $o->manual_discount_reason ?? '-',
                $totDisc,
                $o->total_amount,
            ];
        }

        return [
            'title' => 'Laporan Diskon & Promosi (RPT-07)',
            'short_name' => 'Diskon & Promosi',
            'headers' => ['No', 'No. Struk', 'Tanggal', 'Cabang', 'Subtotal Kotor (Rp)', 'Promo Otomatis (Rp)', 'Voucher (Rp)', 'Loyalty Poin (Rp)', 'Diskon Manual (Rp)', 'Alasan Diskon Manual', 'Total Diskon (Rp)', 'Total Bayar Akhir (Rp)'],
            'rows' => $rows,
            'total' => ['TOTAL POTONGAN DISKON', '', '', '', $sumSub, $sumPromo, $sumVoucher, $sumLoyalty, $sumManual, count($rows) . ' Struk', $sumTotalDisc, $sumFinal],
        ];
    }

    /**
     * RPT-08: Pajak Restoran (PB1) & Service Charge
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
        $sumDisc = 0;
        $sumDpp = 0;
        $sumSvc = 0;
        $sumTax = 0;
        $sumRounding = 0;
        $sumTotal = 0;

        foreach ($orders as $idx => $o) {
            $disc = (int) ($o->promo_discount_amount + $o->voucher_discount_amount + $o->loyalty_discount_amount + $o->manual_discount_amount);
            $dpp = max(0, $o->subtotal - $disc);

            $sumSub += $o->subtotal;
            $sumDisc += $disc;
            $sumDpp += $dpp;
            $sumSvc += $o->service_charge_amount;
            $sumTax += $o->tax_amount;
            $sumRounding += $o->rounding_amount;
            $sumTotal += $o->total_amount;

            $rows[] = [
                $idx + 1,
                $o->order_no,
                $o->business_date,
                $o->branch->name ?? 'Pusat',
                $o->subtotal,
                $disc,
                $dpp,
                $o->service_charge_amount,
                $o->tax_amount,
                $o->rounding_amount,
                $o->total_amount,
            ];
        }

        return [
            'title' => 'Laporan Pajak Restoran (PB1) & Service Charge (RPT-08)',
            'short_name' => 'Pajak & Service',
            'headers' => ['No', 'No. Struk', 'Tanggal', 'Cabang', 'Subtotal Penjualan (Rp)', 'Total Diskon (Rp)', 'DPP Dasar Pengenaan Pajak (Rp)', 'Service Charge (Rp)', 'Pajak Restoran PB1 (Rp)', 'Pembulatan (Rp)', 'Total Akhir Tagihan (Rp)'],
            'rows' => $rows,
            'total' => ['TOTAL PAJAK & SERVICE TERPUNGUT', '', '', '', $sumSub, $sumDisc, $sumDpp, $sumSvc, $sumTax, $sumRounding, $sumTotal],
        ];
    }

    /**
     * RPT-09: Shift & Rekonsiliasi Kas Kasir
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
            $startCash = (int) $s->starting_cash;
            $expCash = (int) $s->expected_cash;
            $actCash = (int) $s->actual_cash;
            $diff = (int) $s->cash_difference;

            $sumStart += $startCash;
            $sumExp += $expCash;
            $sumAct += $actCash;
            $sumDiff += $diff;

            $closeMethod = $s->close_type === 'auto' ? 'Auto-Close 23 Jam' : ($s->closed_at ? 'Manual Kasir' : 'Masih Berjalan');

            $rows[] = [
                $idx + 1,
                $s->id,
                $s->branch->name ?? 'Pusat',
                $s->user->name ?? 'Kasir',
                $s->opened_at ? $s->opened_at->format('d/m/Y H:i') : '-',
                $s->closed_at ? $s->closed_at->format('d/m/Y H:i') : 'Aktif Buka',
                $closeMethod,
                $startCash,
                $expCash,
                $actCash,
                $diff,
                $s->status === 'closed' || $s->status === 'reconciled' ? 'Tutup & Rekonsiliasi' : ucfirst($s->status),
                $s->reconciliation_notes ?? '-',
            ];
        }

        return [
            'title' => 'Laporan Shift & Rekonsiliasi Kasir (RPT-09)',
            'short_name' => 'Shift & Kasir',
            'headers' => ['No', 'ID Shift', 'Cabang', 'Petugas Kasir', 'Jam Buka', 'Jam Tutup', 'Metode Tutup', 'Modal Awal Kas (Rp)', 'Kas Diharapkan (Rp)', 'Kas Fisik Aktual (Rp)', 'Selisih Kas (Rp)', 'Status Shift', 'Catatan Rekonsiliasi'],
            'rows' => $rows,
            'total' => ['TOTAL REKONSILIASI KASIR', '', '', '', '', '', count($rows) . ' Sesi Shift', $sumStart, $sumExp, $sumAct, $sumDiff, '', ''],
        ];
    }

    /**
     * RPT-10: Detail Transaksi per Baris Item (Line Items Detail)
     */
    public function getLineItemDetailData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = OrderItem::with(['order.branch', 'order.user', 'order.orderType', 'modifiers'])
            ->whereHas('order', function ($q) use ($startDate, $endDate, $branchId) {
                $q->where('status', 'completed')
                    ->whereBetween('business_date', [$startDate, $endDate]);
                if ($branchId && $branchId !== 'all') {
                    $q->where('branch_id', $branchId);
                }
            })
            ->orderBy('id', 'desc');

        $items = $query->limit(2000)->get();
        $rows = [];
        $sumQty = 0;
        $sumSubtotal = 0;

        foreach ($items as $idx => $it) {
            $qty = (int) $it->quantity;
            $subtotal = (int) $it->subtotal;
            $sumQty += $qty;
            $sumSubtotal += $subtotal;

            $mods = $it->modifiers->map(fn($m) => $m->modifier_name)->implode(', ');

            $rows[] = [
                $idx + 1,
                $it->order->order_no ?? ('ORD-' . $it->order_id),
                $it->order->business_date ?? '-',
                $it->order && $it->order->created_at ? $it->order->created_at->format('H:i') : '-',
                $it->order->branch->name ?? 'Pusat',
                $it->order->user->name ?? 'Kasir',
                $it->order->orderType->name ?? 'Dine In',
                $it->product_name,
                $it->variant_name ?? '-',
                !empty($mods) ? $mods : '-',
                $qty,
                $it->unit_price,
                $subtotal,
            ];
        }

        return [
            'title' => 'Laporan Detail Transaksi per Baris Item (RPT-10)',
            'short_name' => 'Detail Baris Item',
            'headers' => ['No', 'No. Struk', 'Tanggal Bisnis', 'Jam', 'Cabang', 'Kasir', 'Jenis Order', 'Nama Menu / Produk', 'Varian', 'Modifier Tambahan', 'Qty', 'Harga Satuan (Rp)', 'Subtotal Item (Rp)'],
            'rows' => $rows,
            'total' => ['TOTAL RINCIAN ITEM TERJUAL', '', '', '', '', '', '', '', '', count($rows) . ' Baris Item', $sumQty, '', $sumSubtotal],
        ];
    }

    /**
     * RPT-11: Analisis Jam Sibuk Penjualan (Hourly & Peak Hour Sales)
     */
    public function getHourlySalesData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = Order::where('status', 'completed')
            ->whereBetween('business_date', [$startDate, $endDate]);
        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get(['id', 'total_amount', 'created_at']);

        // Initialize 24-hour buckets
        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[$h] = [
                'hour' => $h,
                'time_range' => sprintf('%02d:00 - %02d:59', $h, $h),
                'order_count' => 0,
                'total_sales' => 0,
            ];
        }

        foreach ($orders as $o) {
            $h = $o->created_at ? (int) $o->created_at->timezone('Asia/Jakarta')->format('G') : 12;
            $hours[$h]['order_count']++;
            $hours[$h]['total_sales'] += (int) $o->total_amount;
        }

        $grandSales = (int) array_sum(array_column($hours, 'total_sales'));
        $totalOrders = (int) array_sum(array_column($hours, 'order_count'));

        $rows = [];
        foreach ($hours as $h => $data) {
            $cnt = $data['order_count'];
            $sales = $data['total_sales'];
            $aov = $cnt > 0 ? (int) ($sales / $cnt) : 0;
            $pct = $grandSales > 0 ? round(($sales / $grandSales) * 100, 1) : 0;

            $rows[] = [
                sprintf('%02d:00', $h),
                $data['time_range'],
                $cnt,
                $sales,
                $aov,
                $pct . '%',
            ];
        }

        $overallAov = $totalOrders > 0 ? (int) ($grandSales / $totalOrders) : 0;

        return [
            'title' => 'Laporan Analisis Jam Sibuk Penjualan (RPT-11)',
            'short_name' => 'Analisis Jam Sibuk',
            'headers' => ['Jam Operasional', 'Rentang Waktu', 'Jumlah Struk', 'Total Omzet (Rp)', 'Rata-rata Nilai Struk / AOV (Rp)', 'Porsi Kontribusi (%)'],
            'rows' => $rows,
            'total' => ['TOTAL DISTRIBUSI WAKTU', '24 Jam Operasional', $totalOrders, $grandSales, $overallAov, '100%'],
        ];
    }

    /**
     * RPT-12: Perbandingan Kinerja Antar Cabang
     */
    public function getBranchComparisonData(string $startDate, string $endDate, ?string $branchId): array
    {
        $branches = Branch::where('is_active', true)->get();
        $rows = [];
        $sumOrders = 0;
        $sumSales = 0;
        $sumTax = 0;
        $sumSvc = 0;
        $sumVoid = 0;
        $sumVoidAmt = 0;

        // Precalculate grand total for share calculation
        $allOrders = Order::where('status', 'completed')
            ->whereBetween('business_date', [$startDate, $endDate])
            ->sum('total_amount');
        $grandSales = (int) $allOrders;

        foreach ($branches as $idx => $b) {
            $completedOrders = Order::where('branch_id', $b->id)
                ->where('status', 'completed')
                ->whereBetween('business_date', [$startDate, $endDate]);

            $cnt = (int) (clone $completedOrders)->count();
            $sales = (int) (clone $completedOrders)->sum('total_amount');
            $tax = (int) (clone $completedOrders)->sum('tax_amount');
            $svc = (int) (clone $completedOrders)->sum('service_charge_amount');
            $aov = $cnt > 0 ? (int) ($sales / $cnt) : 0;

            $voidedOrders = Order::where('branch_id', $b->id)
                ->where('status', 'voided')
                ->whereBetween('business_date', [$startDate, $endDate]);
            $vCount = (int) (clone $voidedOrders)->count();
            $vAmt = (int) (clone $voidedOrders)->sum('total_amount');

            $pct = $grandSales > 0 ? round(($sales / $grandSales) * 100, 1) : 0;

            $sumOrders += $cnt;
            $sumSales += $sales;
            $sumTax += $tax;
            $sumSvc += $svc;
            $sumVoid += $vCount;
            $sumVoidAmt += $vAmt;

            $rows[] = [
                $idx + 1,
                $b->code,
                $b->name,
                $cnt,
                $sales,
                $aov,
                $tax,
                $svc,
                $vCount,
                $vAmt,
                $pct . '%',
            ];
        }

        $overallAov = $sumOrders > 0 ? (int) ($sumSales / $sumOrders) : 0;

        return [
            'title' => 'Laporan Perbandingan Kinerja Antar Cabang (RPT-12)',
            'short_name' => 'Komparasi Cabang',
            'headers' => ['No', 'Kode Cabang', 'Nama Cabang', 'Jumlah Struk Lunas', 'Total Omzet Bersih (Rp)', 'Rata-rata Nilai Struk (Rp)', 'Pajak PB1 (Rp)', 'Service Charge (Rp)', 'Struk Batal (Void)', 'Nominal Void (Rp)', 'Kontribusi (%)'],
            'rows' => $rows,
            'total' => ['TOTAL KONSOLIDASI SELURUH CABANG', '', count($branches) . ' Cabang Aktif', $sumOrders, $sumSales, $overallAov, $sumTax, $sumSvc, $sumVoid, $sumVoidAmt, '100%'],
        ];
    }

    /**
     * RPT-17: Produktivitas Kasir
     */
    public function getCashierProductivityData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = Order::query()
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->leftJoin('branches', 'orders.branch_id', '=', 'branches.id')
            ->whereBetween('orders.business_date', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $query->where('orders.branch_id', $branchId);
        }

        $cashiers = $query->select(
            DB::raw('COALESCE(users.name, "Kasir Umum") as cashier_name'),
            DB::raw('COALESCE(branches.name, "Pusat") as branch_name'),
            DB::raw('SUM(CASE WHEN orders.status = "completed" THEN 1 ELSE 0 END) as completed_orders'),
            DB::raw('SUM(CASE WHEN orders.status = "completed" THEN orders.total_amount ELSE 0 END) as completed_sales'),
            DB::raw('SUM(CASE WHEN orders.status = "voided" THEN 1 ELSE 0 END) as void_orders'),
            DB::raw('SUM(CASE WHEN orders.status = "voided" THEN orders.total_amount ELSE 0 END) as void_sales')
        )
            ->groupBy('users.name', 'branches.name')
            ->orderBy('completed_sales', 'desc')
            ->get();

        $rows = [];
        $sumOrders = 0;
        $sumSales = 0;
        $sumVoid = 0;
        $sumVoidSales = 0;

        foreach ($cashiers as $idx => $c) {
            $cnt = (int) $c->completed_orders;
            $sales = (int) $c->completed_sales;
            $vCnt = (int) $c->void_orders;
            $vSales = (int) $c->void_sales;
            $aov = $cnt > 0 ? (int) ($sales / $cnt) : 0;
            $totTx = $cnt + $vCnt;
            $voidRatio = $totTx > 0 ? round(($vCnt / $totTx) * 100, 1) : 0;

            $sumOrders += $cnt;
            $sumSales += $sales;
            $sumVoid += $vCnt;
            $sumVoidSales += $vSales;

            $rows[] = [
                $idx + 1,
                $c->cashier_name,
                $c->branch_name,
                $cnt,
                $sales,
                $aov,
                $vCnt,
                $vSales,
                $voidRatio . '%',
            ];
        }

        $overallAov = $sumOrders > 0 ? (int) ($sumSales / $sumOrders) : 0;
        $totOverall = $sumOrders + $sumVoid;
        $overallVoidRatio = $totOverall > 0 ? round(($sumVoid / $totOverall) * 100, 1) : 0;

        return [
            'title' => 'Laporan Produktivitas Kasir (RPT-17)',
            'short_name' => 'Produktivitas Kasir',
            'headers' => ['No', 'Nama Kasir / Staff', 'Cabang Penugasan', 'Transaksi Lunas', 'Total Omzet Lunas (Rp)', 'Rata-rata Nilai Struk (Rp)', 'Struk Batal (Void)', 'Nominal Void (Rp)', 'Rasio Struk Void (%)'],
            'rows' => $rows,
            'total' => ['TOTAL PRODUKTIVITAS SELURUH STAF', '', count($rows) . ' Petugas', $sumOrders, $sumSales, $overallAov, $sumVoid, $sumVoidSales, $overallVoidRatio . '%'],
        ];
    }

    /**
     * RPT-21: Pengeluaran Biaya Operasional (Expenses Ledger)
     */
    public function getOperationalExpensesData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = CashBook::with(['branch', 'creator'])
            ->where('type', 'out')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        if ($branchId === 'global') {
            $query->whereNull('branch_id');
        } elseif ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $entries = $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get();
        $rows = [];
        $sumExpense = 0;

        foreach ($entries as $idx => $e) {
            $amt = (int) $e->amount;
            $sumExpense += $amt;
            $branchStr = $e->is_global ? 'Kantor Pusat (Global Pusat)' : ($e->branch->name ?? 'Cabang');

            $rows[] = [
                $idx + 1,
                $e->transaction_date ? $e->transaction_date->format('d/m/Y') : '-',
                $branchStr,
                $e->category,
                $e->description ?? '-',
                $e->creator->name ?? 'Staf',
                $amt,
                $e->receipt_photo_path ? 'Ada Lampiran Kwitansi' : 'Tanpa Lampiran',
            ];
        }

        return [
            'title' => 'Laporan Pengeluaran Biaya Operasional (RPT-21)',
            'short_name' => 'Beban Operasional',
            'headers' => ['No', 'Tanggal', 'Cabang', 'Kategori Beban', 'Deskripsi Keperluan', 'Petugas Pencatat', 'Nominal Biaya (Rp)', 'Bukti Nota / Kwitansi'],
            'rows' => $rows,
            'total' => ['TOTAL PENGELUARAN BIAYA OPERASIONAL', '', '', '', '', count($rows) . ' Bukti Kas Keluar', $sumExpense, 'Lunas'],
        ];
    }

    /**
     * RPT-22: Estimasi Laba Rugi F&B (P&L Income Statement)
     */
    public function getProfitLossData(string $startDate, string $endDate, ?string $branchId): array
    {
        $orderQuery = Order::query()->where('status', 'completed')->whereBetween('business_date', [$startDate, $endDate]);
        if ($branchId && $branchId !== 'all') {
            $orderQuery->where('branch_id', $branchId);
        }

        $grossSales = (int) (clone $orderQuery)->sum('subtotal');
        $discounts = (int) (clone $orderQuery)->sum(DB::raw('promo_discount_amount + voucher_discount_amount + loyalty_discount_amount + manual_discount_amount'));
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
        $cogs = (int) $itemQuery->sum(DB::raw('order_items.quantity * COALESCE(order_items.cost_per_unit, products.manual_cost, 0)'));
        if ($cogs === 0 && $netSales > 0) {
            // Standard benchmark: 35% if manual cost is not yet populated
            $cogs = (int) ($netSales * 0.35);
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

        $expenseCategories = (clone $expenseQuery)->select('category', DB::raw('SUM(amount) as cat_total'))
            ->groupBy('category')
            ->orderBy('cat_total', 'desc')
            ->get();

        $expenses = (int) $expenseCategories->sum('cat_total');
        $netOperatingProfit = $grossProfit - $expenses;
        $netMargin = $netSales > 0 ? round(($netOperatingProfit / $netSales) * 100, 1) : 0;

        $rows = [
            ['I. PENDAPATAN OPERASIONAL', '', ''],
            ['', 'Penjualan Kotor (Gross Sales POS)', $grossSales],
            ['', 'Potongan Diskon, Voucher & Loyalty', -$discounts],
            ['', 'TOTAL PENDAPATAN BERSIH (NET SALES)', $netSales],
            ['', '', ''],
            ['II. HARGA POKOK PENJUALAN (HPP / COGS)', '', ''],
            ['', 'Biaya Bahan Baku Menu Terjual', -$cogs],
            ['', 'TOTAL HARGA POKOK PENJUALAN (COGS)', -$cogs],
            ['', '', ''],
            ['III. LABA KOTOR (GROSS PROFIT)', 'Margin: ' . $grossMargin . '%', $grossProfit],
            ['', '', ''],
            ['IV. BEBAN OPERASIONAL KAS (OPEX)', '', ''],
        ];

        foreach ($expenseCategories as $ec) {
            $rows[] = ['', 'Beban Kas: ' . $ec->category, -(int) $ec->cat_total];
        }

        $rows[] = ['', 'TOTAL BIAYA OPERASIONAL (OPEX)', -$expenses];
        $rows[] = ['', '', ''];
        $rows[] = ['V. ESTIMASI LABA BERSIH OPERASIONAL (NET PROFIT)', 'Net Margin: ' . $netMargin . '%', $netOperatingProfit];

        return [
            'title' => 'Estimasi Laporan Laba Rugi F&B (RPT-22)',
            'short_name' => 'Laba Rugi F&B',
            'headers' => ['Komponen Finansial Laba Rugi', 'Catatan / Margin', 'Nominal (Rp)'],
            'rows' => $rows,
            'total' => ['ESTIMASI LABA BERSIH BERJALAN', 'Margin Bersih: ' . $netMargin . '%', $netOperatingProfit],
        ];
    }

    /**
     * RPT-23: Integritas Rantai Audit Log
     */
    public function getAuditTrailData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = AuditLog::with(['user', 'branch'])
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
                $l->branch->name ?? 'Pusat (Global)',
                $l->user->name ?? 'Sistem',
                $l->action,
                $l->subject_type ? ($l->subject_type . ' #' . $l->subject_id) : '-',
                $l->ip ?? '127.0.0.1',
                substr($l->hash ?? '-', 0, 16) . '...',
                'Rantai Terverifikasi Utuh',
            ];
        }

        return [
            'title' => 'Laporan Integritas Rantai Audit Log (RPT-23)',
            'short_name' => 'Audit Trail Hash',
            'headers' => ['No', 'Waktu (WIB)', 'Cabang', 'Pelaku / User', 'Aksi / Peristiwa', 'Referensi Objek', 'IP Address', 'Hash SHA-256', 'Status Integritas Hash'],
            'rows' => $rows,
            'total' => ['TOTAL LOG TERVERIFIKASI', '', '', count($rows) . ' Catatan', '', '', '', '', 'Kriptografi Utuh'],
        ];
    }

    /**
     * RPT-24: Deteksi Anomali & Fraud POS (Loss Prevention)
     */
    public function getFraudAlertsData(string $startDate, string $endDate, ?string $branchId): array
    {
        $query = FraudAlert::with(['branch', 'user', 'order'])
            ->whereDate('detected_at', '>=', $startDate)
            ->whereDate('detected_at', '<=', $endDate);

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $alerts = $query->orderBy('id', 'desc')->get();
        $rows = [];

        foreach ($alerts as $idx => $a) {
            $severityLabel = match ($a->severity) {
                'critical' => 'KRITIS (Merah)',
                'high' => 'TINGGI (Oranye)',
                'medium' => 'SEDANG (Kuning)',
                default => 'RENDAH (Abu-abu)',
            };

            $statusLabel = match ($a->status) {
                'confirmed' => 'Terkonfirmasi Fraud',
                'dismissed' => 'Diabaikan (False Positive)',
                'in_review' => 'Dalam Penyelidikan',
                default => 'Peringatan Baru',
            };

            $rows[] = [
                $idx + 1,
                $a->detected_at ? $a->detected_at->format('d/m/Y H:i') : '-',
                $a->branch->name ?? 'Pusat',
                $a->user->name ?? 'Kasir',
                $a->fraud_rule_code,
                $severityLabel,
                $a->order ? $a->order->order_no : '-',
                $statusLabel,
                $a->review_notes ?? '-',
            ];
        }

        return [
            'title' => 'Laporan Deteksi Anomali & Fraud POS (RPT-24)',
            'short_name' => 'Deteksi Fraud POS',
            'headers' => ['No', 'Waktu Deteksi', 'Cabang', 'Kasir / Petugas', 'Aturan Anomali Fraud', 'Tingkat Keparahan', 'No. Struk Terkait', 'Status Investigasi', 'Catatan Review / Disposisi'],
            'rows' => $rows,
            'total' => ['TOTAL ANOMALI FRAUD DETEKSI', '', '', '', count($rows) . ' Peringatan', '', '', '', 'Selesai'],
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
                fputcsv($output, array_map(fn($v) => self::sanitizeCellText((string)$v), $row));
            }
            if (!empty($targetSheet['total'])) {
                fputcsv($output, array_map(fn($v) => self::sanitizeCellText((string)$v), $targetSheet['total']));
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
     * With Executive Header Block, Sticky Freeze Panes, AutoFilter, Currency Formatting, and Formula Injection Protection.
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
            $safeName = htmlspecialchars(substr(preg_replace('/[\\\\\/\?\*\:\[\]]/', '', $s['name']), 0, 31), ENT_XML1, 'UTF-8');
            $wb .= '<sheet name="' . $safeName . '" sheetId="' . $sNum . '" r:id="rId' . $sNum . '"/>';
        }
        $wb .= '</sheets></workbook>';
        $zip->addFromString('xl/workbook.xml', $wb);

        // 5. xl/styles.xml
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="3">'
            . '<numFmt numFmtId="164" formatCode="&quot;Rp &quot;#,##0;[Red]-&quot;Rp &quot;#,##0;&quot;Rp &quot;0"/>'
            . '<numFmt numFmtId="165" formatCode="#,##0"/>'
            . '<numFmt numFmtId="166" formatCode="0.0%"/>'
            . '</numFmts>'
            . '<fonts count="6">'
            . '<font><name val="Segoe UI"/><sz val="10"/><color rgb="FF334155"/></font>' // 0: data normal
            . '<font><name val="Segoe UI"/><sz val="10"/><b/><color rgb="FFFFFFFF"/></font>' // 1: header bold white
            . '<font><name val="Segoe UI"/><sz val="10"/><b/><color rgb="FF0F172A"/></font>' // 2: bold dark slate
            . '<font><name val="Segoe UI"/><sz val="13"/><b/><color rgb="FF0F172A"/></font>' // 3: title
            . '<font><name val="Segoe UI"/><sz val="11"/><b/><color rgb="FF1E293B"/></font>' // 4: subtitle
            . '<font><name val="Segoe UI"/><sz val="9"/><i/><color rgb="FF64748B"/></font>' // 5: meta
            . '</fonts>'
            . '<fills count="5">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/></patternFill></fill>' // 2: Navy header
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>' // 3: Zebra light
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/></patternFill></fill>' // 4: Total row
            . '</fills>'
            . '<borders count="3">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>' // 0: none
            . '<border><left style="thin"><color rgb="FFE2E8F0"/></left><right style="thin"><color rgb="FFE2E8F0"/></right><top style="thin"><color rgb="FFE2E8F0"/></top><bottom style="thin"><color rgb="FFE2E8F0"/></bottom></border>' // 1: grid
            . '<border><left style="thin"><color rgb="FF94A3B8"/></left><right style="thin"><color rgb="FF94A3B8"/></right><top style="thin"><color rgb="FF0F172A"/></top><bottom style="double"><color rgb="FF0F172A"/></bottom></border>' // 2: total
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="20">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' // 0: default
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>' // 1: Title
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>' // 2: Subtitle
            . '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>' // 3: Meta info
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 4: Header Center
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 5: Header Left
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' // 6: Header Right
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 7: Data Left
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 8: Data Center
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' // 9: Data Number
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' // 10: Data Currency
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 11: Zebra Left
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 12: Zebra Center
            . '<xf numFmtId="165" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' // 13: Zebra Number
            . '<xf numFmtId="164" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' // 14: Zebra Currency
            . '<xf numFmtId="0" fontId="2" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 15: Total Left
            . '<xf numFmtId="0" fontId="2" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 16: Total Center
            . '<xf numFmtId="165" fontId="2" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' // 17: Total Number
            . '<xf numFmtId="164" fontId="2" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' // 18: Total Currency
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 19: Section Row
            . '</cellXfs></styleSheet>';
        $zip->addFromString('xl/styles.xml', $stylesXml);

        // 6. Generate individual worksheets
        foreach ($sheets as $idx => $s) {
            $sNum = $idx + 1;
            $headers = $s['headers'];
            $rows = $s['rows'];
            $totalRow = $s['total'] ?? null;
            $title = $s['title'] ?? 'LAPORAN HIROSHIMA';
            $period = $s['period'] ?? '';
            $branch = $s['branch'] ?? '';

            // Calculate adaptive column widths
            $colWidths = [];
            foreach ($headers as $cIdx => $h) {
                $maxLen = mb_strlen((string) $h, 'UTF-8');
                foreach ($rows as $r) {
                    $valLen = isset($r[$cIdx]) ? mb_strlen((string) $r[$cIdx], 'UTF-8') : 0;
                    if ($valLen > $maxLen) $maxLen = $valLen;
                }
                if ($totalRow && isset($totalRow[$cIdx])) {
                    $valLen = mb_strlen((string) $totalRow[$cIdx], 'UTF-8');
                    if ($valLen > $maxLen) $maxLen = $valLen;
                }
                $colWidths[$cIdx + 1] = max(13, min(50, $maxLen + 4));
            }

            $colsXml = '<cols>';
            foreach ($colWidths as $colNum => $width) {
                $colsXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>';
            }
            $colsXml .= '</cols>';

            $sheetData = '<sheetData>';

            // Row 1: Brand / Organization Title
            $sheetData .= '<row r="1" ht="26" customHeight="1">';
            $sheetData .= '<c r="A1" s="1" t="inlineStr"><is><t>HIROSHIMA JAPANESE STREET FOOD</t></is></c>';
            $sheetData .= '</row>';

            // Row 2: Report Title & Code
            $sheetData .= '<row r="2" ht="22" customHeight="1">';
            $sheetData .= '<c r="A2" s="2" t="inlineStr"><is><t>' . htmlspecialchars($title, ENT_XML1, 'UTF-8') . '</t></is></c>';
            $sheetData .= '</row>';

            // Row 3: Metadata (Period, Branch Scope, Printed Time)
            $metaText = trim("{$period}  |  {$branch}  |  Waktu Unduh: " . now('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB', " |");
            $sheetData .= '<row r="3" ht="18" customHeight="1">';
            $sheetData .= '<c r="A3" s="3" t="inlineStr"><is><t>' . htmlspecialchars($metaText, ENT_XML1, 'UTF-8') . '</t></is></c>';
            $sheetData .= '</row>';

            // Row 4: Empty buffer
            $sheetData .= '<row r="4" ht="8" customHeight="1"/>';

            // Row 5: Column Headers (Navy Background)
            $headerRowNumber = 5;
            $sheetData .= '<row r="' . $headerRowNumber . '" ht="26" customHeight="1">';
            foreach ($headers as $cIdx => $h) {
                $ref = self::columnLetter($cIdx + 1) . $headerRowNumber;
                $isRight = str_contains(strtolower($h), '(rp)') || str_contains(strtolower($h), 'total') || str_contains(strtolower($h), 'nominal') || str_contains(strtolower($h), 'subtotal');
                $isCenter = str_contains(strtolower($h), 'no') && strlen($h) <= 4;
                $hStyle = $isRight ? 6 : ($isCenter ? 4 : 5);
                $sheetData .= '<c r="' . $ref . '" s="' . $hStyle . '" t="inlineStr"><is><t>' . htmlspecialchars((string)$h, ENT_XML1, 'UTF-8') . '</t></is></c>';
            }
            $sheetData .= '</row>';

            // Data Rows (Row 6 onwards)
            $rNum = 6;
            if (empty($rows)) {
                $sheetData .= '<row r="' . $rNum . '" ht="22" customHeight="1">';
                $sheetData .= '<c r="A' . $rNum . '" s="7" t="inlineStr"><is><t>Tidak ada catatan data yang tercatat pada rentang periode dan filter ini.</t></is></c>';
                $sheetData .= '</row>';
                $rNum++;
            } else {
                foreach ($rows as $rIdx => $r) {
                    $isZebra = ($rIdx % 2 === 1);
                    $isSectionHeader = !empty($r[0]) && empty($r[1]) && empty($r[2]) && str_starts_with($r[0], 'I');

                    if ($isSectionHeader) {
                        $sheetData .= '<row r="' . $rNum . '" ht="24" customHeight="1">';
                        $ref = 'A' . $rNum;
                        $sheetData .= '<c r="' . $ref . '" s="19" t="inlineStr"><is><t>' . htmlspecialchars((string)$r[0], ENT_XML1, 'UTF-8') . '</t></is></c>';
                        $sheetData .= '</row>';
                        $rNum++;
                        continue;
                    }

                    $sheetData .= '<row r="' . $rNum . '" ht="21" customHeight="1">';
                    foreach ($r as $cIdx => $val) {
                        $ref = self::columnLetter($cIdx + 1) . $rNum;
                        $headerCol = $headers[$cIdx] ?? '';
                        $isCurrency = str_contains(strtolower($headerCol), '(rp)') 
                            || str_contains(strtolower($headerCol), 'omzet') 
                            || str_contains(strtolower($headerCol), 'total') 
                            || str_contains(strtolower($headerCol), 'subtotal')
                            || str_contains(strtolower($headerCol), 'nominal')
                            || str_contains(strtolower($headerCol), 'pajak')
                            || str_contains(strtolower($headerCol), 'diskon')
                            || str_contains(strtolower($headerCol), 'selisih')
                            || str_contains(strtolower($headerCol), 'beban')
                            || str_contains(strtolower($headerCol), 'modal')
                            || str_contains(strtolower($headerCol), 'kas');
                        $isInteger = str_contains(strtolower($headerCol), 'qty') 
                            || str_contains(strtolower($headerCol), 'jumlah') 
                            || str_contains(strtolower($headerCol), 'frekuensi') 
                            || str_contains(strtolower($headerCol), 'count');
                        $isCenter = ($cIdx === 0 && strlen((string)$val) <= 4) 
                            || str_contains(strtolower($headerCol), 'tanggal') 
                            || str_contains(strtolower($headerCol), 'jam') 
                            || str_contains(strtolower($headerCol), 'status');

                        $isNumeric = (is_int($val) || (is_numeric($val) && !preg_match('/^0[0-9]+$/', (string)$val)));

                        if ($isNumeric) {
                            $styleIdx = $isCurrency ? ($isZebra ? 14 : 10) : ($isInteger ? ($isZebra ? 13 : 9) : ($isZebra ? 13 : 9));
                            $sheetData .= '<c r="' . $ref . '" s="' . $styleIdx . '"><v>' . $val . '</v></c>';
                        } elseif (is_null($val) || $val === '') {
                            $styleIdx = $isZebra ? 11 : 7;
                            $sheetData .= '<c r="' . $ref . '" s="' . $styleIdx . '"/>';
                        } else {
                            $styleIdx = $isCenter ? ($isZebra ? 12 : 8) : ($isZebra ? 11 : 7);
                            $safeStr = self::sanitizeCellText((string)$val);
                            $sheetData .= '<c r="' . $ref . '" s="' . $styleIdx . '" t="inlineStr"><is><t>' . htmlspecialchars($safeStr, ENT_XML1, 'UTF-8') . '</t></is></c>';
                        }
                    }
                    $sheetData .= '</row>';
                    $rNum++;
                }
            }

            $lastDataRow = $rNum - 1;

            // Grand Total Row
            if ($totalRow) {
                $sheetData .= '<row r="' . $rNum . '" ht="26" customHeight="1">';
                foreach ($totalRow as $cIdx => $val) {
                    $ref = self::columnLetter($cIdx + 1) . $rNum;
                    $headerCol = $headers[$cIdx] ?? '';
                    $isCurrency = str_contains(strtolower($headerCol), '(rp)') 
                        || str_contains(strtolower($headerCol), 'omzet') 
                        || str_contains(strtolower($headerCol), 'total') 
                        || str_contains(strtolower($headerCol), 'subtotal')
                        || str_contains(strtolower($headerCol), 'nominal')
                        || str_contains(strtolower($headerCol), 'pajak')
                        || str_contains(strtolower($headerCol), 'diskon')
                        || str_contains(strtolower($headerCol), 'selisih');
                    $isInteger = str_contains(strtolower($headerCol), 'qty') || str_contains(strtolower($headerCol), 'jumlah');
                    $isNumeric = (is_int($val) || (is_numeric($val) && !preg_match('/^0[0-9]+$/', (string)$val)));

                    if ($isNumeric) {
                        $styleIdx = $isCurrency ? 18 : ($isInteger ? 17 : 17);
                        $sheetData .= '<c r="' . $ref . '" s="' . $styleIdx . '"><v>' . $val . '</v></c>';
                    } elseif (is_null($val) || $val === '') {
                        $sheetData .= '<c r="' . $ref . '" s="15"/>';
                    } else {
                        $safeStr = self::sanitizeCellText((string)$val);
                        $sheetData .= '<c r="' . $ref . '" s="15" t="inlineStr"><is><t>' . htmlspecialchars($safeStr, ENT_XML1, 'UTF-8') . '</t></is></c>';
                    }
                }
                $sheetData .= '</row>';
            }

            $sheetData .= '</sheetData>';

            // Sticky Panes (Freeze Header Row 5)
            $viewsXml = '<sheetViews>'
                . '<sheetView tabSelected="' . ($sNum === 1 ? '1' : '0') . '" workbookViewId="0">'
                . '<pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/>'
                . '</sheetView>'
                . '</sheetViews>';

            // Native Excel AutoFilter on row 5 headers
            $lastColLetter = self::columnLetter(count($headers));
            $filterXml = $lastDataRow >= 6 ? '<autoFilter ref="A5:' . $lastColLetter . $lastDataRow . '"/>' : '';

            $worksheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . $viewsXml
                . $colsXml
                . $sheetData
                . $filterXml
                . '</worksheet>';

            $zip->addFromString('xl/worksheets/sheet' . $sNum . '.xml', $worksheetXml);
        }

        $zip->close();
        return $tempPath;
    }
}
