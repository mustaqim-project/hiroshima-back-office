<?php

namespace App\Http\Controllers\Api\V1\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Shift;
use App\Services\AuditLogger;
use App\Services\HoldBillService;
use App\Services\OrderCalculator;
use App\Services\PinAuthorizationService;
use App\Services\ShiftService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @req FR-POS-02, FR-POS-03, FR-POS-04, FR-POS-05, FR-POS-05a, FR-POS-06, FR-POS-07, FR-POS-08, FR-POS-09, FR-POS-10, FR-POS-11, FSD-06, FSD-07, FSD-08, FSD-09
 */
class OrderController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected PinAuthorizationService $pinService,
        protected ShiftService $shiftService,
        protected HoldBillService $holdBillService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid',
            'order_type_id' => 'required|exists:order_types,id',
            'table_id' => 'nullable|exists:tables,id',
            'customer_id' => 'nullable|exists:customers,id',
            'shift_id' => 'required|exists:shifts,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.modifiers' => 'nullable|array',
            'promo_discount' => 'nullable|integer|min:0',
            'voucher_discount' => 'nullable|integer|min:0',
            'loyalty_discount' => 'nullable|integer|min:0',
            'manual_discount' => 'nullable|integer|min:0',
            'manual_discount_reason' => 'nullable|string',
            'pin_discount' => 'nullable|string',
            'payments' => 'nullable|array',
        ]);

        $user = $request->user();
        $canSwitch = is_null($user->branch_id)
            || in_array($user->role, ['owner', 'central_admin'])
            || $user->can('branch.manage');

        $requestedBranchId = $request->header('X-Branch-Id') ?? $request->input('branch_id');

        if ($requestedBranchId && $canSwitch) {
            $branch = Branch::where('is_active', true)->findOrFail($requestedBranchId);
        } elseif ($user->branch_id) {
            $branch = Branch::findOrFail($user->branch_id);
        } else {
            $branch = Branch::where('is_active', true)->firstOrFail();
        }

        $shift = Shift::find($validated['shift_id']);

        if (! $shift || $shift->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ditolak. Kasir wajib melakukan Open Shift terlebih dahulu.',
            ], 403);
        }

        // Assert shift is active and <= 23 hours
        try {
            $this->shiftService->assertOpenAndFresh($shift);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }

        // Calculate subtotal
        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $itemTotal = $item['unit_price'] * $item['quantity'];
            if (! empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $mod) {
                    $itemTotal += ($mod['unit_price'] ?? 0) * ($mod['quantity'] ?? 1);
                }
            }
            $subtotal += $itemTotal;
        }

        $manualDiscount = $validated['manual_discount'] ?? 0;
        $requiresPin = false;

        // Check discount thresholds (FSD-07)
        if ($manualDiscount > 0) {
            $percent = $subtotal > 0 ? ($manualDiscount / $subtotal) * 100 : 0;
            if ($percent > $branch->discount_percent_threshold || $manualDiscount > $branch->discount_amount_threshold) {
                $requiresPin = true;
                $pin = $validated['pin_discount'] ?? '';
                if (empty($pin)) {
                    return response()->json([
                        'success' => false,
                        'requires_pin' => true,
                        'message' => 'Diskon manual melebihi ambang batas. Diperlukan otorisasi PIN Diskon Kepala Cabang.',
                    ], 403);
                }
                $this->pinService->verify($branch, 'discount', $pin, null, $validated['manual_discount_reason'] ?? 'Diskon besar');
            }
        }

        // Calculate totals using OrderCalculator
        $calc = OrderCalculator::calculate(
            $subtotal,
            $validated['promo_discount'] ?? 0,
            $validated['voucher_discount'] ?? 0,
            $validated['loyalty_discount'] ?? 0,
            $manualDiscount,
            $branch
        );

        $uuid = $validated['uuid'] ?? (string) Str::uuid();
        $dateStr = now('Asia/Jakarta')->format('Ymd');
        $seq = Order::where('branch_id', $branch->id)->whereDate('created_at', today())->count() + 1;
        $orderNo = sprintf('%s-POS-%s-%04d', $branch->code, $dateStr, $seq);

        $order = DB::transaction(function () use ($validated, $branch, $shift, $user, $calc, $uuid, $orderNo, $requiresPin) {
            $order = Order::create([
                'uuid' => $uuid,
                'order_no' => $orderNo,
                'branch_id' => $branch->id,
                'shift_id' => $shift->id,
                'user_id' => $user->id,
                'customer_id' => $validated['customer_id'] ?? null,
                'order_type_id' => $validated['order_type_id'],
                'table_id' => $validated['table_id'] ?? null,
                'status' => empty($validated['payments']) ? 'open' : 'completed',
                'business_date' => now('Asia/Jakarta')->toDateString(),
                'subtotal' => $calc['subtotal'],
                'promo_discount_amount' => $calc['promo_discount'],
                'voucher_discount_amount' => $calc['voucher_discount'],
                'loyalty_discount_amount' => $calc['loyalty_discount'],
                'manual_discount_amount' => $calc['manual_discount'],
                'manual_discount_reason' => $validated['manual_discount_reason'] ?? null,
                'manual_discount_requires_pin' => $requiresPin,
                'service_charge_amount' => $calc['service_charge_amount'],
                'tax_amount' => $calc['tax_amount'],
                'rounding_amount' => $calc['rounding_amount'],
                'total_amount' => $calc['total_amount'],
                'client_created_at' => now('Asia/Jakarta'),
                'synced_at' => now('Asia/Jakarta'),
            ]);

            foreach ($validated['items'] as $itemData) {
                $itemSubtotal = $itemData['unit_price'] * $itemData['quantity'];
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'product_variant_id' => $itemData['product_variant_id'] ?? null,
                    'product_name' => $itemData['product_name'],
                    'variant_name' => $itemData['variant_name'] ?? null,
                    'order_type_name_snapshot' => 'Dine In',
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'cost_per_unit' => 0,
                    'subtotal' => $itemSubtotal,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                if (! empty($itemData['modifiers'])) {
                    foreach ($itemData['modifiers'] as $mod) {
                        OrderItemModifier::create([
                            'order_item_id' => $orderItem->id,
                            'modifier_id' => $mod['modifier_id'],
                            'modifier_name' => $mod['modifier_name'],
                            'unit_price' => $mod['unit_price'] ?? 0,
                            'quantity' => $mod['quantity'] ?? 1,
                        ]);
                    }
                }
            }

            if (! empty($validated['payments'])) {
                $paidTotal = 0;
                foreach ($validated['payments'] as $p) {
                    Payment::create([
                        'order_id' => $order->id,
                        'shift_id' => $shift->id,
                        'payment_method_id' => $p['payment_method_id'],
                        'payment_method_name_snapshot' => $p['payment_method_name'] ?? 'Tunai',
                        'amount' => $p['amount'],
                        'reference_no' => $p['reference_no'] ?? null,
                        'paid_at' => now('Asia/Jakarta'),
                    ]);
                    $paidTotal += $p['amount'];
                }

                $order->update([
                    'paid_amount' => $paidTotal,
                    'change_amount' => max(0, $paidTotal - $calc['total_amount']),
                    'status' => 'completed',
                ]);
            }

            return $order;
        });

        $this->auditLogger->record('order.created', $order, [], $order->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil disimpan.',
            'order' => $order->load(['items.modifiers', 'payments']),
            'calculation' => $calc,
        ], 201);
    }

    /**
     * Park/Hold active order temporarily.
     */
    public function hold(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid',
            'order_type_id' => 'required|exists:order_types,id',
            'table_id' => 'nullable|exists:tables,id',
            'customer_id' => 'nullable|exists:customers,id',
            'shift_id' => 'required|exists:shifts,id',
            'hold_reference' => 'nullable|string',
            'hold_notes' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.modifiers' => 'nullable|array',
            'promo_discount' => 'nullable|integer|min:0',
            'voucher_discount' => 'nullable|integer|min:0',
            'loyalty_discount' => 'nullable|integer|min:0',
            'manual_discount' => 'nullable|integer|min:0',
            'manual_discount_reason' => 'nullable|string',
        ]);

        $user = $request->user();
        $branch = Branch::findOrFail($user->branch_id ?? 1);
        $shift = Shift::find($validated['shift_id']);

        if (! $shift || $shift->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Hold Bill ditolak. Kasir wajib memiliki shift aktif.',
            ], 403);
        }

        try {
            $order = $this->holdBillService->hold($user, $branch, $shift, $validated);

            return response()->json([
                'success' => true,
                'message' => "Pesanan berhasil ditahan sementara (Hold Bill #{$order->order_no}).",
                'order' => $order,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * List open Hold Bills for the cashier's branch.
     */
    public function heldOrders(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = $user->branch_id ?? 1;
        $shiftId = $request->query('shift_id');

        $heldBills = $this->holdBillService->listHeld($branchId, $shiftId ? (int) $shiftId : null);

        return response()->json([
            'success' => true,
            'count' => $heldBills->count(),
            'held_bills' => $heldBills,
        ]);
    }

    /**
     * Resume a Hold Bill to cart / checkout.
     */
    public function resume(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $activeShift = Shift::where('user_id', $user->id)->where('status', 'open')->latest('id')->first();

        if (! $activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Harap buka shift kasir terlebih dahulu untuk melanjutkan transaksi Hold Bill ini.',
            ], 403);
        }

        try {
            $order = $this->holdBillService->resume($id, $user, $activeShift);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi Hold Bill berhasil dilanjutkan.',
                'order' => $order,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel a Hold Bill.
     */
    public function cancelHold(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        $user = $request->user();

        try {
            $order = $this->holdBillService->cancel($id, $user, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi Hold Bill berhasil dibatalkan.',
                'order' => $order,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function void(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'pin_void' => 'required|string',
            'reason' => 'required|string|min:3',
        ]);

        if ($order->status === 'voided') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi sudah dibatalkan sebelumnya.',
            ], 409);
        }

        $branch = $order->branch ?? Branch::findOrFail(1);

        // Verify PIN Void (FSD-08)
        $this->pinService->verify($branch, 'void', $request->pin_void, null, $request->reason);

        DB::transaction(function () use ($order, $request) {
            $order->update([
                'status' => 'voided',
                'void_reason' => $request->reason,
                'voided_by' => auth()->id(),
                'voided_at' => now('Asia/Jakarta'),
            ]);

            // Create refund records for all payments
            foreach ($order->payments as $payment) {
                Refund::create([
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'shift_id' => $order->shift_id,
                    'amount' => $payment->amount,
                    'reason' => $request->reason,
                    'authorized_by_pin' => true,
                    'authorized_by_user_id' => auth()->id(),
                ]);
            }
        });

        $this->auditLogger->record('order.voided', $order, ['status' => 'completed'], ['status' => 'voided', 'reason' => $request->reason]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dibatalkan (Void). Dana dikembalikan ke pelanggan.',
        ]);
    }
}
