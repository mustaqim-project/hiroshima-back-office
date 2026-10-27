<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\OrderType;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service for Managing POS Hold Bills (Parking Orders temporarily)
 * Strictly zero inventory/stock interactions.
 */
class HoldBillService
{
    public function __construct(
        protected ShiftService $shiftService,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Create/Park a new Hold Bill.
     */
    public function hold(User $user, Branch $branch, Shift $shift, array $data): Order
    {
        // 1. Check Back Office setting
        if (! Setting::isHoldBillEnabled()) {
            throw new Exception("Fitur Hold Bill sedang dinonaktifkan oleh Administrator Back Office.");
        }

        // 2. Validate shift freshness
        $this->shiftService->assertOpenAndFresh($shift);

        // 3. Calculate subtotal & totals
        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new Exception("Item pesanan tidak boleh kosong untuk Hold Bill.");
        }

        $subtotal = 0;
        foreach ($items as $item) {
            $itemTotal = ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1);
            if (! empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $mod) {
                    $itemTotal += ($mod['unit_price'] ?? 0) * ($mod['quantity'] ?? 1);
                }
            }
            $subtotal += $itemTotal;
        }

        $calc = OrderCalculator::calculate(
            $subtotal,
            $data['promo_discount'] ?? 0,
            $data['voucher_discount'] ?? 0,
            $data['loyalty_discount'] ?? 0,
            $data['manual_discount'] ?? 0,
            $branch
        );

        $uuid = $data['uuid'] ?? (string) Str::uuid();
        $dateStr = now('Asia/Jakarta')->format('Ymd');
        $seq = Order::where('branch_id', $branch->id)->whereDate('created_at', today())->count() + 1;
        $orderNo = sprintf('%s-HOLD-%s-%04d', $branch->code, $dateStr, $seq);

        return DB::transaction(function () use ($user, $branch, $shift, $data, $items, $calc, $uuid, $orderNo) {
            $order = Order::create([
                'uuid' => $uuid,
                'order_no' => $orderNo,
                'branch_id' => $branch->id,
                'shift_id' => $shift->id,
                'user_id' => $user->id,
                'customer_id' => $data['customer_id'] ?? null,
                'order_type_id' => $data['order_type_id'] ?? OrderType::where('is_default', true)->value('id') ?? 1,
                'table_id' => $data['table_id'] ?? null,
                'status' => 'on_hold',
                'business_date' => now('Asia/Jakarta')->toDateString(),
                'subtotal' => $calc['subtotal'],
                'promo_discount_amount' => $calc['promo_discount'],
                'voucher_discount_amount' => $calc['voucher_discount'],
                'loyalty_discount_amount' => $calc['loyalty_discount'],
                'manual_discount_amount' => $calc['manual_discount'],
                'manual_discount_reason' => $data['manual_discount_reason'] ?? null,
                'manual_discount_requires_pin' => false,
                'service_charge_amount' => $calc['service_charge_amount'],
                'tax_amount' => $calc['tax_amount'],
                'rounding_amount' => $calc['rounding_amount'],
                'total_amount' => $calc['total_amount'],
                'paid_amount' => 0,
                'change_amount' => 0,
                'held_at' => now('Asia/Jakarta'),
                'hold_reference' => $data['hold_reference'] ?? ($data['customer_name'] ?? ('Bill ' . date('H:i'))),
                'hold_notes' => $data['hold_notes'] ?? null,
                'held_by' => $user->id,
                'is_unresolved_hold' => false,
                'client_created_at' => now('Asia/Jakarta'),
                'synced_at' => now('Asia/Jakarta'),
            ]);

            foreach ($items as $itemData) {
                $itemSubtotal = ($itemData['unit_price'] ?? 0) * ($itemData['quantity'] ?? 1);
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'product_variant_id' => $itemData['product_variant_id'] ?? null,
                    'product_name' => $itemData['product_name'],
                    'variant_name' => $itemData['variant_name'] ?? null,
                    'order_type_name_snapshot' => $itemData['order_type_name_snapshot'] ?? 'Default',
                    'quantity' => $itemData['quantity'] ?? 1,
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'cost_per_unit' => 0,
                    'subtotal' => $itemSubtotal,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                if (! empty($itemData['modifiers'])) {
                    foreach ($itemData['modifiers'] as $mod) {
                        OrderItemModifier::create([
                            'order_item_id' => $orderItem->id,
                            'modifier_id' => $mod['modifier_id'] ?? $mod['id'],
                            'modifier_name' => $mod['modifier_name'] ?? $mod['name'],
                            'unit_price' => $mod['unit_price'] ?? $mod['price'] ?? 0,
                            'quantity' => $mod['quantity'] ?? 1,
                        ]);
                    }
                }
            }

            $this->auditLogger->record('order.held', $order, [], [
                'hold_reference' => $order->hold_reference,
                'total_amount' => $order->total_amount,
                'shift_id' => $shift->id,
            ]);

            return $order->load(['items.modifiers', 'orderType', 'user']);
        });
    }

    /**
     * List open Hold Bills for a branch.
     */
    public function listHeld(int $branchId, ?int $shiftId = null): Collection
    {
        $query = Order::with(['items.modifiers', 'orderType', 'user', 'heldByUser'])
            ->where('branch_id', $branchId)
            ->where('status', 'on_hold');

        if ($shiftId) {
            $query->where('shift_id', $shiftId);
        }

        return $query->latest('held_at')->get();
    }

    /**
     * Resume a Hold Bill with concurrency protection.
     */
    public function resume(int $orderId, User $user, ?Shift $activeShift = null): Order
    {
        return DB::transaction(function () use ($orderId, $user, $activeShift) {
            /** @var Order $order */
            $order = Order::lockForUpdate()->find($orderId);

            if (! $order) {
                throw new Exception("Transaksi Hold Bill tidak ditemukan.");
            }

            if ($order->status !== 'on_hold') {
                throw new Exception("Transaksi ini tidak dapat dilanjutkan karena statusnya bukan on_hold ({$order->status}). Kemungkinan sudah diproses oleh kasir lain.");
            }

            // If active shift is provided, verify it is open and update order shift if previous shift ended
            if ($activeShift) {
                $this->shiftService->assertOpenAndFresh($activeShift);
                $order->shift_id = $activeShift->id;
            }

            $order->status = 'open';
            $order->is_unresolved_hold = false;
            $order->save();

            $this->auditLogger->record('order.resumed', $order, ['status' => 'on_hold'], [
                'status' => 'open',
                'resumed_by' => $user->id,
                'shift_id' => $order->shift_id,
            ]);

            return $order->load(['items.modifiers', 'orderType', 'user']);
        });
    }

    /**
     * Cancel a Hold Bill with mandatory reason and audit logging.
     */
    public function cancel(int $orderId, User $user, string $reason): Order
    {
        if (trim($reason) === '') {
            throw new Exception("Alasan pembatalan Hold Bill wajib diisi.");
        }

        return DB::transaction(function () use ($orderId, $user, $reason) {
            /** @var Order $order */
            $order = Order::lockForUpdate()->find($orderId);

            if (! $order) {
                throw new Exception("Transaksi Hold Bill tidak ditemukan.");
            }

            if ($order->status !== 'on_hold') {
                throw new Exception("Transaksi ini tidak dapat dibatalkan karena statusnya bukan on_hold ({$order->status}).");
            }

            $order->update([
                'status' => 'voided',
                'void_reason' => $reason,
                'voided_by' => $user->id,
                'voided_at' => now('Asia/Jakarta'),
                'is_unresolved_hold' => false,
            ]);

            $this->auditLogger->record('order.hold_cancelled', $order, ['status' => 'on_hold'], [
                'status' => 'voided',
                'reason' => $reason,
                'cancelled_by' => $user->id,
            ]);

            return $order;
        });
    }
}
