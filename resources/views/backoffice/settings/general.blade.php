@extends('layouts.app', ['title' => 'Pengaturan Identitas & Tampilan'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Pengaturan Identitas & Tampilan Sistem</h2>
        <p style="color: var(--text-muted); font-size: 14px;">
            Sesuaikan nama sistem POS, slogan operasional, logo brand, favicon browser, dan informasi hak cipta.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="{{ route('settings.smtp') }}" class="btn btn-outline btn-sm">
            <x-icon name="mail" size="14" /> <span style="margin-left: 6px;">Pengaturan SMTP Mail</span>
        </a>
    </div>
</div>

<form method="POST" action="{{ route('settings.general.update') }}" enctype="multipart/form-data">
    @csrf

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Card 1: Identitas Teks -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <div class="card-title">1. Nama Sistem & Badan Usaha</div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Sistem / Aplikasi <span style="color: #dc2626;">*</span></label>
                        <input type="text" id="app_name" name="app_name" value="{{ old('app_name', $settings['app_name']) }}" required placeholder="Contoh: HIROSHIMA POS" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600;" oninput="updateLivePreview()">
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Muncul pada header sidebar, halaman login, dan judul tab browser.</div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Perusahaan / Bisnis</label>
                        <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $settings['company_name']) }}" placeholder="Contoh: Hiroshima Japanese Street Food" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;" oninput="updateLivePreview()">
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Nama resmi pemilik lisensi dan entitas usaha.</div>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Slogan / Tagline Sistem</label>
                    <input type="text" id="app_tagline" name="app_tagline" value="{{ old('app_tagline', $settings['app_tagline']) }}" placeholder="Contoh: Pusat Kendali Operasional F&B Multi-Cabang" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;" oninput="updateLivePreview()">
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Keterangan deskriptif singkat di bawah nama aplikasi.</div>
                </div>
            </div>

            <!-- Card 2: Logo & Favicon -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <div class="card-title">2. Visual Branding (Logo & Favicon)</div>
                </div>

                <!-- Logo Section -->
                <div style="padding-bottom: 20px; border-bottom: 1px solid var(--border); margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Logo Utama Sistem</label>
                    <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                        <div id="logo-preview-box" style="width: 120px; height: 70px; background: #0f172a; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 6px; border: 1px solid var(--border); overflow: hidden;">
                            @if($settings['app_logo_url'])
                                <img id="preview-logo-img" src="{{ $settings['app_logo_url'] }}" alt="Logo" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            @else
                                <div id="preview-logo-placeholder" style="background: var(--primary); color: #fff; width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px;">
                                    {{ strtoupper(substr($settings['app_name'], 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div style="flex: 1; min-width: 240px;">
                            <input type="file" name="app_logo" id="app_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" style="font-size: 13px; width: 100%; margin-bottom: 6px;" onchange="previewUploadedImage(this, 'preview-logo-img', 'logo-preview-box')">
                            <div style="font-size: 11px; color: var(--text-muted);">
                                Format: PNG, JPG, WEBP, atau SVG (Maks. 2MB). Disarankan latar transparan (PNG/SVG).
                            </div>
                            @if($settings['app_logo'])
                                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #dc2626; margin-top: 8px; cursor: pointer;">
                                    <input type="checkbox" name="remove_logo" value="1"> Hapus logo kustom (kembali ke monogram default)
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Favicon Section -->
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Favicon Tab Browser</label>
                    <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                        <div id="favicon-preview-box" style="width: 48px; height: 48px; background: #ffffff; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 4px; border: 1px solid var(--border); box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            @if($settings['app_favicon_url'])
                                <img id="preview-favicon-img" src="{{ $settings['app_favicon_url'] }}" alt="Favicon" style="width: 32px; height: 32px; object-fit: contain;">
                            @else
                                <img id="preview-favicon-img" src="{{ asset('favicon.ico') }}" alt="Default" style="width: 28px; height: 28px; object-fit: contain;" onerror="this.style.display='none'">
                            @endif
                        </div>
                        <div style="flex: 1; min-width: 240px;">
                            <input type="file" name="app_favicon" id="app_favicon" accept=".ico,image/png,image/svg+xml" style="font-size: 13px; width: 100%; margin-bottom: 6px;" onchange="previewUploadedImage(this, 'preview-favicon-img', 'favicon-preview-box')">
                            <div style="font-size: 11px; color: var(--text-muted);">
                                Format: .ICO, PNG, atau SVG (Maks. 1MB). Disarankan resolusi 32x32 atau 64x64 px.
                            </div>
                            @if($settings['app_favicon'])
                                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #dc2626; margin-top: 8px; cursor: pointer;">
                                    <input type="checkbox" name="remove_favicon" value="1"> Hapus favicon kustom
                                </label>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Footer & Bantuan -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <div class="card-title">3. Teks Footer & Informasi Kontak</div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Teks Hak Cipta / Footer</label>
                    <input type="text" id="footer_text" name="footer_text" value="{{ old('footer_text', $settings['footer_text']) }}" placeholder="© 2026 Hiroshima Japanese Street Food. All rights reserved." style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Email Bantuan / Dukungan</label>
                        <input type="email" id="support_email" name="support_email" value="{{ old('support_email', $settings['support_email']) }}" placeholder="support@hiroshima.web.id" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">No. Telepon / WhatsApp Support</label>
                        <input type="text" id="support_phone" name="support_phone" value="{{ old('support_phone', $settings['support_phone']) }}" placeholder="+62 812-3456-7890" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 15px; font-weight: 700;">
                    <x-icon name="check" size="16" /> <span style="margin-left: 8px;">Simpan Perubahan Identitas</span>
                </button>
            </div>
        </div>

        <!-- Kolom Kanan: Pratinjau Tampilan (Live Preview) -->
        <div style="display: flex; flex-direction: column; gap: 20px; position: sticky; top: 90px;">
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <div class="card-title" style="font-size: 15px;">Pratinjau Sidebar Brand</div>
                </div>
                <div style="background: #0f172a; padding: 16px; border-radius: 8px; color: #ffffff;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div id="sim-badge" style="background: linear-gradient(135deg, #ef4444, #b91c1c); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px; color: #fff; flex-shrink: 0;">
                            @if($settings['app_logo_url'])
                                <img src="{{ $settings['app_logo_url'] }}" alt="Logo" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            @else
                                {{ strtoupper(substr($settings['app_name'], 0, 1)) }}
                            @endif
                        </div>
                        <div style="overflow: hidden;">
                            <div id="sim-app-name" style="font-size: 15px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $settings['app_name'] }}
                            </div>
                            <div id="sim-app-tagline" style="font-size: 11px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $settings['app_tagline'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <div class="card-title" style="font-size: 15px;">Simulasi Tab Browser</div>
                </div>
                <div style="background: #e2e8f0; padding: 8px 12px; border-radius: 8px; display: flex; align-items: center; gap: 8px; font-size: 12px; color: #334155;">
                    <span style="font-size: 14px;">🌐</span>
                    <strong id="sim-tab-title" style="font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $settings['app_name'] }} - {{ $settings['app_tagline'] }}
                    </strong>
                </div>
            </div>

            <div class="card" style="margin-bottom: 0; background: #f8fafc;">
                <div style="font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">Catatan Keamanan & Hak Akses</div>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5; margin-bottom: 8px;">
                    Perubahan identitas sistem dicatat secara otomatis dalam <strong>Audit Log Chain</strong> dengan mencatat nilai sebelum dan sesudah diperbarui.
                </p>
                <div style="font-size: 11px; color: #0284c7; background: #e0f2fe; padding: 8px; border-radius: 6px;">
                    ✓ Tersimpan permanen di database tabel <code>app_settings</code>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    function updateLivePreview() {
        const appName = document.getElementById('app_name').value || 'HIROSHIMA POS';
        const appTagline = document.getElementById('app_tagline').value || 'Pusat Kendali Operasional F&B';

        const simAppName = document.getElementById('sim-app-name');
        const simAppTagline = document.getElementById('sim-app-tagline');
        const simTabTitle = document.getElementById('sim-tab-title');

        if (simAppName) simAppName.textContent = appName;
        if (simAppTagline) simAppTagline.textContent = appTagline;
        if (simTabTitle) simTabTitle.textContent = appName + ' - ' + appTagline;
    }

    function previewUploadedImage(input, targetImgId, containerId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const container = document.getElementById(containerId);
                let img = document.getElementById(targetImgId);
                if (!img && container) {
                    container.innerHTML = '';
                    img = document.createElement('img');
                    img.id = targetImgId;
                    img.style.maxHeight = '100%';
                    img.style.maxWidth = '100%';
                    img.style.objectFit = 'contain';
                    container.appendChild(img);
                }
                if (img) {
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endpush
@endsection
