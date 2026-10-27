@props([
    'module',
    'title' => 'Data',
])

@php
    $modalId = 'import-modal-' . Str::slug($module);
@endphp

<div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
    <!-- Unduh Template -->
    <a href="{{ route($module . '.template') }}" class="btn btn-outline btn-sm" title="Unduh template Excel dengan contoh data dummy">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
        <span>Unduh Template</span>
    </a>

    <!-- Ekspor Excel -->
    <a href="{{ route($module . '.export') }}" class="btn btn-outline btn-sm" title="Ekspor semua data saat ini (atau template dummy jika kosong) ke Excel">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h2"/><path d="M8 17h2"/><path d="M14 13h2"/><path d="M14 17h2"/></svg>
        <span style="color: #15803d; font-weight: 600;">Ekspor Excel</span>
    </a>

    <!-- Impor Excel -->
    <button type="button" class="btn btn-outline btn-sm" onclick="openExcelModal('{{ $modalId }}')" title="Impor data dari spreadsheet Excel / CSV">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
        <span style="color: #0369a1; font-weight: 600;">Impor Excel</span>
    </button>
</div>

<!-- Modal Dialog Impor -->
<div id="{{ $modalId }}" class="excel-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);" onclick="if(event.target === this) closeExcelModal('{{ $modalId }}')">
    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 500px; margin: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; border: 1px solid #e2e8f0;">
        <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="background: #e0f2fe; color: #0284c7; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="12" x2="12" y1="18" y2="12"/><line x1="9" x2="15" y1="15" y2="15"/></svg>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Impor {{ $title }}</h3>
                    <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Unggah file spreadsheet Excel (.xlsx) atau CSV</p>
                </div>
            </div>
            <button type="button" onclick="closeExcelModal('{{ $modalId }}')" style="background: transparent; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; line-height: 1; padding: 4px;">&times;</button>
        </div>

        <form action="{{ route($module . '.import') }}" method="POST" enctype="multipart/form-data" style="margin: 0;">
            @csrf
            <div style="padding: 24px;">
                <div style="background: #f1f5f9; padding: 14px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 12px; color: #334155; line-height: 1.5;">
                    <div style="font-weight: 700; margin-bottom: 4px; color: #0f172a;">Petunjuk Format Data:</div>
                    <ul style="margin: 0; padding-left: 18px;">
                        <li>Pastikan urutan kolom sesuai dengan template standar.</li>
                        <li>Gunakan tombol <strong>Unduh Template</strong> jika Anda belum memiliki formatnya.</li>
                        <li>Jika data dengan kunci yang sama sudah ada di sistem, data tersebut akan <strong>diperbarui (*update*)</strong> otomatis.</li>
                    </ul>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 8px;">Pilih File Spreadsheet <span style="color: #dc2626;">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required style="width: 100%; padding: 10px; border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; font-size: 13px; cursor: pointer;">
                    <div style="font-size: 11px; color: #64748b; margin-top: 6px;">Format yang didukung: .xlsx, .xls, .csv (Maks. 10 MB).</div>
                </div>
            </div>

            <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; background: #f8fafc;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeExcelModal('{{ $modalId }}')">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm" style="padding: 8px 18px; font-weight: 600;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                    <span>Mulai Impor File</span>
                </button>
            </div>
        </form>
    </div>
</div>

@once
@push('scripts')
<script>
    function openExcelModal(id) {
        const m = document.getElementById(id);
        if (m) m.style.display = 'flex';
    }
    function closeExcelModal(id) {
        const m = document.getElementById(id);
        if (m) m.style.display = 'none';
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.excel-modal-backdrop').forEach(el => el.style.display = 'none');
        }
    });
</script>
@endpush
@endonce
