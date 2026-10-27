@extends('layouts.app', ['title' => 'Edit Cabang: ' . $branch->name])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('branches.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Cabang</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Pengaturan Cabang: {{ $branch->name }}</h2>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Main Branch Settings -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Parameter Finansial & POS</div>
        </div>

        <form method="POST" action="{{ route('branches.update', $branch) }}">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Cabang</label>
                    <input type="text" name="name" value="{{ $branch->name }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nomor Telepon</label>
                    <input type="text" name="phone" value="{{ $branch->phone }}" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Alamat Cabang</label>
                <textarea name="address" rows="2" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">{{ $branch->address }}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Pajak (PPN %)</label>
                    <input type="number" step="0.01" name="tax_percent" value="{{ $branch->tax_percent }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Service Charge (%)</label>
                    <input type="number" step="0.01" name="service_charge_percent" value="{{ $branch->service_charge_percent }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Harga Termasuk Pajak</label>
                    <select name="tax_included" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                        <option value="0" {{ ! $branch->tax_included ? 'selected' : '' }}>Pajak Ditambahkan</option>
                        <option value="1" {{ $branch->tax_included ? 'selected' : '' }}>Harga Sudah Termasuk Pajak</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Aturan Pembulatan</label>
                    <select name="rounding_mode" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                        <option value="round_nearest_100" {{ $branch->rounding_mode === 'round_nearest_100' ? 'selected' : '' }}>Rp 100 Terdekat</option>
                        <option value="round_down_100" {{ $branch->rounding_mode === 'round_down_100' ? 'selected' : '' }}>Bulat ke Bawah (Rp 100)</option>
                        <option value="round_up_100" {{ $branch->rounding_mode === 'round_up_100' ? 'selected' : '' }}>Bulat ke Atas (Rp 100)</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Ambang Diskon (%)</label>
                    <input type="number" step="0.01" name="discount_percent_threshold" value="{{ $branch->discount_percent_threshold }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Ambang Diskon (Rp)</label>
                    <input type="number" name="discount_amount_threshold" value="{{ $branch->discount_amount_threshold }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Cetak Rangkap 2 Dapur</label>
                    <select name="print_kitchen_copy" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                        <option value="1" {{ $branch->print_kitchen_copy ? 'selected' : '' }}>Aktif (Cetak Rangkap 2 Dapur)</option>
                        <option value="0" {{ ! $branch->print_kitchen_copy ? 'selected' : '' }}>Nonaktif (Hanya Struk Pelanggan)</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Lebar Kertas Printer</label>
                    <select name="printer_paper_width" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                        <option value="58mm" {{ $branch->printer_paper_width === '58mm' ? 'selected' : '' }}>58 mm (Standar Portable)</option>
                        <option value="80mm" {{ $branch->printer_paper_width === '80mm' ? 'selected' : '' }}>80 mm (Printer Desktop)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Perubahan Cabang</button>
        </form>
    </div>

    <!-- Security & PIN Management -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">Atur PIN Void Cabang</div>
            </div>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px;">
                Wajib tepat 6 digit angka. Digunakan kasir saat membatalkan transaksi.
            </p>
            <form method="POST" action="{{ route('branches.pin.update', $branch) }}">
                @csrf
                <input type="hidden" name="type" value="void">
                <div style="margin-bottom: 12px;">
                    <input type="password" name="pin" maxlength="6" pattern="\d{6}" placeholder="6 digit PIN Void" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 16px; letter-spacing: 4px; text-align: center;">
                </div>
                <button type="submit" class="btn btn-outline btn-sm" style="width: 100%;">Perbarui PIN Void</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Atur PIN Diskon Cabang</div>
            </div>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px;">
                Wajib tepat 6 digit angka. Digunakan jika diskon kasir melebihi ambang batas.
            </p>
            <form method="POST" action="{{ route('branches.pin.update', $branch) }}">
                @csrf
                <input type="hidden" name="type" value="discount">
                <div style="margin-bottom: 12px;">
                    <input type="password" name="pin" maxlength="6" pattern="\d{6}" placeholder="6 digit PIN Diskon" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 16px; letter-spacing: 4px; text-align: center;">
                </div>
                <button type="submit" class="btn btn-outline btn-sm" style="width: 100%;">Perbarui PIN Diskon</button>
            </form>
        </div>
    </div>
</div>
@endsection
