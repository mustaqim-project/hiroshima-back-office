@extends('layouts.app', ['title' => 'Edit Metode: ' . $paymentMethod->name])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('payment-methods.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Metode Bayar</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Edit Metode: {{ $paymentMethod->name }}</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Perbarui konfigurasi tender kasir dan parameter tipe pembayaran.</p>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <div class="card-title">Perbarui Metode Bayar</div>
    </div>

    <form method="POST" action="{{ route('payment-methods.update', $paymentMethod) }}">
        @csrf
        @method('PUT')

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Metode Pembayaran</label>
            <input type="text" name="name" value="{{ old('name', $paymentMethod->name) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kode Metode</label>
                <input type="text" name="code" value="{{ old('code', $paymentMethod->code) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; text-transform: uppercase;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tipe Tender</label>
                <select name="type" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="cash" {{ old('type', $paymentMethod->type) === 'cash' ? 'selected' : '' }}>Tunai (Cash)</option>
                    <option value="qris_static" {{ old('type', $paymentMethod->type) === 'qris_static' ? 'selected' : '' }}>QRIS Statis</option>
                    <option value="debit_card" {{ old('type', $paymentMethod->type) === 'debit_card' ? 'selected' : '' }}>Kartu Debit</option>
                    <option value="credit_card" {{ old('type', $paymentMethod->type) === 'credit_card' ? 'selected' : '' }}>Kartu Kredit</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Klasifikasi Uang Tunai</label>
                <select name="is_cash" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="0" {{ old('is_cash', $paymentMethod->is_cash ? '1' : '0') == '0' ? 'selected' : '' }}>Bukan Tunai (Non-Cash)</option>
                    <option value="1" {{ old('is_cash', $paymentMethod->is_cash ? '1' : '0') == '1' ? 'selected' : '' }}>Uang Tunai (Masuk Laci Kasir)</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Keaktifan</label>
                <select name="is_active" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="1" {{ old('is_active', $paymentMethod->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Aktif (Tampil di POS)</option>
                    <option value="0" {{ old('is_active', $paymentMethod->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('payment-methods.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
