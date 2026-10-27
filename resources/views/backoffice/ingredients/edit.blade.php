@extends('layouts.app', ['title' => 'Edit Bahan: ' . $ingredient->name])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('ingredients.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Bahan Baku</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Edit Bahan: {{ $ingredient->name }}</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Perbarui data bahan baku, satuan unit, dan standar biaya per satuan.</p>
</div>

<div class="card" style="max-width: 650px;">
    <div class="card-header">
        <div class="card-title">Perbarui Bahan Baku</div>
    </div>

    <form method="POST" action="{{ route('ingredients.update', $ingredient) }}">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Bahan Baku</label>
                <input type="text" name="name" value="{{ old('name', $ingredient->name) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kode SKU</label>
                <input type="text" name="sku" value="{{ old('sku', $ingredient->sku) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; text-transform: uppercase;">
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Satuan Ukur (Unit of Measure)</label>
            <select name="unit_id" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                @foreach($units as $u)
                    <option value="{{ $u->id }}" {{ old('unit_id', $ingredient->unit_id) == $u->id ? 'selected' : '' }}>
                        {{ $u->name }} ({{ $u->symbol }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Biaya Standar / HPP (Rp)</label>
                <input type="number" name="standard_cost" value="{{ old('standard_cost', $ingredient->standard_cost) }}" required min="0" step="100" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Batas Minimum Stok</label>
                <input type="number" step="0.001" name="minimum_stock" value="{{ old('minimum_stock', (float)$ingredient->minimum_stock) }}" required min="0" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Ketersediaan</label>
            <select name="is_active" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <option value="1" {{ old('is_active', $ingredient->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Aktif (Dapat digunakan dalam resep)</option>
                <option value="0" {{ old('is_active', $ingredient->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('ingredients.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
