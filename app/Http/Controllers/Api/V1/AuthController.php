<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Device;
use App\Models\DeviceActivationCode;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @req FR-AUTH-01, FR-AUTH-02, FR-AUTH-05, FR-AUTH-06, FR-DEV-01, FR-DEV-02, FSD-01
 */
class AuthController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username_or_email' => 'required|string',
            'password_or_pin' => 'required|string',
            'device_code' => 'nullable|string',
        ]);

        $identifier = $request->input('username_or_email');
        $credential = $request->input('password_or_pin');

        /** @var User|null $user */
        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (! $user || ! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan atau berstatus nonaktif.',
            ], 401);
        }

        // Check 15-minute lockout
        if ($user->isLocked()) {
            $unlocksAt = $user->locked_until->format('H:i');
            return response()->json([
                'success' => false,
                'message' => "Akun Anda terkunci hingga {$unlocksAt} karena 5 kali kegagalan login beruntun.",
            ], 423);
        }

        // Verify password or PIN safely across Bcrypt and Argon2id
        $passwordValid = password_verify($credential, $user->password);
        $pinValid = $user->pin_hash ? password_verify($credential, $user->pin_hash) : false;

        if (! $passwordValid && ! $pinValid) {
            $user->increment('failed_login_attempts');

            if ($user->failed_login_attempts >= 5) {
                $user->update([
                    'locked_until' => now('Asia/Jakarta')->addMinutes(15),
                    'failed_login_attempts' => 0,
                ]);

                $this->auditLogger->record('auth.lockout', $user, [], ['reason' => '5 consecutive failed attempts']);

                return response()->json([
                    'success' => false,
                    'message' => '5 kali kesalahan login beruntun. Akun dikunci selama 15 menit.',
                ], 423);
            }

            $remaining = 5 - $user->failed_login_attempts;
            return response()->json([
                'success' => false,
                'message' => "Kredensial salah. Sisa kesempatan: {$remaining} kali.",
            ], 401);
        }

        // Reset failed login attempts on success
        $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);

        // Create Sanctum Token
        $token = $user->createToken('pos-token', ['pos:access'])->plainTextToken;

        $this->auditLogger->record('auth.login', $user, [], ['method' => $passwordValid ? 'password' : 'pin']);

        // Determine multi-branch access (FR-AUTH-04)
        $canSwitchBranch = is_null($user->branch_id)
            || in_array($user->role, ['owner', 'central_admin'])
            || $user->can('branch.manage');

        $accessibleBranches = $canSwitchBranch
            ? Branch::where('is_active', true)->select('id', 'name', 'code', 'phone', 'address', 'tax_percent', 'service_charge_percent', 'tax_included', 'rounding_mode')->get()
            : ($user->branch ? Branch::where('id', $user->branch_id)->select('id', 'name', 'code', 'phone', 'address', 'tax_percent', 'service_charge_percent', 'tax_included', 'rounding_mode')->get() : collect([]));

        $assignedBranch = $user->branch ? [
            'id' => $user->branch->id,
            'name' => $user->branch->name,
            'code' => $user->branch->code,
            'phone' => $user->branch->phone,
            'address' => $user->branch->address,
            'tax_percent' => $user->branch->tax_percent,
            'service_charge_percent' => $user->branch->service_charge_percent,
            'tax_included' => $user->branch->tax_included,
            'rounding_mode' => $user->branch->rounding_mode,
        ] : ($accessibleBranches->first() ? [
            'id' => $accessibleBranches->first()->id,
            'name' => $accessibleBranches->first()->name,
            'code' => $accessibleBranches->first()->code,
            'phone' => $accessibleBranches->first()->phone,
            'address' => $accessibleBranches->first()->address,
            'tax_percent' => $accessibleBranches->first()->tax_percent,
            'service_charge_percent' => $accessibleBranches->first()->service_charge_percent,
            'tax_included' => $accessibleBranches->first()->tax_included,
            'rounding_mode' => $accessibleBranches->first()->rounding_mode,
        ] : null);

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'branch_id' => $assignedBranch ? $assignedBranch['id'] : $user->branch_id,
                'branch' => $assignedBranch,
                'can_switch_branch' => $canSwitchBranch,
                'accessible_branches' => $accessibleBranches,
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    public function activateDevice(Request $request): JsonResponse
    {
        $request->validate([
            'activation_code' => 'required|string',
            'device_name' => 'required|string',
        ]);

        $code = DeviceActivationCode::where('code', $request->activation_code)
            ->whereNull('used_at')
            ->where('expires_at', '>', now('Asia/Jakarta'))
            ->first();

        if (! $code) {
            return response()->json([
                'success' => false,
                'message' => 'Kode aktivasi tidak valid atau telah kedaluwarsa (berlaku 24 jam).',
            ], 404);
        }

        $deviceCode = 'DEV-' . strtoupper(substr(uniqid(), -6));

        $device = Device::create([
            'branch_id' => $code->branch_id,
            'device_code' => $deviceCode,
            'device_name' => $request->device_name,
            'is_active' => true,
        ]);

        $code->update([
            'used_at' => now('Asia/Jakarta'),
            'used_by_device_id' => $device->id,
        ]);

        $deviceToken = bin2hex(random_bytes(32));
        $device->update(['device_token_hash' => hash('sha256', $deviceToken)]);

        $this->auditLogger->record('device.activated', $device, [], ['code' => $code->code]);

        return response()->json([
            'success' => true,
            'message' => 'Perangkat POS berhasil diaktivasi.',
            'device' => [
                'id' => $device->id,
                'device_code' => $device->device_code,
                'device_name' => $device->device_name,
                'branch_id' => $device->branch_id,
                'device_token' => $deviceToken,
            ],
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load('branch');
        $canSwitchBranch = is_null($user->branch_id)
            || in_array($user->role, ['owner', 'central_admin'])
            || $user->can('branch.manage');

        $accessibleBranches = $canSwitchBranch
            ? Branch::where('is_active', true)->select('id', 'name', 'code', 'phone', 'address', 'tax_percent', 'service_charge_percent', 'tax_included', 'rounding_mode')->get()
            : ($user->branch ? Branch::where('id', $user->branch_id)->select('id', 'name', 'code', 'phone', 'address', 'tax_percent', 'service_charge_percent', 'tax_included', 'rounding_mode')->get() : collect([]));

        $userData = $user->toArray();
        $userData['can_switch_branch'] = $canSwitchBranch;
        $userData['accessible_branches'] = $accessibleBranches;
        $userData['permissions'] = $user->getAllPermissions()->pluck('name');

        return response()->json($userData);
    }

    public function branches(Request $request): JsonResponse
    {
        $user = $request->user();
        $canSwitchBranch = is_null($user->branch_id)
            || in_array($user->role, ['owner', 'central_admin'])
            || $user->can('branch.manage');

        $branches = $canSwitchBranch
            ? Branch::where('is_active', true)->select('id', 'name', 'code', 'phone', 'address', 'tax_percent', 'service_charge_percent', 'tax_included', 'rounding_mode')->get()
            : ($user->branch ? Branch::where('id', $user->branch_id)->select('id', 'name', 'code', 'phone', 'address', 'tax_percent', 'service_charge_percent', 'tax_included', 'rounding_mode')->get() : collect([]));

        return response()->json([
            'success' => true,
            'can_switch_branch' => $canSwitchBranch,
            'branches' => $branches,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
    }
}
