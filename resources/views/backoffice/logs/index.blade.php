@extends('layouts.app', ['title' => 'Log Monitoring & Diagnostik Sistem'])

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
        background: #dc2626;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.25);
    }
    .log-entry-item {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 16px 20px;
        margin-bottom: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        transition: border-color 0.15s;
    }
    .log-entry-item:hover {
        border-color: #cbd5e1;
    }
    .stack-trace-box {
        background: #0f172a;
        color: #f8fafc;
        border-radius: 8px;
        padding: 14px 16px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 11.5px;
        line-height: 1.6;
        overflow-x: auto;
        white-space: pre-wrap;
        word-break: break-all;
        margin-top: 12px;
        display: none;
    }
    .live-ping-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }
    @keyframes pingSlow {
        0% { transform: scale(0.95); opacity: 1; }
        50% { transform: scale(1.4); opacity: 0.4; }
        100% { transform: scale(0.95); opacity: 1; }
    }
    .ping-active {
        background: #16a34a;
        animation: pingSlow 1.8s infinite;
    }
    .ping-inactive {
        background: #94a3b8;
    }
</style>

<div style="margin-bottom: 24px;">
    <!-- Top Action & Diagnostic Header Banner -->
    <div class="card" style="padding: 20px 24px; border-radius: 16px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 16px; min-width: 0;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #ef4444, #f97316); color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35); flex-shrink: 0;">
                    <x-icon name="terminal" size="24" />
                </div>
                <div style="min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <h2 style="font-size: 20px; font-weight: 800; letter-spacing: -0.02em; color: #0f172a;">Pusat Diagnostik & Log Sistem</h2>
                        <span class="badge" style="background: #f1f5f9; color: #334155; font-family: monospace; font-size: 11px;">
                            {{ $selectedFile }}
                        </span>
                        <span class="badge badge-info" style="font-size: 10.5px;">
                            Rotasi Harian (30 Hari)
                        </span>
                        <span style="font-size: 12px; color: var(--text-muted);">
                            ({{ $fileSize > 1024 ? round($fileSize / 1024, 2) . ' MB' : $fileSize . ' KB' }})
                        </span>
                    </div>
                    <p style="color: var(--text-muted); font-size: 13px; margin-top: 3px;">
                        Streaming memory-safe untuk pemeriksaan runtime exception, database queries, dan failure stack trace aplikasi.
                    </p>
                </div>
            </div>

            <!-- Action Buttons Tray -->
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <!-- Auto-Refresh Toggle -->
                <button type="button" id="autoRefreshBtn" onclick="toggleAutoRefresh()" class="btn btn-outline btn-sm" style="font-size: 12.5px;">
                    <span id="livePingDot" class="live-ping-dot ping-inactive"></span>
                    <span id="autoRefreshLabel">Live Refresh (10s)</span>
                </button>

                <!-- Refresh Button -->
                <button type="button" onclick="window.location.reload();" class="btn btn-outline btn-sm" style="font-size: 12.5px;" title="Muat ulang halaman">
                    <x-icon name="refresh" size="14" />
                    <span>Refresh</span>
                </button>

                <!-- Download Raw Log Button -->
                <a href="{{ route('logs.download', ['file' => $selectedFile]) }}" class="btn btn-outline btn-sm" style="color: #0284c7; border-color: #bae6fd; font-size: 12.5px;" title="Unduh berkas log mentah">
                    <x-icon name="download" size="14" />
                    <span>Unduh Log</span>
                </a>

                <!-- Clear Log Button -->
                <button type="button" onclick="openClearModal()" class="btn btn-sm" style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; font-size: 12.5px;" title="Kosongkan isi berkas log">
                    <x-icon name="trash" size="14" />
                    <span>Bersihkan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="log-tabs">
        <a href="{{ route('logs.index') }}" class="log-tab-btn active">
            <x-icon name="terminal" size="16" />
            <span>🖥️ Log Sistem & Error (Monolog)</span>
        </a>
        <a href="{{ route('logs.transactions') }}" class="log-tab-btn">
            <x-icon name="cash-book" size="16" />
            <span>🧾 Log Backup Transaksi POS (Zero Data Loss)</span>
        </a>
    </div>

    <!-- Bento KPI Stats Grid (Adaptive 4-col) -->
    <div class="log-bento-grid">
        <!-- Total Log Entries -->
        <div class="log-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Total Baris Log</span>
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center;">
                    <x-icon name="clipboard" size="16" />
                </div>
            </div>
            <div style="font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">
                {{ number_format($stats['total'] ?? 0) }}
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                File: <code style="font-family: monospace;">{{ $selectedFile }}</code>
            </div>
        </div>

        <!-- Errors & Critical -->
        <div class="log-card" style="border-left: 4px solid #dc2626;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #dc2626; letter-spacing: 0.05em;">Errors &amp; Critical</span>
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center;">
                    <x-icon name="alert" size="16" />
                </div>
            </div>
            <div style="font-size: 26px; font-weight: 800; color: #dc2626; letter-spacing: -0.02em;">
                {{ number_format($stats['errors'] ?? 0) }}
            </div>
            <div style="font-size: 11px; color: #dc2626; margin-top: 4px; font-weight: 600;">
                Exception &amp; Fatal Failure
            </div>
        </div>

        <!-- Warnings -->
        <div class="log-card" style="border-left: 4px solid #d97706;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #d97706; letter-spacing: 0.05em;">Warnings</span>
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center;">
                    <x-icon name="warning" size="16" />
                </div>
            </div>
            <div style="font-size: 26px; font-weight: 800; color: #d97706; letter-spacing: -0.02em;">
                {{ number_format($stats['warnings'] ?? 0) }}
            </div>
            <div style="font-size: 11px; color: #d97706; margin-top: 4px; font-weight: 600;">
                Peringatan Operasional
            </div>
        </div>

        <!-- Info & Debug -->
        <div class="log-card" style="border-left: 4px solid #0284c7;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #0284c7; letter-spacing: 0.05em;">Info &amp; Debug</span>
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center;">
                    <x-icon name="server" size="16" />
                </div>
            </div>
            <div style="font-size: 26px; font-weight: 800; color: #0284c7; letter-spacing: -0.02em;">
                {{ number_format($stats['info'] ?? 0) }}
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                Audit Trail &amp; Telemetri
            </div>
        </div>
    </div>

    <!-- Filter & File Switcher Tray -->
    <div class="card" style="padding: 16px 20px; border-radius: 14px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('logs.index') }}" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1; min-width: 280px;">
                <!-- Search Input -->
                <div style="position: relative; flex: 1; min-width: 220px;">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari pesan error, exception class, file, waktu..."
                        class="form-control" style="width: 100%; padding: 8px 12px; font-size: 13px; border-radius: 8px; border: 1px solid var(--border);">
                </div>

                <!-- Select File Log -->
                <div style="min-width: 240px;">
                    <select name="file" onchange="this.form.submit()" class="form-control" style="width: 100%; padding: 8px 12px; font-size: 13px; border-radius: 8px; border: 1px solid var(--border); font-family: monospace;">
                        @if(!empty($filesMetadata))
                            @foreach($filesMetadata as $fKey => $fMeta)
                                <option value="{{ $fKey }}" {{ $selectedFile === $fKey ? 'selected' : '' }}>
                                    {{ $fMeta['label'] }}
                                </option>
                            @endforeach
                        @else
                            @foreach($files as $f)
                                <option value="{{ $f }}" {{ $selectedFile === $f ? 'selected' : '' }}>
                                    📁 {{ $f }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Select Level Log -->
                <div style="min-width: 160px;">
                    <select name="level" onchange="this.form.submit()" class="form-control" style="width: 100%; padding: 8px 12px; font-size: 13px; border-radius: 8px; border: 1px solid var(--border);">
                        <option value="all">Semua Level Log</option>
                        <option value="error" {{ request('level') === 'error' ? 'selected' : '' }}>🔴 ERROR &amp; CRITICAL</option>
                        <option value="warning" {{ request('level') === 'warning' ? 'selected' : '' }}>🟡 WARNING</option>
                        <option value="info" {{ request('level') === 'info' ? 'selected' : '' }}>🔵 INFO</option>
                        <option value="debug" {{ request('level') === 'debug' ? 'selected' : '' }}>🟣 DEBUG</option>
                    </select>
                </div>
            </div>

            <!-- Submit & Reset CTAs -->
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm" style="font-size: 13px; padding: 8px 16px;">
                    <x-icon name="search" size="14" />
                    <span>Filter</span>
                </button>

                @if(request()->hasAny(['search', 'level']))
                    <a href="{{ route('logs.index', ['file' => $selectedFile]) }}" class="btn btn-outline btn-sm" style="font-size: 13px;">
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Log Entries Feed (Bento Cards List) -->
    <div style="display: flex; flex-direction: column; gap: 12px;">
        @forelse($logs as $index => $log)
            @php
                $isErr = in_array($log['level'], ['ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT'], true);
                $isWarn = $log['level'] === 'WARNING';
                $accentColor = $isErr ? '#dc2626' : ($isWarn ? '#d97706' : '#0284c7');
                $badgeClass = $isErr ? 'badge-danger' : ($isWarn ? 'badge-warning' : 'badge-info');
                $logKey = $log['id'] ?? $index;
            @endphp
            <div class="log-entry-item" style="border-left: 4px solid {{ $accentColor }};">
                <!-- Top Meta Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span class="badge {{ $badgeClass }}" style="font-weight: 800; font-size: 11px;">
                            {{ $log['level'] }}
                        </span>
                        <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 11px;">
                            {{ $log['env'] }}
                        </span>
                        <span style="font-family: monospace; font-size: 12px; color: var(--text-muted);">
                            🕒 {{ $log['timestamp'] }} WIB
                        </span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        @if(!empty($log['stack']))
                            <button type="button" onclick="toggleTrace('trace-{{ $logKey }}', this)" class="btn btn-outline btn-sm" style="font-size: 11.5px; padding: 4px 10px; min-height: 28px;">
                                <x-icon name="eye" size="13" />
                                <span>Lihat Stack Trace</span>
                            </button>
                            <button type="button" onclick="copyTrace('trace-content-{{ $logKey }}', this)" class="btn btn-outline btn-sm" style="font-size: 11.5px; padding: 4px 10px; min-height: 28px;" title="Salin error log">
                                <x-icon name="copy" size="13" />
                                <span>Salin</span>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Log Message Body -->
                <div style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; color: #0f172a; line-height: 1.5; word-break: break-all;">
                    {{ $log['message'] }}
                </div>

                <!-- Hidden / Expandable Stack Trace -->
                @if(!empty($log['stack']))
                    <div id="trace-{{ $logKey }}" class="stack-trace-box">
                        <div id="trace-content-{{ $logKey }}">{{ $log['stack'] }}</div>
                    </div>
                @endif
            </div>
        @empty
            <div class="card" style="text-align: center; padding: 48px 24px; color: var(--text-muted); border-radius: 14px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #f1f5f9; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <x-icon name="check-circle" size="24" />
                </div>
                <h4 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Tidak Ada Log Tercatat</h4>
                <p style="font-size: 13px;">Tidak ada rekaman log sistem yang cocok dengan kriteria filter pada berkas <strong>{{ $selectedFile }}</strong>.</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Modal Dialog Clear Log -->
<div id="clearModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 440px; padding: 24px; margin: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <x-icon name="trash" size="20" />
            </div>
            <div>
                <h3 style="font-size: 17px; font-weight: 800; color: #0f172a;">Bersihkan Berkas Log?</h3>
                <span style="font-size: 12px; color: var(--text-muted); font-family: monospace;">{{ $selectedFile }}</span>
            </div>
        </div>
        <p style="font-size: 13.5px; color: #475569; line-height: 1.5; margin-bottom: 20px;">
            Tindakan ini akan mengosongkan seluruh riwayat error dan stack trace pada berkas log yang dipilih. Tindakan ini tidak dapat dibatalkan.
        </p>
        <form method="POST" action="{{ route('logs.clear') }}">
            @csrf
            <input type="hidden" name="file" value="{{ $selectedFile }}">
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeClearModal()" class="btn btn-outline" style="font-size: 13px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="background: #dc2626; font-size: 13px;">Ya, Kosongkan Log</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let autoRefreshActive = false;
    let autoRefreshTimer = null;

    function initAutoRefresh() {
        if (localStorage.getItem('hiroshima_log_auto_refresh') === '1') {
            autoRefreshActive = true;
            document.getElementById('livePingDot').className = 'live-ping-dot ping-active';
            document.getElementById('autoRefreshLabel').textContent = 'Live (10s Aktif)';
            autoRefreshTimer = setInterval(() => {
                window.location.reload();
            }, 10000);
        }
    }

    function toggleAutoRefresh() {
        autoRefreshActive = !autoRefreshActive;
        if (autoRefreshActive) {
            localStorage.setItem('hiroshima_log_auto_refresh', '1');
            document.getElementById('livePingDot').className = 'live-ping-dot ping-active';
            document.getElementById('autoRefreshLabel').textContent = 'Live (10s Aktif)';
            autoRefreshTimer = setInterval(() => {
                window.location.reload();
            }, 10000);
        } else {
            localStorage.removeItem('hiroshima_log_auto_refresh');
            document.getElementById('livePingDot').className = 'live-ping-dot ping-inactive';
            document.getElementById('autoRefreshLabel').textContent = 'Live Refresh (10s)';
            if (autoRefreshTimer) {
                clearInterval(autoRefreshTimer);
                autoRefreshTimer = null;
            }
        }
    }

    function toggleTrace(id, btn) {
        const box = document.getElementById(id);
        if (!box) return;
        if (box.style.display === 'block') {
            box.style.display = 'none';
            btn.querySelector('span').textContent = 'Lihat Stack Trace';
        } else {
            box.style.display = 'block';
            btn.querySelector('span').textContent = 'Tutup Stack Trace';
        }
    }

    function copyTrace(elementId, btn) {
        const el = document.getElementById(elementId);
        if (!el) return;
        const text = el.innerText || el.textContent;
        navigator.clipboard.writeText(text).then(() => {
            const span = btn.querySelector('span');
            const originalText = span.textContent;
            span.textContent = 'Tersalin!';
            btn.style.borderColor = '#16a34a';
            btn.style.color = '#16a34a';
            setTimeout(() => {
                span.textContent = originalText;
                btn.style.borderColor = '';
                btn.style.color = '';
            }, 2000);
        }).catch(() => {
            alert('Stack trace berhasil disalin ke clipboard.');
        });
    }

    function openClearModal() {
        const m = document.getElementById('clearModal');
        if (m) m.style.display = 'flex';
    }

    function closeClearModal() {
        const m = document.getElementById('clearModal');
        if (m) m.style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', initAutoRefresh);
</script>
@endpush
@endsection

