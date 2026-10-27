<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\MailConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmtpSettingController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $settings = MailConfigService::getDisplaySettings();
        $isConfigured = MailConfigService::isConfigured();

        return view('backoffice.settings.smtp', compact('settings', 'isConfigured'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_host' => 'required|string|max:191',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_encryption' => 'required|in:tls,ssl,none',
            'mail_username' => 'required|string|max:191',
            'mail_password' => 'nullable|string|max:191',
            'mail_from_address' => 'required|email|max:191',
            'mail_from_name' => 'required|string|max:191',
        ]);

        MailConfigService::saveSettings($validated);

        $this->auditLogger->record('settings.smtp.updated', null, [], [
            'host' => $validated['mail_host'],
            'port' => $validated['mail_port'],
            'encryption' => $validated['mail_encryption'],
            'username' => $validated['mail_username'],
            'from_address' => $validated['mail_from_address'],
        ]);

        return redirect()->route('settings.smtp')->with('success', 'Konfigurasi SMTP berhasil disimpan ke database dan aktif seketika.');
    }

    public function testMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => 'required|email',
        ]);

        $result = MailConfigService::testConnection($validated['test_email']);

        if ($result['success']) {
            return redirect()->route('settings.smtp')->with('success', $result['message']);
        }

        return redirect()->route('settings.smtp')->with('error', $result['message']);
    }
}
