@extends('layouts.app', ['title' => 'Manajemen Meja Pelanggan'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Manajemen Meja Pelanggan</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Kelola tata letak nomor meja, kapasitas kursi, area makan, dan status reservasi per cabang.</p>
    </div>
    <div>
        <a href="{{ route('tables.create') }}" class="btn btn-primary">+ Tambah Meja</a>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Cabang</th>
                <th>Nomor Meja</th>
                <th>Area / Section</th>
                <th>Kapasitas</th>
                <th>Status Realtime</th>
                <th>Status Aktif</th>
                <th style="width: 160px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tables as $t)
                <tr>
                    <td>
                        <strong>{{ $t->branch->name ?? 'Cabang #' . $t->branch_id }}</strong>
                    </td>
                    <td>
                        <strong style="font-size: 16px;">Meja {{ $t->table_no }}</strong>
                    </td>
                    <td>
                        <span class="badge badge-info">{{ $t->section ?? 'Area Utama' }}</span>
                    </td>
                    <td>
                        {{ $t->capacity }} Kursi
                    </td>
                    <td>
                        @if($t->status === 'available')
                            <span class="badge badge-success">● Kosong (Available)</span>
                        @elseif($t->status === 'occupied')
                            <span class="badge badge-danger">● Terisi (Occupied)</span>
                        @else
                            <span class="badge badge-warning">● Dipesan (Reserved)</span>
                        @endif
                    </td>
                    <td>
                        @if($t->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <a href="{{ route('tables.edit', $t) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('tables.destroy', $t) }}" method="POST" onsubmit="return confirm('Hapus Meja {{ $t->table_no }}? Data akan di-soft delete.')" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecaca;">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">Belum ada meja terdaftar. Silakan tambahkan meja pertama.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
