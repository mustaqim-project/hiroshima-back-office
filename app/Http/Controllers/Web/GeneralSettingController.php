<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GeneralSettingController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $settings = [
            'app_name' => Setting::get('app_name', 'HIROSHIMA POS'),
            'app_tagline' => Setting::get('app_tagline', 'Pusat Kendali Operasional F&B Multi-Cabang'),
            'company_name' => Setting::get('company_name', 'Hiroshima Japanese Street Food'),
            'app_logo' => Setting::get('app_logo'),
            'app_logo_url' => Setting::getLogoUrl(),
            'app_favicon' => Setting::get('app_favicon'),
            'app_favicon_url' => Setting::getFaviconUrl(),
            'footer_text' => Setting::get('footer_text', '© ' . date('Y') . ' Hiroshima Japanese Street Food. All rights reserved.'),
            'support_email' => Setting::get('support_email', 'hiroshimajapanese@gmail.com'),
            'support_phone' => Setting::get('support_phone', ''),
        ];

        return view('backoffice.settings.general', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:100',
            'app_tagline' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:150',
            'footer_text' => 'nullable|string|max:255',
            'support_email' => 'nullable|email|max:100',
            'support_phone' => 'nullable|string|max:50',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'app_favicon' => 'nullable|file|mimes:ico,png,svg|max:1024',
            'remove_logo' => 'nullable|boolean',
            'remove_favicon' => 'nullable|boolean',
        ]);

        $oldValues = [
            'app_name' => Setting::get('app_name'),
            'app_tagline' => Setting::get('app_tagline'),
            'company_name' => Setting::get('company_name'),
            'app_logo' => Setting::get('app_logo'),
            'app_favicon' => Setting::get('app_favicon'),
        ];

        // Update text settings
        Setting::set('app_name', $validated['app_name'], 'general');
        Setting::set('app_tagline', $validated['app_tagline'] ?? '', 'general');
        Setting::set('company_name', $validated['company_name'] ?? '', 'general');
        Setting::set('footer_text', $validated['footer_text'] ?? '', 'general');
        Setting::set('support_email', $validated['support_email'] ?? '', 'general');
        Setting::set('support_phone', $validated['support_phone'] ?? '', 'general');

        // Handle Logo Removal
        if ($request->boolean('remove_logo')) {
            $existingLogo = Setting::get('app_logo');
            if ($existingLogo && Storage::disk('public')->exists($existingLogo)) {
                Storage::disk('public')->delete($existingLogo);
            }
            Setting::set('app_logo', null, 'general');
        }

        // Handle Logo Upload
        if ($request->hasFile('app_logo')) {
            $file = $request->file('app_logo');
            $existingLogo = Setting::get('app_logo');
            if ($existingLogo && Storage::disk('public')->exists($existingLogo)) {
                Storage::disk('public')->delete($existingLogo);
            }
            $path = $file->store('branding', 'public');
            Setting::set('app_logo', $path, 'general');
        }

        // Handle Favicon Removal
        if ($request->boolean('remove_favicon')) {
            $existingFavicon = Setting::get('app_favicon');
            if ($existingFavicon && Storage::disk('public')->exists($existingFavicon)) {
                Storage::disk('public')->delete($existingFavicon);
            }
            Setting::set('app_favicon', null, 'general');
        }

        // Handle Favicon Upload
        if ($request->hasFile('app_favicon')) {
            $file = $request->file('app_favicon');
            $existingFavicon = Setting::get('app_favicon');
            if ($existingFavicon && Storage::disk('public')->exists($existingFavicon)) {
                Storage::disk('public')->delete($existingFavicon);
            }
            $path = $file->store('branding', 'public');
            Setting::set('app_favicon', $path, 'general');
        }

        $newValues = [
            'app_name' => Setting::get('app_name'),
            'app_tagline' => Setting::get('app_tagline'),
            'company_name' => Setting::get('company_name'),
            'app_logo' => Setting::get('app_logo'),
            'app_favicon' => Setting::get('app_favicon'),
        ];

        $this->auditLogger->record(
            'settings.general.updated',
            null,
            $oldValues,
            $newValues
        );

        return redirect()->route('settings.general')->with('success', 'Pengaturan identitas sistem (nama, logo, favicon, dan footer) berhasil disimpan.');
    }
}
