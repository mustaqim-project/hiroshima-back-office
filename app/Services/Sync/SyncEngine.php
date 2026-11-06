<?php

namespace App\Services\Sync;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Device;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\OrderType;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\Promo;
use App\Models\Shift;
use App\Models\SyncLog;
use App\Models\Table;
use App\Models\User;
use App\Models\Voucher;
use App\Services\InventoryGate;
use App\Services\TransactionBackupLogger;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @req FR-OFF-01, FR-OFF-02, FR-OFF-03, FR-OFF-04, FR-OFF-05, FSD-10
 */
class SyncEngine
{
    public function __construct(
        protected ConflictResolver $conflictResolver
    ) {}

    /**
     * Build offline cache payload for a tablet device.
     */
    public function pull(Branch $branch, ?string $since = null): array
    {
        $serverTime = now('Asia/Jakarta')->toIso8601String();

        return [
            'server_time' => $serverTime,
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'tax_percent' => $branch->tax_percent,
                'service_charge_percent' => $branch->service_charge_percent,
                'tax_included' => $branch->tax_included,
                'rounding_mode' => $branch->rounding_mode,
                'pin_void_hash' => $branch->pin_void_hash,
                'pin_discount_hash' => $branch->pin_discount_hash,
                'discount_percent_threshold' => $branch->discount_percent_threshold,
                'discount_amount_threshold' => $branch->discount_amount_threshold,
                'inventory_enabled' => InventoryGate::isActive($branch->id),
                'print_kitchen_copy' => $branch->print_kitchen_copy,
            ],
            'order_types' => OrderType::where('is_active', true)->get(),
            'categories' => Category::where('is_active', true)->get(),
            'payment_methods' => PaymentMethod::where('is_active', true)
                ->whereHas('branchPaymentMethods', fn ($q) => $q->where('branch_id', $branch->id)->where('is_active', true))
                ->get(),
            'products' => Product::with(['variants', 'modifierGroups.modifiers', 'prices'])->where('is_active', true)->get(),
            'product_prices' => ProductPrice::where(function ($q) use ($branch) {
                $q->where('branch_id', $branch->id)->orWhereNull('branch_id');
            })->get(),
            'promos' => Promo::where('is_active', true)->whereHas('branches', fn ($q) => $q->where('branch_id', $branch->id))->get(),
            'vouchers' => Voucher::where('is_active', true)->where('ends_at', '>=', now('Asia/Jakarta'))->get(),
            'tables' => Table::where('branch_id', $branch->id)->where('is_active', true)->get(),
            'users' => \App\Models\User::where('branch_id', $branch->id)->where('is_active', true)->select('id', 'name', 'username', 'role')->get(),
            'settings' => [
                'app_name' => \App\Models\Setting::getAppName(),
                'app_tagline' => \App\Models\Setting::getAppTagline(),
                'app_logo_url' => \App\Models\Setting::getLogoUrl(),
                'hold_bill_enabled' => \App\Models\Setting::isHoldBillEnabled(),
            ],
        ];
    }

    /**
     * Process batch push from offline outbox FIFO queue.
     */
    public function push(Branch $branch, ?Device $device, array $items): array
    {
        $startTime = microtime(true);
        $results = [];
        $successCount = 0;
        $conflictCount = 0;

        foreach ($items as $item) {
            $uuid = $item['uuid'];

            // Idempotency check: if transaction with this UUID already exists, acknowledge OK
            if ($existing = Order::where('uuid', $uuid)->first()) {
                TransactionBackupLogger::record($item, $existing, 'duplicate_ok', 'pos_sync');
                $results[] = [
                    'uuid' => $uuid,
                    'status' => 'duplicate_ok',
                    'order_no' => $existing->order_no,
                    'message' => 'Transaksi sudah tersinkronisasi sebelumnya.',
                ];
                $successCount++;
                continue;
            }

            // Resolve target branch for this item (allowing multi-branch user transactions)
            $orderBranch = $branch;
            if (!empty($item['branch_id']) && (int)$item['branch_id'] !== (int)$branch->id) {
                $targetBranch = Branch::where('is_active', true)->find($item['branch_id']);
                if ($targetBranch) {
                    $orderBranch = $targetBranch;
                }
            }

            try {
                $evaluation = $this->conflictResolver->evaluateOrder($item, $orderBranch);

                if ($evaluation['status'] === 'rejected') {
                    TransactionBackupLogger::record($item, null, 'rejected', 'pos_sync', $evaluation['reason'] ?? 'Shift atau validasi ditolak');
                    $results[] = [
                        'uuid' => $uuid,
                        'status' => 'rejected',
                        'reason' => $evaluation['reason'],
                    ];
                    $conflictCount++;
                    continue;
                }

                // Ingest transaction in a database transaction with bulletproof defensive fallbacks
                $order = DB::transaction(function () use ($item, $orderBranch, $device, $evaluation) {
                    $clientCreatedAt = Carbon::parse($item['client_created_at']);
                    $businessDate = $clientCreatedAt->copy()->setTimezone('Asia/Jakarta')->toDateString();

                    // 1. Resolve & validate Shift ID safely (prevent foreign key violation)
                    $shiftId = null;
                    if (!empty($item['shift_id'])) {
                        $shiftExists = Shift::where('id', $item['shift_id'])->exists();
                        if ($shiftExists) {
                            $shiftId = $item['shift_id'];
                        } else {
                            $shiftId = Shift::where('branch_id', $orderBranch->id)->where('status', 'open')->latest('id')->value('id')
                                ?? Shift::where('branch_id', $orderBranch->id)->latest('id')->value('id');
                        }
                    } else {
                        $shiftId = Shift::where('branch_id', $orderBranch->id)->where('status', 'open')->latest('id')->value('id')
                            ?? Shift::where('branch_id', $orderBranch->id)->latest('id')->value('id');
                    }

                    // 2. Resolve & validate Table ID safely (prevent foreign key violation if tables table is empty)
                    $tableId = null;
                    if (!empty($item['table_id'])) {
                        if (Table::where('id', $item['table_id'])->where('branch_id', $orderBranch->id)->exists()) {
                            $tableId = $item['table_id'];
                        } elseif ($foundTable = Table::where('table_no', (string)$item['table_id'])->where('branch_id', $orderBranch->id)->first()) {
                            $tableId = $foundTable->id;
                        }
                    }

                    // 3. Resolve & validate User ID safely (prevent foreign key violation)
                    $userId = $item['user_id'] ?? auth()->id();
                    if (!$userId || !User::where('id', $userId)->exists()) {
                        $userId = auth()->id() ?? User::where('branch_id', $orderBranch->id)->value('id') ?? User::first()?->id ?? 1;
                    }

                    // 4. Resolve & validate Order Type ID safely
                    $orderTypeId = $item['order_type_id'] ?? null;
                    if (!$orderTypeId || !OrderType::where('id', $orderTypeId)->exists()) {
                        $orderTypeId = OrderType::where('is_default', true)->value('id') ?? OrderType::first()?->id ?? 1;
                    }

                    // 5. Ensure order_no uniqueness without throwing duplicate key exception
                    $orderNo = $item['order_no'];
                    if (Order::where('order_no', $orderNo)->exists()) {
                        $orderNo = sprintf('%s-%s', $orderNo, substr(str_replace('-', '', (string)Str::uuid()), 0, 4));
                    }

                    $order = Order::create([
                        'uuid' => $item['uuid'],
                        'order_no' => $orderNo,
                        'branch_id' => $orderBranch->id,
                        'device_id' => $device?->id,
                        'shift_id' => $shiftId,
                        'user_id' => $userId,
                        'customer_id' => $item['customer_id'] ?? null,
                        'order_type_id' => $orderTypeId,
                        'table_id' => $tableId,
                        'status' => $item['status'] ?? 'completed',
                        'business_date' => $businessDate,
                        'hold_reference' => $item['hold_reference'] ?? $item['customer_name'] ?? null,
                        'hold_notes' => $item['hold_notes'] ?? null,
                        'subtotal' => $item['subtotal'],
                        'promo_discount_amount' => $item['promo_discount_amount'] ?? 0,
                        'voucher_discount_amount' => $item['voucher_discount_amount'] ?? 0,
                        'loyalty_discount_amount' => $item['loyalty_discount_amount'] ?? 0,
                        'manual_discount_amount' => $item['manual_discount_amount'] ?? 0,
                        'manual_discount_reason' => $item['manual_discount_reason'] ?? null,
                        'manual_discount_requires_pin' => $item['manual_discount_requires_pin'] ?? false,
                        'service_charge_amount' => $item['service_charge_amount'] ?? 0,
                        'tax_amount' => $item['tax_amount'] ?? 0,
                        'rounding_amount' => $item['rounding_amount'] ?? 0,
                        'total_amount' => $item['total_amount'],
                        'paid_amount' => $item['paid_amount'] ?? $item['total_amount'],
                        'change_amount' => $item['change_amount'] ?? 0,
                        'client_created_at' => $clientCreatedAt,
                        'synced_at' => now('Asia/Jakarta'),
                        'clock_skew_flag' => $item['clock_skew_flag'] ?? false,
                        'sync_status' => $evaluation['status'],
                    ]);

                    // Items
                    foreach ($item['items'] as $itemData) {
                        $productId = $itemData['product_id'] ?? null;
                        if (!$productId || !Product::where('id', $productId)->exists()) {
                            $productId = Product::where('name', $itemData['product_name'])->value('id') 
                                ?? Product::first()?->id ?? 1;
                        }

                        $variantName = $itemData['variant_name'] ?? null;
                        $variantId = $itemData['product_variant_id'] ?? null;
                        if ($variantId && !ProductVariant::where('id', $variantId)->exists()) {
                            $variantId = null;
                        }
                        if (empty($variantName) && !empty($variantId)) {
                            $variantName = ProductVariant::find($variantId)?->name;
                        }

                        $orderItem = OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $productId,
                            'product_variant_id' => $variantId,
                            'product_name' => $itemData['product_name'],
                            'variant_name' => $variantName,
                            'order_type_name_snapshot' => $itemData['order_type_name_snapshot'] ?? 'Default',
                            'quantity' => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'cost_per_unit' => $itemData['cost_per_unit'] ?? 0,
                            'subtotal' => $itemData['subtotal'] ?? ($itemData['unit_price'] * $itemData['quantity']),
                            'notes' => $itemData['notes'] ?? null,
                        ]);

                        if (! empty($itemData['modifiers'])) {
                            foreach ($itemData['modifiers'] as $modData) {
                                $modId = $modData['modifier_id'] ?? $modData['id'] ?? null;
                                $modName = $modData['modifier_name'] ?? $modData['name'] ?? 'Modifier';

                                if ($modId && !Modifier::where('id', $modId)->exists()) {
                                    $modId = Modifier::where('name', $modName)->value('id');
                                }
                                if (!$modId) {
                                    $modId = Modifier::first()?->id;
                                }

                                if ($modId) {
                                    OrderItemModifier::create([
                                        'order_item_id' => $orderItem->id,
                                        'modifier_id' => $modId,
                                        'modifier_name' => $modName,
                                        'unit_price' => $modData['unit_price'] ?? $modData['price'] ?? 0,
                                        'quantity' => $modData['quantity'] ?? 1,
                                    ]);
                                }
                            }
                        }
                    }

                    // Payments
                    if (! empty($item['payments'])) {
                        foreach ($item['payments'] as $paymentData) {
                            $pmId = $paymentData['payment_method_id'] ?? null;
                            if ($pmId && !PaymentMethod::where('id', $pmId)->exists()) {
                                $pmId = null;
                            }
                            if (!$pmId) {
                                $pmId = PaymentMethod::where('type', 'cash')->value('id') 
                                    ?? PaymentMethod::first()?->id 
                                    ?? 1;
                            }

                            $pmObj = PaymentMethod::find($pmId);
                            $pmName = $paymentData['payment_method_name_snapshot'] 
                                ?? $paymentData['payment_method_name'] 
                                ?? $pmObj?->name 
                                ?? 'Tunai (Cash)';

                            Payment::create([
                                'order_id' => $order->id,
                                'shift_id' => $shiftId,
                                'payment_method_id' => $pmId,
                                'payment_method_name_snapshot' => $pmName,
                                'amount' => $paymentData['amount'] ?? $order->total_amount,
                                'reference_no' => $paymentData['reference_no'] ?? null,
                                'paid_at' => $clientCreatedAt,
                            ]);
                        }
                    }

                    // Resolve resumed hold bill if referenced so it does not remain as open draft
                    if (!empty($item['resumed_order_id'])) {
                        $resumedOrder = Order::find($item['resumed_order_id']);
                        if ($resumedOrder && in_array($resumedOrder->status, ['open', 'on_hold'])) {
                            $resumedOrder->update([
                                'status' => 'cancelled',
                                'void_reason' => 'Diselesaikan via transaksi ' . $order->order_no,
                                'voided_by' => $order->user_id,
                                'voided_at' => now('Asia/Jakarta'),
                            ]);
                        }
                    }

                    return $order;
                });

                TransactionBackupLogger::record($item, $order, $evaluation['status'], 'pos_sync');

                if ($evaluation['status'] === 'accepted_flagged') {
                    $conflictCount++;
                } else {
                    $successCount++;
                }

                $results[] = [
                    'uuid' => $uuid,
                    'status' => $evaluation['status'],
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'flags' => $evaluation['flags'],
                ];

            } catch (Exception $e) {
                TransactionBackupLogger::record($item, null, 'failed', 'pos_sync', $e->getMessage());

                Log::error("[SyncEngine] Push Order Failed for UUID {$uuid}: " . $e->getMessage(), [
                    'order_no' => $item['order_no'] ?? null,
                    'branch_id' => $orderBranch->id,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $conflictCount++;
                $results[] = [
                    'uuid' => $uuid,
                    'status' => 'rejected',
                    'reason' => $e->getMessage(),
                ];
            }
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        // Record sync log
        SyncLog::create([
            'branch_id' => $branch->id,
            'device_id' => $device?->id,
            'type' => 'push',
            'batch_size' => count($items),
            'success_count' => $successCount,
            'conflict_count' => $conflictCount,
            'duration_ms' => $durationMs,
        ]);

        return [
            'success' => true,
            'success_count' => $successCount,
            'conflict_count' => $conflictCount,
            'duration_ms' => $durationMs,
            'results' => $results,
        ];
    }
}
