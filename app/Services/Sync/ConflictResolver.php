<?php

namespace App\Services\Sync;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Shift;
use App\Services\InventoryGate;
use Carbon\Carbon;

/**
 * @req FSD-10, FR-OFF-04, FR-OFF-05
 */
class ConflictResolver
{
    /**
     * Resolve incoming offline item against server business rules.
     * Returns ['status' => 'accepted'|'accepted_flagged'|'rejected', 'flags' => [], 'reason' => '']
     */
    public function evaluateOrder(array $payload, Branch $branch): array
    {
        $flags = [];
        $status = 'accepted';
        $reason = null;

        $clientCreatedAt = Carbon::parse($payload['client_created_at']);

        // 1. Shift check (FSD-10 Conflict Table Row 5)
        if (empty($payload['shift_id'])) {
            return [
                'status' => 'rejected',
                'flags' => ['missing_active_shift'],
                'reason' => 'Transaksi ditolak karena kasir belum melakukan Open Shift.',
            ];
        }

        $shift = Shift::find($payload['shift_id']);
        if (! $shift) {
            return [
                'status' => 'rejected',
                'flags' => ['invalid_shift'],
                'reason' => 'Transaksi ditolak karena data shift tidak ditemukan di sistem.',
            ];
        }

        $shiftOpenedAt = Carbon::parse($shift->opened_at);
        $shiftDeadline = $shiftOpenedAt->copy()->addHours(23);

        if ($clientCreatedAt->greaterThan($shiftDeadline)) {
            return [
                'status' => 'rejected',
                'flags' => ['shift_expired_before_transaction'],
                'reason' => 'Transaksi ditolak karena dibuat setelah batas waktu shift 23 jam terlampaui.',
            ];
        }

        if (in_array($shift->status, ['closed', 'auto_closed'])) {
            $flags[] = 'shift_already_closed_on_server';
            $status = 'accepted_flagged';
        }

        // 2. Clock skew flag check (FSD-11)
        if (! empty($payload['clock_skew_flag'])) {
            $flags[] = 'client_clock_skew_detected';
            $status = 'accepted_flagged';
        }

        // 3. Voucher & Quota check (FSD-10 Conflict Table Row 2)
        if (! empty($payload['voucher_code'])) {
            $voucher = \App\Models\Voucher::where('code', $payload['voucher_code'])->first();
            if ($voucher && $voucher->quota && $voucher->used_count >= $voucher->quota) {
                $flags[] = 'voucher_quota_exceeded_offline';
                $status = 'accepted_flagged';
            }
        }

        return [
            'status' => $status,
            'flags' => $flags,
            'reason' => empty($flags) ? 'OK' : implode('; ', $flags),
        ];
    }
}
