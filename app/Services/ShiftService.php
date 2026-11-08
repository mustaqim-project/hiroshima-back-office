<?php

namespace App\Services;

use App\Exceptions\HoldBillPendingException;
use App\Exceptions\UnfinalizedTransactionException;
use App\Models\CashMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Setting;
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
    protected AuditLogger $auditLogger;

    public function __construct(?AuditLogger $auditLogger = null)
    {
        $this->auditLogger = $auditLogger ?? app(AuditLogger::class);
    }

    /**
     * Open a new shift for a cashier and device.
     */
    public function open(int $branchId, int $userId, ?int $deviceId, int $startingCash): Shift
    {
        // 1. Auto-close any expired shifts across the system (>15h)
        $this->autoCloseExpiredShifts();

        // 2. Check if branch already has an active shift (shifts are per branch)
        $existingBranchShift = Shift::with(['branch', 'user'])
            ->where('branch_id', $branchId)
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if ($existingBranchShift) {
            try {
                $this->assertOpenAndFresh($existingBranchShift);
            } catch (Exception $e) {
                $existingBranchShift = Shift::with(['branch', 'user'])
                    ->where('branch_id', $branchId)
                    ->where('status', 'open')
                    ->latest('id')
                    ->first();
            }
        }

        if ($existingBranchShift) {
            $branchName = $existingBranchShift->branch?->name ? "Cabang {$existingBranchShift->branch->name}" : "Cabang #{$branchId}";
            $cashierName = $existingBranchShift->user?->name ?? "Kasir";
            throw new Exception("{$branchName} sudah memiliki shift aktif yang sedang berjalan (Shift #{$existingBranchShift->id} oleh {$cashierName}). Shift berlaku per cabang, silakan lanjutkan transaksi.");
        }

        // 3. Check if cashier already has an active shift on another branch (cross branch)
        $existingUserShift = Shift::with('branch')->where('user_id', $userId)->where('status', 'open')->first();
        if ($existingUserShift) {
            // Check if this shift has exceeded 15 hours
            try {
                $this->assertOpenAndFresh($existingUserShift);
            } catch (Exception $e) {
                // If it was auto-closed due to expiration, re-fetch
                $existingUserShift = Shift::with('branch')->where('user_id', $userId)->where('status', 'open')->first();
            }
        }

        if ($existingUserShift) {
            $branchName = $existingUserShift->branch?->name ? " (Cabang {$existingUserShift->branch->name})" : "";
            throw new Exception("Kasir masih memiliki shift aktif yang belum ditutup pada cabang lain (Shift #{$existingUserShift->id}{$branchName}). Harap tutup shift sebelumnya.");
        }

        if ($deviceId) {
            $existingDeviceShift = Shift::where('device_id', $deviceId)->where('status', 'open')->first();
            if ($existingDeviceShift) {
                try {
                    $this->assertOpenAndFresh($existingDeviceShift);
                } catch (Exception $e) {
                    $existingDeviceShift = Shift::where('device_id', $deviceId)->where('status', 'open')->first();
                }
            }
            if ($existingDeviceShift) {
                throw new Exception("Perangkat tablet ini masih memiliki shift terbuka (Shift #{$existingDeviceShift->id}). Harap tutup shift tersebut terlebih dahulu.");
            }
        }

        return Shift::create([
            'branch_id' => $branchId,
            'user_id' => $userId,
            'device_id' => $deviceId,
            'opened_at' => now('Asia/Jakarta'),
            'starting_cash' => $startingCash,
            'status' => 'open',
            'close_type' => 'manual',
        ]);
    }

    /**
     * Calculate expected cash for a shift:
     * modal_awal + penerimaan_tunai - pengeluaran_refund_tunai + kas_masuk - kas_keluar
     *
     * Catatan Akuntansi Kas Laci:
     * 1. Pembayaran Non-Tunai (QRIS, Kartu Debit, Transfer) memiliki `is_cash: false`.
     *    Uang digital langsung masuk rekening bank/settlement, TIDAK MASUK kas laci fisik kasir.
     *    Sehingga transaksi QRIS dan void QRIS TIDAK BOLEH mempengaruhi ekspektasi kas laci (tetap Rp 0).
     * 2. Pembayaran Tunai (Cash) memiliki `is_cash: true`.
     *    Ketika transaksi tunai selesai, uang masuk ke laci kasir ($cashReceived).
     *    Ketika transaksi tunai di-void, uang tunai dikembalikan ke pelanggan dari laci ($cashRefunds).
     *    Oleh karena itu, penerimaan tunai dihitung dari seluruh pembayaran tunai yang diterima pada shift ini
     *    dikurangi seluruh refund tunai yang dikeluarkan dari laci pada shift ini.
     *    (Menghindari double-deduction bug di mana transaksi void dikeluarkan dari completed sales
     *    DAN dikurangi lagi di refunds).
     */
    public function calculateExpectedCash(Shift $shift): int
    {
        // 1. Total seluruh pembayaran tunai fisik yang diterima ke laci pada shift ini (bersih setelah dikurangi kembalian)
        $cashPayments = Payment::where('shift_id', $shift->id)
            ->where(function ($query) {
                $query->whereHas('paymentMethod', fn ($q) => $q->where('is_cash', true))
                    ->orWhere(function ($q) {
                        $q->whereNull('payment_method_id')
                            ->where(function ($sub) {
                                $sub->where('payment_method_name_snapshot', 'like', '%tunai%')
                                    ->orWhere('payment_method_name_snapshot', 'like', '%cash%');
                            });
                    });
            })
            ->with('order')
            ->get();

        $cashReceived = $cashPayments->sum(function ($p) {
            if (!empty($p->change_amount) && !empty($p->tendered_amount)) {
                return (int) $p->amount;
            }
            if ($p->order && $p->order->change_amount > 0 && (int)$p->amount === (int)$p->order->paid_amount) {
                return max(0, (int)$p->amount - (int)$p->order->change_amount);
            }
            return (int) $p->amount;
        });

        // 2. Total uang tunai fisik yang dikeluarkan dari laci sebagai refund pelanggan pada shift ini
        $cashRefunds = Refund::where('shift_id', $shift->id)
            ->where(function ($query) {
                $query->whereHas('payment.paymentMethod', fn ($q) => $q->where('is_cash', true))
                    ->orWhereHas('payment', function ($pq) {
                        $pq->whereNull('payment_method_id')
                            ->where(function ($sub) {
                                $sub->where('payment_method_name_snapshot', 'like', '%tunai%')
                                    ->orWhere('payment_method_name_snapshot', 'like', '%cash%');
                            });
                    });
            })
            ->sum('amount');

        // 3. Penyesuaian Kas Masuk & Kas Keluar operasional (Petty Cash)
        $cashIn = CashMovement::where('shift_id', $shift->id)->where('type', 'cash_in')->sum('amount');
        $cashOut = CashMovement::where('shift_id', $shift->id)->where('type', 'cash_out')->sum('amount');

        $expected = (int) ($shift->starting_cash + $cashReceived - $cashRefunds + $cashIn - $cashOut);

        // Fisik uang di laci tidak boleh negatif
        return max(0, $expected);
    }

    /**
     * Close a shift manually by cashier with physical cash count.
     * Validates that no Hold Bills or uncompleted checkouts remain open.
     */
    public function close(Shift $shift, int $actualCash, string $closeType = 'manual', ?string $reason = null, bool $cancelUnfinalized = false, ?int $closedByUserId = null): Shift
    {
        // 1. Validate: No open Hold Bills on this shift
        $openHoldBills = Order::where('shift_id', $shift->id)
            ->where('status', 'on_hold')
            ->get();

        if ($openHoldBills->isNotEmpty()) {
            throw new HoldBillPendingException(
                "Tidak dapat menutup shift. Masih terdapat {$openHoldBills->count()} transaksi Hold Bill yang belum diselesaikan atau dibatalkan.",
                $openHoldBills
            );
        }

        // 2. Validate: No unfinalized checkouts (status 'open')
        $unfinalizedOrders = Order::where('shift_id', $shift->id)
            ->where('status', 'open')
            ->get();

        if ($unfinalizedOrders->isNotEmpty()) {
            if ($cancelUnfinalized) {
                foreach ($unfinalizedOrders as $unf) {
                    $unf->update([
                        'status' => 'cancelled',
                        'void_reason' => 'Dibatalkan otomatis saat penutupan shift kasir.',
                        'voided_by' => auth()->id() ?? $shift->user_id,
                        'voided_at' => now('Asia/Jakarta'),
                    ]);
                    $this->auditLogger->record('order.cancelled_on_shift_close', $unf, ['status' => 'open'], [
                        'status' => 'cancelled',
                        'shift_id' => $shift->id,
                    ]);
                }
            } else {
                throw new UnfinalizedTransactionException(
                    "Tidak dapat menutup shift. Masih terdapat {$unfinalizedOrders->count()} transaksi dengan pembayaran yang belum selesai diproses.",
                    $unfinalizedOrders
                );
            }
        }

        $expectedCash = $this->calculateExpectedCash($shift);
        $difference = $actualCash - $expectedCash;
        $closedBy = $closedByUserId ?? auth()->id() ?? $shift->user_id;

        $shift->update([
            'closed_at' => now('Asia/Jakarta'),
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'cash_difference' => $difference,
            'status' => 'closed',
            'close_type' => $closeType,
            'close_reason' => $reason ?? 'Penutupan shift reguler oleh kasir.',
            'closed_by' => $closedBy,
        ]);

        return $shift;
    }

    /**
     * Assert that shift is open and within 15-hour lifespan; auto-close if expired.
     */
    public function assertOpenAndFresh(Shift $shift, ?CarbonInterface $now = null): void
    {
        $now = $now ?? now('Asia/Jakarta');
        $openedAt = Carbon::parse($shift->opened_at);

        if ($shift->status !== 'open') {
            throw new Exception("Shift #{$shift->id} tidak berstatus aktif ({$shift->status}).");
        }

        if ($openedAt->diffInHours($now) >= 15) {
            $closedAt = $openedAt->copy()->addHours(15);
            $expectedCash = $this->calculateExpectedCash($shift);

            // Auto close expired shift
            $shift->update([
                'closed_at' => $closedAt,
                'expected_cash' => $expectedCash,
                'actual_cash' => null,
                'cash_difference' => null,
                'status' => 'auto_closed',
                'close_type' => 'auto',
                'close_reason' => 'Shift ditutup otomatis oleh sistem karena telah mencapai batas waktu operasional 15 jam.',
            ]);

            // Flag any open hold bills in this shift as unresolved
            Order::where('shift_id', $shift->id)
                ->where('status', 'on_hold')
                ->update(['is_unresolved_hold' => true]);

            throw new Exception("Shift #{$shift->id} telah ditutup otomatis karena melebihi batas waktu 15 jam. Silakan buka shift baru.");
        }
    }

    /**
     * Scan and auto-close all shifts older than 15 hours (called by scheduler every 5 minutes).
     */
    public function autoCloseExpiredShifts(?CarbonInterface $now = null): int
    {
        $now = $now ?? now('Asia/Jakarta');
        $cutoff = $now->copy()->subHours(15);

        $expiredShifts = Shift::where('status', 'open')
            ->where('opened_at', '<=', $cutoff)
            ->get();

        $count = 0;
        foreach ($expiredShifts as $shift) {
            $openedAt = Carbon::parse($shift->opened_at);
            $closedAt = $openedAt->copy()->addHours(15);
            $expectedCash = $this->calculateExpectedCash($shift);

            $shift->update([
                'closed_at' => $closedAt,
                'expected_cash' => $expectedCash,
                'actual_cash' => null,
                'cash_difference' => null,
                'status' => 'auto_closed',
                'close_type' => 'auto',
                'close_reason' => 'Shift ditutup otomatis oleh scheduler karena melebihi batas waktu operasional 15 jam.',
            ]);

            // Flag open hold bills as unresolved hold items
            Order::where('shift_id', $shift->id)
                ->where('status', 'on_hold')
                ->update(['is_unresolved_hold' => true]);

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

    /**
     * Generate Comprehensive Close Shift Report based on actual completed transactions.
     * Complies with Section 5 of specification:
     * A. Rekap Penjualan per Menu (No, Menu, Total Qty, Total Penjualan, Baris TOTAL)
     * B. Ringkasan Transaksi (Tx count, Gross, Discounts, Tax, Service, Net, Payments breakdown, Cash Rec)
     * C. Informasi Shift (Business, Outlet, Cashier, Shift ID, Opened At, Closed At, Printed At, Close Type)
     */
    public function generateCloseShiftReport(Shift $shift): array
    {
        // 1. Rekap Penjualan per Menu (Items from completed orders belonging to this shift)
        $itemsData = OrderItem::whereHas('order', function ($q) use ($shift) {
            $q->where('shift_id', $shift->id)->where('status', 'completed');
        })
            ->select(
                'product_name',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_sales')
            )
            ->groupBy('product_name')
            ->orderByDesc('total_sales')
            ->get();

        $menuSummary = [];
        $totalQtyAll = 0;
        $totalSalesAll = 0;
        $no = 1;

        foreach ($itemsData as $item) {
            $qty = (int) $item->total_qty;
            $sales = (int) $item->total_sales;
            $totalQtyAll += $qty;
            $totalSalesAll += $sales;

            $menuSummary[] = [
                'no' => $no++,
                'menu' => $item->product_name,
                'total_qty' => $qty,
                'total_sales' => $sales,
            ];
        }

        // 2. Ringkasan Transaksi (Completed Orders for this shift)
        $completedOrders = Order::where('shift_id', $shift->id)->where('status', 'completed')->get();
        $voidedOrders = Order::where('shift_id', $shift->id)->where('status', 'voided')->get();

        $transactionCount = $completedOrders->count();
        $grossSales = (int) $completedOrders->sum('subtotal');
        $promoDiscount = (int) $completedOrders->sum('promo_discount_amount');
        $voucherDiscount = (int) $completedOrders->sum('voucher_discount_amount');
        $loyaltyDiscount = (int) $completedOrders->sum('loyalty_discount_amount');
        $manualDiscount = (int) $completedOrders->sum('manual_discount_amount');
        $totalDiscount = $promoDiscount + $voucherDiscount + $loyaltyDiscount + $manualDiscount;

        $taxAmount = (int) $completedOrders->sum('tax_amount');
        $serviceChargeAmount = (int) $completedOrders->sum('service_charge_amount');
        $roundingAmount = (int) $completedOrders->sum('rounding_amount');
        $netSales = (int) $completedOrders->sum('total_amount');

        $voidCount = $voidedOrders->count();
        $voidAmount = (int) $voidedOrders->sum('total_amount');

        // Payments Breakdown by Method
        $payments = Payment::where('shift_id', $shift->id)
            ->whereHas('order', fn ($q) => $q->where('status', 'completed'))
            ->with('order')
            ->get();

        $groupedPayments = $payments->groupBy('payment_method_name_snapshot');

        $paymentsByMethod = [];
        foreach ($groupedPayments as $name => $items) {
            $txCount = $items->count();
            $netAmount = 0;
            $tenderedAmount = 0;
            $changeAmount = 0;

            foreach ($items as $p) {
                $tendered = $p->tendered_amount ?? $p->amount;
                $change = $p->change_amount ?? 0;

                // Backward compatibility if old record had amount = paid_amount
                if ($change == 0 && $p->order && $p->order->change_amount > 0 && (int)$p->amount === (int)$p->order->paid_amount) {
                    $tendered = (int) $p->order->paid_amount;
                    $change = (int) $p->order->change_amount;
                    $net = (int) $p->order->total_amount;
                } else {
                    $net = (int) $p->amount;
                    if ($tendered == $net && $change > 0) {
                        $tendered = $net + $change;
                    }
                }

                $netAmount += $net;
                $tenderedAmount += $tendered;
                $changeAmount += $change;
            }

            $paymentsByMethod[] = [
                'name' => $name,
                'tx_count' => $txCount,
                'total_amount' => $netAmount,
                'tendered_amount' => $tenderedAmount,
                'change_amount' => $changeAmount,
            ];
        }

        // Cash Reconciliation
        $startingCash = (int) $shift->starting_cash;
        $expectedCash = $shift->expected_cash !== null ? max(0, (int) $shift->expected_cash) : $this->calculateExpectedCash($shift);
        $actualCash = $shift->actual_cash !== null ? (int) $shift->actual_cash : null;
        $cashDifference = $shift->cash_difference !== null ? (int) $shift->cash_difference : null;

        // 3. Shift Information
        $branch = $shift->branch;
        $user = $shift->user;
        $closer = $shift->closer;

        $openedByName = $user?->name ?? 'Kasir Hiroshima';
        $openedByUsername = $user?->username ?? 'kasir';

        if ($shift->status === 'open') {
            $closedByName = 'Shift Masih Berjalan';
            $closedByUsername = null;
        } elseif ($shift->status === 'auto_closed' || $shift->close_type === 'auto') {
            $closedByName = 'Sistem (Otomatis 15 Jam)';
            $closedByUsername = 'system';
        } else {
            $closedByName = $closer?->name ?? $user?->name ?? 'Kasir Hiroshima';
            $closedByUsername = $closer?->username ?? $user?->username ?? 'kasir';
        }

        return [
            'branch_id' => $shift->branch_id,
            'opened_by_name' => $openedByName,
            'closed_by_name' => $closedByName,
            'shift_info' => [
                'company_name' => Setting::getCompanyName(),
                'branch_id' => $shift->branch_id,
                'branch_name' => $branch?->name ?? 'Hiroshima Japanese Street Food',
                'branch_code' => $branch?->code ?? 'HRS01',
                'cashier_name' => $openedByName,
                'cashier_username' => $openedByUsername,
                'opened_by_id' => $shift->user_id,
                'opened_by_name' => $openedByName,
                'opened_by_username' => $openedByUsername,
                'closed_by_id' => $shift->closed_by,
                'closed_by_name' => $closedByName,
                'closed_by_username' => $closedByUsername,
                'shift_id' => $shift->id,
                'opened_at' => $shift->opened_at ? $shift->opened_at->toIso8601String() : null,
                'closed_at' => $shift->closed_at ? $shift->closed_at->toIso8601String() : null,
                'printed_at' => now('Asia/Jakarta')->toIso8601String(),
                'close_type' => $shift->close_type ?? ($shift->status === 'auto_closed' ? 'auto' : 'manual'),
                'status' => $shift->status,
            ],
            'menu_summary' => [
                'items' => $menuSummary,
                'total' => [
                    'total_qty' => $totalQtyAll,
                    'total_sales' => $totalSalesAll,
                ],
            ],
            'transaction_summary' => [
                'completed_transaction_count' => $transactionCount,
                'gross_sales' => $grossSales,
                'total_discount' => $totalDiscount,
                'discount_breakdown' => [
                    'promo' => $promoDiscount,
                    'voucher' => $voucherDiscount,
                    'loyalty' => $loyaltyDiscount,
                    'manual' => $manualDiscount,
                ],
                'tax_amount' => $taxAmount,
                'service_charge_amount' => $serviceChargeAmount,
                'rounding_amount' => $roundingAmount,
                'void_refund' => [
                    'void_count' => $voidCount,
                    'void_amount' => $voidAmount,
                ],
                'net_sales' => $netSales,
                'payments_by_method' => $paymentsByMethod,
                'cash_reconciliation' => [
                    'starting_cash' => $startingCash,
                    'expected_cash' => $expectedCash,
                    'actual_cash' => $actualCash,
                    'cash_difference' => $cashDifference,
                ],
            ],
        ];
    }
}
