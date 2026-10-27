<?php

namespace Tests\Feature;

use App\Exceptions\HoldBillPendingException;
use App\Exceptions\UnfinalizedTransactionException;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\HoldBillService;
use App\Services\ShiftService;
use App\Services\Sync\ConflictResolver;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosShiftAndHoldBillTest extends TestCase
{
    protected Branch $branch;
    protected User $cashier;
    protected ShiftService $shiftService;
    protected HoldBillService $holdBillService;
    protected OrderType $orderType;
    protected Product $productA;
    protected Product $productB;
    protected PaymentMethod $cashMethod;
    protected PaymentMethod $qrisMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shiftService = app(ShiftService::class);
        $this->holdBillService = app(HoldBillService::class);

        // Reset Settings
        Setting::set('hold_bill_enabled', '1', 'general');

        $business = Business::firstOrCreate(
            ['code' => 'HRS'],
            ['name' => 'Hiroshima Japanese Food']
        );

        $this->branch = Branch::firstOrCreate(
            ['code' => 'HRS01'],
            [
                'business_id' => $business->id,
                'name' => 'Hiroshima Jakarta Pusat',
                'tax_percent' => 11,
                'service_charge_percent' => 0,
                'tax_included' => false,
                'rounding_mode' => 'round_100',
                'discount_percent_threshold' => 10,
                'discount_amount_threshold' => 20000,
            ]
        );

        $this->orderType = OrderType::firstOrCreate(
            ['code' => 'dine_in'],
            ['name' => 'Dine In', 'is_default' => true, 'is_active' => true]
        );

        $category = Category::firstOrCreate(
            ['code' => 'ramen_donburi'],
            ['name' => 'Ramen & Donburi', 'is_active' => true]
        );

        $this->productA = Product::firstOrCreate(
            ['sku' => 'RAMEN-01'],
            [
                'category_id' => $category->id,
                'name' => 'Hiroshima Special Ramen',
                'is_active' => true,
            ]
        );

        $this->productB = Product::firstOrCreate(
            ['sku' => 'GYOZA-01'],
            [
                'category_id' => $category->id,
                'name' => 'Gyoza Panggang 5 Pcs',
                'is_active' => true,
            ]
        );

        $this->cashMethod = PaymentMethod::firstOrCreate(
            ['code' => 'cash'],
            ['name' => 'Tunai', 'type' => 'cash', 'is_cash' => true, 'is_active' => true]
        );

        $this->qrisMethod = PaymentMethod::firstOrCreate(
            ['code' => 'qris'],
            ['name' => 'QRIS GoPay', 'type' => 'qris', 'is_cash' => false, 'is_active' => true]
        );

        $this->cashier = User::firstOrCreate(
            ['username' => 'kasir_test'],
            [
                'branch_id' => $this->branch->id,
                'name' => 'Kasir Uji Shift',
                'email' => 'kasir_test@hiroshima.id',
                'password' => Hash::make('secret123'),
                'role' => 'cashier',
                'is_active' => true,
            ]
        );

        // Ensure no leftover active shift for cashier
        Shift::where('user_id', $this->cashier->id)->where('status', 'open')->update(['status' => 'closed', 'closed_at' => now('Asia/Jakarta')]);
    }

    /**
     * Skenario 1: Transaksi ditolak jika belum Open Shift.
     */
    public function test_01_transaction_rejected_if_no_open_shift(): void
    {
        Sanctum::actingAs($this->cashier);

        // Try direct order store with a non-existent or closed shift_id
        $response = $this->postJson('/api/v1/orders', [
            'shift_id' => 999999, // non-existent
            'order_type_id' => $this->orderType->id,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'product_name' => $this->productA->name,
                    'quantity' => 1,
                    'unit_price' => 50000,
                ],
            ],
        ]);

        $response->assertStatus(422); // Validation error: shift_id exists check
    }

    /**
     * Skenario 2: Transaksi berhasil setelah Open Shift.
     */
    public function test_02_transaction_succeeds_after_open_shift(): void
    {
        Sanctum::actingAs($this->cashier);

        // 1. Open shift
        $shift = $this->shiftService->open($this->branch->id, $this->cashier->id, null, 200000);
        $this->assertEquals('open', $shift->status);

        // 2. Perform transaction
        $response = $this->postJson('/api/v1/orders', [
            'shift_id' => $shift->id,
            'order_type_id' => $this->orderType->id,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'product_name' => $this->productA->name,
                    'quantity' => 2,
                    'unit_price' => 50000,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'payment_method_name' => 'Tunai',
                    'amount' => 111000,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));

        $orderId = $response->json('order.id');
        $order = Order::find($orderId);
        $this->assertEquals('completed', $order->status);
        $this->assertEquals($shift->id, $order->shift_id);
    }

    /**
     * Skenario 3: Transaksi ditolak setelah 23 jam, meskipun scheduler terlambat.
     */
    public function test_03_transaction_rejected_after_23_hours_even_if_scheduler_delayed(): void
    {
        Sanctum::actingAs($this->cashier);

        // Shift opened 23 hours and 5 minutes ago, but scheduler has NOT run (status is still 'open' in DB)
        $shift = Shift::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'opened_at' => now('Asia/Jakarta')->subHours(23)->subMinutes(5),
            'starting_cash' => 200000,
            'status' => 'open',
        ]);

        $response = $this->postJson('/api/v1/orders', [
            'shift_id' => $shift->id,
            'order_type_id' => $this->orderType->id,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'product_name' => $this->productA->name,
                    'quantity' => 1,
                    'unit_price' => 50000,
                ],
            ],
        ]);

        $response->assertStatus(403);
        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('23 jam', $response->json('message'));

        // Backend must have automatically marked the shift as auto_closed
        $shift->refresh();
        $this->assertEquals('auto_closed', $shift->status);
        $this->assertEquals('auto', $shift->close_type);
    }

    /**
     * Skenario 4: Auto-close memperbarui status shift dan mencatat alasannya.
     */
    public function test_04_auto_close_updates_shift_status_and_records_reason(): void
    {
        // Shift opened 24 hours ago
        $shift = Shift::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'opened_at' => now('Asia/Jakarta')->subHours(24),
            'starting_cash' => 150000,
            'status' => 'open',
        ]);

        $closedCount = $this->shiftService->autoCloseExpiredShifts();
        $this->assertEquals(1, $closedCount);

        $shift->refresh();
        $this->assertEquals('auto_closed', $shift->status);
        $this->assertEquals('auto', $shift->close_type);
        $this->assertNotNull($shift->close_reason);
        $this->assertStringContainsString('23 jam', $shift->close_reason);
        $this->assertNotNull($shift->closed_at);
    }

    /**
     * Skenario 5: Hold Bill berfungsi saat diaktifkan.
     */
    public function test_05_hold_bill_works_when_enabled(): void
    {
        Sanctum::actingAs($this->cashier);
        Setting::set('hold_bill_enabled', '1', 'general');

        $shift = $this->shiftService->open($this->branch->id, $this->cashier->id, null, 200000);

        $response = $this->postJson('/api/v1/orders/hold', [
            'shift_id' => $shift->id,
            'order_type_id' => $this->orderType->id,
            'hold_reference' => 'Meja 5 - Pak Bambang',
            'hold_notes' => 'Tunggu teman datang',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'product_name' => $this->productA->name,
                    'quantity' => 2,
                    'unit_price' => 50000,
                ],
                [
                    'product_id' => $this->productB->id,
                    'product_name' => $this->productB->name,
                    'quantity' => 1,
                    'unit_price' => 25000,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));

        $orderId = $response->json('order.id');
        $order = Order::find($orderId);
        $this->assertEquals('on_hold', $order->status);
        $this->assertEquals('Meja 5 - Pak Bambang', $order->hold_reference);
        $this->assertEquals(2, $order->items()->count());

        // Hold Bill is NOT a completed sale
        $this->assertDatabaseMissing('orders', ['id' => $orderId, 'status' => 'completed']);
    }

    /**
     * Skenario 6: Hold Bill tidak dapat dibuat saat dinonaktifkan.
     */
    public function test_06_hold_bill_cannot_be_created_when_disabled(): void
    {
        Sanctum::actingAs($this->cashier);

        // Turn off Hold Bill setting in Back Office
        Setting::set('hold_bill_enabled', '0', 'general');

        $shift = $this->shiftService->open($this->branch->id, $this->cashier->id, null, 200000);

        $response = $this->postJson('/api/v1/orders/hold', [
            'shift_id' => $shift->id,
            'order_type_id' => $this->orderType->id,
            'hold_reference' => 'Meja 2',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'product_name' => $this->productA->name,
                    'quantity' => 1,
                    'unit_price' => 50000,
                ],
            ],
        ]);

        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('dinonaktifkan', $response->json('message'));
    }

    /**
     * Skenario 7: Bill dapat dilanjutkan tanpa kehilangan detail.
     */
    public function test_07_bill_can_be_resumed_without_loss_of_detail(): void
    {
        Sanctum::actingAs($this->cashier);
        Setting::set('hold_bill_enabled', '1', 'general');

        $shift = $this->shiftService->open($this->branch->id, $this->cashier->id, null, 200000);

        $heldOrder = $this->holdBillService->hold($this->cashier, $this->branch, $shift, [
            'order_type_id' => $this->orderType->id,
            'hold_reference' => 'VIP 1 - Rapat Tim',
            'hold_notes' => 'Jangan pakai daun bawang',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'product_name' => $this->productA->name,
                    'quantity' => 3,
                    'unit_price' => 50000,
                    'notes' => 'Pedas level 3',
                ],
            ],
        ]);

        $this->assertEquals('on_hold', $heldOrder->status);

        // Resume bill
        $resumed = $this->holdBillService->resume($heldOrder->id, $this->cashier, $shift);
        $this->assertEquals('open', $resumed->status);
        $this->assertEquals(1, $resumed->items->count());
        $this->assertEquals('Hiroshima Special Ramen', $resumed->items->first()->product_name);
        $this->assertEquals(3, $resumed->items->first()->quantity);
        $this->assertEquals('Pedas level 3', $resumed->items->first()->notes);
    }

    /**
     * Skenario 8: Close Shift ditolak ketika masih ada Hold Bill.
     */
    public function test_08_close_shift_rejected_when_hold_bills_remain(): void
    {
        $shift = $this->shiftService->open($this->branch->id, $this->cashier->id, null, 200000);

        // Create an open hold bill on this shift
        $this->holdBillService->hold($this->cashier, $this->branch, $shift, [
            'order_type_id' => $this->orderType->id,
            'hold_reference' => 'Meja 9',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'product_name' => $this->productA->name,
                    'quantity' => 1,
                    'unit_price' => 50000,
                ],
            ],
        ]);

        // Attempting to close shift must throw HoldBillPendingException
        $this->expectException(HoldBillPendingException::class);
        $this->shiftService->close($shift, 200000);
    }

    /**
     * Skenario 9: Close Shift ditolak ketika pembayaran atau checkout belum selesai.
     */
    public function test_09_close_shift_rejected_when_transactions_unfinalized(): void
    {
        $shift = $this->shiftService->open($this->branch->id, $this->cashier->id, null, 200000);

        // Create an unfinalized order (status = 'open')
        Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'order_no' => 'HRS01-POS-TEST-UNFIN',
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => $this->orderType->id,
            'status' => 'open', // unpaid/in progress
            'business_date' => now('Asia/Jakarta')->toDateString(),
            'total_amount' => 50000,
            'client_created_at' => now('Asia/Jakarta'),
        ]);

        // Close shift must throw UnfinalizedTransactionException
        $this->expectException(UnfinalizedTransactionException::class);
        $this->shiftService->close($shift, 200000);
    }

    /**
     * Skenario 10: Total kuantitas dan penjualan per menu sesuai dengan transaksi aktual.
     */
    public function test_10_menu_summary_quantities_and_sales_match_actual_transactions(): void
    {
        $shift = $this->shiftService->open($this->branch->id, $this->cashier->id, null, 200000);

        // Transaction 1: 4 Ramen (4 x 50.000 = 200.000)
        $order1 = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'order_no' => 'HRS01-POS-T1',
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => $this->orderType->id,
            'status' => 'completed',
            'business_date' => now('Asia/Jakarta')->toDateString(),
            'subtotal' => 200000,
            'total_amount' => 200000,
            'client_created_at' => now('Asia/Jakarta'),
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $this->productA->id,
            'product_name' => $this->productA->name,
            'order_type_name_snapshot' => 'Dine In',
            'quantity' => 4,
            'unit_price' => 50000,
            'subtotal' => 200000,
        ]);

        // Transaction 2: 3 Gyoza (3 x 25.000 = 75.000)
        $order2 = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'order_no' => 'HRS01-POS-T2',
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => $this->orderType->id,
            'status' => 'completed',
            'business_date' => now('Asia/Jakarta')->toDateString(),
            'subtotal' => 75000,
            'total_amount' => 75000,
            'client_created_at' => now('Asia/Jakarta'),
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $this->productB->id,
            'product_name' => $this->productB->name,
            'order_type_name_snapshot' => 'Dine In',
            'quantity' => 3,
            'unit_price' => 25000,
            'subtotal' => 75000,
        ]);

        // Close shift
        $closedShift = $this->shiftService->close($shift, 475000);
        $report = $this->shiftService->generateCloseShiftReport($closedShift);

        $menuItems = $report['menu_summary']['items'];
        $this->assertCount(2, $menuItems);

        // Check Ramen item
        $ramen = collect($menuItems)->firstWhere('menu', $this->productA->name);
        $this->assertNotNull($ramen);
        $this->assertEquals(4, $ramen['total_qty']);
        $this->assertEquals(200000, $ramen['total_sales']);

        // Check Gyoza item
        $gyoza = collect($menuItems)->firstWhere('menu', $this->productB->name);
        $this->assertNotNull($gyoza);
        $this->assertEquals(3, $gyoza['total_qty']);
        $this->assertEquals(75000, $gyoza['total_sales']);

        // Check TOTAL footer row
        $this->assertEquals(7, $report['menu_summary']['total']['total_qty']);
        $this->assertEquals(275000, $report['menu_summary']['total']['total_sales']);
    }

    /**
     * Skenario 11: Total laporan sesuai dengan transaksi selesai pada shift tersebut.
     */
    public function test_11_report_totals_strictly_match_completed_shift_transactions(): void
    {
        $shift = $this->shiftService->open($this->branch->id, $this->cashier->id, null, 100000);

        // Completed Order (Total: 100.000, subtotal 100.000, discount 10.000, tax 10.000)
        $comp = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'order_no' => 'HRS01-POS-COMP',
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => $this->orderType->id,
            'status' => 'completed',
            'business_date' => now('Asia/Jakarta')->toDateString(),
            'subtotal' => 100000,
            'manual_discount_amount' => 10000,
            'tax_amount' => 10000,
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'client_created_at' => now('Asia/Jakarta'),
        ]);

        Payment::create([
            'order_id' => $comp->id,
            'shift_id' => $shift->id,
            'payment_method_id' => $this->cashMethod->id,
            'payment_method_name_snapshot' => 'Tunai',
            'amount' => 100000,
            'paid_at' => now('Asia/Jakarta'),
        ]);

        // Voided Order (must NOT be counted in net sales or gross sales)
        Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'order_no' => 'HRS01-POS-VOID',
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => $this->orderType->id,
            'status' => 'voided',
            'business_date' => now('Asia/Jakarta')->toDateString(),
            'subtotal' => 50000,
            'total_amount' => 50000,
            'client_created_at' => now('Asia/Jakarta'),
        ]);

        $closedShift = $this->shiftService->close($shift, 200000);
        $report = $this->shiftService->generateCloseShiftReport($closedShift);

        $summary = $report['transaction_summary'];
        $this->assertEquals(1, $summary['completed_transaction_count']);
        $this->assertEquals(100000, $summary['gross_sales']);
        $this->assertEquals(10000, $summary['total_discount']);
        $this->assertEquals(10000, $summary['tax_amount']);
        $this->assertEquals(100000, $summary['net_sales']);
        $this->assertEquals(1, $summary['void_refund']['void_count']);
        $this->assertEquals(50000, $summary['void_refund']['void_amount']);
        $this->assertEquals(200000, $summary['cash_reconciliation']['expected_cash']);
    }

    /**
     * Skenario 12: Laporan tetap benar ketika shift melewati tengah malam.
     */
    public function test_12_report_correctly_aggregates_orders_crossing_midnight(): void
    {
        $day1 = Carbon::parse('2026-10-26 21:00:00');
        $day2 = Carbon::parse('2026-10-27 02:00:00');

        // Shift opened on Day 1 at 21:00
        $shift = Shift::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'opened_at' => $day1,
            'starting_cash' => 200000,
            'status' => 'open',
        ]);

        // Order 1 on Day 1 (before midnight, 23:30)
        $o1 = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'order_no' => 'HRS01-POS-DAY1',
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => $this->orderType->id,
            'status' => 'completed',
            'business_date' => '2026-10-26',
            'subtotal' => 50000,
            'total_amount' => 50000,
            'client_created_at' => $day1->copy()->addHours(2)->addMinutes(30),
        ]);
        OrderItem::create([
            'order_id' => $o1->id,
            'product_id' => $this->productA->id,
            'product_name' => $this->productA->name,
            'order_type_name_snapshot' => 'Dine In',
            'quantity' => 1,
            'unit_price' => 50000,
            'subtotal' => 50000,
        ]);

        // Order 2 on Day 2 (after midnight, 01:30)
        $o2 = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'order_no' => 'HRS01-POS-DAY2',
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => $this->orderType->id,
            'status' => 'completed',
            'business_date' => '2026-10-27',
            'subtotal' => 75000,
            'total_amount' => 75000,
            'client_created_at' => $day2,
        ]);
        OrderItem::create([
            'order_id' => $o2->id,
            'product_id' => $this->productB->id,
            'product_name' => $this->productB->name,
            'order_type_name_snapshot' => 'Dine In',
            'quantity' => 3,
            'unit_price' => 25000,
            'subtotal' => 75000,
        ]);

        $closedShift = $this->shiftService->close($shift, 200000);
        $report = $this->shiftService->generateCloseShiftReport($closedShift);

        // Report must include both orders across midnight
        $this->assertEquals(2, $report['transaction_summary']['completed_transaction_count']);
        $this->assertEquals(125000, $report['transaction_summary']['gross_sales']);
        $this->assertEquals(4, $report['menu_summary']['total']['total_qty']);
    }

    /**
     * Skenario 13: Cetak ulang tidak menggandakan transaksi.
     */
    public function test_13_reprinting_report_is_strictly_idempotent(): void
    {
        $shift = $this->shiftService->open($this->branch->id, $this->cashier->id, null, 100000);

        $order = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'order_no' => 'HRS01-POS-REPRINT-TEST',
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => $this->orderType->id,
            'status' => 'completed',
            'business_date' => now('Asia/Jakarta')->toDateString(),
            'subtotal' => 50000,
            'total_amount' => 50000,
            'client_created_at' => now('Asia/Jakarta'),
        ]);

        $closedShift = $this->shiftService->close($shift, 100000);

        $orderCountBefore = Order::count();
        $paymentCountBefore = Payment::count();

        // Print 1
        $report1 = $this->shiftService->generateCloseShiftReport($closedShift);
        // Print 2 (Reprint)
        $report2 = $this->shiftService->generateCloseShiftReport($closedShift);
        // Print 3 (Reprint)
        $report3 = $this->shiftService->generateCloseShiftReport($closedShift);

        $this->assertEquals($orderCountBefore, Order::count());
        $this->assertEquals($paymentCountBefore, Payment::count());
        $this->assertEquals($report1['transaction_summary']['net_sales'], $report2['transaction_summary']['net_sales']);
        $this->assertEquals($report2['transaction_summary']['net_sales'], $report3['transaction_summary']['net_sales']);
    }

    /**
     * Skenario 14: Request API langsung tidak dapat melewati validasi shift.
     */
    public function test_14_direct_api_calls_cannot_bypass_shift_validation(): void
    {
        Sanctum::actingAs($this->cashier);

        // 1. Direct API order store with closed shift
        $closedShift = Shift::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'opened_at' => now('Asia/Jakarta')->subHours(5),
            'closed_at' => now('Asia/Jakarta')->subHours(1),
            'starting_cash' => 100000,
            'status' => 'closed',
        ]);

        $resp = $this->postJson('/api/v1/orders', [
            'shift_id' => $closedShift->id,
            'order_type_id' => $this->orderType->id,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'product_name' => $this->productA->name,
                    'quantity' => 1,
                    'unit_price' => 50000,
                ],
            ],
        ]);
        $resp->assertStatus(403);

        // 2. Direct offline push without valid shift is rejected by ConflictResolver
        $conflictResolver = app(ConflictResolver::class);
        $evaluation = $conflictResolver->evaluateOrder([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shift_id' => null, // missing shift
            'client_created_at' => now('Asia/Jakarta')->toIso8601String(),
        ], $this->branch);

        $this->assertEquals('rejected', $evaluation['status']);
        $this->assertStringContainsString('Open Shift', $evaluation['reason']);
    }

    /**
     * Skenario 15: Tidak ada dependensi terhadap sistem stok atau inventory.
     */
    public function test_15_zero_inventory_or_stock_dependency(): void
    {
        Sanctum::actingAs($this->cashier);

        // Ensure orders and shifts function completely without requiring stock, stock reservations, or inventory records
        $shift = $this->shiftService->open($this->branch->id, $this->cashier->id, null, 250000);
        $this->assertEquals('open', $shift->status);

        // Create Order and complete it
        $resp = $this->postJson('/api/v1/orders', [
            'shift_id' => $shift->id,
            'order_type_id' => $this->orderType->id,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'product_name' => $this->productA->name,
                    'quantity' => 100, // Large quantity - must not check stock availability
                    'unit_price' => 50000,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 5550000,
                ],
            ],
        ]);

        $resp->assertStatus(201);
        $this->assertTrue($resp->json('success'));

        // Close shift
        $closed = $this->shiftService->close($shift, 5800000);
        $this->assertEquals('closed', $closed->status);
    }
}
