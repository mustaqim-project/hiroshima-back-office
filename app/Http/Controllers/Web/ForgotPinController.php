<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\ResetPinMail;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MailConfigService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ForgotPinController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function showForgotPinForm(): View
    {
        return view('backoffice.auth.forgot_pin');
    }

    public function sendPinResetOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_or_username' => 'required|string',
        ]);

        $identifier = $validated['email_or_username'];
        $fieldType = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($fieldType, $identifier)->first();

        if (! $user || empty($user->email)) {
            return back()->withInput()->withErrors([
                'email_or_username' => 'Akun pengguna atau alamat email tersebut tidak terdaftar di sistem.',
            ]);
        }

        // Terapkan konfigurasi SMTP database
        MailConfigService::apply();

        $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('pin_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($otpCode),
                'created_at' => now('Asia/Jakarta'),
            ]
        );

        try {
            Mail::to($user->email)->send(new ResetPinMail($user, $otpCode));

            $this->auditLogger->record('auth.pin_reset_otp_sent', $user, [], [
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return redirect()->route('pin.verify', ['email' => $user->email])
                ->with('success', "Kode OTP 6-angka telah dikirimkan ke email {$user->email}. Silakan masukkan kode OTP tersebut di bawah.");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'email_or_username' => 'Gagal mengirim email: ' . $e->getMessage() . '. Pastikan pengaturan SMTP di Backoffice sudah benar.',
            ]);
        }
    }

    public function showVerifyPinForm(Request $request): View
    {
        $email = $request->query('email', '');
        return view('backoffice.auth.verify_pin', compact('email'));
    }

    public function verifyAndResetPin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
            'pin' => 'required|digits:6|confirmed',
        ]);

        $record = DB::table('pin_reset_tokens')->where('email', $validated['email'])->first();

        if (! $record) {
            return back()->withInput()->withErrors([
                'otp' => 'Permintaan reset PIN tidak ditemukan atau belum pernah diminta.',
            ]);
        }

        // Periksa masa berlaku (15 menit)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('pin_reset_tokens')->where('email', $validated['email'])->delete();
            return back()->withInput()->withErrors([
                'otp' => 'Kode OTP telah kadaluarsa (lebih dari 15 menit). Silakan minta kode OTP baru.',
            ]);
        }

        // Validasi OTP hash
        if (! Hash::check($validated['otp'], $record->token)) {
            return back()->withInput()->withErrors([
                'otp' => 'Kode OTP 6-angka yang Anda masukkan salah.',
            ]);
        }

        $user = User::where('email', $validated['email'])->first();

        // Hash PIN baru dengan Argon2id (standar Hiroshima POS)
        $pinHash = password_hash($validated['pin'], PASSWORD_ARGON2ID);
        $user->update([
            'pin_hash' => $pinHash,
        ]);

        DB::table('pin_reset_tokens')->where('email', $validated['email'])->delete();

        $this->auditLogger->record('auth.pin_reset_success', $user, [], [
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('login')->with('success', 'PIN kasir 6-digit berhasil diperbarui! Anda kini dapat login ke aplikasi POS menggunakan PIN baru.');
    }
}
