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
        // 1. Auto-close any expired shifts across the system (>23h)
        $this->autoCloseExpiredShifts();

        // 2. Check if cashier already has an active shift
        $existingUserShift = Shift::with('branch')->where('user_id', $userId)->where('status', 'open')->first();
        if ($existingUserShift) {
            // Check if this shift has exceeded 23 hours
            try {
                $this->assertOpenAndFresh($existingUserShift);
            } catch (Exception $e) {
                // If it was auto-closed due to expiration, re-fetch
                $existingUserShift = Shift::with('branch')->where('user_id', $userId)->where('status', 'open')->first();
            }
        }

        if ($existingUserShift) {
            $branchName = $existingUserShift->branch?->name ? " (Cabang {$existingUserShift->branch->name})" : "";
            throw new Exception("Kasir masih memiliki shift aktif yang belum ditutup (Shift #{$existingUserShift->id}{$branchName}). Harap tutup shift sebelumnya.");
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
     * modal_awal + tunai - refund_tunai + kas_masuk - kas_keluar
     */
    public function calculateExpectedCash(Shift $shift): int
    {
        // 1. Total cash payments received
        $cashPayments = Payment::where('shift_id', $shift->id)
            ->whereHas('order', fn ($q) => $q->where('status', 'completed'))
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
     * Validates that no Hold Bills or uncompleted checkouts remain open.
     */
    public function close(Shift $shift, int $actualCash, string $closeType = 'manual', ?string $reason = null, bool $cancelUnfinalized = false): Shift
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

        $shift->update([
            'closed_at' => now('Asia/Jakarta'),
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'cash_difference' => $difference,
            'status' => 'closed',
            'close_type' => $closeType,
            'close_reason' => $reason ?? 'Penutupan shift reguler oleh kasir.',
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
            $closedAt = $openedAt->copy()->addHours(23);
            $expectedCash = $this->calculateExpectedCash($shift);

            // Auto close expired shift
            $shift->update([
                'closed_at' => $closedAt,
                'expected_cash' => $expectedCash,
                'actual_cash' => null,
                'cash_difference' => null,
                'status' => 'auto_closed',
                'close_type' => 'auto',
                'close_reason' => 'Shift ditutup otomatis oleh sistem karena telah mencapai batas waktu operasional 23 jam.',
            ]);

            // Flag any open hold bills in this shift as unresolved
            Order::where('shift_id', $shift->id)
                ->where('status', 'on_hold')
                ->update(['is_unresolved_hold' => true]);

            throw new Exception("Shift #{$shift->id} telah ditutup otomatis karena melebihi batas waktu 23 jam. Silakan buka shift baru.");
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
            $closedAt = $openedAt->copy()->addHours(23);
            $expectedCash = $this->calculateExpectedCash($shift);

            $shift->update([
                'closed_at' => $closedAt,
                'expected_cash' => $expectedCash,
                'actual_cash' => null,
                'cash_difference' => null,
                'status' => 'auto_closed',
                'close_type' => 'auto',
                'close_reason' => 'Shift ditutup otomatis oleh scheduler karena melebihi batas waktu operasional 23 jam.',
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
        $paymentsByMethod = Payment::where('shift_id', $shift->id)
            ->whereHas('order', fn ($q) => $q->where('status', 'completed'))
            ->select(
                'payment_method_name_snapshot',
                DB::raw('COUNT(*) as tx_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('payment_method_name_snapshot')
            ->get()
            ->map(fn ($p) => [
                'name' => $p->payment_method_name_snapshot,
                'tx_count' => (int) $p->tx_count,
                'total_amount' => (int) $p->total_amount,
            ])
            ->toArray();

        // Cash Reconciliation
        $startingCash = (int) $shift->starting_cash;
        $expectedCash = $shift->expected_cash !== null ? (int) $shift->expected_cash : $this->calculateExpectedCash($shift);
        $actualCash = $shift->actual_cash !== null ? (int) $shift->actual_cash : null;
        $cashDifference = $shift->cash_difference !== null ? (int) $shift->cash_difference : null;

        // 3. Shift Information
        $branch = $shift->branch;
        $user = $shift->user;

        return [
            'shift_info' => [
                'company_name' => Setting::getCompanyName(),
                'branch_name' => $branch?->name ?? 'Hiroshima Japanese Street Food',
                'branch_code' => $branch?->code ?? 'HRS01',
                'cashier_name' => $user?->name ?? 'Kasir Hiroshima',
                'cashier_username' => $user?->username ?? 'kasir',
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
