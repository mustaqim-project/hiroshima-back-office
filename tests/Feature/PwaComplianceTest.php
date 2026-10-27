<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaComplianceTest extends TestCase
{
    public function test_manifest_webmanifest_is_valid_and_accessible()
    {
        $manifestPath = public_path('manifest.webmanifest');
        $this->assertFileExists($manifestPath);

        $json = file_get_contents($manifestPath);
        $manifest = json_decode($json, true);

        $this->assertIsArray($manifest);
        $this->assertEquals('Hiroshima Back Office', $manifest['name']);
        $this->assertEquals('Hiroshima', $manifest['short_name']);
        $this->assertEquals('standalone', $manifest['display']);
        $this->assertEquals('/?source=pwa', $manifest['start_url']);
        $this->assertEquals('#dc2626', $manifest['theme_color']);
        $this->assertEquals('#0f172a', $manifest['background_color']);
        $this->assertNotEmpty($manifest['icons']);

        // Assert 192x192 and 512x512 icons exist
        $sizes = array_column($manifest['icons'], 'sizes');
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);

        // Assert maskable icon exists
        $purposes = array_column($manifest['icons'], 'purpose');
        $this->assertContains('maskable', $purposes);
    }

    public function test_manifest_json_alias_exists()
    {
        $manifestJsonPath = public_path('manifest.json');
        $this->assertFileExists($manifestJsonPath);
    }

    public function test_service_worker_and_registration_scripts_exist()
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);
        $swContent = file_get_contents($swPath);
        $this->assertStringContainsString('CACHE_NAME', $swContent);
        $this->assertStringContainsString('OFFLINE_URL', $swContent);

        $pwaRegPath = public_path('pwa-register.js');
        $this->assertFileExists($pwaRegPath);
        $pwaRegContent = file_get_contents($pwaRegPath);
        $this->assertStringContainsString('serviceWorker', $pwaRegContent);
        $this->assertStringContainsString('beforeinstallprompt', $pwaRegContent);
    }

    public function test_offline_fallback_page_exists_with_noindex()
    {
        $offlinePath = public_path('offline.html');
        $this->assertFileExists($offlinePath);
        $offlineHtml = file_get_contents($offlinePath);
        $this->assertStringContainsString('noindex', $offlineHtml);
        $this->assertStringContainsString('Koneksi Terputus', $offlineHtml);
    }

    public function test_pwa_icons_all_exist_and_are_valid_images()
    {
        $icons = [
            'icon-72x72.png',
            'icon-96x96.png',
            'icon-128x128.png',
            'icon-144x144.png',
            'icon-152x152.png',
            'icon-192x192.png',
            'icon-384x384.png',
            'icon-512x512.png',
            'icon-maskable-512x512.png',
            'apple-touch-icon.png',
        ];

        foreach ($icons as $icon) {
            $path = public_path('icons/' . $icon);
            $this->assertFileExists($path, "Icon {$icon} missing.");
            $this->assertGreaterThan(500, filesize($path));
        }

        $this->assertFileExists(public_path('favicon.png'));
    }

    public function test_login_page_renders_pwa_meta_tags()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('manifest.webmanifest');
        $response->assertSee('name="theme-color"', false);
        $response->assertSee('apple-touch-icon');
        $response->assertSee('pwa-register.js');
    }
}
