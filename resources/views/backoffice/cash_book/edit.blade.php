@extends('layouts.app', ['title' => 'Edit Transaksi Kas #' . $cashBook->id])

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('cash-books.index') }}" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">← Kembali ke Buku Kas</a>
    <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin-top: 8px;">Edit Transaksi Kas #{{ $cashBook->id }}</h2>
    <p style="color: var(--text-muted); font-size: 14px;">Perbarui data pemasukan/pengeluaran kas dan ganti bukti foto nota jika diperlukan.</p>
</div>

<div class="card" style="max-width: 720px;">
    <div class="card-header">
        <div class="card-title">Perbarui Data Arus Kas</div>
    </div>

    <form method="POST" action="{{ route('cash-books.update', $cashBook) }}" enctype="multipart/form-data" id="cashBookEditForm">
        @csrf
        @method('PUT')

        <!-- Tipe Transaksi (Uang Masuk / Keluar) -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px;">Jenis Arus Kas</label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <label style="display: flex; align-items: center; gap: 10px; padding: 14px 18px; border: 2px solid #16a34a; border-radius: 10px; cursor: pointer; background: #f0fdf4;">
                    <input type="radio" name="type" value="in" {{ old('type', $cashBook->type) === 'in' ? 'checked' : '' }} onchange="updateCategoryOptions()" style="width: 18px; height: 18px; accent-color: #16a34a;">
                    <div>
                        <strong style="color: #166534; font-size: 15px; display: inline-flex; align-items: center; gap: 6px;"><x-icon name="arrow-down" size="14" /> <span>Uang Masuk</span></strong>
                        <div style="font-size: 11px; color: #15803d;">Suntikan modal, catering, penjualan lain</div>
                    </div>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; padding: 14px 18px; border: 2px solid #dc2626; border-radius: 10px; cursor: pointer; background: #fef2f2;">
                    <input type="radio" name="type" value="out" {{ old('type', $cashBook->type) === 'out' ? 'checked' : '' }} onchange="updateCategoryOptions()" style="width: 18px; height: 18px; accent-color: #dc2626;">
                    <div>
                        <strong style="color: #991b1b; font-size: 15px; display: inline-flex; align-items: center; gap: 6px;"><x-icon name="arrow-up" size="14" /> <span>Uang Keluar</span></strong>
                        <div style="font-size: 11px; color: #b91c1c;">Belanja bumbu, gas LPG, sewa, upah</div>
                    </div>
                </label>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Cabang Outlet / Lingkup Kas</label>
                <select name="branch_id" {{ $isGlobalAllowed ? '' : 'required' }} style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    @if($isGlobalAllowed)
                        <option value="" {{ old('branch_id', $cashBook->branch_id) === null ? 'selected' : '' }}>
                            Global (Kantor Pusat / Seluruh Usaha)
                        </option>
                    @endif
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', $cashBook->branch_id) == $b->id ? 'selected' : '' }}>
                            {{ $b->name }} ({{ $b->code }})
                        </option>
                    @endforeach
                </select>
                @if($isGlobalAllowed)
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Pilih <strong>Global</strong> jika transaksi kas kantor pusat/seluruh usaha, bukan milik outlet gerobak tertentu.</div>
                @endif
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tanggal Transaksi</label>
                <input type="date" name="transaction_date" value="{{ old('transaction_date', $cashBook->transaction_date->format('Y-m-d')) }}" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Kategori Arus Kas</label>
                <select name="category" id="categorySelect" required onchange="handleCategoryChange()" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <!-- Diisi dinamis oleh JavaScript -->
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nominal (Rp)</label>
                <input type="number" name="amount" value="{{ old('amount', $cashBook->amount) }}" required min="1" step="500" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 15px; font-weight: 600;">
            </div>
        </div>

        <!-- Kolom Keterangan (Wajib jika Kategori 'Lainnya') -->
        <div style="margin-bottom: 16px;" id="descriptionContainer">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;" id="descriptionLabel">
                Keterangan / Rincian Pengeluaran <span id="requiredTag" style="color: #dc2626; font-size: 11px;">(Wajib diisi jika kategori Lainnya)</span>
            </label>
            <textarea name="description" id="descriptionInput" rows="3" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">{{ old('description', $cashBook->description) }}</textarea>
        </div>

        <!-- Upload Bukti Foto (Optional) -->
        <div style="margin-bottom: 24px; padding: 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px;">
            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">
                Upload Bukti Foto / Struk Nota Baru <span style="font-size: 11px; font-weight: normal; color: var(--text-muted);">(Opsional, kosongkan jika tidak diubah)</span>
            </label>

            @if($cashBook->receipt_photo_path)
                <div style="margin-bottom: 12px; display: flex; align-items: center; gap: 12px;">
                    <img src="{{ asset('storage/' . $cashBook->receipt_photo_path) }}" alt="Foto Lama" style="height: 60px; border-radius: 6px; border: 1px solid var(--border);">
                    <span style="font-size: 12px; color: var(--text-muted);">Foto saat ini terpasang</span>
                </div>
            @endif

            <input type="file" name="receipt_photo" id="receiptPhotoInput" accept="image/*" onchange="previewPhoto(this)" style="font-size: 13px;">
            <div id="photoPreviewContainer" style="display: none; margin-top: 12px;">
                <img id="photoPreview" src="" alt="Preview Bukti Baru" style="max-height: 140px; border-radius: 8px; border: 1px solid var(--border);">
            </div>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="background: #047857; padding: 10px 24px;">Simpan Perubahan</button>
            <a href="{{ route('cash-books.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

<script>
    const inCategories = @json($categories['in']);
    const outCategories = @json($categories['out']);
    const currentCategory = @json(old('category', $cashBook->category));

    function updateCategoryOptions() {
        const type = document.querySelector('input[name="type"]:checked')?.value || 'out';
        const list = type === 'in' ? inCategories : outCategories;
        const select = document.getElementById('categorySelect');

        select.innerHTML = '';
        list.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat;
            opt.textContent = cat;
            if (currentCategory === cat) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });

        handleCategoryChange();
    }

    function handleCategoryChange() {
        const select = document.getElementById('categorySelect');
        const descInput = document.getElementById('descriptionInput');
        const requiredTag = document.getElementById('requiredTag');
        const isLainnya = select.value.toLowerCase().trim() === 'lainnya';

        if (isLainnya) {
            requiredTag.style.display = 'inline';
            requiredTag.textContent = '(WAJIB DIISI)';
            requiredTag.style.fontWeight = 'bold';
            requiredTag.style.color = '#dc2626';
            descInput.setAttribute('required', 'required');
            descInput.placeholder = 'WAJIB: Tuliskan alasan/rincian transaksi untuk kategori Lainnya...';
        } else {
            requiredTag.style.display = 'inline';
            requiredTag.textContent = '(Opsional)';
            requiredTag.style.fontWeight = 'normal';
            requiredTag.style.color = '#64748b';
            descInput.removeAttribute('required');
            descInput.placeholder = 'Catatan atau nomor nota (opsional)';
        }
    }

    function previewPhoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('photoPreview').src = e.target.result;
                document.getElementById('photoPreviewContainer').style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateCategoryOptions();
    });
</script>
@endsection
