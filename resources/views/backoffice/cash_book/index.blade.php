@extends('layouts.app', ['title' => 'Buku Kas & Keuangan (Finance)'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Buku Kas Keuangan (Street Food Finance)</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Catat seluruh arus uang masuk & uang keluar operasional, modal gerobak, bumbu harian, gas, retribusi, dan bukti foto nota.</p>
    </div>
    <div>
        <a href="{{ route('cash-books.create') }}" class="btn btn-primary" style="background: #047857; font-size: 14px;">+ Catat Kas Masuk / Keluar</a>
    </div>
</div>

<!-- Financial Stats Grid -->
<div class="stats-grid">
    <div class="stat-card" style="border-left: 4px solid #16a34a;">
        <div class="stat-label">TOTAL UANG MASUK (PERIODE INI)</div>
        <div class="stat-value" style="color: #16a34a;">+ Rp {{ number_format($totalIn, 0, ',', '.') }}</div>
        <div class="stat-desc">
            @if(empty($branchId))
                <span style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="globe" size="12" /> <span>Global: Rp {{ number_format($globalIn, 0, ',', '.') }}</span></span> | <span style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="location" size="12" /> <span>Outlet: Rp {{ number_format($outletIn, 0, ',', '.') }}</span></span>
            @else
                Suntikan modal, pendapatan catering & kas masuk
            @endif
        </div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #dc2626;">
        <div class="stat-label">TOTAL UANG KELUAR (PERIODE INI)</div>
        <div class="stat-value" style="color: #dc2626;">- Rp {{ number_format($totalOut, 0, ',', '.') }}</div>
        <div class="stat-desc">
            @if(empty($branchId))
                <span style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="globe" size="12" /> <span>Global: Rp {{ number_format($globalOut, 0, ',', '.') }}</span></span> | <span style="display: inline-flex; align-items: center; gap: 4px;"><x-icon name="location" size="12" /> <span>Outlet: Rp {{ number_format($outletOut, 0, ',', '.') }}</span></span>
            @else
                Belanja bahan/bumbu, gas LPG, upah & retribusi
            @endif
        </div>
    </div>
    <div class="stat-card" style="border-left: 4px solid {{ $netBalance >= 0 ? '#2563eb' : '#dc2626' }};">
        <div class="stat-label">SALDO KAS BERSIH (NET CASH FLOW)</div>
        <div class="stat-value" style="color: {{ $netBalance >= 0 ? '#2563eb' : '#dc2626' }};">
            {{ $netBalance >= 0 ? '+' : '' }} Rp {{ number_format($netBalance, 0, ',', '.') }}
        </div>
        <div class="stat-desc">{{ $netBalance >= 0 ? 'Surplus kas operasional' : 'Defisit kas operasional' }}</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('cash-books.index') }}" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 180px;">
            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">Cabang / Lingkup</label>
            <select name="branch_id" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                <option value="">Semua (Cabang & Global)</option>
                @if($isGlobalAllowed)
                    <option value="global" {{ request('branch_id') === 'global' ? 'selected' : '' }}>Hanya Global (Kantor Pusat)</option>
                @endif
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>

        <div style="width: 150px;">
            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">Arus Kas</label>
            <select name="type" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                <option value="">Semua Tipe</option>
                <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Uang Masuk</option>
                <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Uang Keluar</option>
            </select>
        </div>

        <div style="width: 160px;">
            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ $startDate }}" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
        </div>

        <div style="width: 160px;">
            <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ $endDate }}" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">Filter</button>
            <a href="{{ route('cash-books.index') }}" class="btn btn-outline" style="padding: 8px 12px; font-size: 13px;">Reset</a>
        </div>
    </form>
</div>

<!-- Transaction Table -->
<div class="card">
    <div class="table-responsive">
        <table class="data-table">
        <thead>
            <tr>
                <th style="width: 110px;">Tanggal</th>
                <th>Cabang</th>
                <th style="width: 120px;">Jenis</th>
                <th>Kategori Transaksi</th>
                <th>Keterangan / Rincian</th>
                <th style="text-align: right; width: 150px;">Nominal (Rp)</th>
                <th style="text-align: center; width: 100px;">Bukti Foto</th>
                <th>Petugas</th>
                <th style="width: 140px; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $t)
                <tr>
                    <td>
                        <strong style="font-size: 13px;">{{ $t->transaction_date->format('d/m/Y') }}</strong>
                        <div style="font-size: 11px; color: var(--text-muted);">{{ $t->created_at->format('H:i') }} WIB</div>
                    </td>
                    <td>
                        @if($t->is_global)
                            <span style="background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #c7d2fe; display: inline-flex; align-items: center; gap: 4px;">
                                <x-icon name="globe" size="13" /> <span>Global Pusat</span>
                            </span>
                        @else
                            <strong style="color: #334155; display: inline-flex; align-items: center; gap: 4px;"><x-icon name="location" size="13" /> <span>{{ $t->branch->name ?? 'Cabang #' . $t->branch_id }}</span></strong>
                        @endif
                    </td>
                    <td>
                        @if($t->type === 'in')
                            <span class="badge badge-success" style="background: #dcfce7; color: #166534; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;"><x-icon name="arrow-down" size="10" /> <span>Uang Masuk</span></span>
                        @else
                            <span class="badge badge-danger" style="background: #fee2e2; color: #991b1b; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;"><x-icon name="arrow-up" size="10" /> <span>Uang Keluar</span></span>
                        @endif
                    </td>
                    <td>
                        <strong style="color: #334155;">{{ $t->category }}</strong>
                        @if(strtolower(trim($t->category)) === 'lainnya')
                            <span class="badge badge-warning" style="font-size: 10px; margin-left: 4px;">Kategori Bebas</span>
                        @endif
                    </td>
                    <td>
                        @if($t->description)
                            <div style="font-size: 13px; color: #1e293b;">{{ $t->description }}</div>
                        @else
                            <span style="color: var(--text-muted); font-size: 12px; font-style: italic;">-</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <strong style="font-size: 15px; color: {{ $t->type === 'in' ? '#16a34a' : '#dc2626' }};">
                            {{ $t->type === 'in' ? '+' : '-' }} Rp {{ number_format($t->amount, 0, ',', '.') }}
                        </strong>
                    </td>
                    <td style="text-align: center;">
                        @if($t->receipt_photo_path)
                            @php
                                $receiptUrl = '/storage/' . ltrim(str_replace('storage/', '', $t->receipt_photo_path), '/');
                            @endphp
                            <button type="button" onclick="openPhotoModal('{{ $receiptUrl }}')" style="border: none; background: none; cursor: pointer; padding: 0;">
                                <img src="{{ $receiptUrl }}" alt="Bukti Foto" style="width: 44px; height: 44px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border);" onerror="this.src='{{ asset('favicon.ico') }}';">
                            </button>
                        @else
                            <span style="font-size: 11px; color: var(--text-muted);">Tanpa Foto</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-size: 12px;">{{ $t->creator->name ?? 'Staf' }}</div>
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                            <a href="{{ route('cash-books.edit', $t) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('cash-books.destroy', $t) }}" method="POST" onsubmit="return confirm('Hapus catatan kas ini? Data akan di-soft delete.')" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecaca;">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 36px;">
                        Belum ada catatan kas pada periode ini. Klik <strong>+ Catat Kas Masuk / Keluar</strong> untuk mulai mencatat.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    {{ $transactions->links() }}
</div>

<!-- Modal Foto Bukti (Native CSS/JS, Zero Third Party) -->
<div id="photoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.75); z-index: 999; justify-content: center; align-items: center;" onclick="closePhotoModal()">
    <div style="background: #ffffff; padding: 16px; border-radius: 12px; max-width: 90%; max-height: 90%; position: relative;" onclick="event.stopPropagation()">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <strong style="font-size: 14px;">Bukti Foto Transaksi / Nota</strong>
            <button onclick="closePhotoModal()" style="background: none; border: none; cursor: pointer; color: #64748b; display: inline-flex; align-items: center;"><x-icon name="close" size="18" /></button>
        </div>
        <img id="modalImg" src="" alt="Bukti Foto Nota" style="max-width: 100%; max-height: 75vh; border-radius: 8px; display: block;">
    </div>
</div>

<script>
    function openPhotoModal(src) {
        document.getElementById('modalImg').src = src;
        document.getElementById('photoModal').style.display = 'flex';
    }
    function closePhotoModal() {
        document.getElementById('photoModal').style.display = 'none';
        document.getElementById('modalImg').src = '';
    }
</script>
@endsection
