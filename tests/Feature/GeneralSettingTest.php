<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GeneralSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'username' => 'admintest',
            'email' => 'admin@test.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_general_settings(): void
    {
        $response = $this->get(route('settings.general'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_general_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.general'));
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Identitas');
        $response->assertSee('Nama Sistem / Aplikasi');
        $response->assertSee('Logo Utama Sistem');
        $response->assertSee('Favicon Tab Browser');
    }

    public function test_admin_can_update_system_identity_and_branding(): void
    {
        Storage::fake('public');

        $logoFile = UploadedFile::fake()->image('custom_logo.png', 200, 200);
        $faviconFile = UploadedFile::fake()->create('favicon.ico', 10, 'image/x-icon');

        $response = $this->actingAs($this->admin)->post(route('settings.general.update'), [
            'app_name' => 'TOKYO POS EXPRESS',
            'app_tagline' => 'Solusi Kasir F&B Modern',
            'company_name' => 'PT Tokyo Kuliner Indonesia',
            'footer_text' => '© 2026 PT Tokyo Kuliner Indonesia',
            'support_email' => 'halo@tokyopos.id',
            'support_phone' => '+6281299998888',
            'app_logo' => $logoFile,
            'app_favicon' => $faviconFile,
        ]);

        $response->assertRedirect(route('settings.general'));
        $response->assertSessionHas('success');

        // Verify in database
        $this->assertEquals('TOKYO POS EXPRESS', Setting::get('app_name'));
        $this->assertEquals('Solusi Kasir F&B Modern', Setting::get('app_tagline'));
        $this->assertEquals('PT Tokyo Kuliner Indonesia', Setting::get('company_name'));
        $this->assertEquals('© 2026 PT Tokyo Kuliner Indonesia', Setting::get('footer_text'));
        $this->assertEquals('halo@tokyopos.id', Setting::get('support_email'));
        $this->assertEquals('+6281299998888', Setting::get('support_phone'));

        // Verify logo and favicon stored
        $storedLogo = Setting::get('app_logo');
        $storedFavicon = Setting::get('app_favicon');
        $this->assertNotNull($storedLogo);
        $this->assertNotNull($storedFavicon);
        Storage::disk('public')->assertExists($storedLogo);
        Storage::disk('public')->assertExists($storedFavicon);

        // Verify helper methods on Setting
        $this->assertEquals('TOKYO POS EXPRESS', Setting::getAppName());
        $this->assertEquals('Solusi Kasir F&B Modern', Setting::getAppTagline());
        $this->assertStringContainsString('storage/' . $storedLogo, Setting::getLogoUrl());
        $this->assertStringContainsString('storage/' . $storedFavicon, Setting::getFaviconUrl());
    }

    public function test_admin_can_remove_custom_logo_and_favicon(): void
    {
        Storage::fake('public');

        // Pre-set existing files
        Storage::disk('public')->put('branding/old_logo.png', 'test');
        Storage::disk('public')->put('branding/old_fav.ico', 'test');
        Setting::set('app_logo', 'branding/old_logo.png', 'general');
        Setting::set('app_favicon', 'branding/old_fav.ico', 'general');

        $response = $this->actingAs($this->admin)->post(route('settings.general.update'), [
            'app_name' => 'HIROSHIMA POS',
            'app_tagline' => 'Pusat Kendali Operasional',
            'company_name' => 'Hiroshima Japanese Street Food',
            'remove_logo' => 1,
            'remove_favicon' => 1,
        ]);

        $response->assertRedirect(route('settings.general'));

        $this->assertNull(Setting::get('app_logo'));
        $this->assertNull(Setting::get('app_favicon'));
        Storage::disk('public')->assertMissing('branding/old_logo.png');
        Storage::disk('public')->assertMissing('branding/old_fav.ico');
    }

    public function test_login_page_does_not_expose_passwords_and_reflects_system_name(): void
    {
        Setting::set('app_name', 'HIROSHIMA RAMEN POS', 'general');

        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // System name is dynamically reflected
        $response->assertSee('HIROSHIMA RAMEN POS');

        // Password information & demo box are completely absent
        $response->assertDontSee('password123');
        $response->assertDontSee('Akun Standar Sistem');
        $response->assertDontSee('demo-box');
    }
}
