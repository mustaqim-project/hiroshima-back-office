<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - {{ \App\Models\Setting::getAppName() }} Back-office</title>
    @if(\App\Models\Setting::getFaviconUrl())
        <link rel="icon" type="image/x-icon" href="{{ \App\Models\Setting::getFaviconUrl() }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @endif
    <!-- PWA & Mobile Web Capabilities -->
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#dc2626">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Hiroshima">
    <meta name="application-name" content="Hiroshima Back Office">
    <meta name="format-detection" content="telephone=no">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
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
        .login-card {
            background: var(--card-surface);
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 440px;
            padding: 40px;
        }
        .brand-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .brand-logo-img {
            max-height: 64px;
            max-width: 140px;
            object-fit: contain;
            margin-bottom: 12px;
            border-radius: 8px;
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
            font-size: 22px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }
        .brand-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
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
            outline: none;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-muted);
            cursor: pointer;
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 10px;
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
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-header">
            @if(\App\Models\Setting::getLogoUrl())
                <div>
                    <img src="{{ \App\Models\Setting::getLogoUrl() }}" 
                         alt="{{ \App\Models\Setting::getAppName() }}" 
                         class="brand-logo-img"
                         onerror="this.style.display='none'; document.getElementById('login-brand-fallback-badge').style.display='inline-flex';">
                    <div id="login-brand-fallback-badge" class="brand-badge" style="display: none; margin-bottom: 12px;">{{ strtoupper(substr(\App\Models\Setting::getAppName(), 0, 1)) }}</div>
                </div>
            @else
                <div class="brand-badge" style="margin-bottom: 12px;">{{ strtoupper(substr(\App\Models\Setting::getAppName(), 0, 1)) }}</div>
            @endif
            <h1 class="brand-title">{{ \App\Models\Setting::getAppName() }}</h1>
            <p class="brand-subtitle">{{ \App\Models\Setting::getAppTagline() }}</p>
        </div>

        @if($errors->has('login_error'))
            <div class="alert-error">
                {{ $errors->first('login_error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="username_or_email">Username atau Email</label>
                <input type="text" id="username_or_email" name="username_or_email" class="form-input" value="{{ old('username_or_email') }}" required autofocus placeholder="contoh: admin atau nama@email.com">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Kata Sandi</label>
                <input type="password" id="password" name="password" class="form-input" value="" required placeholder="Masukkan kata sandi">
            </div>

            <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                <label class="form-checkbox">
                    <input type="checkbox" name="remember" value="1" checked> Ingat saya
                </label>
                <a href="{{ route('password.request') }}" style="font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 600;">Lupa Kata Sandi?</a>
            </div>

            <button type="submit" class="btn-submit">Masuk ke Back-office</button>

            <div style="text-align: center; margin-top: 16px;">
                <a href="{{ route('pin.request') }}" style="font-size: 13px; color: var(--text-muted); text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                    <x-icon name="key" size="14" /> <span>Lupa PIN Kasir POS? <span style="color: var(--primary); font-weight: 600;">Reset PIN di sini</span></span>
                </a>
            </div>

            <div style="text-align: center; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border);">
                <a href="{{ route('welcome') }}" style="font-size: 13px; color: var(--text-muted); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                    <span>← Lihat Menu & Gerai Hiroshima</span>
                </a>
            </div>
        </form>
    </div>
    <!-- PWA Service Worker Registration & Install Engine -->
    <script defer src="{{ asset('pwa-register.js') }}"></script>
</body>
</html>
