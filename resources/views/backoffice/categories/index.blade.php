@extends('layouts.app', ['title' => 'Manajemen Kategori Menu'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Manajemen Kategori Menu</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Kelola pengelompokan menu, stasiun printer target, dan urutan tampilan pada POS tablet.</p>
    </div>
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <x-excel-toolbar module="categories" title="Kategori Menu" />
        <a href="{{ route('categories.create') }}" class="btn btn-primary">+ Tambah Kategori</a>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 80px;">Urutan</th>
                <th>Kode</th>
                <th>Nama Kategori</th>
                <th>Target Printer</th>
                <th>Total Menu</th>
                <th>Status</th>
                <th style="width: 160px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $c)
                <tr>
                    <td><strong>#{{ $c->sort_order }}</strong></td>
                    <td><code>{{ $c->code }}</code></td>
                    <td>
                        <strong style="font-size: 15px;">{{ $c->name }}</strong>
                    </td>
                    <td>
                        @if($c->target_printer === 'kitchen')
                            <span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="kitchen" size="12" /> <span>Dapur (Kitchen)</span></span>
                        @elseif($c->target_printer === 'bar')
                            <span class="badge badge-info" style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="bar" size="12" /> <span>Bar & Minuman</span></span>
                        @else
                            <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="cashier" size="12" /> <span>Kasir Depan</span></span>
                        @endif
                    </td>
                    <td>{{ $c->products_count }} item menu</td>
                    <td>
                        @if($c->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <a href="{{ route('categories.edit', $c) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('categories.destroy', $c) }}" method="POST" onsubmit="return confirm('Hapus kategori {{ $c->name }}? Data akan di-soft delete.')" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecaca;">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">Belum ada kategori terdaftar. Silakan tambahkan kategori pertama.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
