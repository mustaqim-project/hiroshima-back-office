<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add tendered_amount and change_amount to payments table
 * and backfill legacy payment records so net payment matches net sales.
 * 
 * @req FR-POS-06, FR-POS-07, FSD-06, FSD-09
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'tendered_amount')) {
                $table->unsignedBigInteger('tendered_amount')->nullable()->after('amount');
            }
            if (! Schema::hasColumn('payments', 'change_amount')) {
                $table->unsignedBigInteger('change_amount')->default(0)->after('tendered_amount');
            }
        });

        // Backfill legacy payment records
        try {
            DB::transaction(function () {
                $payments = Payment::with('order')->get();
                $affectedShiftIds = [];

                foreach ($payments as $payment) {
                    $order = $payment->order;
                    if (! $order) {
                        $payment->update([
                            'tendered_amount' => $payment->tendered_amount ?? $payment->amount,
                            'change_amount' => $payment->change_amount ?? 0,
                        ]);
                        continue;
                    }

                    $orderTotal = (int) $order->total_amount;
                    $orderChange = (int) $order->change_amount;
                    $orderPaid = (int) $order->paid_amount;
                    $currentAmount = (int) $payment->amount;

                    if ($orderChange > 0 && ($currentAmount === $orderPaid || $currentAmount > $orderTotal)) {
                        $netAmount = max(0, $currentAmount - $orderChange);
                        $payment->update([
                            'amount' => $netAmount > 0 ? $netAmount : $orderTotal,
                            'tendered_amount' => $currentAmount,
                            'change_amount' => $orderChange,
                        ]);

                        if ($payment->shift_id) {
                            $affectedShiftIds[$payment->shift_id] = true;
                        }
                    } else {
                        $payment->update([
                            'tendered_amount' => $payment->tendered_amount ?? $currentAmount,
                            'change_amount' => $payment->change_amount ?? 0,
                        ]);
                    }
                }

                // Recalculate closed shifts that had cash payments with change
                if (! empty($affectedShiftIds)) {
                    $shiftService = app(ShiftService::class);
                    foreach (array_keys($affectedShiftIds) as $shiftId) {
                        $shift = Shift::find($shiftId);
                        if ($shift && in_array($shift->status, ['closed', 'auto_closed', 'reconciled'])) {
                            $recalcExpected = $shiftService->calculateExpectedCash($shift);
                            $newDiff = $shift->actual_cash !== null ? ((int) $shift->actual_cash - $recalcExpected) : null;
                            $shift->update([
                                'expected_cash' => $recalcExpected,
                                'cash_difference' => $newDiff,
                            ]);
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            // Log but don't halt schema creation if table was empty or sqlite test environment
            report($e);
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'change_amount')) {
                $table->dropColumn('change_amount');
            }
            if (Schema::hasColumn('payments', 'tendered_amount')) {
                $table->dropColumn('tendered_amount');
            }
        });
    }
};
