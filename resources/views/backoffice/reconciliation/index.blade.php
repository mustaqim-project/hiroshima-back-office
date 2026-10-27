@extends('layouts.app', ['title' => 'Rekonsiliasi Shift Kasir'])

@section('content')
<div style="margin-bottom: 24px;">
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Rekonsiliasi Shift Kasir</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Tinjau selisih kas fisik vs kas diharapkan, shift yang ditutup manual maupun auto-close 23 jam.</p>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID Shift</th>
                <th>Cabang</th>
                <th>Kasir</th>
                <th>Waktu Dibuka</th>
                <th>Waktu Ditutup</th>
                <th>Modal Awal</th>
                <th>Kas Diharapkan</th>
                <th>Kas Fisik</th>
                <th>Selisih Kas</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shifts as $s)
                <tr>
                    <td><strong>#{{ $s->id }}</strong></td>
                    <td>{{ $s->branch->name ?? '-' }}</td>
                    <td>{{ $s->user->name ?? '-' }}</td>
                    <td>{{ $s->opened_at->format('d/m H:i') }}</td>
                    <td>{{ $s->closed_at ? $s->closed_at->format('d/m H:i') : '-' }}</td>
                    <td>Rp {{ number_format($s->starting_cash, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($s->expected_cash ?? 0, 0, ',', '.') }}</td>
                    <td>
                        @if($s->actual_cash !== null)
                            Rp {{ number_format($s->actual_cash, 0, ',', '.') }}
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td>
                        @if($s->cash_difference !== null)
                            <strong style="color: {{ $s->cash_difference < 0 ? 'var(--danger)' : ($s->cash_difference > 0 ? 'var(--warning)' : 'var(--success)') }};">
                                {{ $s->cash_difference < 0 ? '-' : '+' }} Rp {{ number_format(abs($s->cash_difference), 0, ',', '.') }}
                            </strong>
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td>
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
                    <td>
                        @if($s->status !== 'reconciled')
                            <form method="POST" action="{{ route('reconciliation.reconcile', $s) }}" style="display: flex; gap: 8px;">
                                @csrf
                                <input type="text" name="notes" placeholder="Catatan rekonsiliasi..." required style="padding: 6px 10px; font-size: 12px; border: 1px solid var(--border); border-radius: 6px;">
                                <button type="submit" class="btn btn-primary btn-sm">Setujui</button>
                            </form>
                        @else
                            <span style="font-size: 12px; color: var(--text-muted);">{{ $s->reconciliation_notes }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center; color: var(--text-muted); padding: 32px;">
                        Belum ada shift yang memerlukan rekonsiliasi.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
