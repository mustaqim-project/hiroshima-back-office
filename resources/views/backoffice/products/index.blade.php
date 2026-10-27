@extends('layouts.app', ['title' => 'Manajemen Menu & Produk'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Manajemen Menu & Produk F&B</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Kelola item katalog, penetapan harga jual, HPP, serta integrasi kategori pos kasir.</p>
    </div>
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <x-excel-toolbar module="products" title="Menu & Produk" />
        <a href="{{ route('products.create') }}" class="btn btn-primary">+ Tambah Menu</a>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 56px;">Foto</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Multi-Harga per Jenis Pesanan</th>
                <th>HPP Estimasi (Rp)</th>
                <th>Status</th>
                <th style="width: 160px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $p)
                <tr>
                    <td style="width: 56px;">
                        @if($p->image_path)
                            <img src="{{ $p->image_path }}" 
                                 alt="{{ $p->name }}" 
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                 style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border);">
                            <div style="display: none; width: 44px; height: 44px; border-radius: 8px; background: #f1f5f9; align-items: center; justify-content: center; color: #64748b; border: 1px solid #e2e8f0;" title="Ikon Standar Produk">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                                    <line x1="6" y1="1" x2="6" y2="4"></line>
                                    <line x1="10" y1="1" x2="10" y2="4"></line>
                                    <line x1="14" y1="1" x2="14" y2="4"></line>
                                </svg>
                            </div>
                        @else
                            <div style="width: 44px; height: 44px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #64748b; border: 1px solid #e2e8f0;" title="Ikon Standar Produk">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                                    <line x1="6" y1="1" x2="6" y2="4"></line>
                                    <line x1="10" y1="1" x2="10" y2="4"></line>
                                    <line x1="14" y1="1" x2="14" y2="4"></line>
                                </svg>
                            </div>
                        @endif
                    </td>
                    <td><code>{{ $p->sku }}</code></td>
                    <td>
                        <strong style="font-size: 15px;">{{ $p->name }}</strong>
                        <div style="display: flex; gap: 4px; margin-top: 4px; flex-wrap: wrap;">
                            @if($p->modifierGroups->isNotEmpty())
                                <span class="badge" style="background: #e0e7ff; color: #3730a3; font-size: 11px;">{{ $p->modifierGroups->count() }} Modifier</span>
                            @endif
                            @if($p->has_variants && $p->variants->isNotEmpty())
                                <span class="badge" style="background: #fae8ff; color: #86198f; font-size: 11px;">{{ $p->variants->count() }} Varian</span>
                            @endif
                        </div>
                        @if($p->description)
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">{{ Str::limit($p->description, 50) }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-info">{{ $p->category->name ?? 'Tanpa Kategori' }}</span>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 4px; min-width: 160px;">
                            @foreach($orderTypes as $ot)
                                @php
                                    $pPrice = $p->prices->firstWhere('order_type_id', $ot->id)?->price;
                                @endphp
                                <div style="font-size: 12px; display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 2px 6px; background: #f8fafc; border-radius: 4px; border: 1px solid #f1f5f9;">
                                    <span style="color: #475569; font-weight: 600;">{{ $ot->name }}:</span>
                                    <strong style="color: #166534;">Rp {{ number_format($pPrice ?? 0, 0, ',', '.') }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        <span style="color: var(--text-muted);">Rp {{ number_format($p->manual_cost, 0, ',', '.') }}</span>
                    </td>
                    <td>
                        @if($p->is_active)
                            <span class="badge badge-success">Tersedia</span>
                        @else
                            <span class="badge badge-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <a href="{{ route('products.edit', $p) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('products.destroy', $p) }}" method="POST" onsubmit="return confirm('Hapus menu {{ $p->name }}? Data akan di-soft delete.')" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fecaca;">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 32px;">Belum ada menu produk terdaftar. Silakan tambahkan menu pertama.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
