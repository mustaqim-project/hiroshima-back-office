@extends('layouts.app', ['title' => 'Metode Pembayaran (Tender)'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Metode Pembayaran (Tender POS)</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Kelola opsi tender pembayaran kasir seperti Uang Tunai, EDC Kartu Debit/Kredit, dan QRIS Statis.</p>
    </div>
    <div>
        <a href="{{ route('payment-methods.create') }}" class="btn btn-primary">+ Tambah Metode Bayar</a>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Metode</th>
                <th>Tipe Tender</th>
                <th>Klasifikasi Kas</th>
                <th>Status</th>
                <th style="width: 160px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($methods as $m)
                <tr>
                    <td><code>{{ $m->code }}</code></td>
                    <td>
                        <strong style="font-size: 15px;">{{ $m->name }}</strong>
                    </td>
                    <td>
                        @if($m->type === 'cash')
                            <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="cash" size="12" /> <span>Tunai (Cash)</span></span>
                        @elseif($m->type === 'qris_static')
                            <span class="badge badge-info" style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="phone" size="12" /> <span>QRIS Statis</span></span>
                        @elseif($m->type === 'debit_card')
                            <span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="payment" size="12" /> <span>EDC Debit Card</span></span>
                        @else
                            <span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="payment" size="12" /> <span>EDC Credit Card</span></span>
                        @endif
                    </td>
                    <td>
                        @if($m->is_cash)
                            <span style="font-weight: 600; color: #16a34a;">Arus Kas Fisik (Cash Drawer)</span>
                        @else
                            <span style="color: var(--text-muted);">Non-Tunai / Bank Settlement</span>
                        @endif
                    </td>
                    <td>
                        @if($m->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <a href="{{ route('payment-methods.edit', $m) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('payment-methods.destroy', $m) }}" method="POST" onsubmit="return confirm('Hapus metode {{ $m->name }}? Data akan di-soft delete.')" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecaca;">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 32px;">Belum ada metode bayar terdaftar. Silakan tambahkan metode bayar pertama.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
