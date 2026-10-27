@extends('layouts.app', ['title' => 'Tambah Pengguna Baru'])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('users.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Pengguna</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Tambah Pengguna Baru</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Daftarkan staf operasional baru dengan hak akses dan PIN kasir yang aman.</p>
</div>

<div class="card" style="max-width: 750px;">
    <div class="card-header">
        <div class="card-title">Profil & Kredensial Pengguna</div>
    </div>

    <form method="POST" action="{{ route('users.store') }}">
        @csrf

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Budi Santoso" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Username Login</label>
                <input type="text" name="username" value="{{ old('username') }}" required placeholder="Contoh: budi.kasir" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="Contoh: budi@hiroshima.id" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nomor Telepon / WA</label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Contoh: 08123456789" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Password Login Web</label>
                <input type="password" name="password" required placeholder="Minimal 6 karakter" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">PIN Kasir POS (6 Angka)</label>
                <input type="password" name="pin" maxlength="6" pattern="\d{6}" placeholder="Contoh: 123456 (Argon2id)" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <span style="font-size: 11px; color: var(--text-muted);">Wajib untuk kasir agar dapat buka/tutup shift di tablet POS.</span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Role / Jabatan</label>
                <select name="role" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="cashier" {{ old('role') === 'cashier' ? 'selected' : '' }}>Kasir (Cashier)</option>
                    <option value="branch_manager" {{ old('role') === 'branch_manager' ? 'selected' : '' }}>Manajer Cabang (Branch Manager)</option>
                    <option value="warehouse_staff" {{ old('role') === 'warehouse_staff' ? 'selected' : '' }}>Staf Gudang (Warehouse Staff)</option>
                    <option value="central_admin" {{ old('role') === 'central_admin' ? 'selected' : '' }}>Admin Pusat (Central Admin)</option>
                    <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>Pemilik Bisnis (Owner)</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Penugasan Cabang</label>
                <select name="branch_id" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="">-- Akses Global / Semua Cabang (Owner & Admin) --</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }} ({{ $b->code }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Akun</label>
            <select name="is_active" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Aktif (Dapat Login)</option>
                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Dibekukan / Nonaktif</option>
            </select>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
