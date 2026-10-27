@extends('layouts.app', ['title' => 'Pusat Deteksi Fraud Operasional'])

@section('content')
<div style="margin-bottom: 24px;">
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Pusat Pengawasan & Deteksi Fraud (EXT)</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Evaluasi 20 aturan anomali kasir (FRD-01 s.d. FRD-20), investigasi kasus, dan penanganan bukti transaksi.</p>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Fraud Alerts List -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Daftar Peringatan Anomali Terdeteksi</div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Aturan</th>
                    <th>Cabang</th>
                    <th>Kasir</th>
                    <th>Tingkat</th>
                    <th>Bukti Sinyal</th>
                    <th>Status</th>
                    <th>Aksi Disposisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alerts as $a)
                    <tr>
                        <td><strong>{{ $a->fraud_rule_code }}</strong></td>
                        <td>{{ $a->branch->name ?? '-' }}</td>
                        <td>{{ $a->user->name ?? '-' }}</td>
                        <td>
                            @if($a->severity === 'critical')
                                <span class="badge badge-danger">Kritis</span>
                            @elseif($a->severity === 'high')
                                <span class="badge badge-danger">Tinggi</span>
                            @elseif($a->severity === 'medium')
                                <span class="badge badge-warning">Sedang</span>
                            @else
                                <span class="badge badge-info">Rendah</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-size: 12px; font-family: monospace; color: var(--text-main); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ json_encode($a->evidence) }}
                            </div>
                        </td>
                        <td>
                            @if($a->status === 'new')
                                <span class="badge badge-warning">Baru</span>
                            @elseif($a->status === 'confirmed')
                                <span class="badge badge-danger">Terkonfirmasi</span>
                            @elseif($a->status === 'dismissed')
                                <span class="badge badge-success">Dikesampingkan</span>
                            @else
                                <span class="badge badge-info">{{ $a->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($a->status === 'new')
                                <form method="POST" action="{{ route('fraud.review', $a) }}" style="display: flex; gap: 6px;">
                                    @csrf
                                    <input type="text" name="review_notes" placeholder="Catatan investigasi..." required style="padding: 4px 8px; font-size: 11px; border: 1px solid var(--border); border-radius: 6px;">
                                    <select name="status" style="padding: 4px; font-size: 11px; border: 1px solid var(--border); border-radius: 6px;">
                                        <option value="confirmed">Konfirmasi</option>
                                        <option value="dismissed">Tutup / Normal</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm" style="padding: 4px 8px; font-size: 11px;">Simpan</button>
                                </form>
                            @else
                                <span style="font-size: 12px; color: var(--text-muted);">{{ $a->review_notes }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">
                            Tidak ada indikasi fraud atau anomali operasional yang terdeteksi. Sistem berjalan normal.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Risk Scores Board -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">Papan Skor Risiko Kasir</div>
            </div>
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">
                Skor risiko dihitung berdasarkan bobot temuan dan frekuensi anomali per 30 hari berjalan.
            </p>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kasir</th>
                        <th>Cabang</th>
                        <th>Skor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riskScores as $r)
                        <tr>
                            <td><strong>{{ $r->user->name ?? '-' }}</strong></td>
                            <td>{{ $r->branch->name ?? '-' }}</td>
                            <td>
                                <strong style="color: {{ $r->score >= 50 ? 'var(--danger)' : ($r->score >= 20 ? 'var(--warning)' : 'var(--success)') }};">
                                    {{ $r->score }}%
                                </strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 16px;">
                                Belum ada kasir yang memiliki catatan skor risiko.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
