<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Kata Sandi</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 24px; color: #1e293b;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <!-- Header -->
        <div style="background: #0f172a; padding: 28px 24px; text-align: center;">
            <div style="display: inline-block; background: #dc2626; color: #ffffff; width: 44px; height: 44px; line-height: 44px; font-weight: 800; font-size: 22px; border-radius: 10px; margin-bottom: 8px;">H</div>
            <h1 style="color: #ffffff; font-size: 20px; font-weight: 800; margin: 0; letter-spacing: -0.5px;">HIROSHIMA POS</h1>
            <p style="color: #94a3b8; font-size: 13px; margin: 4px 0 0;">Japanese Street Food - Operasional Multi-Cabang</p>
        </div>

        <!-- Body -->
        <div style="padding: 32px 28px;">
            <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 12px;">Halo, {{ $user->name }}!</h2>
            <p style="font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 20px;">
                Kami menerima permintaan untuk mereset kata sandi akun Back-office Hiroshima POS Anda (Username: <strong>{{ $user->username }}</strong>).
            </p>

            <div style="text-align: center; margin: 28px 0;">
                <a href="{{ $resetUrl }}" style="background: #dc2626; color: #ffffff; padding: 14px 28px; text-decoration: none; font-size: 15px; font-weight: 700; border-radius: 8px; display: inline-block; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.3);">
                    Atur Ulang Kata Sandi Saya
                </a>
            </div>

            <p style="font-size: 13px; line-height: 1.6; color: #64748b; margin-bottom: 12px;">
                Tautan di atas hanya berlaku selama <strong>60 menit</strong> sejak email ini dikirimkan.
            </p>
            <p style="font-size: 13px; line-height: 1.6; color: #64748b;">
                Jika Anda tidak merasa melakukan permintaan ini, silakan abaikan email ini. Kata sandi Anda akan tetap aman dan tidak berubah.
            </p>

            <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 24px 0;">
            <p style="font-size: 11px; color: #94a3b8; line-height: 1.5; word-break: break-all;">
                Jika tombol di atas tidak dapat diklik, salin dan tempel URL berikut ke browser Anda:<br>
                <a href="{{ $resetUrl }}" style="color: #dc2626;">{{ $resetUrl }}</a>
            </p>
        </div>

        <!-- Footer -->
        <div style="background: #f8fafc; padding: 16px 24px; text-align: center; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8;">
            © {{ date('Y') }} Hiroshima Japanese Street Food. Hak Cipta Dilindungi.
        </div>
    </div>
</body>
</html>
