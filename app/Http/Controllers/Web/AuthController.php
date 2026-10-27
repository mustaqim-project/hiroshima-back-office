<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * @req FR-AUTH-01, FR-AUTH-02, FR-AUTH-03, FSD-01
 */
class AuthController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('backoffice.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username_or_email' => 'required|string',
            'password' => 'required|string',
        ]);

        $identifier = $credentials['username_or_email'];
        $fieldType = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($fieldType, $identifier)->first();

        if (! $user || ! $user->is_active) {
            return back()->withInput()->withErrors([
                'login_error' => 'Pengguna tidak ditemukan atau berstatus nonaktif.',
            ]);
        }

        if (Auth::attempt([$fieldType => $identifier, 'password' => $credentials['password'], 'is_active' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $this->auditLogger->record('web.auth.login', $user, [], ['ip' => $request->ip()]);

            return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang kembali, ' . $user->name);
        }

        return back()->withInput()->withErrors([
            'login_error' => 'Kata sandi atau username yang Anda masukkan salah.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            $this->auditLogger->record('web.auth.logout', $user, [], ['ip' => $request->ip()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
