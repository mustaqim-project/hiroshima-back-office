<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP & Reset PIN - Hiroshima POS</title>
    <style>
        :root {
            --primary: #dc2626;
            --primary-hover: #b91c1c;
            --dark-surface: #0f172a;
            --card-surface: #ffffff;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .auth-card {
            background: var(--card-surface);
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 460px;
            padding: 40px;
        }
        .brand-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .brand-badge {
            width: 48px;
            height: 48px;
            background: var(--primary);
            color: #ffffff;
            font-size: 24px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-bottom: 12px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-main);
        }
        .brand-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 6px;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 8px;
        }
        .form-input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
        }
        .otp-input {
            font-family: monospace;
            font-size: 24px;
            letter-spacing: 12px;
            text-align: center;
            font-weight: 700;
        }
        .pin-input {
            font-family: monospace;
            font-size: 20px;
            letter-spacing: 8px;
            text-align: center;
            font-weight: 700;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover {
            background: var(--primary-hover);
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: var(--text-muted);
            font-size: 13px;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="brand-header">
            <div class="brand-badge"><x-icon name="lock" size="24" /></div>
            <h1 class="brand-title">Verifikasi OTP & PIN Baru</h1>
            <p class="brand-subtitle">Masukkan kode 6-digit OTP yang dikirim ke email Anda, lalu buat 6-digit PIN login kasir yang baru.</p>
        </div>

        @if($errors->any())
            <div class="alert-error">
                <ul style="margin-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('pin.update') }}" method="POST">
            @csrf

            <div class="form-group">
                <label class="form-label" for="email">Alamat Email Penerima OTP</label>
                <input type="email" id="email" name="email" class="form-input" value="{{ old('email', $email) }}" required readonly style="background: #f1f5f9; cursor: not-allowed;">
            </div>

            <div class="form-group">
                <label class="form-label" for="otp">Kode 6-Digit OTP Email (Berlaku 15 Menit)</label>
                <input type="text" id="otp" name="otp" class="form-input otp-input" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required autofocus placeholder="000000" value="{{ old('otp') }}">
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px; text-align: center;">Cek email masuk atau folder spam Anda.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="pin">PIN Kasir Baru (6 Digit Angka)</label>
                <input type="password" id="pin" name="pin" class="form-input pin-input" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required placeholder="••••••">
            </div>

            <div class="form-group">
                <label class="form-label" for="pin_confirmation">Konfirmasi PIN Kasir Baru</label>
                <input type="password" id="pin_confirmation" name="pin_confirmation" class="form-input pin-input" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required placeholder="••••••">
            </div>

            <button type="submit" class="btn-submit">Simpan & Aktifkan PIN Baru</button>
        </form>

        <a href="{{ route('pin.request') }}" class="back-link">Kirim Ulang Kode OTP Baru</a>
        <a href="{{ route('login') }}" class="back-link" style="margin-top: 8px;">← Kembali ke Halaman Masuk</a>
    </div>
</body>
</html>
