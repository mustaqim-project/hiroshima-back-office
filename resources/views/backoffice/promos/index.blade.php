@extends('layouts.app', ['title' => 'Promosi & Diskon Cabang'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Manajemen Promosi & Diskon</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Atur skema promosi persentase/potongan harga langsung, minimum transaksi, dan cakupan cabang.</p>
    </div>
    <div>
        <a href="{{ route('promos.create') }}" class="btn btn-primary">+ Tambah Promosi</a>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Promosi</th>
                <th>Nilai Diskon</th>
                <th>Syarat Belanja</th>
                <th>Periode Berlaku</th>
                <th>Cakupan Cabang</th>
                <th>Prioritas</th>
                <th>Status</th>
                <th style="width: 160px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($promos as $pro)
                <tr>
                    <td><code>{{ $pro->code }}</code></td>
                    <td>
                        <strong style="font-size: 15px;">{{ $pro->name }}</strong>
                    </td>
                    <td>
                        @if($pro->type === 'percentage')
                            <strong style="color: #2563eb; font-size: 15px;">{{ (float) $pro->value }}%</strong>
                            @if($pro->max_discount)
                                <div style="font-size: 11px; color: var(--text-muted);">Maks. Rp {{ number_format($pro->max_discount, 0, ',', '.') }}</div>
                            @endif
                        @else
                            <strong style="color: #047857; font-size: 15px;">Rp {{ number_format($pro->value, 0, ',', '.') }}</strong>
                        @endif
                    </td>
                    <td>
                        @if($pro->min_purchase > 0)
                            Min. Rp {{ number_format($pro->min_purchase, 0, ',', '.') }}
                        @else
                            <span style="color: var(--text-muted);">Tanpa Min. Belanja</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-size: 12px;">Mulai: {{ $pro->starts_at?->format('d M Y') }}</div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            Hingga: {{ $pro->ends_at ? $pro->ends_at->format('d M Y') : 'Selamanya' }}
                        </div>
                    </td>
                    <td>
                        @if($pro->branches->count() > 0)
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                @foreach($pro->branches as $b)
                                    <span class="badge badge-info" style="font-size: 11px;">{{ $b->code }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="badge badge-warning">Semua Cabang</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-info">P-{{ $pro->priority }}</span>
                    </td>
                    <td>
                        @if($pro->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <a href="{{ route('promos.edit', $pro) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('promos.destroy', $pro) }}" method="POST" onsubmit="return confirm('Hapus promo {{ $pro->name }}? Data akan di-soft delete.')" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecaca;">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 32px;">Belum ada promosi terdaftar. Silakan buat promosi pertama.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
