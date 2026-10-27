@extends('layouts.app', ['title' => 'Dashboard Eksekutif & Operasional'])

@section('content')
<style>
    .dash-grid-2-1 {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    .dash-grid-2-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    @media (max-width: 991px) {
        .dash-grid-2-1, .dash-grid-2-col {
            grid-template-columns: 1fr !important;
            gap: 16px;
        }
    }
</style>
<div style="margin-bottom: 20px;">
    <!-- Dashboard Header Title & Action Buttons -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 18px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; color: #0f172a;">Dashboard Eksekutif & Operasional</h2>
                <span class="badge badge-info" style="font-size: 11px; padding: 4px 10px; border-radius: 20px;">Real-Time F&B Analytics</span>
            </div>
            <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">
                Analisis terpadu performa multi-cabang, grafik trafik jam sibuk, distribusi layanan POS, dan arus buku kas keuangan.
            </p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('cash-books.create') }}" class="btn btn-primary" style="background: #059669; font-size: 13px; font-weight: 600;">
                + Catat Kas Masuk / Keluar
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <x-icon name="reports" size="14" /> <span>Laporan Lengkap &rarr;</span>
            </a>
        </div>
    </div>

    <!-- Filter Hub: Presets & Custom Date Range -->
    <div class="card" style="padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
        <form method="GET" action="{{ route('dashboard') }}" id="filterForm">
            <!-- Top Row: Quick Preset Buttons -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-right: 4px;">Pilihan Cepat:</span>
                    
                    <a href="{{ route('dashboard', ['preset' => 'today', 'branch_id' => $branchId]) }}" 
                       class="btn btn-sm {{ $preset === 'today' ? 'btn-primary' : 'btn-outline' }}" 
                       style="font-size: 12px; border-radius: 20px; padding: 4px 12px;">
                        Hari Ini
                    </a>
                    <a href="{{ route('dashboard', ['preset' => 'yesterday', 'branch_id' => $branchId]) }}" 
                       class="btn btn-sm {{ $preset === 'yesterday' ? 'btn-primary' : 'btn-outline' }}" 
                       style="font-size: 12px; border-radius: 20px; padding: 4px 12px;">
                        Kemarin
                    </a>
                    <a href="{{ route('dashboard', ['preset' => 'this_week', 'branch_id' => $branchId]) }}" 
                       class="btn btn-sm {{ $preset === 'this_week' ? 'btn-primary' : 'btn-outline' }}" 
                       style="font-size: 12px; border-radius: 20px; padding: 4px 12px;">
                        Minggu Ini
                    </a>
                    <a href="{{ route('dashboard', ['preset' => 'this_month', 'branch_id' => $branchId]) }}" 
                       class="btn btn-sm {{ $preset === 'this_month' ? 'btn-primary' : 'btn-outline' }}" 
                       style="font-size: 12px; border-radius: 20px; padding: 4px 12px;">
                        Bulan Ini
                    </a>
                    <a href="{{ route('dashboard', ['preset' => 'last_month', 'branch_id' => $branchId]) }}" 
                       class="btn btn-sm {{ $preset === 'last_month' ? 'btn-primary' : 'btn-outline' }}" 
                       style="font-size: 12px; border-radius: 20px; padding: 4px 12px;">
                        Bulan Lalu
                    </a>
                    <a href="{{ route('dashboard', ['preset' => 'this_year', 'branch_id' => $branchId]) }}" 
                       class="btn btn-sm {{ $preset === 'this_year' ? 'btn-primary' : 'btn-outline' }}" 
                       style="font-size: 12px; border-radius: 20px; padding: 4px 12px;">
                        Tahun Ini
                    </a>
                </div>

                <div style="font-size: 12px; color: #64748b; font-weight: 500; display: flex; align-items: center; gap: 6px;">
                    <x-icon name="calendar" size="14" /> <span>Rentang Aktif: <strong style="color: #0f172a;">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</strong> s/d <strong style="color: #0f172a;">{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</strong></span>
                </div>
            </div>

            <!-- Bottom Row: Date Inputs, Branch Dropdown, Apply Button -->
            <div style="display: flex; align-items: flex-end; flex-wrap: wrap; gap: 14px;">
                <input type="hidden" name="preset" value="custom" id="presetInput">

                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px; text-transform: uppercase;">
                        Dari Tanggal
                    </label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="form-control" style="font-size: 13px; padding: 7px 10px; width: 150px;">
                </div>

                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px; text-transform: uppercase;">
                        Sampai Tanggal
                    </label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="form-control" style="font-size: 13px; padding: 7px 10px; width: 150px;">
                </div>

                <div style="flex-grow: 1; min-width: 200px;">
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px; text-transform: uppercase;">
                        Cabang Operasional
                    </label>
                    <select name="branch_id" class="form-control" style="font-size: 13px; padding: 7px 10px; width: 100%;">
                        <option value="all" {{ $branchId === 'all' ? 'selected' : '' }}>Semua Cabang (Konsolidasi Global)</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) $branchId === (string) $b->id ? 'selected' : '' }}>
                                {{ $b->name }} ({{ $b->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="font-size: 13px; padding: 8px 18px;">
                        Terapkan Filter
                    </button>
                    @if($preset !== 'today' || $branchId !== 'all')
                        <a href="{{ route('dashboard') }}" class="btn btn-outline" style="font-size: 13px; padding: 8px 14px; display: inline-flex; align-items: center; gap: 4px;" title="Reset ke Hari Ini">
                            <x-icon name="refresh" size="13" /> <span>Reset</span>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Row 1: Primary Executive KPI Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <!-- Penjualan POS -->
        <div class="card" style="padding: 18px 20px; border-left: 4px solid #dc2626; border-radius: 12px; background: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Total Penjualan POS</div>
                    <div style="font-size: 24px; font-weight: 800; color: #dc2626; margin-top: 6px; letter-spacing: -0.02em;">
                        Rp {{ number_format($totalSales, 0, ',', '.') }}
                    </div>
                </div>
                <div style="background: #fee2e2; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #dc2626;">
                    <x-icon name="food" size="20" />
                </div>
            </div>
            <div style="font-size: 12px; color: #64748b; margin-top: 8px; display: flex; align-items: center; gap: 6px;">
                <span class="badge badge-success" style="font-size: 10px; padding: 2px 6px;">{{ number_format($totalOrders) }} Struk</span>
                <span>tercatat selesai</span>
            </div>
        </div>

        <!-- Total Trafik / Transaksi -->
        <div class="card" style="padding: 18px 20px; border-left: 4px solid #2563eb; border-radius: 12px; background: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Trafik Pesanan & AOV</div>
                    <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 6px; letter-spacing: -0.02em;">
                        {{ number_format($totalOrders) }} <span style="font-size: 14px; font-weight: 600; color: #64748b;">Pesanan</span>
                    </div>
                </div>
                <div style="background: #eff6ff; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #2563eb;">
                    <x-icon name="users" size="20" />
                </div>
            </div>
            <div style="font-size: 12px; color: #64748b; margin-top: 8px;">
                Rata-rata / Struk (AOV): <strong style="color: #2563eb;">Rp {{ number_format($avgOrderValue, 0, ',', '.') }}</strong>
            </div>
        </div>

        <!-- Jam Sibuk Trafik -->
        <div class="card" style="padding: 18px 20px; border-left: 4px solid #f59e0b; border-radius: 12px; background: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Puncak Trafik (Peak Hour)</div>
                    <div style="font-size: 18px; font-weight: 800; color: #b45309; margin-top: 8px; letter-spacing: -0.01em;">
                        {{ $peakTrafficDisplay }}
                    </div>
                </div>
                <div style="background: #fef3c7; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #d97706;">
                    <x-icon name="clock" size="20" />
                </div>
            </div>
            <div style="font-size: 12px; color: #64748b; margin-top: 8px;">
                Jam tersibuk gerobak & antrian kasir
            </div>
        </div>

        <!-- Estimasi Laba Bersih -->
        <div class="card" style="padding: 18px 20px; border-left: 4px solid {{ $netProfitEstimate >= 0 ? '#10b981' : '#dc2626' }}; border-radius: 12px; background: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Estimasi Laba Bersih</div>
                    <div style="font-size: 24px; font-weight: 800; color: {{ $netProfitEstimate >= 0 ? '#059669' : '#dc2626' }}; margin-top: 6px; letter-spacing: -0.02em;">
                        {{ $netProfitEstimate >= 0 ? '+' : '' }} Rp {{ number_format($netProfitEstimate, 0, ',', '.') }}
                    </div>
                </div>
                <div style="background: {{ $netProfitEstimate >= 0 ? '#ecfdf5' : '#fee2e2' }}; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: {{ $netProfitEstimate >= 0 ? '#059669' : '#dc2626' }};">
                    <x-icon name="cash" size="20" />
                </div>
            </div>
            <div style="font-size: 12px; color: #64748b; margin-top: 8px;">
                Penjualan POS dikurangi beban kas keluar
            </div>
        </div>
    </div>

    <!-- Row 2: Finance & Cash Flow Secondary Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 24px;">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px;">
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Uang Masuk Buku Kas</div>
            <div style="font-size: 18px; font-weight: 800; color: #16a34a; margin-top: 4px;">+ Rp {{ number_format($totalCashIn, 0, ',', '.') }}</div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Modal awal & pemasukan lain</div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px;">
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Uang Keluar Buku Kas</div>
            <div style="font-size: 18px; font-weight: 800; color: #dc2626; margin-top: 4px;">- Rp {{ number_format($totalCashOut, 0, ',', '.') }}</div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Belanja bumbu, gas, sewa, upah</div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px;">
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Saldo Kas Bersih</div>
            <div style="font-size: 18px; font-weight: 800; color: {{ $netCash >= 0 ? '#2563eb' : '#dc2626' }}; margin-top: 4px;">
                {{ $netCash >= 0 ? '+' : '' }} Rp {{ number_format($netCash, 0, ',', '.') }}
            </div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Arus kas masuk vs kas keluar</div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px;">
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Pembatalan / Void</div>
            <div style="font-size: 18px; font-weight: 800; color: {{ $totalVoid > 0 ? '#d97706' : '#16a34a' }}; margin-top: 4px;">
                {{ $totalVoid }} Transaksi ({{ $voidRate }}%)
            </div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Nominal void: Rp {{ number_format($totalVoidAmount, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Section: Overview Tiap Cabang (Multi-Branch Performance) -->
    <div class="card" style="margin-bottom: 24px; border-radius: 14px; overflow: hidden; background: #ffffff; border: 1px solid #e2e8f0;">
        <div class="card-header" style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <div style="font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <span style="display: flex; align-items: center; gap: 6px;"><x-icon name="building" size="18" style="color: #64748b;" /> Overview Performa Tiap Cabang Hiroshima</span>
                    <span class="badge badge-info" style="font-size: 10px;">{{ count($branchOverview) }} Cabang Terdaftar</span>
                </div>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    Matriks perbandingan penjualan, trafik pesanan, kontribusi omzet, dan jam sibuk per outlet.
                </div>
            </div>
            <a href="{{ route('branches.index') }}" class="btn btn-outline btn-sm" style="font-size: 12px;">
                Kelola Cabang →
            </a>
        </div>

        <div style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f1f5f9; text-align: left;">
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569;">Cabang</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: right;">Total Omzet</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569;">Kontribusi Omzet</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: center;">Trafik Pesanan</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: right;">Rata-rata (AOV)</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: right;">Beban Kas Keluar</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: center;">Jam Sibuk</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: center;">Status Shift</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branchOverview as $bData)
                        @php $b = $bData['branch']; @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 14px 16px;">
                                <strong style="font-size: 13px; color: #0f172a;">{{ $b->name }}</strong>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                    Kode: <span style="font-family: monospace; font-weight: bold;">{{ $b->code }}</span> • {{ $b->phone ?: 'No Telp -' }}
                                </div>
                            </td>
                            <td style="padding: 14px 16px; text-align: right;">
                                <strong style="font-size: 14px; color: #dc2626;">Rp {{ number_format($bData['sales'], 0, ',', '.') }}</strong>
                            </td>
                            <td style="padding: 14px 16px; min-width: 150px;">
                                <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 4px; font-weight: 600;">
                                    <span>{{ $bData['contribution_pct'] }}%</span>
                                </div>
                                <div style="background: #e2e8f0; border-radius: 4px; height: 8px; width: 100%; overflow: hidden;">
                                    <div style="background: linear-gradient(90deg, #ef4444, #dc2626); height: 100%; width: {{ min(100, $bData['contribution_pct']) }}%; border-radius: 4px;"></div>
                                </div>
                            </td>
                            <td style="padding: 14px 16px; text-align: center;">
                                <span class="badge badge-info" style="font-size: 12px; padding: 4px 10px;">
                                    {{ number_format($bData['orders_count']) }} Struk
                                </span>
                            </td>
                            <td style="padding: 14px 16px; text-align: right;">
                                <span style="font-size: 12px; font-weight: 600; color: #2563eb;">
                                    Rp {{ number_format($bData['aov'], 0, ',', '.') }}
                                </span>
                            </td>
                            <td style="padding: 14px 16px; text-align: right;">
                                <span style="font-size: 12px; font-weight: 600; color: #dc2626;">
                                    Rp {{ number_format($bData['cash_out'], 0, ',', '.') }}
                                </span>
                            </td>
                            <td style="padding: 14px 16px; text-align: center;">
                                <span style="font-size: 11px; font-weight: 700; color: #b45309; background: #fef3c7; padding: 3px 8px; border-radius: 6px;">
                                    {{ $bData['peak_hour'] }}
                                </span>
                            </td>
                            <td style="padding: 14px 16px; text-align: center;">
                                @if($bData['has_active_shift'])
                                    <span class="badge badge-success" style="font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><x-icon name="dot" size="10" /> Shift Buka</span>
                                    <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{{ $bData['active_shift_cashier'] }}</div>
                                @else
                                    <span class="badge badge-warning" style="font-size: 11px; background: #e2e8f0; color: #475569; display: inline-flex; align-items: center; gap: 4px;"><x-icon name="dot" size="10" /> Shift Tutup</span>
                                @endif
                            </td>
                            <td style="padding: 14px 16px; text-align: center;">
                                <a href="{{ route('dashboard', ['branch_id' => $b->id, 'preset' => $preset, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                                   class="btn btn-outline btn-sm" 
                                   style="font-size: 11px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;"
                                   title="Filter dashboard hanya untuk cabang ini">
                                    <span>Fokus</span> <x-icon name="search" size="11" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 24px; color: #64748b;">
                                Belum ada cabang aktif yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section: Interactive Visual Charts (Row 3: Tren & Trafik Jam Sibuk) -->
    <div class="dash-grid-2-1">
        <!-- Chart 1: Tren Penjualan & Transaksi Harian -->
        <div class="card" style="padding: 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                <div>
                    <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;"><x-icon name="reports" size="18" style="color: #2563eb;" /> Tren Penjualan & Trafik Transaksi</h3>
                    <p style="font-size: 12px; color: #64748b;">Grafik perkembangan omzet harian (Rp) dan volume struk pesanan.</p>
                </div>
                <div style="display: flex; gap: 12px; font-size: 12px;">
                    <span style="display: flex; align-items: center; gap: 4px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #dc2626; border-radius: 2px;"></span>
                        Penjualan (Rp)
                    </span>
                    <span style="display: flex; align-items: center; gap: 4px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #2563eb; border-radius: 2px;"></span>
                        Transaksi
                    </span>
                </div>
            </div>
            <div style="position: relative; height: 280px; width: 100%;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Analisis Trafik Jam Sibuk (Footfall Hourly) -->
        <div class="card" style="padding: 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;">
            <div style="margin-bottom: 16px;">
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;"><x-icon name="clock" size="18" style="color: #d97706;" /> Distribusi Trafik Jam Sibuk</h3>
                <p style="font-size: 12px; color: #64748b;">Jumlah transaksi per jam (10:00 - 23:00).</p>
            </div>
            <div style="position: relative; height: 280px; width: 100%;">
                <canvas id="hourlyTrafficChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Section: Multi-Dimensional Breakdown Charts (Row 4: Cabang, Layanan, Pembayaran) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <!-- Chart 3: Proporsi Penjualan Tiap Cabang -->
        <div class="card" style="padding: 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;">
            <div style="margin-bottom: 14px;">
                <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;"><x-icon name="building" size="16" style="color: #0f172a;" /> Pangsa Penjualan Cabang</h3>
                <p style="font-size: 12px; color: #64748b;">Komposisi kontribusi omzet antar outlet.</p>
            </div>
            <div style="position: relative; height: 220px; width: 100%;">
                <canvas id="branchShareChart"></canvas>
            </div>
        </div>

        <!-- Chart 4: Jenis Layanan (Dine In vs Take Away vs Delivery) -->
        <div class="card" style="padding: 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;">
            <div style="margin-bottom: 14px;">
                <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;"><x-icon name="order-type" size="16" style="color: #0f172a;" /> Komposisi Jenis Layanan POS</h3>
                <p style="font-size: 12px; color: #64748b;">Distribusi Dine In, Bungkus & Delivery.</p>
            </div>
            <div style="position: relative; height: 220px; width: 100%;">
                <canvas id="orderTypeChart"></canvas>
            </div>
        </div>

        <!-- Chart 5: Saluran Pembayaran (Payment Methods) -->
        <div class="card" style="padding: 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;">
            <div style="margin-bottom: 14px;">
                <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;"><x-icon name="payment" size="16" style="color: #0f172a;" /> Metode Pembayaran Kasir</h3>
                <p style="font-size: 12px; color: #64748b;">Distribusi penerimaan per saluran tender POS.</p>
            </div>
            <div style="position: relative; height: 180px; width: 100%; margin-bottom: 14px;">
                <canvas id="paymentChart"></canvas>
            </div>

            <!-- List Rincian Setiap Metode Pembayaran -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
                @php
                    $methodColors = ['#10b981', '#6366f1', '#0284c7', '#f59e0b', '#ec4899', '#8b5cf6'];
                @endphp
                @foreach($paymentStats as $idx => $pm)
                    @php
                        $color = $methodColors[$idx % count($methodColors)];
                    @endphp
                    <div style="padding: 7px 10px; background: #f8fafc; border-radius: 8px; border: 1px solid #f1f5f9;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: {{ $color }};"></span>
                                <strong style="font-size: 12px; color: #0f172a;">{{ $pm['name'] }}</strong>
                            </div>
                            <div style="text-align: right;">
                                <strong style="font-size: 12px; color: #0f172a;">Rp {{ number_format($pm['amount'], 0, ',', '.') }}</strong>
                                <span style="font-size: 11px; color: #64748b; margin-left: 4px;">({{ $pm['count'] }}x • {{ $pm['pct'] }}%)</span>
                            </div>
                        </div>
                        <div style="background: #e2e8f0; height: 4px; border-radius: 2px; overflow: hidden; width: 100%;">
                            <div style="background: {{ $color }}; height: 100%; width: {{ max(1, $pm['pct']) }}%; border-radius: 2px;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Section: Leaderboard Menu Terlaris & Recent Feeds -->
    <div class="dash-grid-2-col">
        <!-- Top 6 Menu Terlaris (Leaderboard) -->
        <div class="card" style="padding: 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;"><x-icon name="trophy" size="18" style="color: #d97706;" /> Menu Terlaris (Best Seller)</h3>
                    <p style="font-size: 12px; color: #64748b;">Item paling diminati pelanggan pada periode ini.</p>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-outline btn-sm" style="font-size: 12px;">Katalog Menu →</a>
            </div>

            @if(count($topProducts) > 0)
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach($topProducts as $idx => $prod)
                        @php
                            $maxQty = $topProducts[0]->total_qty ?: 1;
                            $barWidth = round(($prod->total_qty / $maxQty) * 100);
                        @endphp
                        <div style="padding: 8px 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #f1f5f9;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-weight: 800; font-size: 12px; width: 22px; height: 22px; border-radius: 50%; background: {{ $idx === 0 ? '#fef3c7' : '#e2e8f0' }}; color: {{ $idx === 0 ? '#b45309' : '#475569' }}; display: flex; align-items: center; justify-content: center;">
                                        {{ $idx + 1 }}
                                    </span>
                                    <strong style="font-size: 13px; color: #0f172a;">{{ $prod->product_name }}</strong>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 12px; font-weight: 800; color: #dc2626;">{{ number_format($prod->total_qty) }} Porsi</span>
                                    <span style="font-size: 11px; color: #64748b; margin-left: 6px;">(Rp {{ number_format($prod->total_revenue, 0, ',', '.') }})</span>
                                </div>
                            </div>
                            <div style="background: #e2e8f0; height: 6px; border-radius: 3px; overflow: hidden; width: 100%;">
                                <div style="background: linear-gradient(90deg, #f87171, #dc2626); height: 100%; width: {{ $barWidth }}%; border-radius: 3px;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 30px; color: #64748b; font-size: 13px;">
                    Belum ada data penjualan menu pada rentang waktu ini.
                </div>
            @endif
        </div>

        <!-- Transaksi POS Real-Time Terkini -->
        <div class="card" style="padding: 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;"><x-icon name="food" size="18" style="color: #dc2626;" /> Transaksi POS Terkini</h3>
                    <p style="font-size: 12px; color: #64748b;">Pesanan baru yang masuk dari tablet kasir.</p>
                </div>
                <span class="badge badge-success" style="font-size: 10px;">Live Sync</span>
            </div>

            <div style="overflow-x: auto;">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; font-size: 11px; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #f1f5f9;">
                            <th style="padding: 8px 6px;">Struk & Waktu</th>
                            <th style="padding: 8px 6px;">Cabang</th>
                            <th style="padding: 8px 6px;">Layanan</th>
                            <th style="padding: 8px 6px; text-align: right;">Total Bayar</th>
                            <th style="padding: 8px 6px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $ro)
                            <tr style="border-bottom: 1px solid #f8fafc;">
                                <td style="padding: 10px 6px;">
                                    <strong style="font-size: 12px; color: #0f172a;">{{ $ro->order_no }}</strong>
                                    <div style="font-size: 10px; color: #94a3b8;">
                                        {{ $ro->client_created_at ? $ro->client_created_at->format('d/m H:i') : $ro->business_date }}
                                    </div>
                                </td>
                                <td style="padding: 10px 6px; font-size: 12px;">
                                    {{ $ro->branch->name ?? 'Cabang' }}
                                </td>
                                <td style="padding: 10px 6px;">
                                    <span class="badge badge-info" style="font-size: 10px; padding: 2px 6px;">
                                        {{ $ro->orderType->name ?? 'Dine In' }}
                                    </span>
                                </td>
                                <td style="padding: 10px 6px; text-align: right;">
                                    <strong style="font-size: 12px; color: #dc2626;">Rp {{ number_format($ro->total_amount, 0, ',', '.') }}</strong>
                                </td>
                                <td style="padding: 10px 6px; text-align: center;">
                                    @if($ro->status === 'completed')
                                        <span class="badge badge-success" style="font-size: 10px;">Selesai</span>
                                    @elseif($ro->status === 'voided')
                                        <span class="badge badge-danger" style="font-size: 10px;">Void</span>
                                    @else
                                        <span class="badge badge-warning" style="font-size: 10px;">{{ $ro->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8; font-size: 12px;">
                                    Belum ada transaksi POS pada filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section: Mutasi Buku Kas Terkini -->
    <div class="card" style="padding: 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
            <div>
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;"><x-icon name="book" size="18" style="color: #059669;" /> Mutasi Buku Kas Terkini (Street Food Finance)</h3>
                <p style="font-size: 12px; color: #64748b;">Catatan uang masuk dan belanja operasional warung gerobak.</p>
            </div>
            <a href="{{ route('cash-books.index') }}" class="btn btn-outline btn-sm" style="font-size: 12px;">Lihat Buku Kas Penuh →</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; font-size: 11px; color: #64748b; text-transform: uppercase; background: #f8fafc;">
                        <th style="padding: 10px 14px;">Tanggal & Cabang</th>
                        <th style="padding: 10px 14px;">Jenis Arus</th>
                        <th style="padding: 10px 14px;">Kategori</th>
                        <th style="padding: 10px 14px;">Keterangan</th>
                        <th style="padding: 10px 14px; text-align: right;">Nominal</th>
                        <th style="padding: 10px 14px; text-align: center;">Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentCashTransactions as $cash)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 14px;">
                                <strong style="font-size: 12px; color: #0f172a;">{{ $cash->transaction_date->format('d/m/Y') }}</strong>
                                <div style="font-size: 11px; color: #64748b;">
                                    @if($cash->is_global)
                                        <span style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="globe" size="12" style="color: #64748b;" /> Kantor Pusat (Global)</span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="location" size="12" style="color: #64748b;" /> {{ $cash->branch->name ?? 'Cabang' }}</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 12px 14px;">
                                @if($cash->type === 'in')
                                    <span class="badge badge-success" style="font-size: 10px; display: inline-flex; align-items: center; gap: 4px;"><x-icon name="arrow-down" size="10" /> Uang Masuk</span>
                                @else
                                    <span class="badge badge-danger" style="font-size: 10px; display: inline-flex; align-items: center; gap: 4px;"><x-icon name="arrow-up" size="10" /> Kas Keluar</span>
                                @endif
                            </td>
                            <td style="padding: 12px 14px;">
                                <strong style="font-size: 12px; color: #1e293b;">{{ $cash->category }}</strong>
                            </td>
                            <td style="padding: 12px 14px; font-size: 12px; color: #475569;">
                                {{ $cash->description ?: '-' }}
                            </td>
                            <td style="padding: 12px 14px; text-align: right;">
                                <strong style="font-size: 13px; color: {{ $cash->type === 'in' ? '#16a34a' : '#dc2626' }};">
                                    {{ $cash->type === 'in' ? '+' : '-' }} Rp {{ number_format($cash->amount, 0, ',', '.') }}
                                </strong>
                            </td>
                            <td style="padding: 12px 14px; text-align: center; font-size: 11px; color: #64748b;">
                                {{ $cash->creator->name ?? 'Admin' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 24px; color: #94a3b8; font-size: 12px;">
                                Belum ada mutasi buku kas pada rentang tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Load Chart.js CDN with graceful fallback -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js library could not be loaded from CDN.');
        return;
    }

    // Chart Global Defaults
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    Chart.defaults.color = '#64748b';

    // 1. Chart: Daily Trend (Sales & Orders)
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartTrendLabels) !!},
                datasets: [
                    {
                        type: 'line',
                        label: 'Trafik Transaksi (Struk)',
                        data: {!! json_encode($chartTrendOrders) !!},
                        borderColor: '#2563eb',
                        backgroundColor: '#2563eb',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 3,
                        yAxisID: 'yOrders',
                    },
                    {
                        type: 'bar',
                        label: 'Penjualan (Rp)',
                        data: {!! json_encode($chartTrendSales) !!},
                        backgroundColor: 'rgba(220, 38, 38, 0.75)',
                        borderColor: '#dc2626',
                        borderWidth: 1,
                        borderRadius: 4,
                        yAxisID: 'ySales',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    ySales: {
                        type: 'linear',
                        position: 'left',
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            callback: function(val) {
                                return 'Rp ' + (val >= 1000000 ? (val / 1000000).toFixed(1) + 'M' : (val / 1000).toFixed(0) + 'k');
                            },
                            font: { size: 10 }
                        }
                    },
                    yOrders: {
                        type: 'linear',
                        position: 'right',
                        grid: { display: false },
                        ticks: {
                            precision: 0,
                            font: { size: 10 }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                if (ctx.dataset.yAxisID === 'ySales') {
                                    return 'Penjualan: Rp ' + new Intl.NumberFormat('id-ID').format(ctx.raw);
                                }
                                return 'Trafik: ' + ctx.raw + ' transaksi';
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Chart: Hourly Footfall Traffic
    const hourlyCtx = document.getElementById('hourlyTrafficChart');
    if (hourlyCtx) {
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartHourlyLabels) !!},
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: {!! json_encode($chartHourlyOrders) !!},
                    backgroundColor: function(context) {
                        const val = context.raw || 0;
                        return val >= 10 ? 'rgba(217, 119, 6, 0.85)' : 'rgba(245, 158, 11, 0.6)';
                    },
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.raw + ' transaksi pelanggan';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 }, maxRotation: 45 }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 10 } },
                        grid: { color: '#f1f5f9' }
                    }
                }
            }
        });
    }

    // 3. Chart: Branch Share (Doughnut)
    const branchCtx = document.getElementById('branchShareChart');
    if (branchCtx) {
        new Chart(branchCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($chartBranchLabels) !!},
                datasets: [{
                    data: {!! json_encode($chartBranchSales) !!},
                    backgroundColor: ['#dc2626', '#2563eb', '#10b981', '#f59e0b', '#8b5cf6'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(ctx.raw);
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 4. Chart: Order Types (Doughnut)
    const orderTypeCtx = document.getElementById('orderTypeChart');
    if (orderTypeCtx) {
        new Chart(orderTypeCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($chartOrderTypeLabels) !!},
                datasets: [{
                    data: {!! json_encode($chartOrderTypeCounts) !!},
                    backgroundColor: ['#ef4444', '#f97316', '#3b82f6', '#14b8a6'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.label + ': ' + ctx.raw + ' transaksi';
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 5. Chart: Payment Methods (Polar / Doughnut)
    const paymentCtx = document.getElementById('paymentChart');
    if (paymentCtx) {
        const paymentRawData = {!! json_encode($chartPaymentAmounts) !!};
        const hasPaymentData = paymentRawData.some(v => v > 0);
        new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($chartPaymentLabels) !!},
                datasets: [{
                    data: hasPaymentData ? paymentRawData : {!! json_encode(array_fill(0, max(1, count($chartPaymentLabels)), 1)) !!},
                    backgroundColor: ['#10b981', '#6366f1', '#0284c7', '#f59e0b', '#ec4899', '#8b5cf6'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const realAmount = hasPaymentData ? ctx.raw : 0;
                                return ctx.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(realAmount);
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
});
</script>
@endpush
