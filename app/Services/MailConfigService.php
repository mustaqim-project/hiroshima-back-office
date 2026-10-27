<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class MailConfigService
{
    /**
     * Terapkan konfigurasi SMTP dari database ke Laravel runtime Mail config.
     */
    public static function apply(): bool
    {
        try {
            $smtp = Setting::getGroup('smtp');

            if (empty($smtp['mail_host'])) {
                return false;
            }

            $encryption = strtolower($smtp['mail_encryption'] ?? 'tls');
            if ($encryption === 'none' || empty($encryption)) {
                $encryption = null;
            }

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $smtp['mail_host'],
                'mail.mailers.smtp.port' => (int) ($smtp['mail_port'] ?? 587),
                'mail.mailers.smtp.encryption' => $encryption,
                'mail.mailers.smtp.username' => $smtp['mail_username'] ?? null,
                'mail.mailers.smtp.password' => $smtp['mail_password'] ?? null,
                'mail.mailers.smtp.timeout' => 15,
                'mail.from.address' => $smtp['mail_from_address'] ?? ($smtp['mail_username'] ?? 'hiroshimajapanese@gmail.com'),
                'mail.from.name' => $smtp['mail_from_name'] ?? 'Hiroshima Japanese Street Food',
            ]);

            // Purge cached transport so Symfony Mailer uses updated config
            Mail::purge('smtp');

            return true;
        } catch (\Throwable $e) {
            Log::warning('Gagal menerapkan konfigurasi SMTP dari database: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Periksa apakah SMTP telah terkonfigurasi di database.
     */
    public static function isConfigured(): bool
    {
        $smtp = Setting::getGroup('smtp');
        return !empty($smtp['mail_host']) && !empty($smtp['mail_username']);
    }

    /**
     * Dapatkan daftar konfigurasi SMTP tersimpan (dengan password ter-mask untuk keamanan UI).
     */
    public static function getDisplaySettings(): array
    {
        $smtp = Setting::getGroup('smtp');

        return [
            'mail_host' => $smtp['mail_host'] ?? '',
            'mail_port' => $smtp['mail_port'] ?? '587',
            'mail_encryption' => $smtp['mail_encryption'] ?? 'tls',
            'mail_username' => $smtp['mail_username'] ?? 'hiroshimajapanese@gmail.com',
            'mail_password' => !empty($smtp['mail_password']) ? '••••••••••••' : '',
            'has_password' => !empty($smtp['mail_password']),
            'mail_from_address' => $smtp['mail_from_address'] ?? ($smtp['mail_username'] ?? 'hiroshimajapanese@gmail.com'),
            'mail_from_name' => $smtp['mail_from_name'] ?? 'Hiroshima Japanese Street Food',
        ];
    }

    /**
     * Simpan konfigurasi SMTP ke database.
     */
    public static function saveSettings(array $data): void
    {
        Setting::set('mail_host', $data['mail_host'], 'smtp');
        Setting::set('mail_port', (string) $data['mail_port'], 'smtp');
        Setting::set('mail_encryption', $data['mail_encryption'], 'smtp');
        Setting::set('mail_username', $data['mail_username'], 'smtp');

        // Hanya perbarui password jika diisi dan bukan masker
        if (!empty($data['mail_password']) && !str_contains($data['mail_password'], '••••')) {
            Setting::set('mail_password', $data['mail_password'], 'smtp', true);
        }

        Setting::set('mail_from_address', $data['mail_from_address'], 'smtp');
        Setting::set('mail_from_name', $data['mail_from_name'], 'smtp');

        // Terapkan langsung
        static::apply();
    }

    /**
     * Kirim email uji coba untuk memverifikasi koneksi SMTP.
     */
    public static function testConnection(string $toEmail): array
    {
        if (!static::apply()) {
            return [
                'success' => false,
                'message' => 'Konfigurasi SMTP belum lengkap diatur. Mohon isi Host, Port, Username, dan Password.',
            ];
        }

        try {
            $fromName = config('mail.from.name');
            $fromAddress = config('mail.from.address');
            $host = config('mail.mailers.smtp.host');
            $port = config('mail.mailers.smtp.port');

            Mail::html("
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <div style='display: inline-block; background: #dc2626; color: #ffffff; width: 44px; height: 44px; line-height: 44px; font-weight: bold; font-size: 20px; border-radius: 8px;'>H</div>
                        <h2 style='color: #0f172a; margin-top: 10px; margin-bottom: 4px;'>Uji Coba Pengiriman Email Berhasil!</h2>
                        <p style='color: #64748b; font-size: 14px;'>Hiroshima Japanese Street Food POS</p>
                    </div>
                    <p style='font-size: 14px; color: #334155;'>Halo,</p>
                    <p style='font-size: 14px; color: #334155; line-height: 1.6;'>
                        Email ini dikirim sebagai pengujian koneksi server SMTP dari menu pengaturan sistem <strong>Hiroshima POS Back-office</strong>.
                    </p>
                    <div style='background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; margin: 20px 0;'>
                        <div><strong>Server Host:</strong> {$host}:{$port}</div>
                        <div><strong>Pengirim (From):</strong> {$fromName} &lt;{$fromAddress}&gt;</div>
                        <div><strong>Tujuan (To):</strong> {$toEmail}</div>
                        <div><strong>Waktu Kirim:</strong> " . now('Asia/Jakarta')->format('d M Y H:i:s') . " WIB</div>
                    </div>
                    <p style='font-size: 13px; color: #166534; background: #dcfce7; padding: 10px; border-radius: 6px;'>
                        ✅ Server SMTP berhasil terhubung! Fitur Reset Password dan Reset PIN Login siap digunakan.
                    </p>
                </div>
            ", function ($message) use ($toEmail, $fromName, $fromAddress) {
                $message->to($toEmail)
                    ->from($fromAddress, $fromName)
                    ->subject('✅ Uji Coba Server SMTP - Hiroshima POS');
            });

            return [
                'success' => true,
                'message' => "Email uji coba berhasil dikirim ke {$toEmail}. Koneksi SMTP berfungsi normal.",
            ];
        } catch (\Throwable $e) {
            Log::error('SMTP Test Mail Failure: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'success' => false,
                'message' => 'Gagal mengirim email: ' . $e->getMessage(),
            ];
        }
    }
}
