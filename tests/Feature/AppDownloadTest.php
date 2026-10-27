<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AppDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Owner Hiroshima',
            'username' => 'ownerhiroshima',
            'email' => 'owner@hiroshima.web.id',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_download_app_serves_hiroshima_apk_file(): void
    {
        // Pastikan dummy file Hiroshima.apk ada di public/downloads
        $downloadsDir = public_path('downloads');
        if (!File::isDirectory($downloadsDir)) {
            File::makeDirectory($downloadsDir, 0755, true, true);
        }
        $testApkPath = $downloadsDir . DIRECTORY_SEPARATOR . 'Hiroshima.apk';
        if (!file_exists($testApkPath)) {
            file_put_contents($testApkPath, 'APK_MOCK_DATA');
        }

        $response = $this->get(route('app.download'));
        $response->assertStatus(200);
        $this->assertEquals('application/vnd.android.package-archive', $response->headers->get('content-type'));
        $this->assertStringContainsString('Hiroshima.apk', (string)$response->headers->get('content-disposition'));
    }

    public function test_download_info_page_displays_hiroshima_mobile_app_details(): void
    {
        $response = $this->actingAs($this->admin)->get(route('app.download.page'));
        $response->assertStatus(200);
        $response->assertSee('Aplikasi Mobile Hiroshima POS');
        $response->assertSee('Download APK Hiroshima');
        $response->assertSee('Scan untuk Unduh Langsung');
        $response->assertSee('Panduan Instalasi di Tablet / Smartphone Android');
    }

    public function test_dashboard_displays_download_app_button_and_banner(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Download App Hiroshima');
        $response->assertSee('Aplikasi Mobile Kasir POS (Hiroshima)');
        $response->assertSee('Download APK Hiroshima');
    }

    public function test_backoffice_layout_has_topbar_and_sidebar_download_links(): void
    {
        $response = $this->actingAs($this->admin)->get(route('products.index'));
        $response->assertStatus(200);
        $response->assertSee('Download App Hiroshima');
        $response->assertSee('APLIKASI MOBILE');
        $response->assertSee('Download Hiroshima');
    }

    public function test_custom_apk_url_redirects_when_configured(): void
    {
        Setting::set('app_apk_url', 'https://drive.google.com/file/d/test-apk/view', 'general');

        $response = $this->get(route('app.download'));
        $response->assertRedirect('https://drive.google.com/file/d/test-apk/view');
    }
}
