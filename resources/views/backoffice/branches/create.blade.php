@extends('layouts.app', ['title' => 'Tambah Cabang Baru'])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('branches.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Cabang</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Tambah Cabang Baru</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Daftarkan cabang baru ke dalam sistem multi-outlet Hiroshima POS.</p>
</div>

<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <div class="card-title">Informasi & Kebijakan Cabang</div>
    </div>

    <form method="POST" action="{{ route('branches.store') }}">
        @csrf

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Cabang</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Hiroshima Riau Bandung" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kode Cabang (Maks 10 Karakter)</label>
                <input type="text" name="code" value="{{ old('code') }}" required placeholder="Contoh: BDG02" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; text-transform: uppercase;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nomor Telepon</label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Contoh: 08123456789" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Lebar Kertas Printer</label>
                <select name="printer_paper_width" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="80mm" {{ old('printer_paper_width') === '80mm' ? 'selected' : '' }}>80mm (Standar POS)</option>
                    <option value="58mm" {{ old('printer_paper_width') === '58mm' ? 'selected' : '' }}>58mm (Mobile Mini)</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Alamat Cabang</label>
            <textarea name="address" rows="2" placeholder="Alamat lengkap outlet" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">{{ old('address') }}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Pajak (PPN %)</label>
                <input type="number" step="0.01" name="tax_percent" value="{{ old('tax_percent', 10) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Service Charge (%)</label>
                <input type="number" step="0.01" name="service_charge_percent" value="{{ old('service_charge_percent', 5) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Harga Termasuk Pajak</label>
                <select name="tax_included" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="0" {{ old('tax_included') == '0' ? 'selected' : '' }}>Pajak Ditambahkan (Exclude)</option>
                    <option value="1" {{ old('tax_included') == '1' ? 'selected' : '' }}>Harga Termasuk Pajak (Include)</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Aturan Pembulatan</label>
                <select name="rounding_mode" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="round_nearest_100">Rp 100 Terdekat</option>
                    <option value="round_down_100">Bulat ke Bawah (Rp 100)</option>
                    <option value="round_up_100">Bulat ke Atas (Rp 100)</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Batas Diskon Kasir (%)</label>
                <input type="number" step="0.01" name="discount_percent_threshold" value="{{ old('discount_percent_threshold', 10) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Batas Diskon Kasir (Rp)</label>
                <input type="number" name="discount_amount_threshold" value="{{ old('discount_amount_threshold', 50000) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; cursor: pointer;">
                <input type="hidden" name="print_kitchen_copy" value="0">
                <input type="checkbox" name="print_kitchen_copy" value="1" {{ old('print_kitchen_copy', '1') == '1' ? 'checked' : '' }} style="width: 18px; height: 18px;">
                Cetak salinan tiket dapur otomatis saat order dibuat
            </label>
            <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px; margin-left: 28px;">
                Catatan: PIN void dan diskon cabang baru akan diinisialisasi otomatis ke default <strong>123456</strong> (Argon2id), dan dapat diperbarui setelah cabang dibuat.
            </p>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Cabang Baru</button>
            <a href="{{ route('branches.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
