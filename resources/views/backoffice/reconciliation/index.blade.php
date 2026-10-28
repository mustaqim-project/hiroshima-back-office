@extends('layouts.app', ['title' => 'Rekonsiliasi Shift Kasir'])

@section('content')
<div style="margin-bottom: 24px;">
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Rekonsiliasi Shift Kasir</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Tinjau selisih kas fisik vs kas diharapkan, shift yang ditutup manual maupun auto-close 23 jam.</p>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
        <thead>
            <tr>
                <th style="width: 80px;">ID Shift</th>
                <th>Cabang</th>
                <th>Kasir</th>
                <th style="white-space: nowrap;">Waktu Buka</th>
                <th style="white-space: nowrap;">Waktu Tutup</th>
                <th style="text-align: right; white-space: nowrap;">Modal Awal</th>
                <th style="text-align: right; white-space: nowrap;">Kas Diharapkan</th>
                <th style="text-align: right; white-space: nowrap;">Kas Fisik</th>
                <th style="text-align: right; white-space: nowrap;">Selisih Kas</th>
                <th style="text-align: center; width: 140px;">Status</th>
                <th style="text-align: center; min-width: 190px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shifts as $s)
                <tr>
                    <td><strong>#{{ $s->id }}</strong></td>
                    <td>{{ $s->branch->name ?? '-' }}</td>
                    <td>{{ $s->user->name ?? '-' }}</td>
                    <td style="white-space: nowrap; font-size: 12.5px;">{{ $s->opened_at->format('d/m H:i') }}</td>
                    <td style="white-space: nowrap; font-size: 12.5px;">{{ $s->closed_at ? $s->closed_at->format('d/m H:i') : '-' }}</td>
                    <td style="text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;">
                        Rp {{ number_format($s->starting_cash, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;">
                        Rp {{ number_format($s->expected_cash ?? 0, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;">
                        @if($s->actual_cash !== null)
                            Rp {{ number_format($s->actual_cash, 0, ',', '.') }}
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;">
                        @if($s->cash_difference !== null)
                            <strong style="color: {{ $s->cash_difference < 0 ? 'var(--danger)' : ($s->cash_difference > 0 ? 'var(--warning)' : 'var(--success)') }};">
                                {{ $s->cash_difference < 0 ? '-' : ($s->cash_difference > 0 ? '+' : '') }} Rp {{ number_format(abs($s->cash_difference), 0, ',', '.') }}
                            </strong>
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if($s->status === 'reconciled')
                            <span class="badge badge-success">Direkonsiliasi</span>
                        @elseif($s->status === 'auto_closed')
                            <span class="badge badge-danger">Auto-Close 23h</span>
                        @elseif($s->status === 'closed')
                            <span class="badge badge-warning">Menunggu Rekonsiliasi</span>
                        @else
                            <span class="badge badge-info">{{ $s->status }}</span>
                        @endif
                    </td>
                    <td style="text-align: center; white-space: nowrap;">
                        <div style="display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                            <a href="{{ Route::has('reconciliation.report') ? route('reconciliation.report', $s) : url('/backoffice/reconciliation/' . $s->id . '/report') }}" 
                               class="btn btn-outline btn-sm" 
                               target="_blank" 
                               title="Cetak / Lihat Laporan Close Shift"
                               style="padding: 5px 10px; font-size: 12px; height: 30px; min-height: 30px; border-color: #cbd5e1; display: inline-flex; align-items: center; gap: 5px;">
                                <x-icon name="printer" size="13" />
                                <span>Laporan</span>
                            </a>

                            @if($s->status !== 'reconciled')
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
                    <td colspan="11" style="text-align: center; color: var(--text-muted); padding: 36px;">
                        Belum ada shift yang memerlukan rekonsiliasi.
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
