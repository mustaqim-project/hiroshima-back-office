@extends('layouts.app', ['title' => 'Tambah Meja Baru'])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('tables.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Meja</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Tambah Meja Baru</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Daftarkan meja makan baru untuk operasional Dine-in di cabang tertentu.</p>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <div class="card-title">Detail Meja Pelanggan</div>
    </div>

    <form method="POST" action="{{ route('tables.store') }}">
        @csrf

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Cabang Outlet</label>
            <select name="branch_id" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>
                        {{ $b->name }} ({{ $b->code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nomor / Kode Meja</label>
                <input type="text" name="table_no" value="{{ old('table_no') }}" required placeholder="Contoh: 01, T-12, VIP-1" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Area / Section</label>
                <input type="text" name="section" value="{{ old('section', 'Main Hall') }}" placeholder="Contoh: Lantai 1, Outdoor, VIP" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kapasitas Kursi</label>
                <input type="number" name="capacity" value="{{ old('capacity', 4) }}" required min="1" max="50" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Awal</label>
                <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="available" {{ old('status') === 'available' ? 'selected' : '' }}>Kosong (Available)</option>
                    <option value="occupied" {{ old('status') === 'occupied' ? 'selected' : '' }}>Terisi (Occupied)</option>
                    <option value="reserved" {{ old('status') === 'reserved' ? 'selected' : '' }}>Dipesan (Reserved)</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Keaktifan</label>
            <select name="is_active" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Aktif (Dapat dipilih kasir)</option>
                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Nonaktif (Sedang renovasi / rusak)</option>
            </select>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Meja</button>
            <a href="{{ route('tables.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
