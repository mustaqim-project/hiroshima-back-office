@extends('layouts.app', ['title' => 'Laporan Bisnis & Finance'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Pusat Laporan Bisnis & Analitik (Reports Center)</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Laporan analitik penjualan POS, laba rugi F&B, rekonsiliasi shift, arus kas, dan audit log komprehensif.</p>
    </div>

    <!-- Export Action Buttons -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <!-- Single Report Export -->
        <a href="{{ Route::has('reports.export.single') ? route('reports.export.single', array_merge(request()->query(), ['type' => $reportType])) : url('/backoffice/reports?' . http_build_query(array_merge(request()->query(), ['export' => 'single', 'type' => $reportType]))) }}" 
           class="btn btn-primary" 
           style="background: #047857; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; border-radius: 8px; padding: 10px 16px; border: none; box-shadow: 0 2px 4px rgba(4, 120, 87, 0.2);">
            <x-icon name="download" size="15" />
            <span>Export Laporan Ini (.xlsx)</span>
        </a>

        <!-- Full Package Multi-Sheet Export -->
        <a href="{{ Route::has('reports.export.package') ? route('reports.export.package', request()->query()) : url('/backoffice/reports?' . http_build_query(array_merge(request()->query(), ['export' => 'package']))) }}" 
           class="btn btn-primary" 
           style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; border-radius: 8px; padding: 10px 16px; border: 1px solid #334155; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.15);">
            <x-icon name="archive" size="15" />
            <span>Export Full Package (Multi-Sheet .xlsx)</span>
            <span style="background: #F59E0B; color: #78350F; font-size: 10px; font-weight: 900; padding: 1px 6px; border-radius: 10px; letter-spacing: 0.05em; text-transform: uppercase;">12 Sheet</span>
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('reports.index') }}" id="reportFilterForm" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 2; min-width: 280px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Pilih Jenis Laporan</label>
            <select name="type" onchange="document.getElementById('reportFilterForm').submit()" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; background: var(--bg-card); color: var(--text);">
                <option value="RPT-01" {{ $reportType === 'RPT-01' ? 'selected' : '' }}>RPT-01: Ringkasan Penjualan & Finansial POS</option>
                <option value="RPT-02" {{ $reportType === 'RPT-02' ? 'selected' : '' }}>RPT-02: Penjualan per Menu (Menu Sales)</option>
                <option value="RPT-03" {{ $reportType === 'RPT-03' ? 'selected' : '' }}>RPT-03: Penjualan per Kategori (Category Sales)</option>
                <option value="RPT-04" {{ $reportType === 'RPT-04' ? 'selected' : '' }}>RPT-04: Metode Pembayaran (Payment Methods)</option>
                <option value="RPT-05" {{ $reportType === 'RPT-05' ? 'selected' : '' }}>RPT-05: Penjualan per Jenis Order (Order Types)</option>
                <option value="RPT-06" {{ $reportType === 'RPT-06' ? 'selected' : '' }}>RPT-06: Laporan Void & Refund (Loss Prevention)</option>
                <option value="RPT-07" {{ $reportType === 'RPT-07' ? 'selected' : '' }}>RPT-07: Diskon & Promosi (Discounts & Vouchers)</option>
                <option value="RPT-08" {{ $reportType === 'RPT-08' ? 'selected' : '' }}>RPT-08: Pajak Restoran & Service Charge (PB1)</option>
                <option value="RPT-09" {{ $reportType === 'RPT-09' ? 'selected' : '' }}>RPT-09: Shift & Rekonsiliasi Kas (Drawer Cash)</option>
                <option value="RPT-17" {{ $reportType === 'RPT-17' ? 'selected' : '' }}>RPT-17: Produktivitas Kasir (Cashier Performance)</option>
                <option value="RPT-21" {{ $reportType === 'RPT-21' ? 'selected' : '' }}>RPT-21: Pengeluaran Biaya Operasional (Expenses)</option>
                <option value="RPT-22" {{ $reportType === 'RPT-22' ? 'selected' : '' }}>RPT-22: Estimasi Laba Rugi F&B (P&L Income Statement)</option>
                <option value="RPT-23" {{ $reportType === 'RPT-23' ? 'selected' : '' }}>RPT-23: Integritas Rantai Audit Log (Hash Trail)</option>
                <option value="RPT-FIN" {{ $reportType === 'RPT-FIN' ? 'selected' : '' }} style="font-weight: 800; color: #047857;">RPT-FIN: Laporan Buku Kas & Arus Keuangan (Ledger)</option>
            </select>
        </div>

        <div style="flex: 1; min-width: 180px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Filter Cabang / Lingkup</label>
            <select name="branch_id" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-card); color: var(--text);">
                <option value="">Semua (Cabang & Global)</option>
                <option value="global" {{ request('branch_id') === 'global' ? 'selected' : '' }}>Hanya Global (Kantor Pusat)</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Dari Tanggal</label>
            <input type="date" name="start_date" id="startDateInput" value="{{ $startDate }}" style="padding: 9px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-card); color: var(--text);">
        </div>

        <div>
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Sampai Tanggal</label>
            <input type="date" name="end_date" id="endDateInput" value="{{ $endDate }}" style="padding: 9px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-card); color: var(--text);">
        </div>

        <button type="submit" class="btn btn-primary" style="height: 42px; display: inline-flex; align-items: center; gap: 6px;">
            <x-icon name="search" size="14" />
            <span>Tampilkan</span>
        </button>
    </form>

    <!-- Quick Date Filter Presets -->
    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border); display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Filter Cepat Periode:</span>
        <button type="button" class="btn btn-sm btn-outline" onclick="setDateRange('{{ now('Asia/Jakarta')->toDateString() }}', '{{ now('Asia/Jakarta')->toDateString() }}')" style="font-size: 11px; padding: 4px 10px;">Hari Ini</button>
        <button type="button" class="btn btn-sm btn-outline" onclick="setDateRange('{{ now('Asia/Jakarta')->subDays(6)->toDateString() }}', '{{ now('Asia/Jakarta')->toDateString() }}')" style="font-size: 11px; padding: 4px 10px;">7 Hari Terakhir</button>
        <button type="button" class="btn btn-sm btn-outline" onclick="setDateRange('{{ now('Asia/Jakarta')->startOfMonth()->toDateString() }}', '{{ now('Asia/Jakarta')->toDateString() }}')" style="font-size: 11px; padding: 4px 10px;">Bulan Ini</button>
        <button type="button" class="btn btn-sm btn-outline" onclick="setDateRange('{{ now('Asia/Jakarta')->subMonth()->startOfMonth()->toDateString() }}', '{{ now('Asia/Jakarta')->subMonth()->endOfMonth()->toDateString() }}')" style="font-size: 11px; padding: 4px 10px;">Bulan Lalu</button>
    </div>
</div>

<!-- Summary Metrics (Bento KPI Cards) -->
<div class="stats-grid" style="margin-bottom: 24px;">
    <div class="stat-card" style="border-left: 4px solid #2563EB;">
        <div class="stat-label">Total Omzet Penjualan POS</div>
        <div class="stat-value" style="color: #2563EB;">Rp {{ number_format($summary['total_sales'], 0, ',', '.') }}</div>
        <div class="stat-desc">{{ $summary['total_orders'] }} transaksi lunas tercatat</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #16A34A;">
        <div class="stat-label">Total Uang Masuk Buku Kas</div>
        <div class="stat-value" style="color: #16A34A;">+ Rp {{ number_format($summary['total_cash_in'], 0, ',', '.') }}</div>
        <div class="stat-desc">Suntikan modal & penerimaan kas</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #DC2626;">
        <div class="stat-label">Total Uang Keluar Buku Kas</div>
        <div class="stat-value" style="color: #DC2626;">- Rp {{ number_format($summary['total_cash_out'], 0, ',', '.') }}</div>
        <div class="stat-desc">Belanja bahan baku, gas, sewa, upah</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid {{ $summary['net_profit_estimate'] >= 0 ? '#059669' : '#DC2626' }};">
        <div class="stat-label">Estimasi Arus Kas Bersih</div>
        <div class="stat-value" style="color: {{ $summary['net_profit_estimate'] >= 0 ? '#059669' : '#DC2626' }};">
            {{ $summary['net_profit_estimate'] >= 0 ? '+' : '' }} Rp {{ number_format($summary['net_profit_estimate'], 0, ',', '.') }}
        </div>
        <div class="stat-desc">Omzet POS dikurangi kas keluar</div>
    </div>
</div>

@if($reportType === 'RPT-FIN' && !empty($cashBreakdown) && count($cashBreakdown) > 0)
    <!-- Ringkasan Arus Kas per Kategori -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="card-title" style="font-size: 16px; font-weight: 700;">Ringkasan Arus Kas per Kategori</div>
            <span style="font-size: 12px; color: var(--text-muted);">Akumulasi Masuk vs Keluar</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Jenis Arus</th>
                    <th>Kategori Transaksi</th>
                    <th style="text-align: center;">Frekuensi</th>
                    <th style="text-align: right;">Total Nominal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cashBreakdown as $b)
                    <tr>
                        <td>
                            @if($b->type === 'in')
                                <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <x-icon name="arrow-down" size="10" /> <span>Uang Masuk</span>
                                </span>
                            @else
                                <span class="badge badge-danger" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <x-icon name="arrow-up" size="10" /> <span>Uang Keluar</span>
                                </span>
                            @endif
                        </td>
                        <td><strong>{{ $b->category }}</strong></td>
                        <td style="text-align: center;">{{ $b->count }}x</td>
                        <td style="text-align: right;">
                            <strong style="color: {{ $b->type === 'in' ? '#16A34A' : '#DC2626' }}; font-size: 14px;">
                                {{ $b->type === 'in' ? '+' : '-' }} Rp {{ number_format($b->total_amount, 0, ',', '.') }}
                            </strong>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<!-- Active Report Data Table -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
        <div>
            <div class="card-title" style="font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                <span style="background: #0F172A; color: #FFFFFF; font-size: 11px; padding: 2px 8px; border-radius: 6px; font-weight: 700;">{{ $reportType }}</span>
                <span>{{ $activeReportData['title'] }}</span>
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                Periode: <strong>{{ date('d M Y', strtotime($startDate)) }}</strong> s/d <strong>{{ date('d M Y', strtotime($endDate)) }}</strong> · Lingkup: <strong>{{ request('branch_id') ? (request('branch_id') === 'global' ? 'Global Pusat' : 'Cabang Terpilih') : 'Semua Cabang' }}</strong>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 12px; font-weight: 600; color: var(--text-muted); background: var(--bg-muted); padding: 4px 10px; border-radius: 6px;">
                {{ count($activeReportData['rows']) }} Baris Data
            </span>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table" style="width: 100%;">
            <thead>
                <tr>
                    @foreach($activeReportData['headers'] as $hIdx => $headerText)
                        @php
                            $isRight = str_contains(strtolower($headerText), '(rp)') 
                                    || str_contains(strtolower($headerText), 'nominal') 
                                    || str_contains(strtolower($headerText), 'total') 
                                    || str_contains(strtolower($headerText), 'subtotal')
                                    || str_contains(strtolower($headerText), 'omzet')
                                    || str_contains(strtolower($headerText), 'pajak')
                                    || str_contains(strtolower($headerText), 'diskon')
                                    || str_contains(strtolower($headerText), 'selisih')
                                    || str_contains(strtolower($headerText), 'margin')
                                    || str_contains(strtolower($headerText), 'kontribusi');
                            $isCenter = str_contains(strtolower($headerText), 'no') && strlen($headerText) <= 4;
                        @endphp
                        <th style="{{ $isRight ? 'text-align: right;' : ($isCenter ? 'text-align: center;' : 'text-align: left;') }}">
                            {{ $headerText }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($activeReportData['rows'] as $rIdx => $row)
                    @php
                        $isSectionHeader = !empty($row[0]) && empty($row[1]) && empty($row[2]) && str_starts_with($row[0], 'I');
                    @endphp
                    @if($isSectionHeader)
                        <tr style="background: #F1F5F9; font-weight: 800;">
                            <td colspan="{{ count($activeReportData['headers']) }}" style="padding: 10px 14px; color: #0F172A; font-size: 13px;">
                                {{ $row[0] }}
                            </td>
                        </tr>
                    @else
                        <tr>
                            @foreach($row as $cIdx => $cell)
                                @php
                                    $headerCol = $activeReportData['headers'][$cIdx] ?? '';
                                    $isNumericMoney = (is_int($cell) || (is_numeric($cell) && !preg_match('/^0[0-9]+$/', (string)$cell))) 
                                        && (str_contains(strtolower($headerCol), '(rp)') 
                                            || str_contains(strtolower($headerCol), 'omzet') 
                                            || str_contains(strtolower($headerCol), 'total') 
                                            || str_contains(strtolower($headerCol), 'subtotal')
                                            || str_contains(strtolower($headerCol), 'nominal')
                                            || str_contains(strtolower($headerCol), 'pajak')
                                            || str_contains(strtolower($headerCol), 'diskon')
                                            || str_contains(strtolower($headerCol), 'selisih')
                                            || str_contains(strtolower($headerCol), 'kas'));
                                    $isCenter = $cIdx === 0 && strlen((string)$cell) <= 4;
                                    $isRight = $isNumericMoney || (is_numeric($cell) && str_contains(strtolower($headerCol), 'qty')) || str_contains((string)$cell, '%');
                                @endphp
                                <td style="{{ $isRight ? 'text-align: right;' : ($isCenter ? 'text-align: center;' : 'text-align: left;') }}">
                                    @if($cell === 'Lunas' || $cell === 'Aktif' || $cell === 'Uang Masuk')
                                        <span class="badge badge-success">{{ $cell }}</span>
                                    @elseif($cell === 'Void' || $cell === 'Uang Keluar')
                                        <span class="badge badge-danger">{{ $cell }}</span>
                                    @elseif($isNumericMoney)
                                        <strong style="font-variant-numeric: tabular-nums;">
                                            @if($cell < 0)
                                                <span style="color: #DC2626;">- Rp {{ number_format(abs($cell), 0, ',', '.') }}</span>
                                            @elseif($cell > 0)
                                                Rp {{ number_format($cell, 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </strong>
                                    @elseif(is_numeric($cell) && str_contains(strtolower($headerCol), 'qty'))
                                        <span style="font-weight: 600; font-variant-numeric: tabular-nums;">{{ number_format($cell, 0, ',', '.') }}</span>
                                    @else
                                        {{ $cell ?? '-' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ count($activeReportData['headers']) }}" style="text-align: center; color: var(--text-muted); padding: 36px 16px;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                <x-icon name="document" size="28" style="opacity: 0.3;" />
                                <strong>Tidak ada data ditemukan untuk laporan {{ $reportType }}</strong>
                                <span style="font-size: 13px;">Silakan pilih rentang tanggal atau cabang yang berbeda.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse

                @if(!empty($activeReportData['total']))
                    <tr style="background: #F8FAFC; border-top: 2px solid #94A3B8; font-weight: 800;">
                        @foreach($activeReportData['total'] as $totIdx => $totCell)
                            @php
                                $headerCol = $activeReportData['headers'][$totIdx] ?? '';
                                $isNumericMoney = is_numeric($totCell) && (
                                    str_contains(strtolower($headerCol), '(rp)') 
                                    || str_contains(strtolower($headerCol), 'omzet') 
                                    || str_contains(strtolower($headerCol), 'total') 
                                    || str_contains(strtolower($headerCol), 'subtotal')
                                    || str_contains(strtolower($headerCol), 'nominal')
                                    || str_contains(strtolower($headerCol), 'pajak')
                                    || str_contains(strtolower($headerCol), 'diskon')
                                    || str_contains(strtolower($headerCol), 'selisih')
                                );
                                $isRight = $isNumericMoney || (is_numeric($totCell) && str_contains(strtolower($headerCol), 'qty')) || str_contains((string)$totCell, '%');
                            @endphp
                            <td style="{{ $isRight ? 'text-align: right;' : 'text-align: left;' }}; font-size: 13px; color: #0F172A; padding: 12px 14px;">
                                @if($isNumericMoney)
                                    <span style="font-variant-numeric: tabular-nums;">
                                        @if($totCell < 0)
                                            <span style="color: #DC2626;">- Rp {{ number_format(abs($totCell), 0, ',', '.') }}</span>
                                        @else
                                            Rp {{ number_format($totCell, 0, ',', '.') }}
                                        @endif
                                    </span>
                                @elseif(is_numeric($totCell) && !empty($totCell))
                                    <span style="font-variant-numeric: tabular-nums;">{{ number_format($totCell, 0, ',', '.') }}</span>
                                @else
                                    {{ $totCell }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<script>
function setDateRange(start, end) {
    document.getElementById('startDateInput').value = start;
    document.getElementById('endDateInput').value = end;
    document.getElementById('reportFilterForm').submit();
}
</script>
@endsection
