@extends('layouts.app', ['title' => 'Download Aplikasi Mobile Hiroshima'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; color: #0f172a;">Aplikasi Mobile Hiroshima POS</h2>
            <span class="badge badge-success" style="font-size: 11px; padding: 4px 10px; border-radius: 20px;">Android v1.0.0</span>
        </div>
        <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">
            Unduh installer APK resmi Hiroshima untuk perangkat tablet kasir dan smartphone operasional cabang.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">
            &larr; <span style="margin-left: 6px;">Kembali ke Dashboard</span>
        </a>
    </div>
</div>

@if(session('warning'))
    <div class="alert-bar" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
        <strong>Perhatian:</strong> {{ session('warning') }}
    </div>
@endif

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
    <!-- Kolom Kiri: Card Utama Unduhan & Panduan -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Card Download Utama -->
        <div class="card" style="margin-bottom: 0; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
            <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap; padding: 10px 0;">
                <div style="width: 72px; height: 72px; background: linear-gradient(135deg, #ef4444, #b91c1c); border-radius: 18px; display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 32px; font-weight: 800; box-shadow: 0 8px 24px rgba(239, 68, 68, 0.35); flex-shrink: 0;">
                    H
                </div>
                <div style="flex: 1; min-width: 220px;">
                    <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">Hiroshima POS (Mobile App)</h3>
                    <p style="font-size: 13.5px; color: var(--text-muted); line-height: 1.5;">
                        Sistem kasir offline-first berkecepatan tinggi, mendukung printer thermal bluetooth, multi-cabang, buka/tutup shift kasir, dan penahanan pesanan (Hold Bill).
                    </p>
                    <div style="display: flex; gap: 16px; margin-top: 10px; font-size: 12.5px; color: #64748b; flex-wrap: wrap;">
                        <span>📱 <strong>Platform:</strong> Android 7.0+ (Tablet & HP)</span>
                        @if($fileSize)
                            <span>📦 <strong>Ukuran:</strong> {{ $fileSize }} MB</span>
                        @endif
                        @if($lastUpdated)
                            <span>🕒 <strong>Pembaruan:</strong> {{ $lastUpdated }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div style="border-top: 1px solid var(--border); margin-top: 20px; padding-top: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="badge {{ $hasApk ? 'badge-success' : 'badge-warning' }}" style="padding: 6px 12px; font-size: 12px;">
                        {{ $hasApk ? '✓ Release APK Tersedia' : 'Dalam Kompilasi / Link Kustom' }}
                    </span>
                    <span style="font-size: 12px; color: var(--text-muted);">Tipe: <strong>Production Release APK</strong></span>
                </div>

                <div style="display: flex; gap: 10px;">
                    @if($hasApk || !empty($externalUrl))
                        <a href="{{ route('app.download') }}" class="btn btn-primary" style="padding: 10px 24px; font-size: 14px; font-weight: 700; background: #dc2626; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35); display: inline-flex; align-items: center; gap: 8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            <span>Download Release APK Hiroshima</span>
                        </a>
                    @else
                        <button type="button" class="btn btn-outline" disabled style="opacity: 0.6; cursor: not-allowed;">
                            Menyiapkan Release APK...
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Panduan Instalasi -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <div class="card-title">📖 Panduan Instalasi di Tablet / Smartphone Android</div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px; font-size: 13.5px; line-height: 1.6;">
                <div style="display: flex; gap: 14px;">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; flex-shrink: 0;">
                        1
                    </div>
                    <div>
                        <strong style="color: #0f172a;">Unduh File Installer</strong>
                        <p style="color: var(--text-muted); margin-top: 2px;">
                            Klik tombol <strong>Download APK Hiroshima</strong> di atas, atau scan QR Code di sebelah kanan menggunakan kamera tablet/smartphone kasir Anda.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 14px;">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; flex-shrink: 0;">
                        2
                    </div>
                    <div>
                        <strong style="color: #0f172a;">Izinkan Instalasi dari Sumber Tidak Dikenal</strong>
                        <p style="color: var(--text-muted); margin-top: 2px;">
                            Saat membuka file APK, jika muncul peringatan keamanan sistem Android, pilih <strong>Pengaturan &rarr; Izinkan dari Sumber Ini</strong> (*Install Unknown Apps*).
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 14px;">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; flex-shrink: 0;">
                        3
                    </div>
                    <div>
                        <strong style="color: #0f172a;">Pasang & Buka Aplikasi</strong>
                        <p style="color: var(--text-muted); margin-top: 2px;">
                            Tekan tombol <strong>Install / Pasang</strong>. Setelah selesai, buka aplikasi <strong>Hiroshima POS</strong> di layar utama.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 14px;">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; flex-shrink: 0;">
                        4
                    </div>
                    <div>
                        <strong style="color: #0f172a;">Login Kasir & Mulai Shift</strong>
                        <p style="color: var(--text-muted); margin-top: 2px;">
                            Pilih cabang Anda, masukkan <strong>PIN Kasir 6 Digit</strong>, lakukan <strong>Buka Shift</strong> dengan menghitung modal awal kas, dan mulai melayani transaksi pelanggan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: QR Code untuk Scan Langsung dari Tablet/HP -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="margin-bottom: 0; text-align: center; padding: 24px 20px;">
            <div class="card-title" style="margin-bottom: 12px; font-size: 16px;">📲 Scan untuk Unduh Langsung</div>
            <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 18px;">
                Arahkan kamera tablet atau HP kasir ke QR Code berikut untuk mengunduh APK langsung ke perangkat:
            </p>

            <div style="display: inline-block; padding: 12px; background: #ffffff; border: 2px dashed #cbd5e1; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 16px;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($downloadUrl) }}" 
                     alt="QR Code Unduh Hiroshima POS" 
                     width="180" 
                     height="180" 
                     style="display: block; border-radius: 8px;">
            </div>

            <div style="background: #f1f5f9; padding: 10px 14px; border-radius: 8px; font-size: 12px; color: #475569; word-break: break-all; text-align: left;">
                <div style="font-weight: 700; margin-bottom: 2px; color: #0f172a;">Link Langsung:</div>
                <a href="{{ $downloadUrl }}" style="color: #0284c7; text-decoration: none;">{{ $downloadUrl }}</a>
            </div>
        </div>

        <!-- Quick Info Box -->
        <div class="card" style="margin-bottom: 0; background: #f8fafc;">
            <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">
                ⚙️ Butuh Update File APK?
            </div>
            <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                Admin dapat mengganti file APK atau menghubungkan link Google Drive/server penyimpanan melalui menu <strong>Pengaturan Umum</strong>.
            </p>
            <a href="{{ route('settings.general') }}" class="btn btn-outline btn-sm" style="width: 100%; font-size: 12px;">
                Buka Pengaturan Umum
            </a>
        </div>
    </div>
</div>

<style>
@media (max-width: 991px) {
    div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endsection
