@extends('layouts.app', ['title' => 'Audit Log Tamper-Evident'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Audit Log Tamper-Evident (FSD-18)</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Seluruh mutasi data sensitif diikat dalam rantai kriptografis SHA-256 (hash chain) anti-manipulasi.</p>
    </div>

    <div>
        @if($integrityStatus['valid'])
            <div style="background: #dcfce7; color: #166534; border: 1px solid #86efac; padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                <x-icon name="check-circle" size="18" /> <span>Integritas Rantai Valid ({{ $integrityStatus['verified_count'] }} Log Terverifikasi)</span>
            </div>
        @else
            <div style="background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                <x-icon name="alert-triangle" size="18" /> <span>PERINGATAN: Kerusakan Rantai Hash Terdeteksi!</span>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Aksi</th>
                    <th>Pengguna</th>
                    <th>Cabang</th>
                    <th>Waktu (WIB)</th>
                    <th>Hash Rantai SHA-256</th>
                    <th>IP Web</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $l)
                    <tr>
                        <td><strong>#{{ $l->id }}</strong></td>
                        <td><span class="badge badge-info">{{ $l->action }}</span></td>
                        <td>{{ $l->user->name ?? 'Sistem' }}</td>
                        <td>{{ $l->branch->name ?? '-' }}</td>
                        <td>{{ $l->occurred_at->format('d/m/Y H:i:s') }}</td>
                        <td>
                            <div style="font-family: monospace; font-size: 11px; color: var(--text-muted); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $l->hash }}
                            </div>
                        </td>
                        <td><span style="font-size: 12px; color: var(--text-muted);">{{ $l->ip ?? '-' }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">
                            Belum ada aktivitas mutasi yang tercatat dalam audit log.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->links() }}
</div>
@endsection
