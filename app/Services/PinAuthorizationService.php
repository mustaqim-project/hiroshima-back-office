<?php

namespace App\Services;

use App\Models\AuthorizationLog;
use App\Models\Branch;
use App\Models\Device;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * @req FSD-07, FSD-08, FSD-12
 */
class PinAuthorizationService
{
    /**
     * Verify PIN Void or PIN Diskon for a given branch and device.
     *
     * @throws Exception
     */
    public function verify(Branch $branch, string $type, string $pin, ?Device $device = null, ?string $reason = null): bool
    {
        $cacheKey = "pin_lockout:branch_{$branch->id}:device_" . ($device ? $device->id : 'web') . ":type_{$type}";
        $attemptsKey = "pin_attempts:branch_{$branch->id}:device_" . ($device ? $device->id : 'web') . ":type_{$type}";

        // Check if currently locked out
        if (Cache::has($cacheKey)) {
            $unlocksAt = Cache::get($cacheKey);
            throw new Exception("Input PIN terkunci selama 15 menit karena 5 kali salah beruntun. Silakan coba lagi setelah {$unlocksAt}.");
        }

        // Determine which PIN hash to test
        $hash = ($type === 'void') ? $branch->pin_void_hash : $branch->pin_discount_hash;

        if (empty($hash)) {
            throw new Exception("PIN cabang ({$type}) belum diatur. Silakan hubungi Kepala Cabang atau Owner.");
        }

        // Verify PIN (Argon2id or bcrypt compatibility via PHP native password_verify)
        $isValid = password_verify($pin, $hash);

        // Record authorization log
        AuthorizationLog::create([
            'branch_id' => $branch->id,
            'device_id' => $device?->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'success' => $isValid,
            'reason' => $reason ?? ($isValid ? 'Approved' : 'Invalid PIN'),
            'ip' => request()->ip() ?? '127.0.0.1',
            'created_at' => now('Asia/Jakarta'),
        ]);

        if ($isValid) {
            // Reset attempts on success
            Cache::forget($attemptsKey);
            return true;
        }

        // Increment failed attempts
        $attempts = (int) Cache::get($attemptsKey, 0) + 1;
        Cache::put($attemptsKey, $attempts, now()->addMinutes(15));

        if ($attempts >= 5) {
            $unlocksAt = now('Asia/Jakarta')->addMinutes(15)->format('H:i');
            Cache::put($cacheKey, $unlocksAt, now()->addMinutes(15));
            Cache::forget($attemptsKey);

            throw new Exception("PIN salah. Batas 5 kali percobaan terlampaui. Input PIN dikunci selama 15 menit (hingga {$unlocksAt}).");
        }

        $remaining = 5 - $attempts;
        throw new Exception("PIN otorisasi ({$type}) salah. Sisa percobaan: {$remaining} kali sebelum terkunci.");
    }

    /**
     * Set / Update branch PIN (stores Argon2id hash).
     */
    public function setBranchPin(Branch $branch, string $type, string $plainPin): void
    {
        if (! preg_match('/^\d{6}$/', $plainPin)) {
            throw new Exception("PIN harus berupa tepat 6 digit angka.");
        }

        $hash = password_hash($plainPin, PASSWORD_ARGON2ID);

        if ($type === 'void') {
            $branch->pin_void_hash = $hash;
        } else {
            $branch->pin_discount_hash = $hash;
        }

        $branch->save();
    }
}
