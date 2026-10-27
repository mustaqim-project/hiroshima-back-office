<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\ResetPinMail;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MailConfigService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PinResetApiController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_or_username' => 'required|string',
        ]);

        $identifier = $validated['email_or_username'];
        $fieldType = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($fieldType, $identifier)->first();

        if (! $user || empty($user->email)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengguna atau email tidak ditemukan di sistem.',
            ], 404);
        }

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

            $this->auditLogger->record('api.pin_reset_otp_sent', $user, [], [
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'success',
                'email' => $user->email,
                'message' => "Kode OTP 6-angka telah dikirim ke email {$user->email}.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim email OTP: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function verifyAndReset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
            'pin' => 'required|digits:6',
        ]);

        $record = DB::table('pin_reset_tokens')->where('email', $validated['email'])->first();

        if (! $record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permintaan OTP tidak ditemukan atau belum pernah diminta.',
            ], 400);
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('pin_reset_tokens')->where('email', $validated['email'])->delete();
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP telah kadaluarsa (lebih dari 15 menit). Silakan minta kode baru.',
            ], 400);
        }

        if (! Hash::check($validated['otp'], $record->token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP yang dimasukkan tidak valid.',
            ], 400);
        }

        $user = User::where('email', $validated['email'])->first();
        $pinHash = password_hash($validated['pin'], PASSWORD_ARGON2ID);
        $user->update(['pin_hash' => $pinHash]);

        DB::table('pin_reset_tokens')->where('email', $validated['email'])->delete();

        $this->auditLogger->record('api.pin_reset_success', $user, [], [
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => '6-Digit PIN Kasir berhasil diperbarui.',
        ]);
    }
}
