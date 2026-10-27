@extends('layouts.app', ['title' => 'Manajemen Bahan Baku & HPP'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Manajemen Bahan Baku & HPP (COGS)</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Kelola inventori bahan mentah, satuan ukur (UoM), standar biaya, dan batas ambang minimum stok.</p>
    </div>
    <div>
        <a href="{{ route('ingredients.create') }}" class="btn btn-primary">+ Tambah Bahan Baku</a>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>SKU Bahan</th>
                <th>Nama Bahan Baku</th>
                <th>Satuan (UoM)</th>
                <th>Biaya Standar / HPP</th>
                <th>Stok Minimum Peringatan</th>
                <th>Status</th>
                <th style="width: 160px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ingredients as $ing)
                <tr>
                    <td><code>{{ $ing->sku }}</code></td>
                    <td>
                        <strong style="font-size: 15px;">{{ $ing->name }}</strong>
                    </td>
                    <td>
                        <span class="badge badge-info">{{ $ing->unit->name ?? 'N/A' }} ({{ $ing->unit->symbol ?? '' }})</span>
                    </td>
                    <td>
                        <strong style="color: #047857; font-size: 15px;">Rp {{ number_format($ing->standard_cost, 0, ',', '.') }}</strong>
                        <span style="font-size: 11px; color: var(--text-muted);">/ {{ $ing->unit->symbol ?? 'unit' }}</span>
                    </td>
                    <td>
                        {{ (float) $ing->minimum_stock }} {{ $ing->unit->symbol ?? '' }}
                    </td>
                    <td>
                        @if($ing->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <a href="{{ route('ingredients.edit', $ing) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('ingredients.destroy', $ing) }}" method="POST" onsubmit="return confirm('Hapus bahan baku {{ $ing->name }}? Data akan di-soft delete.')" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecaca;">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">Belum ada bahan baku terdaftar. Silakan tambahkan bahan baku pertama.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
