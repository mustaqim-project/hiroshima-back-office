@extends('layouts.app', ['title' => 'Rekonsiliasi Shift Kasir & Realtime Monitoring'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">Rekonsiliasi Shift Kasir & Realtime Monitoring</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Pantau transaksi realtime dari buka hingga tutup shift, verifikasi kasir yang buka dan tutup shift, serta audit selisih kas fisik.</p>
    </div>
    
    <!-- Refresh Button -->
    <a href="{{ route('reconciliation.index', request()->query()) }}" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
        <x-icon name="refresh-cw" size="14" />
        <span>Segarkan Data Realtime</span>
    </a>
</div>

<!-- Banner Monitoring Shift Aktif Realtime -->
@if(isset($activeShifts) && $activeShifts->isNotEmpty())
    <div style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
            <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25); animation: pulseDot 2s infinite;"></span>
            <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">
                Shift Sedang Berjalan (Realtime Live Active Shifts)
            </h3>
            <span class="badge" style="background: #dcfce7; color: #166534; font-weight: 700; font-size: 11px;">
                {{ $activeShifts->count() }} Outlet Aktif
            </span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px;">
            @foreach($activeShifts as $ashift)
                <div style="background: #ffffff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 18px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.06); position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #3b82f6, #10b981);"></div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                        <div>
                            <div style="font-size: 15px; font-weight: 800; color: #0f172a;">{{ $ashift->branch->name ?? 'Cabang #' . $ashift->branch_id }}</div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                Shift ID: <strong>#{{ $ashift->id }}</strong> &bull; Buka: <strong>{{ $ashift->opened_at->format('d/m/Y H:i') }} WIB</strong>
                            </div>
                        </div>
                        <span class="badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #2563eb; display: inline-block;"></span>
                            LIVE
                        </span>
                    </div>

                    <!-- Detail Kasir Buka & Status Tutup -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; font-size: 12px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span style="color: #64748b;">Kasir Buka Shift:</span>
                            <strong style="color: #0f172a;">{{ $ashift->user->name ?? 'Kasir' }} ({{ $ashift->user->username ?? '-' }})</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">Status Penutupan:</span>
                            <span style="color: #2563eb; font-weight: 600;">Sedang Berjalan (Belum Tutup)</span>
                        </div>
                    </div>

                    <!-- Metrik Transaksi Realtime -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                        <div style="background: #f1f5f9; padding: 10px; border-radius: 8px;">
                            <div style="font-size: 11px; color: #64748b;">Transaksi Selesai</div>
                            <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 2px;">
                                {{ $ashift->live_completed_tx }} Tx
                            </div>
                        </div>
                        <div style="background: #f1f5f9; padding: 10px; border-radius: 8px;">
                            <div style="font-size: 11px; color: #64748b;">Penjualan Bersih Live</div>
                            <div style="font-size: 15px; font-weight: 800; color: #10b981; margin-top: 2px;">
                                Rp {{ number_format($ashift->live_sales, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #e2e8f0; padding-top: 12px;">
                        <div>
                            <div style="font-size: 11px; color: #64748b;">Ekspektasi Kas Laci Saat Ini</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">
                                Rp {{ number_format($ashift->live_expected_cash, 0, ',', '.') }}
                            </div>
                        </div>
                        <a href="{{ route('reconciliation.report', $ashift) }}" target="_blank" class="btn btn-sm" style="background: #2563eb; color: #ffffff; font-weight: 600; font-size: 12px; padding: 6px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none;">
                            <x-icon name="eye" size="13" />
                            <span>Pantau Realtime (X-Report)</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 16px; padding: 14px 18px;">
    <form method="GET" action="{{ route('reconciliation.index') }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin: 0;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <label for="branch_id" style="font-size: 13px; font-weight: 600; color: #475569; white-space: nowrap;">Cabang:</label>
            <select name="branch_id" id="branch_id" onchange="this.form.submit()" style="padding: 6px 10px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff;">
                <option value="">Semua Cabang (All)</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ (string)$selectedBranchId === (string)$b->id ? 'selected' : '' }}>
                        {{ $b->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <label for="status" style="font-size: 13px; font-weight: 600; color: #475569; white-space: nowrap;">Status Shift:</label>
            <select name="status" id="status" onchange="this.form.submit()" style="padding: 6px 10px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff;">
                <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Shift (Termasuk Sedang Berjalan)</option>
                <option value="open" {{ $selectedStatus === 'open' ? 'selected' : '' }}>🟢 Sedang Berjalan (Realtime)</option>
                <option value="closed" {{ $selectedStatus === 'closed' ? 'selected' : '' }}>Menunggu Rekonsiliasi Kasir</option>
                <option value="auto_closed" {{ $selectedStatus === 'auto_closed' ? 'selected' : '' }}>Auto-Close 15 Jam</option>
                <option value="reconciled" {{ $selectedStatus === 'reconciled' ? 'selected' : '' }}>Direkonsiliasi</option>
            </select>
        </div>

        @if($selectedBranchId || ($selectedStatus && $selectedStatus !== 'all'))
            <a href="{{ route('reconciliation.index') }}" class="btn btn-outline btn-sm" style="font-size: 12px; padding: 6px 10px;">
                Reset Filter
            </a>
        @endif
    </form>
</div>

<!-- Tabel Riwayat & Rekonsiliasi Shift -->
<div class="card">
    <div class="table-responsive">
        <table class="data-table">
        <thead>
            <tr>
                <th style="width: 70px;">ID Shift</th>
                <th>Cabang</th>
                <th>Buka Shift Oleh</th>
                <th>Tutup Shift Oleh</th>
                <th style="text-align: right; white-space: nowrap;">Modal Awal</th>
                <th style="text-align: right; white-space: nowrap;">Kas Diharapkan</th>
                <th style="text-align: right; white-space: nowrap;">Kas Fisik Kasir</th>
                <th style="text-align: right; white-space: nowrap;">Selisih Kas</th>
                <th style="text-align: center; width: 150px;">Status Shift</th>
                <th style="text-align: center; min-width: 190px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shifts as $s)
                <tr style="{{ $s->status === 'open' ? 'background: #f0fdf4;' : '' }}">
                    <td><strong>#{{ $s->id }}</strong></td>
                    <td>
                        <div style="font-weight: 600;">{{ $s->branch->name ?? '-' }}</div>
                        <div style="font-size: 11px; color: var(--text-muted);">{{ $s->branch->code ?? '' }}</div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #0f172a;">{{ $s->user->name ?? '-' }}</div>
                        <div style="font-size: 11.5px; color: var(--text-muted); white-space: nowrap;">
                            {{ $s->opened_at->format('d/m/Y H:i') }} WIB
                        </div>
                    </td>
                    <td>
                        @if($s->status === 'open')
                            <span class="badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 11px; font-weight: 600;">
                                Sedang Berjalan
                            </span>
                        @elseif($s->status === 'auto_closed' || $s->close_type === 'auto')
                            <span class="badge badge-danger" style="font-size: 11px;" title="Ditutup otomatis oleh sistem setelah 15 jam">
                                Sistem (Auto 15h)
                            </span>
                            @if($s->closed_at)
                                <div style="font-size: 11.5px; color: var(--text-muted); white-space: nowrap; margin-top: 2px;">
                                    {{ $s->closed_at->format('d/m/Y H:i') }}
                                </div>
                            @endif
                        @else
                            <div style="font-weight: 600; color: #0f172a;">
                                {{ $s->closer->name ?? $s->user->name ?? '-' }}
                            </div>
                            @if($s->closed_at)
                                <div style="font-size: 11.5px; color: var(--text-muted); white-space: nowrap;">
                                    {{ $s->closed_at->format('d/m/Y H:i') }} WIB
                                </div>
                            @endif
                        @endif
                    </td>
                    <td style="text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;">
                        Rp {{ number_format($s->starting_cash, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;">
                        <strong>Rp {{ number_format($s->expected_cash ?? 0, 0, ',', '.') }}</strong>
                        @if($s->status === 'open')
                            <div style="font-size: 10.5px; color: #16a34a; font-weight: 600;">(Live Realtime)</div>
                        @endif
                    </td>
                    <td style="text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;">
                        @if($s->status === 'open')
                            <span style="color: #64748b; font-size: 12px; font-style: italic;">Belum Tutup</span>
                        @elseif($s->actual_cash !== null)
                            Rp {{ number_format($s->actual_cash, 0, ',', '.') }}
                        @else
                            <span style="color: var(--text-muted); font-size: 12px;">- (Auto)</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;">
                        @if($s->status === 'open')
                            <span style="color: #64748b; font-size: 12px; font-style: italic;">Menunggu Tutup</span>
                        @elseif($s->cash_difference !== null)
                            <strong style="color: {{ $s->cash_difference < 0 ? 'var(--danger)' : ($s->cash_difference > 0 ? 'var(--warning)' : 'var(--success)') }};">
                                {{ $s->cash_difference < 0 ? '-' : ($s->cash_difference > 0 ? '+' : '') }} Rp {{ number_format(abs($s->cash_difference), 0, ',', '.') }}
                            </strong>
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if($s->status === 'open')
                            <span class="badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #2563eb; display: inline-block;"></span>
                                Sedang Berjalan
                            </span>
                        @elseif($s->status === 'reconciled')
                            <span class="badge badge-success">Direkonsiliasi</span>
                        @elseif($s->status === 'auto_closed')
                            <span class="badge badge-danger">Auto-Close 15h</span>
                        @elseif($s->status === 'closed')
                            <span class="badge badge-warning">Menunggu Rekonsiliasi</span>
                        @else
                            <span class="badge badge-info">{{ $s->status }}</span>
                        @endif
                    </td>
                    <td style="text-align: center; white-space: nowrap;">
                        <div style="display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                            <a href="{{ route('reconciliation.report', $s) }}" 
                               class="btn btn-outline btn-sm" 
                               target="_blank" 
                               title="{{ $s->status === 'open' ? 'Pantau Transaksi Realtime (X-Report)' : 'Cetak / Lihat Laporan Close Shift' }}"
                               style="padding: 5px 10px; font-size: 12px; height: 30px; min-height: 30px; border-color: #cbd5e1; display: inline-flex; align-items: center; gap: 5px;">
                                <x-icon name="{{ $s->status === 'open' ? 'eye' : 'printer' }}" size="13" />
                                <span>{{ $s->status === 'open' ? 'Pantau Live' : 'Laporan' }}</span>
                            </a>

                            @if($s->status === 'open')
                                <span style="font-size: 11px; color: #2563eb; font-weight: 600; padding: 5px 10px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px;" title="Shift sedang berlangsung. Tombol rekonsiliasi kas fisik akan aktif setelah shift ditutup oleh kasir.">
                                    Shift Aktif
                                </span>
                            @elseif($s->status !== 'reconciled')
                                <button type="button" 
                                        class="btn btn-sm btn-reconcile-action" 
                                        onclick="openReconcileModal({{ $s->id }}, '{{ addslashes($s->branch->name ?? '-') }}', '{{ addslashes($s->user->name ?? '-') }}', 'Rp {{ number_format($s->starting_cash, 0, ',', '.') }}', 'Rp {{ number_format($s->expected_cash ?? 0, 0, ',', '.') }}', '{{ $s->actual_cash !== null ? 'Rp ' . number_format($s->actual_cash, 0, ',', '.') : '-' }}', '{{ $s->cash_difference !== null ? ($s->cash_difference < 0 ? '-' : ($s->cash_difference > 0 ? '+' : '')) . ' Rp ' . number_format(abs($s->cash_difference), 0, ',', '.') : '-' }}', {{ $s->cash_difference ?? 0 }})"
                                        style="background: #10b981; color: #ffffff; border: none; padding: 5px 12px; font-size: 12px; height: 30px; min-height: 30px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 1px 2px rgba(16, 185, 129, 0.2); cursor: pointer; border-radius: 6px; transition: background 0.15s ease;"
                                        title="Tinjau Kas & Setujui Rekonsiliasi">
                                    <x-icon name="check-circle" size="13" />
                                    <span>Setujui</span>
                                </button>
                            @else
                                <span class="badge badge-success" 
                                      style="padding: 5px 10px; font-size: 11px; height: 26px; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #bbf7d0;" 
                                      title="{{ $s->reconciliation_notes ? 'Catatan: ' . $s->reconciliation_notes : 'Sudah direkonsiliasi' }}">
                                    <x-icon name="check" size="12" />
                                    <span>Selesai</span>
                                </span>
                            @endif
                        </div>
                        @if($s->status === 'reconciled' && $s->reconciliation_notes)
                            <div style="font-size: 10.5px; color: var(--text-muted); text-align: center; margin-top: 4px; max-width: 180px; margin-left: auto; margin-right: auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $s->reconciliation_notes }}">
                                <span style="font-weight: 600; color: #64748b;">Ket:</span> {{ $s->reconciliation_notes }}
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 36px;">
                        Belum ada shift yang tercatat sesuai kriteria filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    {{ $shifts->links() }}
</div>

<!-- Modal Rekonsiliasi Shift (FSD-09 / RPT-09) -->
<div id="reconcileModal" class="reconcile-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 16px;" onclick="if(event.target === this) closeReconcileModal()">
    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 520px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; border: 1px solid #e2e8f0; animation: modalFadeIn 0.2s ease-out;">
        <!-- Header -->
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: #dcfce7; color: #166534; width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <x-icon name="check-circle" size="20" />
                </div>
                <div>
                    <h3 id="modalShiftTitle" style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Rekonsiliasi Shift Kasir</h3>
                    <p id="modalShiftSubtitle" style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Cabang &bull; Kasir</p>
                </div>
            </div>
            <button type="button" onclick="closeReconcileModal()" style="background: transparent; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; line-height: 1; padding: 4px; border-radius: 6px;" title="Tutup">&times;</button>
        </div>

        <form id="reconcileForm" method="POST" action="" style="margin: 0;">
            @csrf
            <div style="padding: 20px 24px;">
                <!-- Ringkasan Shift Bento -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 18px;">
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">Ringkasan Kas Shift</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <div style="font-size: 11.5px; color: #64748b;">Modal Awal</div>
                            <div id="modalStartCash" style="font-size: 13.5px; font-weight: 700; color: #0f172a;">Rp 0</div>
                        </div>
                        <div>
                            <div style="font-size: 11.5px; color: #64748b;">Kas Diharapkan</div>
                            <div id="modalExpectedCash" style="font-size: 13.5px; font-weight: 700; color: #0f172a;">Rp 0</div>
                        </div>
                        <div>
                            <div style="font-size: 11.5px; color: #64748b;">Kas Fisik Kasir</div>
                            <div id="modalActualCash" style="font-size: 13.5px; font-weight: 700; color: #0f172a;">Rp 0</div>
                        </div>
                        <div>
                            <div style="font-size: 11.5px; color: #64748b;">Selisih Kas</div>
                            <div id="modalDifference" style="font-size: 13.5px; font-weight: 800;">Rp 0</div>
                        </div>
                    </div>
                </div>

                <!-- Input Catatan -->
                <div style="margin-bottom: 14px;">
                    <label for="reconcileNotes" style="display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px;">
                        Catatan Rekonsiliasi <span style="color: #dc2626;">*</span>
                    </label>
                    <textarea id="reconcileNotes" name="notes" rows="3" required minlength="3" placeholder="Tuliskan catatan rekonsiliasi atau verifikasi fisik kas..." style="width: 100%; padding: 10px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; resize: vertical; font-family: inherit; outline: none;"></textarea>
                    
                    <!-- Quick Chips -->
                    <div style="margin-top: 8px;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px;">Pilihan Cepat (klik untuk mengisi):</div>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <button type="button" class="quick-chip" onclick="setNote('Kas fisik telah dihitung ulang dan sesuai.')">✓ Kas fisik sesuai</button>
                            <button type="button" class="quick-chip" onclick="setNote('Selisih pembulatan transaksi kasir telah diverifikasi.')">✓ Selisih pembulatan</button>
                            <button type="button" class="quick-chip" onclick="setNote('Telah dicek dan disetujui oleh Supervisor cabang.')">✓ Diverifikasi Supervisor</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div style="padding: 14px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; background: #f8fafc;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeReconcileModal()">Batal</button>
                <button type="submit" class="btn btn-sm" style="background: #10b981; color: #ffffff; border: none; font-weight: 700; padding: 8px 18px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; border-radius: 8px;">
                    <x-icon name="check-circle" size="15" />
                    <span>Konfirmasi & Setujui</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.96) translateY(-8px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    @keyframes pulseDot {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .quick-chip {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #334155;
        padding: 4px 9px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .quick-chip:hover {
        background: #e2e8f0;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .btn-reconcile-action:hover {
        background: #059669 !important;
    }
    .table-responsive::-webkit-scrollbar {
        height: 6px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
</style>

<script>
    function openReconcileModal(shiftId, branchName, cashierName, startCash, expectedCash, actualCash, differenceStr, diffValue) {
        const modal = document.getElementById('reconcileModal');
        const form = document.getElementById('reconcileForm');
        
        form.action = "{{ url('/backoffice/reconciliation') }}/" + shiftId;
        document.getElementById('modalShiftTitle').textContent = 'Rekonsiliasi Shift #' + shiftId;
        document.getElementById('modalShiftSubtitle').textContent = branchName + ' • Kasir: ' + cashierName;
        document.getElementById('modalStartCash').textContent = startCash;
        document.getElementById('modalExpectedCash').textContent = expectedCash;
        document.getElementById('modalActualCash').textContent = actualCash;
        
        const diffEl = document.getElementById('modalDifference');
        diffEl.textContent = differenceStr;
        if (diffValue < 0) {
            diffEl.style.color = '#dc2626'; // Merah (Minus)
        } else if (diffValue > 0) {
            diffEl.style.color = '#d97706'; // Amber (Surplus)
        } else {
            diffEl.style.color = '#16a34a'; // Hijau (Sesuai)
        }
        
        const notesArea = document.getElementById('reconcileNotes');
        notesArea.value = '';
        
        modal.style.display = 'flex';
        setTimeout(() => notesArea.focus(), 100);
    }

    function closeReconcileModal() {
        document.getElementById('reconcileModal').style.display = 'none';
    }

    function setNote(text) {
        const notesArea = document.getElementById('reconcileNotes');
        notesArea.value = text;
        notesArea.focus();
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeReconcileModal();
        }
    });
</script>
@endsection
