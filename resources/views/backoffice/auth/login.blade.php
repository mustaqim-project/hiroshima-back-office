<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Hiroshima POS Back-office</title>
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
        .demo-box {
            margin-top: 28px;
            padding: 16px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .demo-box strong {
            color: var(--text-main);
            display: block;
            margin-bottom: 6px;
        }
        .demo-chip {
            display: inline-block;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 4px 8px;
            border-radius: 4px;
            margin-top: 4px;
            cursor: pointer;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-header">
            <div class="brand-badge">H</div>
            <h1 class="brand-title">HIROSHIMA POS</h1>
            <p class="brand-subtitle">Pusat Kendali Operasional F&B Multi-Cabang</p>
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
                <input type="text" id="username_or_email" name="username_or_email" class="form-input" value="{{ old('username_or_email', 'hiroshimajapanese@gmail.com') }}" required autofocus placeholder="contoh: hiroshimajapanese@gmail.com atau admin">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Kata Sandi</label>
                <input type="password" id="password" name="password" class="form-input" value="password123" required placeholder="Masukkan kata sandi">
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
        </form>

        <div class="demo-box">
            <strong>Akun Standar Sistem:</strong>
            <div>• Admin Utama: <code>hiroshimajapanese@gmail.com</code> (atau <code>admin</code>) / <code>password123</code></div>
            <div>• Owner: <code>owner@hiroshima.id</code> / <code>password123</code></div>
            <div>• Kepala Cabang (Cikini): <code>hrs-ckn_spv@hiroshima.id</code> / <code>password123</code></div>
            <div style="margin-top: 4px; color: #64748b; font-size: 11px;">PIN Default Kasir & Otorisasi: <code>123456</code></div>
        </div>
    </div>
</body>
</html>
