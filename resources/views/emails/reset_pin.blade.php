<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Reset PIN Login Kasir</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 24px; color: #1e293b;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <!-- Header -->
        <div style="background: #0f172a; padding: 28px 24px; text-align: center;">
            <div style="display: inline-block; background: #dc2626; color: #ffffff; width: 44px; height: 44px; line-height: 44px; font-weight: 800; font-size: 22px; border-radius: 10px; margin-bottom: 8px;">H</div>
            <h1 style="color: #ffffff; font-size: 20px; font-weight: 800; margin: 0; letter-spacing: -0.5px;">HIROSHIMA POS</h1>
            <p style="color: #94a3b8; font-size: 13px; margin: 4px 0 0;">Otorisasi PIN Kasir & Supervisor</p>
        </div>

        <!-- Body -->
        <div style="padding: 32px 28px;">
            <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 12px;">Halo, {{ $user->name }}!</h2>
            <p style="font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 20px;">
                Permintaan reset 6-digit PIN login kasir POS untuk akun <strong>{{ $user->username }}</strong> ({{ strtoupper($user->role) }}) telah diterima.
            </p>

            <div style="text-align: center; margin: 28px 0; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 8px; font-weight: 700;">KODE OTP VERIFIKASI PIN</div>
                <div style="font-size: 36px; font-weight: 900; letter-spacing: 8px; color: #dc2626; font-family: monospace;">
                    {{ $otpCode }}
                </div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 8px;">Berlaku selama 15 menit ke depan</div>
            </div>

            <p style="font-size: 13px; line-height: 1.6; color: #475569; margin-bottom: 12px;">
                Masukkan 6 angka di atas pada formulir reset PIN untuk menentukan 6-digit PIN baru Anda.
            </p>
            <p style="font-size: 13px; line-height: 1.6; color: #dc2626; background: #fef2f2; padding: 10px 14px; border-radius: 6px; border: 1px solid #fee2e2; display: flex; align-items: center; gap: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                <span><strong>Penting:</strong> Jangan berikan kode OTP ini kepada siapapun demi keamanan transaksi kasir dan otorisasi supervisor cabang.</span>
            </p>
        </div>

        <!-- Footer -->
        <div style="background: #f8fafc; padding: 16px 24px; text-align: center; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8;">
            © {{ date('Y') }} Hiroshima Japanese Street Food. Hak Cipta Dilindungi.
        </div>
    </div>
</body>
</html>
