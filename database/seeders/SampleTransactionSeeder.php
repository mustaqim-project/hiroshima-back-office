<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CashBook;
use App\Models\Category;
use App\Models\Modifier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\OrderType;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::where('is_active', true)->get();
        if ($branches->isEmpty()) {
            return;
        }

        $adminUser = User::where('role', 'central_admin')->orWhere('role', 'owner')->first() ?? User::first();
        $orderTypes = OrderType::where('is_active', true)->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $products = Product::where('is_active', true)->with(['variants', 'modifierGroups.modifiers'])->get();

        if ($products->isEmpty() || $orderTypes->isEmpty() || $paymentMethods->isEmpty()) {
            return;
        }

        $now = now('Asia/Jakarta');

        // Clean up previous sample orders to avoid double counts
        // Only seed if empty or fresh
        if (Order::count() > 0) {
            return;
        }

        $orderIndex = 1;

        // Generate transactions for the last 30 days up to today
        for ($daysAgo = 29; $daysAgo >= 0; $daysAgo--) {
            $currentDate = $now->copy()->subDays($daysAgo);
            $dateString = $currentDate->toDateString();

            foreach ($branches as $branch) {
                // Different volume per branch
                $orderCountToday = match ($branch->code) {
                    'HRS-CKN' => rand(15, 26), // Cikini: High traffic
                    'HRS-TBT' => rand(10, 18), // Tebet: Medium-high traffic
                    'HRS-CLD' => rand(6, 12),  // Cilandak: Medium traffic
                    default => rand(8, 15),
                };

                // Add daily Cash Book entries for this branch
                // 1. Modal Awal Kasir (Cash In)
                CashBook::create([
                    'branch_id' => $branch->id,
                    'type' => 'in',
                    'category' => 'Modal Awal',
                    'amount' => 300000,
                    'transaction_date' => $dateString,
                    'description' => 'Modal awal kas kecil operasional meja kasir ' . $branch->name,
                    'created_by' => $adminUser->id,
                ]);

                // 2. Belanja Harian (Bumbu, Es Batu, Gas LPG) (Cash Out)
                $dailyExpenses = [
                    ['cat' => 'Bahan Baku & Bumbu', 'min' => 120000, 'max' => 250000, 'desc' => 'Beli bumbu basah, sayuran garnish, dan telur'],
                    ['cat' => 'Gas & Energi', 'min' => 22000, 'max' => 45000, 'desc' => 'Isi ulang Gas LPG 3kg / operasional kompor'],
                    ['cat' => 'Es & Minuman', 'min' => 15000, 'max' => 30000, 'desc' => 'Es batu kristal higienis 2 karung'],
                ];

                foreach ($dailyExpenses as $exp) {
                    CashBook::create([
                        'branch_id' => $branch->id,
                        'type' => 'out',
                        'category' => $exp['cat'],
                        'amount' => rand($exp['min'] / 1000, $exp['max'] / 1000) * 1000,
                        'transaction_date' => $dateString,
                        'description' => $exp['desc'],
                        'created_by' => $adminUser->id,
                    ]);
                }

                // Generate Orders for today
                for ($o = 0; $o < $orderCountToday; $o++) {
                    // Realistic hourly footfall distribution
                    // 11:30 - 14:00 (Lunch peak: 35%)
                    // 15:00 - 17:30 (Afternoon: 20%)
                    // 18:00 - 21:30 (Dinner rush peak: 45%)
                    $randDistribution = rand(1, 100);
                    if ($randDistribution <= 35) {
                        $hour = rand(11, 13);
                        $minute = rand(15, 59);
                    } elseif ($randDistribution <= 55) {
                        $hour = rand(15, 17);
                        $minute = rand(0, 59);
                    } else {
                        $hour = rand(18, 21);
                        $minute = rand(0, 55);
                    }

                    $orderTime = $currentDate->copy()->setTime($hour, $minute, rand(0, 59));

                    // Order Type: Dine In (55%), Take Away (35%), Delivery (10%)
                    $otRoll = rand(1, 100);
                    if ($otRoll <= 55) {
                        $chosenOt = $orderTypes->firstWhere('code', 'dine_in') ?? $orderTypes->first();
                    } elseif ($otRoll <= 90) {
                        $chosenOt = $orderTypes->firstWhere('code', 'take_away') ?? $orderTypes->first();
                    } else {
                        $chosenOt = $orderTypes->firstWhere('code', 'delivery') ?? $orderTypes->last();
                    }

                    $orderNo = sprintf('HRS-%s-%s-%04d', $branch->code, $currentDate->format('ymd'), $orderIndex++);
                    $orderUuid = (string) Str::uuid();

                    // Pick 1 to 4 random products
                    $itemCount = rand(1, 3);
                    $selectedProducts = $products->random(min($itemCount, $products->count()));

                    $subtotal = 0;
                    $itemsData = [];

                    foreach ($selectedProducts as $prod) {
                        $qty = rand(1, 2);
                        $unitPrice = (int) ($prod->manual_cost ? $prod->manual_cost * 1.6 : 28000);

                        // If has variants
                        $variantName = null;
                        $variantId = null;
                        if ($prod->has_variants && $prod->variants->isNotEmpty()) {
                            $chosenVar = $prod->variants->random();
                            $variantId = $chosenVar->id;
                            $variantName = $chosenVar->name;
                            $unitPrice = (int) ($chosenVar->price ?: $unitPrice);
                        }

                        // Modifiers
                        $modAdditions = 0;
                        $modItems = [];
                        if ($prod->modifierGroups->isNotEmpty()) {
                            foreach ($prod->modifierGroups as $mg) {
                                if ($mg->modifiers->isNotEmpty() && ($mg->is_required || rand(1, 10) > 5)) {
                                    $mod = $mg->modifiers->random();
                                    $modAdditions += (int) $mod->price;
                                    $modItems[] = $mod;
                                }
                            }
                        }

                        $itemFinalPrice = $unitPrice + $modAdditions;
                        $itemSubtotal = $itemFinalPrice * $qty;
                        $subtotal += $itemSubtotal;

                        $itemsData[] = [
                            'product' => $prod,
                            'variant_id' => $variantId,
                            'variant_name' => $variantName,
                            'quantity' => $qty,
                            'unit_price' => $itemFinalPrice,
                            'subtotal' => $itemSubtotal,
                            'modifiers' => $modItems,
                        ];
                    }

                    $taxPercent = (float) $branch->tax_percent;
                    $taxAmount = (int) round(($subtotal * $taxPercent) / 100);
                    $rawTotal = $subtotal + $taxAmount;

                    // Rounding nearest 100
                    $roundedTotal = (int) (round($rawTotal / 100) * 100);
                    $roundingAmount = $roundedTotal - $rawTotal;

                    // 2% of orders are voided (fraud/cancellation simulation)
                    $isVoid = (rand(1, 100) <= 2);
                    $status = $isVoid ? 'voided' : 'completed';

                    $order = Order::create([
                        'uuid' => $orderUuid,
                        'order_no' => $orderNo,
                        'branch_id' => $branch->id,
                        'user_id' => $adminUser->id,
                        'order_type_id' => $chosenOt->id,
                        'status' => $status,
                        'business_date' => $dateString,
                        'subtotal' => $subtotal,
                        'promo_discount_amount' => 0,
                        'voucher_discount_amount' => 0,
                        'loyalty_discount_amount' => 0,
                        'manual_discount_amount' => 0,
                        'service_charge_amount' => 0,
                        'tax_amount' => $taxAmount,
                        'rounding_amount' => $roundingAmount,
                        'total_amount' => $roundedTotal,
                        'paid_amount' => $roundedTotal,
                        'change_amount' => 0,
                        'client_created_at' => $orderTime,
                        'synced_at' => $orderTime->copy()->addSeconds(rand(5, 30)),
                        'sync_status' => 'accepted',
                        'void_reason' => $isVoid ? 'Pelanggan membatalkan pesanan (ganti menu)' : null,
                        'voided_by' => $isVoid ? $adminUser->id : null,
                        'voided_at' => $isVoid ? $orderTime->copy()->addMinutes(3) : null,
                    ]);

                    foreach ($itemsData as $it) {
                        $orderItem = OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $it['product']->id,
                            'product_variant_id' => $it['variant_id'],
                            'product_name' => $it['product']->name,
                            'variant_name' => $it['variant_name'],
                            'order_type_name_snapshot' => $chosenOt->name,
                            'quantity' => $it['quantity'],
                            'unit_price' => $it['unit_price'],
                            'subtotal' => $it['subtotal'],
                        ]);

                        foreach ($it['modifiers'] as $m) {
                            OrderItemModifier::create([
                                'order_item_id' => $orderItem->id,
                                'modifier_id' => $m->id,
                                'modifier_name' => $m->name,
                                'unit_price' => (int) $m->price,
                                'quantity' => 1,
                            ]);
                        }
                    }

                    if (!$isVoid) {
                        // Payment method: Cash (60%), QRIS (35%), Transfer/EDC (5%)
                        $pmRoll = rand(1, 100);
                        if ($pmRoll <= 60) {
                            $pm = $paymentMethods->firstWhere('type', 'cash') ?? $paymentMethods->first();
                        } elseif ($pmRoll <= 95) {
                            $pm = $paymentMethods->firstWhere('type', 'qris') ?? $paymentMethods->skip(1)->first() ?? $paymentMethods->first();
                        } else {
                            $pm = $paymentMethods->last();
                        }

                        Payment::create([
                            'order_id' => $order->id,
                            'payment_method_id' => $pm->id,
                            'payment_method_name_snapshot' => $pm->name,
                            'amount' => $roundedTotal,
                            'paid_at' => $orderTime,
                        ]);
                    }
                }
            }
        }

        // Add 2 Global HQ CashBook transactions (e.g. Kantor Pusat)
        CashBook::create([
            'branch_id' => null,
            'type' => 'out',
            'category' => 'Operasional Kantor',
            'amount' => 1500000,
            'transaction_date' => $now->copy()->subDays(5)->toDateString(),
            'description' => 'Biaya promosi digital & konten media sosial bulanan',
            'created_by' => $adminUser->id,
        ]);

        CashBook::create([
            'branch_id' => null,
            'type' => 'in',
            'category' => 'Suntikan Modal',
            'amount' => 5000000,
            'transaction_date' => $now->copy()->subDays(15)->toDateString(),
            'description' => 'Suntikan modal kerja ekspansi bahan baku dari Owner',
            'created_by' => $adminUser->id,
        ]);
    }
}
