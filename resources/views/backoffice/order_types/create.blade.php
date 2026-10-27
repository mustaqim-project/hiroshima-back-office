@extends('layouts.app', ['title' => 'Tambah Jenis Pesanan'])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('order-types.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Master Jenis Pesanan</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Tambah Jenis Pesanan Baru</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Tambahkan jenis layanan pesanan baru (contoh: GrabFood, ShopeeFood, Bazaar / Event) untuk menetapkan multi-harga khusus.</p>
</div>

<div class="card" style="max-width: 650px;">
    <div class="card-header">
        <div class="card-title">Formulir Jenis Pesanan</div>
    </div>

    <form method="POST" action="{{ route('order-types.store') }}">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Jenis Pesanan</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: ShopeeFood" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kode Unik</label>
                <input type="text" name="code" value="{{ old('code') }}" required placeholder="Contoh: shopee_food" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nomor Urutan Tampilan POS</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 1) }}" required min="0" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Operasional</label>
                <select name="is_active" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Aktif & Muncul di Kasir POS</option>
                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom: 24px; padding: 14px; background: #f8fafc; border-radius: 8px; border: 1px solid var(--border);">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="hidden" name="is_default" value="0">
                <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }} style="width: 18px; height: 18px;">
                <div>
                    <strong style="font-size: 14px;">Jadikan Pilihan Default saat Kasir Dibuka</strong>
                    <div style="font-size: 12px; color: var(--text-muted);">Pilihan jenis ini akan otomatis terpilih di awal transaksi kasir.</div>
                </div>
            </label>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Jenis Pesanan</button>
            <a href="{{ route('order-types.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
