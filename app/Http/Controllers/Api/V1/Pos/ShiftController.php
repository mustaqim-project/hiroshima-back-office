<?php

namespace App\Http\Controllers\Api\V1\Pos;

use App\Exceptions\HoldBillPendingException;
use App\Exceptions\UnfinalizedTransactionException;
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
        $requestedBranchId = $request->header('X-Branch-Id') ?? $request->query('branch_id') ?? $request->input('branch_id');

        // Auto-close any expired shifts system-wide first so we never return stale open shifts
        $this->shiftService->autoCloseExpiredShifts();

        // Check if there is an active shift for this user on the requested branch
        $shift = null;
        $isOtherBranch = false;

        if ($requestedBranchId) {
            $shift = Shift::with('branch')
                ->where('user_id', $user->id)
                ->where('branch_id', $requestedBranchId)
                ->where('status', 'open')
                ->latest('id')
                ->first();
        }

        // If not found on requested branch, look for any active shift for this user across all branches
        if (! $shift) {
            $shift = Shift::with('branch')
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->latest('id')
                ->first();
            if ($shift && $requestedBranchId && (int)$shift->branch_id !== (int)$requestedBranchId) {
                $isOtherBranch = true;
            }
        }

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
                'success' => true,
                'has_active_shift' => false,
                'shift' => null,
                'notice' => $e->getMessage(),
            ]);
        }

        $expectedCash = $this->shiftService->calculateExpectedCash($shift);

        return response()->json([
            'success' => true,
            'has_active_shift' => true,
            'shift' => [
                'id' => $shift->id,
                'branch_id' => $shift->branch_id,
                'branch_name' => $shift->branch?->name ?? 'Cabang #' . $shift->branch_id,
                'is_other_branch' => $isOtherBranch,
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
        $canSwitch = is_null($user->branch_id)
            || in_array($user->role, ['owner', 'central_admin'])
            || $user->can('branch.manage');

        $requestedBranchId = $request->header('X-Branch-Id') ?? $request->input('branch_id');

        if ($requestedBranchId && $canSwitch) {
            $branchId = (int)$requestedBranchId;
        } elseif ($user->branch_id) {
            $branchId = (int)$user->branch_id;
        } else {
            $branchId = (int)(\App\Models\Branch::where('is_active', true)->value('id') ?? 1);
        }

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
            'force_cancel_unfinalized' => 'nullable|boolean',
            'shift_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $requestedBranchId = $request->header('X-Branch-Id') ?? $request->query('branch_id') ?? $request->input('branch_id');

        // 1. If explicit shift_id provided, find it
        $shift = null;
        if (! empty($validated['shift_id'])) {
            $shift = Shift::where('id', $validated['shift_id'])
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->first();
        }

        // 2. Otherwise prioritize open shift at requested branch
        if (! $shift && $requestedBranchId) {
            $shift = Shift::where('user_id', $user->id)
                ->where('branch_id', $requestedBranchId)
                ->where('status', 'open')
                ->latest('id')
                ->first();
        }

        // 3. Fallback to any open shift of this user across branches
        if (! $shift) {
            $shift = Shift::where('user_id', $user->id)
                ->where('status', 'open')
                ->latest('id')
                ->first();
        }

        if (! $shift) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ditemukan shift aktif yang dapat ditutup. Harap buka shift terlebih dahulu.',
            ], 404);
        }

        try {
            $closedShift = $this->shiftService->close(
                $shift,
                $validated['actual_cash'],
                'manual',
                null,
                $validated['force_cancel_unfinalized'] ?? false
            );
            $report = $this->shiftService->generateCloseShiftReport($closedShift);

            $this->auditLogger->record('shift.closed', $closedShift, [], [
                'expected_cash' => $closedShift->expected_cash,
                'actual_cash' => $closedShift->actual_cash,
                'difference' => $closedShift->cash_difference,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shift berhasil ditutup.',
                'shift' => $closedShift,
                'report' => $report,
            ]);
        } catch (HoldBillPendingException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'HOLD_BILLS_PENDING',
                'message' => $e->getMessage(),
                'held_bills' => $e->getHeldBills(),
            ], 422);
        } catch (UnfinalizedTransactionException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'UNFINALIZED_TRANSACTIONS',
                'message' => $e->getMessage(),
                'unfinalized_orders' => $e->getOrders(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function report(Request $request, Shift $shift): JsonResponse
    {
        $report = $this->shiftService->generateCloseShiftReport($shift);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    public function currentReport(Request $request): JsonResponse
    {
        $user = $request->user();
        $requestedBranchId = $request->header('X-Branch-Id') ?? $request->query('branch_id') ?? $request->input('branch_id');

        $shift = null;
        if ($requestedBranchId) {
            $shift = Shift::where('user_id', $user->id)
                ->where('branch_id', $requestedBranchId)
                ->where('status', 'open')
                ->latest('id')
                ->first();

            if (! $shift) {
                $shift = Shift::where('user_id', $user->id)
                    ->where('branch_id', $requestedBranchId)
                    ->latest('id')
                    ->first();
            }
        }

        if (! $shift) {
            $shift = Shift::where('user_id', $user->id)->where('status', 'open')->latest('id')->first();
        }

        if (! $shift) {
            $shift = Shift::where('user_id', $user->id)->latest('id')->first();
        }

        if (! $shift) {
            return response()->json([
                'success' => false,
                'message' => 'Data shift tidak ditemukan.',
            ], 404);
        }

        $report = $this->shiftService->generateCloseShiftReport($shift);

        return response()->json([
            'success' => true,
            'report' => $report,
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

        try {
            $this->shiftService->assertOpenAndFresh($shift);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
