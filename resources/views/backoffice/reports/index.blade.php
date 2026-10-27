@extends('layouts.app', ['title' => 'Laporan Bisnis & Finance'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Pusat Laporan Bisnis & Keuangan (Finance)</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Laporan analitik penjualan POS, rekonsiliasi shift, dan buku kas arus uang masuk/keluar.</p>
    </div>
    @if($reportType === 'RPT-FIN')
        <div>
            <a href="{{ route('reports.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-primary" style="background: #047857; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                <x-icon name="download" size="14" /> <span>Export Buku Kas ke Excel / CSV</span>
            </a>
        </div>
    @endif
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('reports.index') }}" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 2; min-width: 260px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Pilih Jenis Laporan</label>
            <select name="type" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <option value="RPT-FIN" {{ $reportType === 'RPT-FIN' ? 'selected' : '' }} style="font-weight: bold; color: #047857;">RPT-FIN: Laporan Buku Kas & Arus Keuangan (Finance)</option>
                <option value="RPT-01" {{ $reportType === 'RPT-01' ? 'selected' : '' }}>RPT-01: Ringkasan Penjualan & Finansial POS</option>
                <option value="RPT-02" {{ $reportType === 'RPT-02' ? 'selected' : '' }}>RPT-02: Penjualan per Menu</option>
                <option value="RPT-03" {{ $reportType === 'RPT-03' ? 'selected' : '' }}>RPT-03: Penjualan per Kategori</option>
                <option value="RPT-04" {{ $reportType === 'RPT-04' ? 'selected' : '' }}>RPT-04: Metode Pembayaran</option>
                <option value="RPT-05" {{ $reportType === 'RPT-05' ? 'selected' : '' }}>RPT-05: Penjualan per Jenis Order</option>
                <option value="RPT-06" {{ $reportType === 'RPT-06' ? 'selected' : '' }}>RPT-06: Laporan Void & Refund</option>
                <option value="RPT-07" {{ $reportType === 'RPT-07' ? 'selected' : '' }}>RPT-07: Diskon & Promosi</option>
                <option value="RPT-08" {{ $reportType === 'RPT-08' ? 'selected' : '' }}>RPT-08: Pajak Restoran & Service Charge</option>
                <option value="RPT-09" {{ $reportType === 'RPT-09' ? 'selected' : '' }}>RPT-09: Shift & Rekonsiliasi Kas</option>
                <option value="RPT-17" {{ $reportType === 'RPT-17' ? 'selected' : '' }}>RPT-17: Produktivitas Kasir</option>
                <option value="RPT-21" {{ $reportType === 'RPT-21' ? 'selected' : '' }}>RPT-21: Pengeluaran Biaya Operasional</option>
                <option value="RPT-22" {{ $reportType === 'RPT-22' ? 'selected' : '' }}>RPT-22: Estimasi Laba Rugi F&B</option>
                <option value="RPT-23" {{ $reportType === 'RPT-23' ? 'selected' : '' }}>RPT-23: Integritas Rantai Audit Log</option>
            </select>
        </div>

        <div style="flex: 1; min-width: 180px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Filter Cabang / Lingkup</label>
            <select name="branch_id" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <option value="">Semua (Cabang & Global)</option>
                <option value="global" {{ request('branch_id') === 'global' ? 'selected' : '' }}>Hanya Global (Kantor Pusat)</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ $startDate }}" style="padding: 9px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div>
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ $endDate }}" style="padding: 9px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <button type="submit" class="btn btn-primary">Tampilkan</button>
    </form>
</div>

<!-- Summary Metrics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Omzet Penjualan POS</div>
        <div class="stat-value">Rp {{ number_format($summary['total_sales'], 0, ',', '.') }}</div>
        <div class="stat-desc">{{ $summary['total_orders'] }} transaksi POS lunas</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #16a34a;">
        <div class="stat-label">Total Uang Masuk Buku Kas</div>
        <div class="stat-value" style="color: #16a34a;">+ Rp {{ number_format($summary['total_cash_in'], 0, ',', '.') }}</div>
        <div class="stat-desc">Suntikan modal & penerimaan lain</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #dc2626;">
        <div class="stat-label">Total Uang Keluar Buku Kas</div>
        <div class="stat-value" style="color: #dc2626;">- Rp {{ number_format($summary['total_cash_out'], 0, ',', '.') }}</div>
        <div class="stat-desc">Belanja bumbu, gas, sewa, upah</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid {{ $summary['net_profit_estimate'] >= 0 ? '#2563eb' : '#dc2626' }};">
        <div class="stat-label">Estimasi Arus Kas Bersih</div>
        <div class="stat-value" style="color: {{ $summary['net_profit_estimate'] >= 0 ? '#2563eb' : '#dc2626' }};">
            {{ $summary['net_profit_estimate'] >= 0 ? '+' : '' }} Rp {{ number_format($summary['net_profit_estimate'], 0, ',', '.') }}
        </div>
        <div class="stat-desc">Omzet POS dikurangi kas keluar</div>
    </div>
</div>

@if($reportType === 'RPT-FIN')
    <!-- Breakdown per Kategori Kas -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <div class="card-title">Ringkasan Arus Kas per Kategori</div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Jenis Arus</th>
                    <th>Kategori Transaksi</th>
                    <th style="text-align: center;">Jumlah Transaksi</th>
                    <th style="text-align: right;">Total Nominal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cashBreakdown as $b)
                    <tr>
                        <td>
                            @if($b->type === 'in')
                                <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="arrow-down" size="10" /> <span>Uang Masuk</span></span>
                            @else
                                <span class="badge badge-danger" style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="arrow-up" size="10" /> <span>Uang Keluar</span></span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $b->category }}</strong>
                        </td>
                        <td style="text-align: center;">{{ $b->count }}x</td>
                        <td style="text-align: right;">
                            <strong style="color: {{ $b->type === 'in' ? '#16a34a' : '#dc2626' }}; font-size: 14px;">
                                {{ $b->type === 'in' ? '+' : '-' }} Rp {{ number_format($b->total_amount, 0, ',', '.') }}
                            </strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 24px;">
                            Tidak ada transaksi buku kas pada rentang tanggal ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Rincian Buku Kas Ledger -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Rincian Transaksi Buku Kas (Ledger)</div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Cabang</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th style="text-align: right;">Nominal (Rp)</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cashBookEntries as $entry)
                    <tr>
                        <td><strong>{{ $entry->transaction_date->format('d/m/Y') }}</strong></td>
                        <td>
                            @if($entry->is_global)
                                <span style="background: #e0e7ff; color: #3730a3; padding: 2px 7px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #c7d2fe; display: inline-flex; align-items: center; gap: 4px;">
                                    <x-icon name="globe" size="12" /> <span>Global Pusat</span>
                                </span>
                            @else
                                <span style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="location" size="12" /> <span>{{ $entry->branch->name ?? '-' }}</span></span>
                            @endif
                        </td>
                        <td>
                            @if($entry->type === 'in')
                                <span class="badge badge-success">Masuk</span>
                            @else
                                <span class="badge badge-danger">Keluar</span>
                            @endif
                        </td>
                        <td><strong>{{ $entry->category }}</strong></td>
                        <td>{{ $entry->description ?? '-' }}</td>
                        <td style="text-align: right;">
                            <strong style="color: {{ $entry->type === 'in' ? '#16a34a' : '#dc2626' }};">
                                {{ $entry->type === 'in' ? '+' : '-' }} Rp {{ number_format($entry->amount, 0, ',', '.') }}
                            </strong>
                        </td>
                        <td>{{ $entry->creator->name ?? 'Staf' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">
                            Tidak ada data transaksi kas ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top: 16px;">
            {{ $cashBookEntries->appends(request()->query())->links() }}
        </div>
    </div>
@else
    <!-- Standard POS Orders Data Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Rincian Data Laporan: {{ $reportType }}</div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>No. Struk</th>
                    <th>Tanggal Bisnis</th>
                    <th>Cabang</th>
                    <th>Jenis Order</th>
                    <th>Subtotal</th>
                    <th>Pajak</th>
                    <th>Total Bayar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td><strong>{{ $o->order_no }}</strong></td>
                        <td>{{ $o->business_date }}</td>
                        <td>{{ $o->branch->name ?? '-' }}</td>
                        <td>{{ $o->orderType->name ?? '-' }}</td>
                        <td>Rp {{ number_format($o->subtotal, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($o->tax_amount, 0, ',', '.') }}</td>
                        <td><strong>Rp {{ number_format($o->total_amount, 0, ',', '.') }}</strong></td>
                        <td>
                            @if($o->status === 'completed')
                                <span class="badge badge-success">Lunas</span>
                            @else
                                <span class="badge badge-danger">Void</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 32px;">
                            Tidak ada transaksi ditemukan pada rentang tanggal dan cabang yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top: 16px;">
            {{ $orders->appends(request()->query())->links() }}
        </div>
    </div>
@endif
@endsection
