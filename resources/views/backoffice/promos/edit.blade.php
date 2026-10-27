@extends('layouts.app', ['title' => 'Edit Promo: ' . $promo->name])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('promos.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Promosi</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Edit Promosi: {{ $promo->name }}</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Perbarui data promosi, persentase diskon, dan cabang berlaku.</p>
</div>

<div class="card" style="max-width: 750px;">
    <div class="card-header">
        <div class="card-title">Perbarui Parameter Promosi</div>
    </div>

    <form method="POST" action="{{ route('promos.update', $promo) }}">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Promosi</label>
                <input type="text" name="name" value="{{ old('name', $promo->name) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kode Promo</label>
                <input type="text" name="code" value="{{ old('code', $promo->code) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; text-transform: uppercase;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tipe Diskon</label>
                <select name="type" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="percentage" {{ old('type', $promo->type) === 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                    <option value="fixed_amount" {{ old('type', $promo->type) === 'fixed_amount' ? 'selected' : '' }}>Nominal Potongan Tetap (Rp)</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nilai Diskon (% atau Rp)</label>
                <input type="number" step="0.01" name="value" value="{{ old('value', (float)$promo->value) }}" required min="0" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Minimum Belanja (Rp)</label>
                <input type="number" name="min_purchase" value="{{ old('min_purchase', $promo->min_purchase) }}" required min="0" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Maksimal Diskon (Rp, Opsional)</label>
                <input type="number" name="max_discount" value="{{ old('max_discount', $promo->max_discount) }}" min="0" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tingkat Prioritas (Angka)</label>
                <input type="number" name="priority" value="{{ old('priority', $promo->priority) }}" required min="0" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tanggal Mulai</label>
                <input type="date" name="starts_at" value="{{ old('starts_at', $promo->starts_at?->format('Y-m-d')) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tanggal Berakhir (Opsional)</label>
                <input type="date" name="ends_at" value="{{ old('ends_at', $promo->ends_at?->format('Y-m-d')) }}" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Cabang yang Berpartisipasi</label>
            @php
                $assignedBranchIds = $promo->branches->pluck('id')->toArray();
            @endphp
            <div style="display: flex; flex-wrap: wrap; gap: 12px; padding: 12px; background: #f8fafc; border: 1px solid var(--border); border-radius: 8px;">
                @foreach($branches as $b)
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                        <input type="checkbox" name="branch_ids[]" value="{{ $b->id }}" {{ in_array($b->id, old('branch_ids', $assignedBranchIds)) ? 'checked' : '' }}>
                        {{ $b->name }} ({{ $b->code }})
                    </label>
                @endforeach
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Promosi</label>
            <select name="is_active" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <option value="1" {{ old('is_active', $promo->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Aktif (Dapat diterapkan di kasir)</option>
                <option value="0" {{ old('is_active', $promo->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('promos.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
