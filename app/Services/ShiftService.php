<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Shift;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * @req FR-POS-01, FR-POS-01a, FR-POS-01b, FR-POS-13, FSD-09, RPT-09
 */
class ShiftService
{
    /**
     * Open a new shift for a cashier and device.
     */
    public function open(int $branchId, int $userId, ?int $deviceId, int $startingCash): Shift
    {
        // Enforce 1 active shift per user and per device
        $existingUserShift = Shift::where('user_id', $userId)->where('status', 'open')->first();
        if ($existingUserShift) {
            throw new Exception("Kasir masih memiliki shift aktif yang belum ditutup (Shift #{$existingUserShift->id}).");
        }

        if ($deviceId) {
            $existingDeviceShift = Shift::where('device_id', $deviceId)->where('status', 'open')->first();
            if ($existingDeviceShift) {
                throw new Exception("Perangkat tablet ini masih memiliki shift terbuka (Shift #{$existingDeviceShift->id}).");
            }
        }

        return Shift::create([
            'branch_id' => $branchId,
            'user_id' => $userId,
            'device_id' => $deviceId,
            'opened_at' => now('Asia/Jakarta'),
            'starting_cash' => $startingCash,
            'status' => 'open',
        ]);
    }

    /**
     * Calculate expected cash for a shift:
     * modal_awal + tunai - refund_tunai + kas_masuk - kas_keluar
     */
    public function calculateExpectedCash(Shift $shift): int
    {
        // 1. Total cash payments received
        $cashPayments = Payment::where('shift_id', $shift->id)
            ->whereHas('paymentMethod', fn ($q) => $q->where('is_cash', true))
            ->sum('amount');

        // 2. Cash refunds
        $cashRefunds = Refund::where('shift_id', $shift->id)
            ->whereHas('payment.paymentMethod', fn ($q) => $q->where('is_cash', true))
            ->sum('amount');

        // 3. Cash in & Cash out
        $cashIn = CashMovement::where('shift_id', $shift->id)->where('type', 'cash_in')->sum('amount');
        $cashOut = CashMovement::where('shift_id', $shift->id)->where('type', 'cash_out')->sum('amount');

        return (int) ($shift->starting_cash + $cashPayments - $cashRefunds + $cashIn - $cashOut);
    }

    /**
     * Close a shift manually by cashier with physical cash count.
     */
    public function close(Shift $shift, int $actualCash): Shift
    {
        $expectedCash = $this->calculateExpectedCash($shift);
        $difference = $actualCash - $expectedCash;

        $shift->update([
            'closed_at' => now('Asia/Jakarta'),
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'cash_difference' => $difference,
            'status' => 'closed',
        ]);

        return $shift;
    }

    /**
     * Assert that shift is open and within 23-hour lifespan; auto-close if expired.
     */
    public function assertOpenAndFresh(Shift $shift, ?CarbonInterface $now = null): void
    {
        $now = $now ?? now('Asia/Jakarta');
        $openedAt = Carbon::parse($shift->opened_at);

        if ($shift->status !== 'open') {
            throw new Exception("Shift #{$shift->id} tidak berstatus aktif ({$shift->status}).");
        }

        if ($openedAt->diffInHours($now) >= 23) {
            // Auto close expired shift
            $shift->update([
                'closed_at' => $openedAt->copy()->addHours(23),
                'expected_cash' => $this->calculateExpectedCash($shift),
                'actual_cash' => null,
                'cash_difference' => null,
                'status' => 'auto_closed',
            ]);

            throw new Exception("Shift telah ditutup otomatis karena melebihi batas waktu 23 jam. Silakan buka shift baru.");
        }
    }

    /**
     * Scan and auto-close all shifts older than 23 hours (called by scheduler every 5 minutes).
     */
    public function autoCloseExpiredShifts(?CarbonInterface $now = null): int
    {
        $now = $now ?? now('Asia/Jakarta');
        $cutoff = $now->copy()->subHours(23);

        $expiredShifts = Shift::where('status', 'open')
            ->where('opened_at', '<=', $cutoff)
            ->get();

        $count = 0;
        foreach ($expiredShifts as $shift) {
            $openedAt = Carbon::parse($shift->opened_at);
            $shift->update([
                'closed_at' => $openedAt->copy()->addHours(23),
                'expected_cash' => $this->calculateExpectedCash($shift),
                'actual_cash' => null,
                'cash_difference' => null,
                'status' => 'auto_closed',
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Reconcile shift differences by Branch Manager or Owner.
     */
    public function reconcile(Shift $shift, int $reconciledByUserId, string $notes): Shift
    {
        $shift->update([
            'status' => 'reconciled',
            'reconciled_by' => $reconciledByUserId,
            'reconciled_at' => now('Asia/Jakarta'),
            'reconciliation_notes' => $notes,
        ]);

        return $shift;
    }
}
