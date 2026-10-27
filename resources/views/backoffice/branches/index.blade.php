@extends('layouts.app', ['title' => 'Manajemen Cabang & Keamanan PIN'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Manajemen Cabang & Kebijakan PIN</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Atur parameter finansial, otorisasi PIN cabang (Argon2id), dan status saklar inventori per cabang.</p>
    </div>
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <x-excel-toolbar module="branches" title="Data Cabang" />
        <a href="{{ route('branches.create') }}" class="btn btn-primary">+ Tambah Cabang</a>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Cabang</th>
                <th>Pajak & Service</th>
                <th>Pembulatan</th>
                <th>Ambang Diskon</th>
                <th>Status PIN Keamanan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($branches as $b)
                <tr>
                    <td><strong>{{ $b->code }}</strong></td>
                    <td>
                        <div style="font-weight: 600;">{{ $b->name }}</div>
                        <div style="font-size: 12px; color: var(--text-muted);">{{ $b->phone ?? 'Tanpa nomor telepon' }}</div>
                    </td>
                    <td>
                        {{ $b->tax_percent }}% PPN / {{ $b->service_charge_percent }}% Svc
                        <div style="font-size: 11px; color: var(--text-muted);">{{ $b->tax_included ? '(Termasuk Pajak)' : '(Pajak Ditambahkan)' }}</div>
                    </td>
                    <td>{{ $b->rounding_mode }}</td>
                    <td>
                        {{ $b->discount_percent_threshold }}% / Rp {{ number_format($b->discount_amount_threshold, 0, ',', '.') }}
                    </td>
                    <td>
                        @if($b->pin_void_hash && $b->pin_discount_hash)
                            <span class="badge badge-success">PIN Aktif (Argon2id)</span>
                        @else
                            <span class="badge badge-danger">Wajib Atur PIN</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <a href="{{ route('branches.edit', $b) }}" class="btn btn-primary btn-sm">Edit & PIN →</a>
                            <form action="{{ route('branches.destroy', $b) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus cabang {{ $b->name }}? Data akan di-soft delete.')" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecaca;">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
