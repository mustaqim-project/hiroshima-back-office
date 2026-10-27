@extends('layouts.app', ['title' => 'Tambah Promosi Baru'])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('promos.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Promosi</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Tambah Promosi Baru</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Buat aturan diskon baru untuk periode promosi penjualan.</p>
</div>

<div class="card" style="max-width: 750px;">
    <div class="card-header">
        <div class="card-title">Parameter Promosi</div>
    </div>

    <form method="POST" action="{{ route('promos.store') }}">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Promosi</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Diskon Grand Opening 20%" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kode Promo</label>
                <input type="text" name="code" value="{{ old('code') }}" required placeholder="Contoh: OPENING20" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; text-transform: uppercase;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tipe Diskon</label>
                <select name="type" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="percentage" {{ old('type') === 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                    <option value="fixed_amount" {{ old('type') === 'fixed_amount' ? 'selected' : '' }}>Nominal Potongan Tetap (Rp)</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nilai Diskon (% atau Rp)</label>
                <input type="number" step="0.01" name="value" value="{{ old('value', 10) }}" required min="0" placeholder="Contoh: 10 untuk 10%, atau 15000" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Minimum Belanja (Rp)</label>
                <input type="number" name="min_purchase" value="{{ old('min_purchase', 0) }}" required min="0" placeholder="0 = Tanpa syarat" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Maksimal Diskon (Rp, Opsional)</label>
                <input type="number" name="max_discount" value="{{ old('max_discount') }}" min="0" placeholder="Contoh: 50000" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tingkat Prioritas (Angka)</label>
                <input type="number" name="priority" value="{{ old('priority', 1) }}" required min="0" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tanggal Mulai</label>
                <input type="date" name="starts_at" value="{{ old('starts_at', date('Y-m-d')) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tanggal Berakhir (Opsional)</label>
                <input type="date" name="ends_at" value="{{ old('ends_at') }}" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Cabang yang Berpartisipasi</label>
            <div style="display: flex; flex-wrap: wrap; gap: 12px; padding: 12px; background: #f8fafc; border: 1px solid var(--border); border-radius: 8px;">
                @foreach($branches as $b)
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                        <input type="checkbox" name="branch_ids[]" value="{{ $b->id }}" checked>
                        {{ $b->name }} ({{ $b->code }})
                    </label>
                @endforeach
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Promosi</label>
            <select name="is_active" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Aktif (Dapat diterapkan di kasir)</option>
                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Promosi</button>
            <a href="{{ route('promos.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
