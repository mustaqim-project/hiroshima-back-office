@extends('layouts.app', ['title' => 'Edit Grup Modifier: ' . $modifierGroup->name])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('modifier-groups.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 13px; font-weight: 600;">← Kembali ke Daftar Grup Modifier</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Edit Grup Modifier: {{ $modifierGroup->name }}</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Perbarui konfigurasi opsi menu, harga tambahan, serta daftar produk yang terhubung.</p>
</div>

<form method="POST" action="{{ route('modifier-groups.update', $modifierGroup) }}">
    @csrf
    @method('PUT')

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
        <div>
            <!-- Informasi Utama Grup -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">1. Aturan & Konfigurasi Grup</div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Grup Modifier</label>
                    <input type="text" name="name" value="{{ old('name', $modifierGroup->name) }}" required placeholder="Contoh: Level Pedas, Ekstra Topping" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tipe Pilihan</label>
                        <select name="selection_type" id="selection_type" onchange="adjustSelectionDefaults()" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                            <option value="single" {{ old('selection_type', $modifierGroup->selection_type) === 'single' ? 'selected' : '' }}>Single (Pilih 1 saja / Radio)</option>
                            <option value="multiple" {{ old('selection_type', $modifierGroup->selection_type) === 'multiple' ? 'selected' : '' }}>Multiple (Bisa banyak / Checkbox)</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Min. Pilihan</label>
                        <input type="number" name="min_select" id="min_select" value="{{ old('min_select', $modifierGroup->min_select) }}" min="0" max="50" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Maks. Pilihan</label>
                        <input type="number" name="max_select" id="max_select" value="{{ old('max_select', $modifierGroup->max_select) }}" min="1" max="50" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    </div>
                </div>

                <div style="display: flex; gap: 24px; padding: 12px 16px; background: #f8fafc; border-radius: 8px; border: 1px solid var(--border);">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        <input type="hidden" name="is_required" value="0">
                        <input type="checkbox" name="is_required" value="1" {{ old('is_required', $modifierGroup->is_required) ? 'checked' : '' }}> Wajib Dipilih (Kasir harus memilih minimal 1)
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $modifierGroup->is_active) ? 'checked' : '' }}> Status Aktif
                    </label>
                </div>
            </div>

            <!-- Daftar Item Opsi Modifier -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">2. Daftar Item Opsi Modifier</div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="addModifierRow()">+ Tambah Baris Opsi</button>
                </div>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                    Tentukan nama opsi serta tambahan biaya harga jual (Rp) yang akan ditambahkan ke harga menu.
                </p>

                <table class="data-table" id="modifierTable">
                    <thead>
                        <tr>
                            <th>Nama Opsi</th>
                            <th style="width: 160px;">Tambahan Harga (Rp)</th>
                            <th style="width: 140px;">HPP / Cost (Rp)</th>
                            <th style="width: 50px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="modifierRows">
                        @foreach($modifierGroup->modifiers as $idx => $mod)
                            <tr class="mod-row">
                                <td>
                                    <input type="hidden" name="modifiers[{{ $idx }}][id]" value="{{ $mod->id }}">
                                    <input type="text" name="modifiers[{{ $idx }}][name]" value="{{ old('modifiers.'.$idx.'.name', $mod->name) }}" required placeholder="Contoh: Level 1" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px;">
                                </td>
                                <td>
                                    <input type="number" name="modifiers[{{ $idx }}][price]" value="{{ old('modifiers.'.$idx.'.price', $mod->price) }}" min="0" placeholder="0" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px;">
                                </td>
                                <td>
                                    <input type="number" name="modifiers[{{ $idx }}][cost]" value="{{ old('modifiers.'.$idx.'.cost', $mod->cost) }}" min="0" placeholder="0" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px;">
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" onclick="removeModifierRow(this)" style="background: transparent; border: none; color: #dc2626; cursor: pointer; display: inline-flex; align-items: center;" title="Hapus"><x-icon name="close" size="14" /></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Terhubung ke Menu Produk -->
        <div>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">3. Terapkan ke Menu</div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="toggleSelectAllProducts()">Pilih Semua</button>
                </div>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
                    Centang produk yang akan menampilkan opsi modifier ini saat dipesan di kasir POS.
                </p>

                <div style="max-height: 480px; overflow-y: auto; padding-right: 6px;">
                    @php $currCat = ''; @endphp
                    @foreach($products as $p)
                        @if($currCat !== ($p->category->name ?? 'Lainnya'))
                            @php $currCat = $p->category->name ?? 'Lainnya'; @endphp
                            <div style="font-size: 11px; font-weight: 700; color: #64748b; margin-top: 10px; margin-bottom: 4px; text-transform: uppercase; display: flex; align-items: center; gap: 4px;">
                                <x-icon name="folder" size="12" /> <span>{{ $currCat }}</span>
                            </div>
                        @endif
                        <label style="display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-radius: 6px; cursor: pointer; font-size: 13px;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                            <input type="checkbox" name="product_ids[]" value="{{ $p->id }}" class="prod-checkbox" {{ in_array($p->id, old('product_ids', $attachedProductIds)) ? 'checked' : '' }}>
                            <span>{{ $p->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 15px; font-weight: 700;">
                Simpan Perubahan Grup Modifier
            </button>
        </div>
    </div>
</form>

<script>
let rowIndex = {{ $modifierGroup->modifiers->count() + 1 }};

function adjustSelectionDefaults() {
    const type = document.getElementById('selection_type').value;
    if (type === 'single') {
        document.getElementById('max_select').value = 1;
    } else {
        if (parseInt(document.getElementById('max_select').value) <= 1) {
            document.getElementById('max_select').value = 5;
        }
    }
}

function addModifierRow() {
    const tbody = document.getElementById('modifierRows');
    const tr = document.createElement('tr');
    tr.className = 'mod-row';
    tr.innerHTML = `
        <td>
            <input type="text" name="modifiers[${rowIndex}][name]" required placeholder="Nama opsi baru" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px;">
        </td>
        <td>
            <input type="number" name="modifiers[${rowIndex}][price]" value="0" min="0" placeholder="0" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px;">
        </td>
        <td>
            <input type="number" name="modifiers[${rowIndex}][cost]" value="0" min="0" placeholder="0" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px;">
        </td>
        <td style="text-align: center;">
            <button type="button" onclick="removeModifierRow(this)" style="background: transparent; border: none; color: #dc2626; cursor: pointer; display: inline-flex; align-items: center;" title="Hapus"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>
        </td>
    `;
    tbody.appendChild(tr);
    rowIndex++;
}

function removeModifierRow(btn) {
    const rows = document.querySelectorAll('.mod-row');
    if (rows.length > 1) {
        btn.closest('tr').remove();
    } else {
        alert('Minimal harus ada 1 item opsi modifier.');
    }
}

function toggleSelectAllProducts() {
    const checkboxes = document.querySelectorAll('.prod-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
}
</script>
@endsection
