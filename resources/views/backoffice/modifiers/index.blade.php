@extends('layouts.app', ['title' => 'Grup Modifier & Varian'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Grup Modifier & Pilihan Menu (Add-ons)</h2>
        <p style="color: var(--text-muted); font-size: 14px;">
            Konfigurasi opsi pilihan menu kasir seperti Level Pedas, Ekstra Topping, Pilihan Saus, dan Preferensi Minuman.
        </p>
    </div>
    <div>
        <a href="{{ route('modifier-groups.create') }}" class="btn btn-primary">+ Tambah Grup Modifier</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Grup Modifier</div>
        <div class="stat-value">{{ $totalGroups }}</div>
        <div class="stat-desc">Kategori aturan kustomisasi menu</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Opsi Modifier</div>
        <div class="stat-value" style="color: #2563eb;">{{ $totalModifiers }}</div>
        <div class="stat-desc">Pilihan tambahan / opsi menu</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Menu Terhubung</div>
        <div class="stat-value" style="color: #16a34a;">{{ $totalProductsAttached }}</div>
        <div class="stat-desc">Item produk yang memiliki opsi</div>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 200px;">Nama Grup Modifier</th>
                <th>Aturan Pilihan</th>
                <th>Opsi Modifier (Item & Tambahan Harga)</th>
                <th>Produk Terhubung</th>
                <th style="width: 100px;">Status</th>
                <th style="width: 140px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($modifierGroups as $group)
                <tr>
                    <td>
                        <strong style="font-size: 15px; color: var(--text-main);">{{ $group->name }}</strong>
                        @if($group->is_required)
                            <div><span class="badge badge-warning" style="font-size: 11px; margin-top: 4px;">Wajib Dipilih</span></div>
                        @else
                            <div><span class="badge badge-info" style="font-size: 11px; margin-top: 4px;">Opsional</span></div>
                        @endif
                    </td>
                    <td>
                        <div style="font-size: 13px; font-weight: 600;">
                            @if($group->selection_type === 'single')
                                <span style="color: #0284c7; display: inline-flex; align-items: center; gap: 4px;"><x-icon name="circle-dot" size="13" /> <span>Single (Pilih 1 Saja)</span></span>
                            @else
                                <span style="color: #7c3aed; display: inline-flex; align-items: center; gap: 4px;"><x-icon name="check" size="13" /> <span>Multiple (Bisa Banyak)</span></span>
                            @endif
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                            Min: {{ $group->min_select }} | Max: {{ $group->max_select }}
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            @forelse($group->modifiers as $mod)
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px;">
                                    <strong>{{ $mod->name }}</strong>
                                    @if($mod->price > 0)
                                        <span style="color: #16a34a; font-weight: 700;">(+Rp {{ number_format($mod->price, 0, ',', '.') }})</span>
                                    @else
                                        <span style="color: #64748b;">(Gratis)</span>
                                    @endif
                                </span>
                            @empty
                                <span style="color: #94a3b8; font-size: 12px; font-style: italic;">Belum ada item opsi</span>
                            @endforelse
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-info" style="font-size: 12px;">{{ $group->products->count() }} Menu</span>
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; max-width: 200px;">
                            {{ $group->products->take(3)->pluck('name')->join(', ') }}
                            @if($group->products->count() > 3)
                                <em>(+{{ $group->products->count() - 3 }} lainnya)</em>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($group->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <a href="{{ route('modifier-groups.edit', $group) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('modifier-groups.destroy', $group) }}" method="POST" onsubmit="return confirm('Hapus grup modifier {{ $group->name }}?')" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecaca;">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 36px;">
                        Belum ada grup modifier terdaftar. Klik tombol <strong>+ Tambah Grup Modifier</strong> di atas untuk membuat opsi menu pertama Anda.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
