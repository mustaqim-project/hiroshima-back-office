@extends('layouts.app', ['title' => 'Tambah Kategori Menu'])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('categories.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Kategori</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Tambah Kategori Menu</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Tambahkan kategori baru untuk pengelompokan produk dan rute tiket pesanan.</p>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <div class="card-title">Detail Kategori</div>
    </div>

    <form method="POST" action="{{ route('categories.store') }}">
        @csrf

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Kategori</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Ramen Signature, Minuman Dingin" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kode Kategori</label>
                <input type="text" name="code" value="{{ old('code') }}" required placeholder="Contoh: RAMEN, BEV" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; text-transform: uppercase;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Target Printer Tiket</label>
                <select name="target_printer" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="kitchen" {{ old('target_printer') === 'kitchen' ? 'selected' : '' }}>Dapur (Kitchen Printer)</option>
                    <option value="bar" {{ old('target_printer') === 'bar' ? 'selected' : '' }}>Bar (Bar Printer)</option>
                    <option value="cashier" {{ old('target_printer') === 'cashier' ? 'selected' : '' }}>Kasir (Front Cashier)</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Urutan Tampilan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 1) }}" required min="0" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Aktif</label>
                <select name="is_active" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Aktif (Tampil di POS)</option>
                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Nonaktif (Sembunyikan)</option>
                </select>
            </div>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Kategori</button>
            <a href="{{ route('categories.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
