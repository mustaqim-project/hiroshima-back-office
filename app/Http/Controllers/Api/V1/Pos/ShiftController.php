<?php

namespace App\Http\Controllers\Api\V1\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\Shift;
use App\Services\AuditLogger;
use App\Services\ShiftService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @req FR-POS-01, FR-POS-01a, FR-POS-01b, FR-POS-13, FSD-09
 */
class ShiftController extends Controller
{
    public function __construct(
        protected ShiftService $shiftService,
        protected AuditLogger $auditLogger
    ) {}

    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $shift = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if (! $shift) {
            return response()->json([
                'success' => true,
                'has_active_shift' => false,
                'shift' => null,
            ]);
        }

        try {
            // Check 23h limit
            $this->shiftService->assertOpenAndFresh($shift);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'has_active_shift' => false,
                'message' => $e->getMessage(),
            ], 410);
        }

        $expectedCash = $this->shiftService->calculateExpectedCash($shift);

        return response()->json([
            'success' => true,
            'has_active_shift' => true,
            'shift' => [
                'id' => $shift->id,
                'branch_id' => $shift->branch_id,
                'opened_at' => $shift->opened_at->toIso8601String(),
                'starting_cash' => $shift->starting_cash,
                'expected_cash' => $expectedCash,
                'status' => $shift->status,
            ],
        ]);
    }

    public function open(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'starting_cash' => 'required|integer|min:0',
            'device_id' => 'nullable|exists:devices,id',
        ]);

        $user = $request->user();
        $branchId = $user->branch_id ?? 1;

        try {
            $shift = $this->shiftService->open(
                $branchId,
                $user->id,
                $validated['device_id'] ?? null,
                $validated['starting_cash']
            );

            $this->auditLogger->record('shift.opened', $shift, [], ['starting_cash' => $shift->starting_cash]);

            return response()->json([
                'success' => true,
                'message' => 'Shift berhasil dibuka.',
                'shift' => $shift,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    public function close(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actual_cash' => 'required|integer|min:0',
        ]);

        $user = $request->user();
        $shift = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if (! $shift) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ditemukan shift aktif yang dapat ditutup.',
            ], 404);
        }

        $closedShift = $this->shiftService->close($shift, $validated['actual_cash']);

        $this->auditLogger->record('shift.closed', $closedShift, [], [
            'expected_cash' => $closedShift->expected_cash,
            'actual_cash' => $closedShift->actual_cash,
            'difference' => $closedShift->cash_difference,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil ditutup.',
            'shift' => $closedShift,
        ]);
    }

    public function cashMovement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:cash_in,cash_out',
            'amount' => 'required|integer|min:100',
            'reason' => 'required|string|min:3',
        ]);

        $user = $request->user();
        $shift = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if (! $shift) {
            return response()->json([
                'success' => false,
                'message' => 'Harap buka shift kasir terlebih dahulu.',
            ], 403);
        }

        $movement = CashMovement::create([
            'shift_id' => $shift->id,
            'branch_id' => $shift->branch_id,
            'user_id' => $user->id,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
        ]);

        $this->auditLogger->record("cash.{$validated['type']}", $movement, [], [
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pergerakan kas berhasil dicatat.',
            'cash_movement' => $movement,
        ], 201);
    }
}
