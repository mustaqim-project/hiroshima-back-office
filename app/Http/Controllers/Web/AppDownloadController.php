<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppDownloadController extends Controller
{
    /**
     * Cari file APK aplikasi mobile Hiroshima dari beberapa lokasi fallback.
     */
    protected function resolveApkPath(): ?string
    {
        // 1. Cek dari pengaturan custom file yang diunggah
        $configuredFile = Setting::get('app_apk_file');
        if (!empty($configuredFile)) {
            $storagePath = storage_path('app/public/' . ltrim($configuredFile, '/'));
            if (file_exists($storagePath)) {
                return $storagePath;
            }
            $publicPath = public_path(ltrim($configuredFile, '/'));
            if (file_exists($publicPath)) {
                return $publicPath;
            }
        }

        // 2. Cek di public/downloads/Hiroshima.apk
        $publicDownload = public_path('downloads/Hiroshima.apk');
        if (file_exists($publicDownload)) {
            return $publicDownload;
        }

        // 3. Cek di storage/app/public/downloads/Hiroshima.apk
        $storageDownload = storage_path('app/public/downloads/Hiroshima.apk');
        if (file_exists($storageDownload)) {
            return $storageDownload;
        }

        // 4. Cek di output flutter build mobile (Wajib Release APK)
        $mobileBuildApks = [
            base_path('../mobile/build/app/outputs/flutter-apk/app-release.apk'),
            'C:/laragon/www/Hiroshima/mobile/build/app/outputs/flutter-apk/app-release.apk',
        ];

        foreach ($mobileBuildApks as $apk) {
            if (file_exists($apk)) {
                return $apk;
            }
        }

        return null;
    }

    /**
     * Download langsung file APK Aplikasi Mobile Hiroshima
     */
    public function download(Request $request): BinaryFileResponse|RedirectResponse
    {
        // Jika ada external URL yang disetel di konfigurasi
        $externalUrl = Setting::get('app_apk_url');
        if (!empty($externalUrl) && (str_starts_with($externalUrl, 'http://') || str_starts_with($externalUrl, 'https://'))) {
            return redirect()->away($externalUrl);
        }

        $apkPath = $this->resolveApkPath();

        if ($apkPath && file_exists($apkPath)) {
            // Pastikan direktori public/downloads ada untuk direct link jika dibutuhkan
            $publicDir = public_path('downloads');
            if (!File::isDirectory($publicDir)) {
                File::makeDirectory($publicDir, 0755, true, true);
            }
            $publicCopy = $publicDir . DIRECTORY_SEPARATOR . 'Hiroshima.apk';
            if (!file_exists($publicCopy) || filemtime($apkPath) > filemtime($publicCopy)) {
                @copy($apkPath, $publicCopy);
            }

            return Response::download($apkPath, 'Hiroshima.apk', [
                'Content-Type' => 'application/vnd.android.package-archive',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]);
        }

        // Jika file belum tersedia, arahkan ke halaman info download
        return redirect()->route('app.download.page')->with('warning', 'File installer APK Hiroshima sedang disiapkan oleh sistem atau dapat diunggah melalui menu Pengaturan Umum.');
    }

    /**
     * Halaman Informasi & Panduan Instalasi Aplikasi Mobile Hiroshima
     */
    public function page(Request $request): View
    {
        $apkPath = $this->resolveApkPath();
        $hasApk = !empty($apkPath) && file_exists($apkPath);
        $fileSize = $hasApk ? round(filesize($apkPath) / (1024 * 1024), 1) : null;
        $lastUpdated = $hasApk ? date('d M Y H:i', filemtime($apkPath)) : null;
        $externalUrl = Setting::get('app_apk_url');

        // URL direct download untuk QR code
        $downloadUrl = route('app.download');

        return view('backoffice.download_app', compact('hasApk', 'fileSize', 'lastUpdated', 'externalUrl', 'downloadUrl'));
    }
}
