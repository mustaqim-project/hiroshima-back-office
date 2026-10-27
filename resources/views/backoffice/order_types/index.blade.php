@extends('layouts.app', ['title' => 'Master Jenis Pesanan & Multi-Harga'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Master Data Jenis Pesanan (Order Types)</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Kelola jenis layanan transaksi (Dine In, Take Away, Delivery / Ojol) sebagai acuan sistem multi-harga produk tanpa meja fisik.</p>
    </div>
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <x-excel-toolbar module="order-types" title="Jenis Pesanan" />
        <a href="{{ route('order-types.create') }}" class="btn btn-primary">+ Tambah Jenis Pesanan</a>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 70px;">Urutan</th>
                <th>Nama Jenis Pesanan</th>
                <th>Kode</th>
                <th>Sistem Meja</th>
                <th>Status Default</th>
                <th>Status Operasional</th>
                <th style="width: 160px; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orderTypes as $ot)
                <tr>
                    <td style="font-weight: 700; color: #64748b;">#{{ $ot->sort_order }}</td>
                    <td>
                        <strong style="font-size: 15px; color: #1e293b;">{{ $ot->name }}</strong>
                    </td>
                    <td>
                        <code>{{ $ot->code }}</code>
                    </td>
                    <td>
                        <span style="font-size: 12px; color: #166534; background: #dcfce7; padding: 3px 8px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                            <x-icon name="ban" size="12" /> <span>Tanpa Meja (Street Food Kaki Lima)</span>
                        </span>
                    </td>
                    <td>
                        @if($ot->is_default)
                            <span class="badge badge-success" style="background: #16a34a; color: white; display: inline-flex; align-items: center; gap: 4px;"><x-icon name="star" size="11" /> <span>Default POS</span></span>
                        @else
                            <span style="color: var(--text-muted); font-size: 12px;">Opsional</span>
                        @endif
                    </td>
                    <td>
                        @if($ot->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                            <a href="{{ route('order-types.edit', $ot) }}" class="btn btn-outline btn-sm">Edit</a>
                            @if(!$ot->is_default)
                                <form action="{{ route('order-types.destroy', $ot) }}" method="POST" onsubmit="return confirm('Hapus jenis pesanan {{ $ot->name }}? Data akan di-soft delete.')" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecaca;">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">Belum ada jenis pesanan terdaftar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
