@extends('layouts.app', ['title' => 'Tambah Bahan Baku Baru'])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('ingredients.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Bahan Baku</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Tambah Bahan Baku Baru</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Daftarkan item bahan mentah untuk perhitungan HPP menu dan kartu stok.</p>
</div>

<div class="card" style="max-width: 650px;">
    <div class="card-header">
        <div class="card-title">Detail Bahan Baku</div>
    </div>

    <form method="POST" action="{{ route('ingredients.store') }}">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Bahan Baku</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Daging Sapi Slice, Mie Ramen Basah" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kode SKU</label>
                <input type="text" name="sku" value="{{ old('sku') }}" required placeholder="Contoh: ING-BEEF-01" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; text-transform: uppercase;">
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Satuan Ukur (Unit of Measure)</label>
            <select name="unit_id" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <option value="">-- Pilih Satuan --</option>
                @foreach($units as $u)
                    <option value="{{ $u->id }}" {{ old('unit_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }} ({{ $u->symbol }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Biaya Standar / HPP (Rp)</label>
                <input type="number" name="standard_cost" value="{{ old('standard_cost', 0) }}" required min="0" step="100" placeholder="Biaya per satuan unit" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Batas Minimum Stok</label>
                <input type="number" step="0.001" name="minimum_stock" value="{{ old('minimum_stock', 5) }}" required min="0" placeholder="Peringatan saat di bawah nilai ini" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Ketersediaan</label>
            <select name="is_active" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Aktif (Dapat digunakan dalam resep)</option>
                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Bahan Baku</button>
            <a href="{{ route('ingredients.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
