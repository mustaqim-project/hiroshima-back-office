<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @req FR-POS-10, FSD-08
 */
class PosVoidOrderTest extends TestCase
{
    protected Branch $branch;
    protected User $cashier;
    protected Shift $shift;
    protected OrderType $orderType;
    protected Product $product;
    protected PaymentMethod $cashMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::firstOrCreate(
            ['code' => 'HRS'],
            ['name' => 'Hiroshima Japanese Food']
        );

        $this->branch = Branch::firstOrCreate(
            ['code' => 'HRS-TEST'],
            [
                'business_id' => $business->id,
                'name' => 'Hiroshima Void Test Branch',
                'pin_void_hash' => Hash::make('123456'),
                'is_active' => true,
            ]
        );

        // Ensure pin_void_hash is hashed 123456
        $this->branch->update(['pin_void_hash' => Hash::make('123456')]);

        $this->cashier = User::firstOrCreate(
            ['email' => 'cashier.void@hiroshima.co.id'],
            [
                'name' => 'Cashier Void',
                'username' => 'cashier_void',
                'password' => Hash::make('password123'),
                'branch_id' => $this->branch->id,
                'role' => 'cashier',
                'is_active' => true,
            ]
        );

        $this->orderType = OrderType::firstOrCreate(
            ['name' => 'Dine In Test'],
            ['code' => 'DINE_IN_TEST', 'is_active' => true]
        );

        $category = \App\Models\Category::firstOrCreate(
            ['code' => 'TAKOYAKI_TEST'],
            ['name' => 'Takoyaki Test', 'branch_id' => $this->branch->id, 'is_active' => true]
        );

        $this->product = Product::firstOrCreate(
            ['sku' => 'TEST-VOID-01'],
            [
                'branch_id' => $this->branch->id,
                'category_id' => $category->id,
                'name' => 'Takoyaki Special',
                'price' => 25000,
                'is_active' => true,
            ]
        );

        $this->cashMethod = PaymentMethod::firstOrCreate(
            ['code' => 'CASH'],
            ['name' => 'Tunai (Cash)', 'type' => 'cash', 'is_active' => true]
        );

        // Open shift for cashier
        $this->shift = Shift::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'opened_at' => now('Asia/Jakarta'),
            'starting_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'open',
        ]);
    }

    public function test_can_void_completed_order_by_id_with_valid_pin_and_reason(): void
    {
        Sanctum::actingAs($this->cashier);

        // Create completed order
        $order = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'order_no' => 'HRS-TEST-001',
            'branch_id' => $this->branch->id,
            'shift_id' => $this->shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => $this->orderType->id,
            'status' => 'completed',
            'business_date' => now('Asia/Jakarta')->toDateString(),
            'subtotal' => 50000,
            'total_amount' => 50000,
            'paid_amount' => 50000,
            'client_created_at' => now('Asia/Jakarta'),
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $this->cashMethod->id,
            'payment_method_name_snapshot' => 'Tunai (Cash)',
            'amount' => 50000,
            'status' => 'paid',
            'paid_at' => now('Asia/Jakarta'),
        ]);

        $this->shift->refresh();
        $initialExpectedCash = $this->shift->expected_cash;

        // Void the order by numeric ID
        $response = $this->postJson("/api/v1/orders/{$order->id}/void", [
            'pin_void' => '123456',
            'reason' => 'Pelanggan membatalkan pesanan',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transaksi berhasil dibatalkan (Void). Dana dikembalikan ke pelanggan.',
            ]);

        $order->refresh();
        $this->assertEquals('voided', $order->status);
        $this->assertEquals('Pelanggan membatalkan pesanan', $order->void_reason);
        $this->assertEquals($this->cashier->id, $order->voided_by);

        // Assert Refund was created
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'shift_id' => $this->shift->id,
            'amount' => 50000,
            'reason' => 'Pelanggan membatalkan pesanan',
        ]);

        // Assert shift expected cash excludes voided/refunded transaction
        $shiftService = app(ShiftService::class);
        $expectedCash = $shiftService->calculateExpectedCash($this->shift);
        $this->assertEquals(100000, $expectedCash);
    }

    public function test_can_void_completed_order_by_order_no_string(): void
    {
        Sanctum::actingAs($this->cashier);

        $orderNo = 'HRS-TEST-ORD-' . rand(1000, 9999);
        $order = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'order_no' => $orderNo,
            'branch_id' => $this->branch->id,
            'shift_id' => $this->shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => $this->orderType->id,
            'status' => 'completed',
            'business_date' => now('Asia/Jakarta')->toDateString(),
            'subtotal' => 30000,
            'total_amount' => 30000,
            'paid_amount' => 30000,
            'client_created_at' => now('Asia/Jakarta'),
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $this->cashMethod->id,
            'payment_method_name_snapshot' => 'Tunai (Cash)',
            'amount' => 30000,
            'status' => 'paid',
            'paid_at' => now('Asia/Jakarta'),
        ]);

        // Void using string order_no in URL
        $response = $this->postJson("/api/v1/orders/{$orderNo}/void", [
            'pin_void' => '123456',
            'reason' => 'Salah input menu makanan',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals('voided', $order->status);
    }

    public function test_rejects_void_with_invalid_pin(): void
    {
        Sanctum::actingAs($this->cashier);

        $order = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'order_no' => 'HRS-TEST-002',
            'branch_id' => $this->branch->id,
            'shift_id' => $this->shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => $this->orderType->id,
            'status' => 'completed',
            'business_date' => now('Asia/Jakarta')->toDateString(),
            'subtotal' => 20000,
            'total_amount' => 20000,
            'paid_amount' => 20000,
            'client_created_at' => now('Asia/Jakarta'),
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/void", [
            'pin_void' => '999999', // Wrong PIN
            'reason' => 'Batal transaksi',
        ]);

        $response->assertStatus(403);
        $order->refresh();
        $this->assertEquals('completed', $order->status);
    }

    public function test_recent_orders_endpoint_returns_list_for_branch(): void
    {
        Sanctum::actingAs($this->cashier);

        $response = $this->getJson('/api/v1/orders/recent');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'orders',
            ]);
    }
}
