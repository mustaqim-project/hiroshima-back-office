<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Sandi - {{ \App\Models\Setting::getAppName() }}</title>
    @if(\App\Models\Setting::getFaviconUrl())
        <link rel="icon" type="image/x-icon" href="{{ \App\Models\Setting::getFaviconUrl() }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif
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
            max-width: 440px;
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
            @if(\App\Models\Setting::getLogoUrl())
                <div>
                    <img src="{{ \App\Models\Setting::getLogoUrl() }}" alt="{{ \App\Models\Setting::getAppName() }}" style="max-height: 56px; max-width: 140px; object-fit: contain; margin-bottom: 12px; border-radius: 8px;">
                </div>
            @else
                <div class="brand-badge">{{ strtoupper(substr(\App\Models\Setting::getAppName(), 0, 1)) }}</div>
            @endif
            <h1 class="brand-title">Lupa Kata Sandi</h1>
            <p class="brand-subtitle">Masukkan username atau alamat email terdaftar Anda. Kami akan mengirimkan tautan reset kata sandi melalui email SMTP.</p>
        </div>

        @if($errors->any())
            <div class="alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="email_or_username">Username atau Email Terdaftar</label>
                <input type="text" id="email_or_username" name="email_or_username" class="form-input" value="{{ old('email_or_username') }}" required autofocus placeholder="Contoh: hiroshimajapanese@gmail.com">
            </div>

            <button type="submit" class="btn-submit">Kirim Tautan Reset ke Email</button>
        </form>

        <a href="{{ route('login') }}" class="back-link">← Kembali ke Halaman Masuk</a>
    </div>
</body>
</html>
