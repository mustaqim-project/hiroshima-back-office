@extends('layouts.app', ['title' => 'Edit Menu: ' . $product->name])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('products.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Daftar Menu</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Edit Menu: {{ $product->name }}</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Perbarui data produk, foto menu, penetapan multi-harga, grup modifier, serta varian.</p>
</div>

<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <div class="card-title">Perbarui Data Produk #{{ $product->id }}</div>
    </div>
    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Menu Produk</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kode SKU</label>
                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; text-transform: uppercase;">
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kategori Menu</label>
            <select name="category_id" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }} ({{ strtoupper($cat->target_printer) }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Foto Menu Produk -->
        <div style="margin-bottom: 20px; padding: 16px; background: #f8fafc; border: 1px solid var(--border); border-radius: 10px;">
            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Foto Menu Produk</label>
            <div style="display: flex; gap: 16px; align-items: center;">
                <div id="photoPreviewBox" style="width: 72px; height: 72px; border-radius: 10px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; border: 1px solid var(--border); overflow: hidden; flex-shrink: 0;">
                    @if($product->image_path)
                        <img id="photoPreviewImg" src="{{ $product->image_path }}" onerror="this.style.display='none'; document.getElementById('defaultIconSvg').style.display='block';" style="width: 100%; height: 100%; object-fit: cover;">
                        <svg id="defaultIconSvg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                        </svg>
                    @else
                        <svg id="defaultIconSvg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                        </svg>
                        <img id="photoPreviewImg" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                    @endif
                </div>
                <div style="flex: 1;">
                    <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp" style="font-size: 13px;" onchange="handlePhotoPreview(this)">
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Pilih file gambar baru untuk mengganti foto saat ini.</div>
                    @if($product->image_path)
                        <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #dc2626; margin-top: 6px; cursor: pointer;">
                            <input type="checkbox" name="remove_photo" value="1"> Hapus foto & gunakan ikon standar
                        </label>
                    @endif
                </div>
            </div>
        </div>

        <!-- Multi-Harga Sesuai Jenis Pesanan Master Data -->
        <div style="margin-bottom: 20px; padding: 18px; background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                <label style="font-size: 14px; font-weight: 800; color: #166534; display: inline-flex; align-items: center; gap: 6px;">
                    <x-icon name="cash" size="16" /> <span>Multi-Harga Sesuai Jenis Pesanan (Master Data)</span>
                </label>
                <span style="font-size: 11px; color: #15803d; font-weight: 600;">Dine In, Take Away, Delivery / Ojol</span>
            </div>
            <p style="font-size: 12px; color: #14532d; margin-bottom: 14px;">
                Tetapkan harga jual khusus per jenis transaksi (contoh: Take Away lebih mahal Rp 1.000 untuk kemasan, atau Delivery disesuaikan margin ojol).
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px;">
                @foreach($orderTypes as $ot)
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                            Harga {{ $ot->name }} (Rp)
                            @if($ot->is_default)
                                <span style="color: #16a34a; font-size: 10px;">(Default)</span>
                            @endif
                        </label>
                        <input type="number" name="prices[{{ $ot->id }}]" id="price_ot_{{ $ot->id }}" value="{{ old('prices.' . $ot->id, $priceMap[$ot->id] ?? $defaultPrice) }}" required min="0" step="500" placeholder="Rp 0" style="width: 100%; padding: 10px; border: 1px solid #86efac; border-radius: 8px; font-size: 14px; font-weight: 700; background: #fff;">
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Grup Modifier yang Berlaku (Add-ons / Level Pedas / Pilihan Menu) -->
        <div style="margin-bottom: 20px; padding: 18px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <label style="font-size: 14px; font-weight: 800; color: var(--text-main); display: inline-flex; align-items: center; gap: 6px;">
                    <x-icon name="modifier" size="16" /> <span>Grup Modifier yang Berlaku pada Menu Ini</span>
                </label>
                <a href="{{ route('modifier-groups.create') }}" target="_blank" style="font-size: 12px; color: var(--primary); text-decoration: none; font-weight: 600;">+ Buat Grup Baru</a>
            </div>
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">
                Centang kelompok pilihan yang akan ditanyakan kasir saat menu ini dipesan (misal Level Pedas, Ekstra Topping, dll.).
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px;">
                @forelse($modifierGroups as $mg)
                    <label style="display: flex; align-items: flex-start; gap: 10px; padding: 10px; background: #ffffff; border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
                        <input type="checkbox" name="modifier_group_ids[]" value="{{ $mg->id }}" {{ in_array($mg->id, old('modifier_group_ids', $attachedModifierGroupIds)) ? 'checked' : '' }} style="margin-top: 3px;">
                        <div>
                            <div style="font-weight: 700; font-size: 13px;">{{ $mg->name }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">
                                {{ $mg->selection_type === 'single' ? 'Pilih 1 Saja' : 'Bisa Banyak' }} • {{ $mg->modifiers->count() }} Opsi
                            </div>
                        </div>
                    </label>
                @empty
                    <div style="font-size: 12px; color: var(--text-muted); font-style: italic;">
                        Belum ada grup modifier. Anda dapat membuatnya di menu <strong>Grup Modifier & Varian</strong>.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Varian Menu Produk (Ukuran / Size / Jenis) -->
        <div style="margin-bottom: 20px; padding: 18px; background: #fdf4ff; border: 1.5px solid #f0abfc; border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <label style="font-size: 14px; font-weight: 800; color: #86198f; display: inline-flex; align-items: center; gap: 6px;">
                    <x-icon name="promo" size="16" /> <span>Varian Produk (Contoh: Ukuran Porsi, Size M/L)</span>
                </label>
                <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: #701a75; cursor: pointer;">
                    <input type="checkbox" name="has_variants" id="hasVariantsToggle" value="1" {{ old('has_variants', $product->has_variants) ? 'checked' : '' }} onchange="toggleVariantSection(this.checked)"> Aktifkan Varian
                </label>
            </div>
            <p style="font-size: 12px; color: #701a75; margin-bottom: 12px;">
                Aktifkan jika produk memiliki variasi ukuran/porsi dengan SKU dan harga tersendiri.
            </p>

            <div id="variantSection" style="display: {{ old('has_variants', $product->has_variants) ? 'block' : 'none' }};">
                <table class="data-table" style="background: #ffffff; border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr>
                            <th>Nama Varian</th>
                            <th>Kode SKU Varian</th>
                            <th style="width: 140px;">Harga Jual (Rp)</th>
                            <th style="width: 130px;">HPP (Rp)</th>
                            <th style="width: 50px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="variantRows">
                        @forelse($variants as $idx => $v)
                            <tr class="var-row">
                                <td>
                                    <input type="hidden" name="variants[{{ $idx }}][id]" value="{{ $v->id }}">
                                    <input type="text" name="variants[{{ $idx }}][name]" value="{{ old('variants.'.$idx.'.name', $v->name) }}" placeholder="Regular" style="width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px;">
                                </td>
                                <td><input type="text" name="variants[{{ $idx }}][sku]" value="{{ old('variants.'.$idx.'.sku', $v->sku) }}" placeholder="SKU-REG" style="width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px;"></td>
                                <td><input type="number" name="variants[{{ $idx }}][price]" value="{{ old('variants.'.$idx.'.price', $v->price) }}" min="0" placeholder="0" style="width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px;"></td>
                                <td><input type="number" name="variants[{{ $idx }}][manual_cost]" value="{{ old('variants.'.$idx.'.manual_cost', $v->manual_cost) }}" min="0" placeholder="0" style="width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px;"></td>
                                <td style="text-align: center;"><button type="button" onclick="this.closest('tr').remove()" style="background: transparent; border: none; color: #dc2626; cursor: pointer; display: inline-flex; align-items: center;" title="Hapus"><x-icon name="close" size="14" /></button></td>
                            </tr>
                        @empty
                            <tr class="var-row">
                                <td><input type="text" name="variants[0][name]" placeholder="Regular" style="width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px;"></td>
                                <td><input type="text" name="variants[0][sku]" placeholder="SKU-REG" style="width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px;"></td>
                                <td><input type="number" name="variants[0][price]" min="0" placeholder="0" style="width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px;"></td>
                                <td><input type="number" name="variants[0][manual_cost]" min="0" placeholder="0" style="width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px;"></td>
                                <td style="text-align: center;"><button type="button" onclick="this.closest('tr').remove()" style="background: transparent; border: none; color: #dc2626; cursor: pointer; display: inline-flex; align-items: center;" title="Hapus"><x-icon name="close" size="14" /></button></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <button type="button" class="btn btn-outline btn-sm" onclick="addVariantRow()" style="margin-top: 8px;">+ Tambah Varian</button>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Estimasi HPP / Modal Bahan Baku (Rp)</label>
                <input type="number" name="manual_cost" value="{{ old('manual_cost', $product->manual_cost) }}" min="0" step="500" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Deskripsi / Catatan Menu</label>
            <textarea name="description" rows="3" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">{{ old('description', $product->description) }}</textarea>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status Ketersediaan</label>
            <select name="is_active" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                <option value="1" {{ old('is_active', $product->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Tersedia & Dijual di Kasir</option>
                <option value="0" {{ old('is_active', $product->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Habis / Tidak Dijual</option>
            </select>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('products.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

<script>
let varIndex = {{ max($variants->count(), 1) + 1 }};

function toggleVariantSection(checked) {
    const sec = document.getElementById('variantSection');
    if (!sec) return;
    sec.style.display = checked ? 'block' : 'none';
    const inputs = sec.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.disabled = !checked;
    });
}

function addVariantRow() {
    const tbody = document.getElementById('variantRows');
    const tr = document.createElement('tr');
    tr.className = 'var-row';
    tr.innerHTML = `
        <td><input type="text" name="variants[${varIndex}][name]" placeholder="Varian baru" style="width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px;"></td>
        <td><input type="text" name="variants[${varIndex}][sku]" placeholder="SKU" style="width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px;"></td>
        <td><input type="number" name="variants[${varIndex}][price]" min="0" placeholder="0" style="width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px;"></td>
        <td><input type="number" name="variants[${varIndex}][manual_cost]" min="0" placeholder="0" style="width: 100%; padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px;"></td>
        <td style="text-align: center;"><button type="button" onclick="this.closest('tr').remove()" style="background: transparent; border: none; color: #dc2626; cursor: pointer; display: inline-flex; align-items: center;" title="Hapus"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button></td>
    `;
    tbody.appendChild(tr);
    const toggle = document.getElementById('hasVariantsToggle');
    if (toggle && !toggle.checked) {
        tr.querySelectorAll('input, select, textarea').forEach(input => input.disabled = true);
    }
    varIndex++;
}

document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('hasVariantsToggle');
    if (toggle) {
        toggleVariantSection(toggle.checked);
    }
});

function handlePhotoPreview(input) {
    const previewImg = document.getElementById('photoPreviewImg');
    const defaultSvg = document.getElementById('defaultIconSvg');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewImg.style.display = 'block';
            defaultSvg.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
