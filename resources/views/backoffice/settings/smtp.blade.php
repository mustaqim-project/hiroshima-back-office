@extends('layouts.app', ['title' => 'Pengaturan SMTP Mail'])

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em;">Pengaturan Server SMTP Email</h2>
        <p style="color: var(--text-muted); font-size: 14px;">
            Konfigurasi koneksi email SMTP langsung via database (bukan file .env) untuk layanan <strong>Reset Kata Sandi</strong> dan <strong>Reset PIN Kasir</strong>.
        </p>
    </div>
    <div>
        @if($isConfigured)
            <span class="badge badge-success" style="font-size: 13px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;"><x-icon name="check-circle" size="14" /> <span>SMTP Terkonfigurasi</span></span>
        @else
            <span class="badge badge-warning" style="font-size: 13px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;"><x-icon name="alert-triangle" size="14" /> <span>SMTP Belum Lengkap</span></span>
        @endif
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
    <!-- Form Pengaturan SMTP -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Parameter Koneksi SMTP</div>
            <div style="display: flex; gap: 6px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="applyPreset('gmail')">Preset Gmail</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="applyPreset('outlook')">Preset Outlook</button>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.smtp.update') }}">
            @csrf

            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">SMTP Host</label>
                    <input type="text" id="mail_host" name="mail_host" value="{{ old('mail_host', $settings['mail_host']) }}" required placeholder="Contoh: smtp.gmail.com" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Port</label>
                    <input type="number" id="mail_port" name="mail_port" value="{{ old('mail_port', $settings['mail_port']) }}" required placeholder="587" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Enkripsi</label>
                    <select id="mail_encryption" name="mail_encryption" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                        <option value="tls" {{ old('mail_encryption', $settings['mail_encryption']) === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ old('mail_encryption', $settings['mail_encryption']) === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="none" {{ old('mail_encryption', $settings['mail_encryption']) === 'none' ? 'selected' : '' }}>None</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Username / Akun Email</label>
                    <input type="text" id="mail_username" name="mail_username" value="{{ old('mail_username', $settings['mail_username']) }}" required placeholder="hiroshimajapanese@gmail.com" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        Password / App Password
                        @if($settings['has_password'])
                            <span style="color: #16a34a; font-size: 11px;">(Sudah tersimpan aman)</span>
                        @endif
                    </label>
                    <div style="position: relative;">
                        <input type="password" id="mail_password" name="mail_password" value="{{ old('mail_password', $settings['mail_password']) }}" placeholder="Masukkan password SMTP" style="width: 100%; padding: 10px 40px 10px 10px; border: 1px solid var(--border); border-radius: 8px;">
                        <button type="button" onclick="togglePasswordVisibility()" style="position: absolute; right: 8px; top: 10px; background: transparent; border: none; cursor: pointer; color: var(--text-muted); display: inline-flex; align-items: center;"><x-icon name="eye" size="16" /></button>
                    </div>
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Untuk Gmail, gunakan <em>16-digit Google App Password</em> bukan sandi email pribadi.</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Alamat Email Pengirim (From)</label>
                    <input type="email" id="mail_from_address" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address']) }}" required placeholder="hiroshimajapanese@gmail.com" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nama Pengirim (From Name)</label>
                    <input type="text" id="mail_from_name" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name']) }}" required placeholder="Hiroshima Japanese Street Food" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Pengaturan SMTP</button>
        </form>
    </div>

    <!-- Panel Pengujian Koneksi Email -->
    <div>
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <div class="card-title">Uji Coba Pengiriman Email</div>
            </div>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                Kirim email uji coba untuk memvalidasi kredensial SMTP, port, serta handshake TLS/SSL secara langsung.
            </p>

            <form method="POST" action="{{ route('settings.smtp.test') }}">
                @csrf
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Email Penerima Uji Coba</label>
                    <input type="email" name="test_email" value="{{ old('test_email', $settings['mail_username']) }}" required placeholder="Masukkan alamat email penerima" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <button type="submit" class="btn btn-outline" style="width: 100%; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                    <x-icon name="mail" size="16" /> <span>Kirim Email Uji Coba</span>
                </button>
            </form>
        </div>

        <div class="card" style="background: #f8fafc;">
            <div style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">Panduan Setup Gmail SMTP:</div>
            <ol style="margin-left: 20px; font-size: 12px; color: #475569; line-height: 1.6;">
                <li>Login ke akun Google <code>hiroshimajapanese@gmail.com</code>.</li>
                <li>Aktifkan <strong>2-Step Verification (Verifikasi 2 Langkah)</strong> di Google Account Security.</li>
                <li>Buat <strong>App Passwords (Sandi Aplikasi)</strong> untuk aplikasi "Mail".</li>
                <li>Salin 16-karakter sandi aplikasi ke kolom Password di samping.</li>
                <li>Host: <code>smtp.gmail.com</code>, Port: <code>587</code>, Enkripsi: <code>TLS</code>.</li>
            </ol>
        </div>
    </div>
</div>

<script>
function applyPreset(type) {
    if (type === 'gmail') {
        document.getElementById('mail_host').value = 'smtp.gmail.com';
        document.getElementById('mail_port').value = '587';
        document.getElementById('mail_encryption').value = 'tls';
        document.getElementById('mail_username').value = 'hiroshimajapanese@gmail.com';
        document.getElementById('mail_from_address').value = 'hiroshimajapanese@gmail.com';
        document.getElementById('mail_from_name').value = 'Hiroshima Japanese Street Food';
    } else if (type === 'outlook') {
        document.getElementById('mail_host').value = 'smtp.office365.com';
        document.getElementById('mail_port').value = '587';
        document.getElementById('mail_encryption').value = 'tls';
    }
}

function togglePasswordVisibility() {
    const input = document.getElementById('mail_password');
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}
</script>
@endsection
