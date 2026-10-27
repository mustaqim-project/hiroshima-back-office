@extends('layouts.app', ['title' => 'Edit Pengguna: ' . $user->name])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('users.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Pengguna</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Edit Pengguna: {{ $user->name }}</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Perbarui data staf, hak akses role, serta kredensial login/PIN.</p>
</div>

<div class="card" style="max-width: 750px;">
    <div class="card-header">
        <div class="card-title">Perbarui Profil & Hak Akses</div>
    </div>

    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Username Login</label>
                <input type="text" name="username" value="{{ old('username', $user->username) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nomor Telepon / WA</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Password Baru (Kosongkan bila tidak diubah)</label>
                <input type="password" name="password" placeholder="Minimal 6 karakter" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">PIN Kasir POS (Kosongkan bila tidak diubah)</label>
                <input type="password" name="pin" maxlength="6" pattern="\d{6}" placeholder="6 digit angka (Argon2id)" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <span style="font-size: 11px; color: var(--text-muted);">
                    Status saat ini: {{ $user->pin_hash ? 'Sudah terpasang PIN' : 'Belum memiliki PIN' }}
                </span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Role / Jabatan</label>
                <select name="role" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="cashier" {{ old('role', $user->role) === 'cashier' ? 'selected' : '' }}>Kasir (Cashier)</option>
                    <option value="branch_manager" {{ old('role', $user->role) === 'branch_manager' ? 'selected' : '' }}>Manajer Cabang (Branch Manager)</option>
                    <option value="warehouse_staff" {{ old('role', $user->role) === 'warehouse_staff' ? 'selected' : '' }}>Staf Gudang (Warehouse Staff)</option>
                    <option value="central_admin" {{ old('role', $user->role) === 'central_admin' ? 'selected' : '' }}>Admin Pusat (Central Admin)</option>
                    <option value="owner" {{ old('role', $user->role) === 'owner' ? 'selected' : '' }}>Pemilik Bisnis (Owner)</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Penugasan Cabang</label>
                <select name="branch_id" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="">-- Akses Global / Semua Cabang (Owner & Admin) --</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', $user->branch_id) == $b->id ? 'selected' : '' }}>
                            {{ $b->name }} ({{ $b->code }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Akun</label>
            <select name="is_active" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <option value="1" {{ old('is_active', $user->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Aktif (Dapat Login)</option>
                <option value="0" {{ old('is_active', $user->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Dibekukan / Nonaktif</option>
            </select>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
