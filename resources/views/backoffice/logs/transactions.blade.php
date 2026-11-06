@extends('layouts.app', ['title' => 'Log Backup Transaksi POS'])

@section('content')
<style>
    .log-bento-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }
    @media (max-width: 991px) {
        .log-bento-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
    }
    @media (max-width: 575px) {
        .log-bento-grid {
            grid-template-columns: 1fr;
        }
    }
    .log-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .log-tabs {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 12px;
        flex-wrap: wrap;
    }
    .log-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13.5px;
        font-weight: 700;
        text-decoration: none;
        color: var(--text-muted);
        background: transparent;
        transition: all 0.15s;
    }
    .log-tab-btn:hover {
        background: #f1f5f9;
        color: var(--text-main);
    }
    .log-tab-btn.active {
        background: #059669;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
    }
    .json-viewer-box {
        background: #0f172a;
        color: #38bdf8;
        border-radius: 10px;
        padding: 16px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12px;
        line-height: 1.6;
        max-height: 380px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-break: break-all;
    }
</style>

<div style="margin-bottom: 24px;">
    <!-- Top Action & Diagnostic Header Banner -->
    <div class="card" style="padding: 20px 24px; border-radius: 16px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 16px; min-width: 0;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #059669, #10b981); color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35); flex-shrink: 0;">
                    <x-icon name="cash-book" size="24" />
                </div>
                <div style="min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <h2 style="font-size: 20px; font-weight: 800; letter-spacing: -0.02em; color: #0f172a;">Log Backup Transaksi POS</h2>
                        <span class="badge badge-success" style="font-size: 11px;">
                            Zero Revenue Loss Guard
                        </span>
                        <span class="badge" style="background: #f1f5f9; color: #334155; font-size: 11px;">
                            Dual-Layer (DB &amp; JSONL Disk)
                        </span>
                    </div>
                    <p style="color: var(--text-muted); font-size: 13px; margin-top: 3px;">
                        Setiap transaksi yang dibuat di kasir diarsipkan dalam format raw payload asli sebagai jaminan backup anti-hilang saat sync.
                    </p>
                </div>
            </div>

            <!-- Action Buttons Tray -->
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <!-- Refresh Button -->
                <button type="button" onclick="window.location.reload();" class="btn btn-outline btn-sm" style="font-size: 12.5px;" title="Muat ulang">
                    <x-icon name="refresh" size="14" />
                    <span>Refresh</span>
                </button>

                <!-- Download JSONL Backup Button -->
                @if(!empty($jsonlFiles))
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <a href="{{ route('logs.transactions.download', ['file' => $jsonlFiles[0]]) }}" class="btn btn-primary btn-sm" style="background: #059669; font-size: 12.5px;" title="Unduh berkas JSONL transaksi hari ini">
                            <x-icon name="download" size="14" />
                            <span>Unduh JSONL ({{ $jsonlFiles[0] }})</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="log-tabs">
        <a href="{{ route('logs.index') }}" class="log-tab-btn">
            <x-icon name="terminal" size="16" />
            <span>🖥️ Log Sistem & Error (Monolog)</span>
        </a>
        <a href="{{ route('logs.transactions') }}" class="log-tab-btn active">
            <x-icon name="cash-book" size="16" />
            <span>🧾 Log Backup Transaksi POS (Zero Data Loss)</span>
        </a>
    </div>

    <!-- Bento KPI Stats Grid (Adaptive 4-col) -->
    <div class="log-bento-grid">
        <!-- Total Transaksi Backup -->
        <div class="log-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Total Transaksi Terfilter</span>
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center;">
                    <x-icon name="clipboard" size="16" />
                </div>
            </div>
            <div style="font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">
                {{ number_format($stats['total_filtered'] ?? 0) }}
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                Kumulatif sistem: {{ number_format($stats['total_all'] ?? 0) }} transaksi
            </div>
        </div>

        <!-- Transaksi Sukses -->
        <div class="log-card" style="border-left: 4px solid #16a34a;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #16a34a; letter-spacing: 0.05em;">Transaksi Tersinkron</span>
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center;">
                    <x-icon name="check-circle" size="16" />
                </div>
            </div>
            <div style="font-size: 26px; font-weight: 800; color: #16a34a; letter-spacing: -0.02em;">
                {{ number_format($stats['completed'] ?? 0) }}
            </div>
            <div style="font-size: 11px; color: #16a34a; margin-top: 4px; font-weight: 600;">
                Status Sukses &amp; Flagged
            </div>
        </div>

        <!-- Transaksi Flagged / Offline Sync -->
        <div class="log-card" style="border-left: 4px solid #d97706;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #d97706; letter-spacing: 0.05em;">Sync Offline (Flagged)</span>
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center;">
                    <x-icon name="warning" size="16" />
                </div>
            </div>
            <div style="font-size: 26px; font-weight: 800; color: #d97706; letter-spacing: -0.02em;">
                {{ number_format($stats['flagged'] ?? 0) }}
            </div>
            <div style="font-size: 11px; color: #d97706; margin-top: 4px; font-weight: 600;">
                Diselamatkan oleh Zero Loss
            </div>
        </div>

        <!-- Akumulasi Omset Backup -->
        <div class="log-card" style="border-left: 4px solid #059669;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #059669; letter-spacing: 0.05em;">Akumulasi Nominal Backup</span>
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center;">
                    <x-icon name="cash" size="16" />
                </div>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #059669; letter-spacing: -0.02em;">
                Rp {{ number_format($stats['total_amount'] ?? 0, 0, ',', '.') }}
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                Nilai omset tercatat dalam log
            </div>
        </div>
    </div>

    <!-- Filter Hub: Presets, Date Range, Branch, Status, Search -->
    <div class="card" style="padding: 16px 20px; border-radius: 14px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('logs.transactions') }}">
            <!-- Top Preset Buttons -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Waktu Cepat:</span>
                    <a href="{{ route('logs.transactions', ['preset' => 'today', 'branch_id' => request('branch_id'), 'status' => request('status')]) }}"
                       class="btn btn-sm {{ $datePreset === 'today' ? 'btn-primary' : 'btn-outline' }}" style="font-size: 12px; border-radius: 20px; padding: 3px 12px; {{ $datePreset === 'today' ? 'background: #059669;' : '' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('logs.transactions', ['preset' => 'yesterday', 'branch_id' => request('branch_id'), 'status' => request('status')]) }}"
                       class="btn btn-sm {{ $datePreset === 'yesterday' ? 'btn-primary' : 'btn-outline' }}" style="font-size: 12px; border-radius: 20px; padding: 3px 12px; {{ $datePreset === 'yesterday' ? 'background: #059669;' : '' }}">
                        Kemarin
                    </a>
                    <a href="{{ route('logs.transactions', ['preset' => '7days', 'branch_id' => request('branch_id'), 'status' => request('status')]) }}"
                       class="btn btn-sm {{ $datePreset === '7days' ? 'btn-primary' : 'btn-outline' }}" style="font-size: 12px; border-radius: 20px; padding: 3px 12px; {{ $datePreset === '7days' ? 'background: #059669;' : '' }}">
                        7 Hari Terakhir
                    </a>
                    <a href="{{ route('logs.transactions', ['preset' => 'all', 'branch_id' => request('branch_id'), 'status' => request('status')]) }}"
                       class="btn btn-sm {{ $datePreset === 'all' ? 'btn-primary' : 'btn-outline' }}" style="font-size: 12px; border-radius: 20px; padding: 3px 12px; {{ $datePreset === 'all' ? 'background: #059669;' : '' }}">
                        Semua Riwayat
                    </a>
                </div>

                @if(!empty($jsonlFiles))
                    <div style="font-size: 12px; color: var(--text-muted);">
                        Berkas Backup Harian: <strong>{{ count($jsonlFiles) }} berkas</strong>
                    </div>
                @endif
            </div>

            <!-- Bottom Row Filters -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                <input type="hidden" name="preset" value="{{ $datePreset }}">

                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1;">
                    <!-- Search Input -->
                    <div style="min-width: 220px; flex: 1;">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari No Order, UUID, Meja, Nama Pelanggan..."
                            class="form-control" style="width: 100%; padding: 8px 12px; font-size: 13px; border-radius: 8px; border: 1px solid var(--border);">
                    </div>

                    <!-- Branch Filter -->
                    <div style="min-width: 180px;">
                        <select name="branch_id" onchange="this.form.submit()" class="form-control" style="width: 100%; padding: 8px 12px; font-size: 13px; border-radius: 8px; border: 1px solid var(--border);">
                            <option value="">Semua Cabang</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div style="min-width: 160px;">
                        <select name="status" onchange="this.form.submit()" class="form-control" style="width: 100%; padding: 8px 12px; font-size: 13px; border-radius: 8px; border: 1px solid var(--border);">
                            <option value="all">Semua Status</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>🟢 Completed</option>
                            <option value="accepted_flagged" {{ request('status') === 'accepted_flagged' ? 'selected' : '' }}>🟡 Accepted Flagged</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>🔴 Rejected</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>❌ Failed</option>
                            <option value="duplicate_ok" {{ request('status') === 'duplicate_ok' ? 'selected' : '' }}>🔵 Duplicate OK</option>
                            <option value="on_hold" {{ request('status') === 'on_hold' ? 'selected' : '' }}>🟠 On Hold</option>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="submit" class="btn btn-primary btn-sm" style="background: #059669; font-size: 13px; padding: 8px 16px;">
                        <x-icon name="search" size="14" />
                        <span>Filter</span>
                    </button>

                    @if(request()->hasAny(['search', 'branch_id', 'status']) || $datePreset !== 'today')
                        <a href="{{ route('logs.transactions') }}" class="btn btn-outline btn-sm" style="font-size: 13px;">
                            <span>Reset</span>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="card" style="padding: 0; border-radius: 14px; overflow: hidden;">
        <div class="table-responsive">
            <table class="data-table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="padding-left: 20px;">No Order &amp; UUID</th>
                        <th>Cabang</th>
                        <th>Kasir</th>
                        <th>Meja / Pelanggan</th>
                        <th style="text-align: right;">Total Transaksi</th>
                        <th style="text-align: center;">Status</th>
                        <th>Sumber</th>
                        <th>Waktu Dibuat (WIB)</th>
                        <th style="text-align: center; padding-right: 20px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $statusClass = match($log->status) {
                                'completed' => 'badge-success',
                                'accepted_flagged' => 'badge-warning',
                                'rejected', 'failed' => 'badge-danger',
                                'duplicate_ok' => 'badge-info',
                                default => 'badge',
                            };
                        @endphp
                        <tr>
                            <td style="padding-left: 20px;">
                                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">{{ $log->order_no }}</div>
                                <div style="font-family: monospace; font-size: 11px; color: var(--text-muted); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->order_uuid }}">
                                    {{ $log->order_uuid }}
                                </div>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #334155;">{{ $log->branch->name ?? '-' }}</span>
                            </td>
                            <td>
                                <span style="color: var(--text-main);">{{ $log->user->name ?? 'Kasir' }}</span>
                            </td>
                            <td>
                                @if($log->table_name && $log->customer_name)
                                    <div style="font-weight: 600;">{{ $log->table_name }}</div>
                                    <div style="font-size: 11.5px; color: var(--text-muted);">{{ $log->customer_name }}</div>
                                @else
                                    <div>{{ $log->table_name ?? ($log->customer_name ?? '-') }}</div>
                                @endif
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;">
                                Rp {{ number_format($log->total_amount, 0, ',', '.') }}
                                <div style="font-size: 11px; color: var(--text-muted); font-weight: normal;">
                                    {{ $log->items_count }} item
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge {{ $statusClass }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td>
                                <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 10.5px;">
                                    {{ strtoupper($log->source) }}
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 12.5px; color: #0f172a;">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                @if($log->client_created_at)
                                    <div style="font-size: 10.5px; color: var(--text-muted);" title="Waktu dibuat di tablet">
                                        Tablet: {{ $log->client_created_at->format('H:i:s') }}
                                    </div>
                                @endif
                            </td>
                            <td style="text-align: center; padding-right: 20px;">
                                <button type="button" onclick="showPayloadModal({{ $log->id }})" class="btn btn-outline btn-sm" style="font-size: 12px; padding: 4px 10px; min-height: 28px;">
                                    <x-icon name="eye" size="13" />
                                    <span>Lihat Payload</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 48px; color: var(--text-muted);">
                                <div style="width: 44px; height: 44px; border-radius: 10px; background: #f1f5f9; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                                    <x-icon name="clipboard" size="20" />
                                </div>
                                <div style="font-weight: 700; font-size: 14px; color: #0f172a; margin-bottom: 2px;">Belum Ada Riwayat Backup Transaksi</div>
                                <div style="font-size: 12.5px;">Tidak ditemukan data transaksi untuk filter yang dipilih.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Inspect Payload Backup -->
<div id="payloadModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 680px; max-height: 85vh; display: flex; flex-direction: column; margin: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <!-- Modal Header -->
        <div style="padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center;">
                    <x-icon name="database" size="18" />
                </div>
                <div>
                    <h3 id="modalOrderNo" style="font-size: 16px; font-weight: 800; color: #0f172a;">Detail Payload Backup</h3>
                    <div id="modalOrderUuid" style="font-family: monospace; font-size: 11px; color: var(--text-muted);">-</div>
                </div>
            </div>
            <button type="button" onclick="closePayloadModal()" style="background: transparent; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer; padding: 4px;">&times;</button>
        </div>

        <!-- Modal Body -->
        <div style="padding: 20px 24px; overflow-y: auto; flex: 1;">
            <!-- Metadata Summary Grid -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px; background: #f8fafc; padding: 14px; border-radius: 10px; border: 1px solid var(--border); font-size: 12.5px;">
                <div>Cabang: <strong id="modalBranch">-</strong></div>
                <div>Kasir: <strong id="modalCashier">-</strong></div>
                <div>Status Sync: <span id="modalStatus" class="badge badge-success">-</span></div>
                <div>Total Nominal: <strong id="modalAmount" style="color: #059669;">-</strong></div>
                <div>Waktu Server: <span id="modalServerTime">-</span></div>
                <div>Waktu Tablet: <span id="modalClientTime">-</span></div>
            </div>

            <!-- Error message if any -->
            <div id="modalErrorContainer" style="display: none; background: #fee2e2; border: 1px solid #fecaca; color: #dc2626; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; margin-bottom: 14px;">
                <strong>Catatan Error:</strong> <span id="modalErrorText">-</span>
            </div>

            <!-- Raw JSON Title & Action -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Raw JSON Payload</span>
                <button type="button" id="copyPayloadBtn" onclick="copyModalPayload()" class="btn btn-outline btn-sm" style="font-size: 11.5px; padding: 3px 8px;">
                    <x-icon name="copy" size="13" />
                    <span>Salin JSON</span>
                </button>
            </div>

            <pre id="modalJsonBox" class="json-viewer-box">Memuat data payload...</pre>
        </div>

        <!-- Modal Footer -->
        <div style="padding: 14px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end;">
            <button type="button" onclick="closePayloadModal()" class="btn btn-outline" style="font-size: 13px;">Tutup</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentRawJson = '';

    function showPayloadModal(id) {
        const modal = document.getElementById('payloadModal');
        modal.style.display = 'flex';
        document.getElementById('modalJsonBox').textContent = 'Mengambil data dari server...';

        fetch(`/backoffice/logs/transactions/${id}/payload`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(response => {
            if (response.success && response.data) {
                const d = response.data;
                document.getElementById('modalOrderNo').textContent = d.order_no;
                document.getElementById('modalOrderUuid').textContent = d.order_uuid;
                document.getElementById('modalBranch').textContent = d.branch_name;
                document.getElementById('modalCashier').textContent = d.cashier_name;
                document.getElementById('modalStatus').textContent = d.status;
                document.getElementById('modalAmount').textContent = 'Rp ' + Number(d.total_amount).toLocaleString('id-ID');
                document.getElementById('modalServerTime').textContent = d.created_at;
                document.getElementById('modalClientTime').textContent = d.client_created_at;

                if (d.error_message) {
                    document.getElementById('modalErrorContainer').style.display = 'block';
                    document.getElementById('modalErrorText').textContent = d.error_message;
                } else {
                    document.getElementById('modalErrorContainer').style.display = 'none';
                }

                currentRawJson = typeof d.parsed_payload === 'object' 
                    ? JSON.stringify(d.parsed_payload, null, 2) 
                    : d.raw_payload;

                document.getElementById('modalJsonBox').textContent = currentRawJson;
            }
        })
        .catch(err => {
            document.getElementById('modalJsonBox').textContent = 'Gagal memuat detail payload: ' + err.message;
        });
    }

    function closePayloadModal() {
        document.getElementById('payloadModal').style.display = 'none';
    }

    function copyModalPayload() {
        if (!currentRawJson) return;
        const btn = document.getElementById('copyPayloadBtn');
        navigator.clipboard.writeText(currentRawJson).then(() => {
            const span = btn.querySelector('span');
            const orig = span.textContent;
            span.textContent = 'Tersalin!';
            btn.style.color = '#059669';
            btn.style.borderColor = '#059669';
            setTimeout(() => {
                span.textContent = orig;
                btn.style.color = '';
                btn.style.borderColor = '';
            }, 2000);
        }).catch(() => {
            alert('Payload berhasil disalin.');
        });
    }
</script>
@endpush
@endsection

