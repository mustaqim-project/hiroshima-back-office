@extends('layouts.app', ['title' => 'Manajemen Pengguna & Staf'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Manajemen Pengguna & Hak Akses</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Kelola akun staf, penugasan cabang (Multi-outlet RBAC), otorisasi PIN kasir, dan keamanan login.</p>
    </div>
    <div>
        <a href="{{ route('users.create') }}" class="btn btn-primary">+ Tambah Pengguna</a>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nama & Username</th>
                <th>Email & Kontak</th>
                <th>Penugasan Cabang</th>
                <th>Role / Jabatan</th>
                <th>PIN POS</th>
                <th>Status</th>
                <th style="width: 160px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $u)
                <tr>
                    <td>
                        <strong style="font-size: 15px;">{{ $u->name }}</strong>
                        <div style="font-size: 12px; color: var(--text-muted);">@<span>{{ $u->username }}</span></div>
                    </td>
                    <td>
                        <div>{{ $u->email }}</div>
                        <div style="font-size: 12px; color: var(--text-muted);">{{ $u->phone ?? '-' }}</div>
                    </td>
                    <td>
                        @if($u->branch)
                            <span class="badge badge-info">{{ $u->branch->name }}</span>
                        @else
                            <span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="globe" size="12" /> <span>Global (Semua Cabang)</span></span>
                        @endif
                    </td>
                    <td>
                        <strong style="font-size: 13px; text-transform: uppercase; color: #1e293b;">
                            {{ str_replace('_', ' ', $u->role) }}
                        </strong>
                    </td>
                    <td>
                        @if($u->pin_hash)
                            <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="check" size="11" /> <span>Terdaftar (Argon2id)</span></span>
                        @else
                            <span class="badge badge-danger">Belum Diatur</span>
                        @endif
                    </td>
                    <td>
                        @if($u->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <a href="{{ route('users.edit', $u) }}" class="btn btn-outline btn-sm">Edit</a>
                            @if($u->id !== auth()->id())
                                <form action="{{ route('users.destroy', $u) }}" method="POST" onsubmit="return confirm('Hapus staf {{ $u->name }}? Data akan di-soft delete.')" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecaca;">Hapus</button>
                                </form>
                            @else
                                <span style="font-size: 11px; color: var(--text-muted); font-style: italic;">Akun Anda</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">Belum ada pengguna terdaftar. Silakan tambahkan pengguna baru.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
