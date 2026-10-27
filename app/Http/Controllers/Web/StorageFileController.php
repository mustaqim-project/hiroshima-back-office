<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFileController extends Controller
{
    /**
     * Fallback file server for /storage/{path}.
     * Ensures images, logos, and uploads are served seamlessly even on shared
     * hosting environments (Hostinger/cPanel) where public/storage symlink may be missing.
     */
    public function show(string $path): BinaryFileResponse|Response|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Prevent directory traversal attacks
        $cleanPath = str_replace(['..', '\\'], ['', '/'], $path);
        $cleanPath = ltrim($cleanPath, '/');

        // Check if exists in public disk (works with real storage & test fakes)
        if (Storage::disk('public')->exists($cleanPath)) {
            return Storage::disk('public')->response($cleanPath, null, [
                'Cache-Control' => 'public, max-age=604800, immutable',
            ]);
        }

        // Fallback: check physical storage_path directly
        $fullPath = storage_path('app/public/' . $cleanPath);
        if (file_exists($fullPath) && !is_dir($fullPath)) {
            $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=604800, immutable',
            ]);
        }

        // Fallback: check public/storage directly if present
        $publicFallback = public_path('storage/' . $cleanPath);
        if (file_exists($publicFallback) && !is_dir($publicFallback)) {
            $mimeType = mime_content_type($publicFallback) ?: 'application/octet-stream';
            return response()->file($publicFallback, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=604800, immutable',
            ]);
        }

        abort(404, 'Berkas tidak ditemukan.');
    }
}
