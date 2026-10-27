@extends('layouts.app', ['title' => 'Tambah Metode Pembayaran'])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('payment-methods.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Metode Bayar</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Tambah Metode Pembayaran</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Tambahkan opsi tender pembayaran baru untuk kasir POS.</p>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <div class="card-title">Detail Metode Bayar</div>
    </div>

    <form method="POST" action="{{ route('payment-methods.store') }}">
        @csrf

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Metode Pembayaran</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: QRIS BCA, Mandiri Debit" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kode Metode</label>
                <input type="text" name="code" value="{{ old('code') }}" required placeholder="Contoh: QRIS_BCA, DEBIT_MDR" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; text-transform: uppercase;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tipe Tender</label>
                <select name="type" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="cash" {{ old('type') === 'cash' ? 'selected' : '' }}>Tunai (Cash)</option>
                    <option value="qris_static" {{ old('type') === 'qris_static' ? 'selected' : '' }}>QRIS Statis</option>
                    <option value="debit_card" {{ old('type') === 'debit_card' ? 'selected' : '' }}>Kartu Debit</option>
                    <option value="credit_card" {{ old('type') === 'credit_card' ? 'selected' : '' }}>Kartu Kredit</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Klasifikasi Uang Tunai</label>
                <select name="is_cash" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="0" {{ old('is_cash') == '0' ? 'selected' : '' }}>Bukan Tunai (Non-Cash)</option>
                    <option value="1" {{ old('is_cash') == '1' ? 'selected' : '' }}>Uang Tunai (Masuk Laci Kasir)</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Keaktifan</label>
                <select name="is_active" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Aktif (Tampil di POS)</option>
                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Metode Bayar</button>
            <a href="{{ route('payment-methods.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
