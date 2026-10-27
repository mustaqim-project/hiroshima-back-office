<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MailConfigService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function showForgotForm(): View
    {
        return view('backoffice.auth.forgot_password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
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

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now('Asia/Jakarta'),
            ]
        );

        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        try {
            Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));

            $this->auditLogger->record('auth.password_reset_link_sent', $user, [], [
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return back()->with('success', "Tautan reset kata sandi telah berhasil dikirim ke alamat email: {$user->email}. Silakan periksa kotak masuk atau folder spam Anda.");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'email_or_username' => 'Gagal mengirim email: ' . $e->getMessage() . '. Pastikan pengaturan SMTP di Backoffice sudah benar.',
            ]);
        }
    }

    public function showResetForm(Request $request, string $token): View
    {
        $email = $request->query('email', '');
        return view('backoffice.auth.reset_password', compact('token', 'email'));
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();

        if (! $record) {
            return back()->withInput()->withErrors([
                'email' => 'Permintaan reset kata sandi tidak ditemukan atau sudah kadaluarsa.',
            ]);
        }

        // Periksa masa berlaku (60 menit)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
            return back()->withInput()->withErrors([
                'email' => 'Tautan reset kata sandi telah kadaluarsa (lebih dari 60 menit). Silakan minta tautan baru.',
            ]);
        }

        // Validasi token hash
        if (! Hash::check($validated['token'], $record->token)) {
            return back()->withInput()->withErrors([
                'email' => 'Token reset kata sandi tidak valid atau telah digunakan.',
            ]);
        }

        $user = User::where('email', $validated['email'])->first();
        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        $this->auditLogger->record('auth.password_reset_success', $user, [], [
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('login')->with('success', 'Kata sandi berhasil diperbarui! Silakan masuk menggunakan kata sandi baru Anda.');
    }
}
