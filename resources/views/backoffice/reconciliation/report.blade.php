@extends('layouts.app', ['title' => 'Laporan Close Shift #' . $shift->id])

@section('content')
<style>
    .report-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 28px;
        max-width: 820px;
        margin: 0 auto 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .report-table {
        width: 100%;
        border-collapse: collapse;
        margin: 16px 0;
        font-size: 13.5px;
    }
    .report-table th, .report-table td {
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
    }
    .report-table th {
        background: #f8fafc;
        color: var(--text-muted);
        font-weight: 700;
        text-align: left;
    }
    .report-table .text-right {
        text-align: right;
    }
    .report-table .text-center {
        text-align: center;
    }
    .summary-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin: 16px 0;
    }
    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 12px;
        background: #f8fafc;
        border-radius: 8px;
        font-size: 13px;
    }
    .summary-item strong {
        font-weight: 700;
    }
    .badge-auto {
        background: #fee2e2;
        color: #dc2626;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
    }
    .badge-manual {
        background: #e0f2fe;
        color: #0369a1;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
    }
    @media print {
        body {
            background: #ffffff !important;
            padding: 0 !important;
        }
        .sidebar, .top-header, .mobile-bottom-nav, .no-print {
            display: none !important;
        }
        .main-content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .report-card {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            max-width: 100% !important;
        }
    }
</style>

<div class="no-print" style="max-width: 820px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <a href="{{ route('reconciliation.index') }}" class="btn btn-outline btn-sm">
            <x-icon name="arrow-left" size="14" /> Kembali ke Rekonsiliasi
        </a>
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <x-icon name="printer" size="16" /> Cetak Laporan Close Shift
        </button>
    </div>
</div>

<div class="report-card">
    <!-- Header Informasi Shift -->
    <div style="text-align: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 20px; margin-bottom: 20px;">
        <h1 style="font-size: 20px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">{{ $report['shift_info']['company_name'] }}</h1>
        <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">{{ $report['shift_info']['branch_name'] }} ({{ $report['shift_info']['branch_code'] }})</div>
        <div style="font-size: 16px; font-weight: 800; color: var(--primary); margin-top: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
            LAPORAN PENUTUPAN SHIFT (CLOSE SHIFT REPORT)
        </div>
        <div style="margin-top: 6px;">
            <span class="{{ $report['shift_info']['close_type'] === 'auto' ? 'badge-auto' : 'badge-manual' }}">
                Penutupan: {{ strtoupper($report['shift_info']['close_type']) }}
            </span>
        </div>
    </div>

    <!-- Informasi Detail Shift (Section C) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 13px; background: #f8fafc; padding: 14px; border-radius: 8px; margin-bottom: 24px;">
        <div><strong>ID Shift:</strong> #{{ $report['shift_info']['shift_id'] }}</div>
        <div><strong>Kasir:</strong> {{ $report['shift_info']['cashier_name'] }} ({{ $report['shift_info']['cashier_username'] }})</div>
        <div><strong>Waktu Buka:</strong> {{ \Carbon\Carbon::parse($report['shift_info']['opened_at'])->format('d/m/Y H:i:s') }}</div>
        <div><strong>Waktu Tutup:</strong> {{ $report['shift_info']['closed_at'] ? \Carbon\Carbon::parse($report['shift_info']['closed_at'])->format('d/m/Y H:i:s') : '-' }}</div>
        <div><strong>Waktu Cetak:</strong> {{ \Carbon\Carbon::parse($report['shift_info']['printed_at'])->format('d/m/Y H:i:s') }} WIB</div>
        <div><strong>Status Shift:</strong> {{ strtoupper($report['shift_info']['status']) }}</div>
    </div>

    <!-- A. Rekap Penjualan per Menu -->
    <div style="margin-bottom: 28px;">
        <h2 style="font-size: 15px; font-weight: 800; border-bottom: 1px solid var(--border); padding-bottom: 8px; text-transform: uppercase;">
            A. Rekap Penjualan per Menu
        </h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No.</th>
                    <th>Menu</th>
                    <th style="width: 130px;" class="text-right">Total Qty</th>
                    <th style="width: 160px;" class="text-right">Total Penjualan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['menu_summary']['items'] as $item)
                    <tr>
                        <td class="text-center">{{ $item['no'] }}</td>
                        <td style="font-weight: 600;">{{ $item['menu'] }}</td>
                        <td class="text-right">{{ number_format($item['total_qty'], 0, ',', '.') }} item</td>
                        <td class="text-right">Rp {{ number_format($item['total_sales'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="color: var(--text-muted); padding: 18px;">
                            Tidak ada item penjualan yang tercatat pada shift ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background: #f8fafc; font-weight: 800; border-top: 2px solid #cbd5e1; border-bottom: 2px solid #cbd5e1;">
                    <td colspan="2" style="font-size: 14px; text-transform: uppercase;">TOTAL</td>
                    <td class="text-right" style="font-size: 14px;">{{ number_format($report['menu_summary']['total']['total_qty'], 0, ',', '.') }} item</td>
                    <td class="text-right" style="font-size: 14px; color: var(--primary);">Rp {{ number_format($report['menu_summary']['total']['total_sales'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- B. Ringkasan Transaksi & Keuangan -->
    <div style="margin-bottom: 28px;">
        <h2 style="font-size: 15px; font-weight: 800; border-bottom: 1px solid var(--border); padding-bottom: 8px; text-transform: uppercase;">
            B. Ringkasan Transaksi & Finansial
        </h2>
        
        <div class="summary-grid">
            <div class="summary-item">
                <span>Jumlah Transaksi Selesai</span>
                <strong>{{ number_format($report['transaction_summary']['completed_transaction_count'], 0, ',', '.') }} Transaksi</strong>
            </div>
            <div class="summary-item">
                <span>Total Penjualan Kotor (Gross)</span>
                <strong>Rp {{ number_format($report['transaction_summary']['gross_sales'], 0, ',', '.') }}</strong>
            </div>
            <div class="summary-item">
                <span>Total Diskon</span>
                <span style="color: var(--danger); font-weight: 700;">- Rp {{ number_format($report['transaction_summary']['total_discount'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span>Pajak Restoran (PB1)</span>
                <strong>Rp {{ number_format($report['transaction_summary']['tax_amount'], 0, ',', '.') }}</strong>
            </div>
            <div class="summary-item">
                <span>Biaya Layanan (Service Charge)</span>
                <strong>Rp {{ number_format($report['transaction_summary']['service_charge_amount'], 0, ',', '.') }}</strong>
            </div>
            <div class="summary-item">
                <span>Pembulatan Nilai</span>
                <strong>Rp {{ number_format($report['transaction_summary']['rounding_amount'], 0, ',', '.') }}</strong>
            </div>
            <div class="summary-item">
                <span>Pembatalan / Void ({{ $report['transaction_summary']['void_refund']['void_count'] }} Tx)</span>
                <span style="color: var(--danger); font-weight: 700;">Rp {{ number_format($report['transaction_summary']['void_refund']['void_amount'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item" style="background: #fef2f2; border: 1px solid #fecaca;">
                <span style="font-weight: 800; color: var(--primary);">Total Penjualan Bersih (Net Sales)</span>
                <strong style="color: var(--primary); font-size: 15px;">Rp {{ number_format($report['transaction_summary']['net_sales'], 0, ',', '.') }}</strong>
            </div>
        </div>

        <!-- Breakdown Pembayaran per Metode -->
        <h3 style="font-size: 13.5px; font-weight: 700; margin: 16px 0 8px; color: var(--text-muted); text-transform: uppercase;">
            Rincian Pembayaran per Metode
        </h3>
        <table class="report-table" style="margin-top: 4px;">
            <thead>
                <tr>
                    <th>Metode Pembayaran</th>
                    <th class="text-center" style="width: 120px;">Frekuensi</th>
                    <th class="text-right" style="width: 180px;">Total Diterima</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['transaction_summary']['payments_by_method'] as $pay)
                    <tr>
                        <td style="font-weight: 600;">{{ $pay['name'] }}</td>
                        <td class="text-center">{{ $pay['tx_count'] }} kali</td>
                        <td class="text-right">Rp {{ number_format($pay['total_amount'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center" style="color: var(--text-muted);">Tidak ada transaksi pembayaran tercatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Rekonsiliasi Kas Laci -->
        <h3 style="font-size: 13.5px; font-weight: 700; margin: 16px 0 8px; color: var(--text-muted); text-transform: uppercase;">
            Rekonsiliasi Kas Laci (Cash Drawer)
        </h3>
        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed var(--border); font-size: 13px;">
                <span>Modal Awal Kas:</span>
                <strong>Rp {{ number_format($report['transaction_summary']['cash_reconciliation']['starting_cash'], 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed var(--border); font-size: 13px;">
                <span>Kas yang Seharusnya Tersedia (Expected):</span>
                <strong>Rp {{ number_format($report['transaction_summary']['cash_reconciliation']['expected_cash'], 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed var(--border); font-size: 13px;">
                <span>Kas Fisik Dihitung Kasir (Actual):</span>
                @if($report['transaction_summary']['cash_reconciliation']['actual_cash'] !== null)
                    <strong>Rp {{ number_format($report['transaction_summary']['cash_reconciliation']['actual_cash'], 0, ',', '.') }}</strong>
                @else
                    <span style="color: var(--text-muted);">- (Auto-Close)</span>
                @endif
            </div>
            <div style="display: flex; justify-content: space-between; padding: 8px 0 2px; font-size: 14px;">
                <span style="font-weight: 700;">Selisih Kas:</span>
                @if($report['transaction_summary']['cash_reconciliation']['cash_difference'] !== null)
                    @php $diff = $report['transaction_summary']['cash_reconciliation']['cash_difference']; @endphp
                    <strong style="color: {{ $diff < 0 ? 'var(--danger)' : ($diff > 0 ? 'var(--warning)' : 'var(--success)') }};">
                        {{ $diff < 0 ? '-' : '+' }} Rp {{ number_format(abs($diff), 0, ',', '.') }}
                    </strong>
                @else
                    <span style="color: var(--text-muted);">-</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Tanda Tangan / Verifikasi Fisik -->
    <div style="margin-top: 36px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; text-align: center; font-size: 13px;">
        <div>
            <div style="margin-bottom: 50px;">Kasir yang Bertugas,</div>
            <div style="font-weight: 700; border-top: 1px solid #94a3b8; display: inline-block; padding-top: 4px; min-width: 160px;">
                {{ $report['shift_info']['cashier_name'] }}
            </div>
        </div>
        <div>
            <div style="margin-bottom: 50px;">Disetujui / Diverifikasi,</div>
            <div style="font-weight: 700; border-top: 1px solid #94a3b8; display: inline-block; padding-top: 4px; min-width: 160px;">
                Kepala Cabang / Supervisor
            </div>
        </div>
    </div>
</div>
@endsection
