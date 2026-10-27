<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashBook;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    protected Business $business;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $adminUser;
    protected Category $category;
    protected OrderType $orderType;
    protected PaymentMethod $paymentMethod;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->business = Business::firstOrCreate(
            ['code' => 'TEST-BIZ'],
            ['name' => 'Hiroshima Street Food', 'phone' => '0812345678', 'email' => 'test@hiroshima.id']
        );

        $this->branch1 = Branch::firstOrCreate(
            ['code' => 'BR-01'],
            [
                'business_id' => $this->business->id,
                'name' => 'Hiroshima Cikini',
                'tax_percent' => 10,
                'service_charge_percent' => 0,
                'tax_included' => false,
                'rounding_mode' => 'round_nearest_100',
                'discount_percent_threshold' => 10,
                'discount_amount_threshold' => 20000,
                'printer_paper_width' => '58mm',
                'is_active' => true,
            ]
        );

        $this->branch2 = Branch::firstOrCreate(
            ['code' => 'BR-02'],
            [
                'business_id' => $this->business->id,
                'name' => 'Hiroshima Tebet',
                'tax_percent' => 10,
                'service_charge_percent' => 0,
                'tax_included' => false,
                'rounding_mode' => 'round_nearest_100',
                'discount_percent_threshold' => 10,
                'discount_amount_threshold' => 20000,
                'printer_paper_width' => '58mm',
                'is_active' => true,
            ]
        );

        $this->adminUser = User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@hiroshima.id',
                'password' => Hash::make('password123'),
                'role' => 'central_admin',
                'is_active' => true,
            ]
        );

        $this->category = Category::firstOrCreate(
            ['code' => 'KATSU'],
            ['name' => 'Katsu & Rice', 'target_printer' => 'kitchen', 'sort_order' => 1, 'is_active' => true]
        );

        $this->orderType = OrderType::firstOrCreate(
            ['code' => 'dine_in'],
            ['name' => 'Dine In', 'sort_order' => 1, 'is_active' => true, 'is_default' => true]
        );

        $this->paymentMethod = PaymentMethod::firstOrCreate(
            ['id' => 1],
            ['code' => 'cash', 'name' => 'Tunai', 'type' => 'cash', 'is_active' => true]
        );

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Chicken Katsu Curry',
            'sku' => 'TEST-KTS-' . Str::random(4),
            'manual_cost' => 15000,
            'has_variants' => false,
            'is_active' => true,
        ]);
    }

    public function test_dashboard_renders_successfully(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard Eksekutif &amp; Operasional', false);
        $response->assertSee('Total Penjualan POS');
        $response->assertSee('Puncak Trafik (Peak Hour)');
        $response->assertSee('Overview Performa Tiap Cabang');
        $response->assertSee('Tren Penjualan');
        $response->assertSee('Distribusi Trafik Jam Sibuk');
    }

    public function test_dashboard_supports_filter_presets_and_date_ranges(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Preset Hari Ini
        $respToday = $this->get(route('dashboard', ['preset' => 'today']));
        $respToday->assertStatus(200);

        // 2. Preset Minggu Ini
        $respWeek = $this->get(route('dashboard', ['preset' => 'this_week']));
        $respWeek->assertStatus(200);

        // 3. Preset Bulan Ini
        $respMonth = $this->get(route('dashboard', ['preset' => 'this_month']));
        $respMonth->assertStatus(200);

        // 4. Preset Tahun Ini
        $respYear = $this->get(route('dashboard', ['preset' => 'this_year']));
        $respYear->assertStatus(200);

        // 5. Custom Date Range
        $respCustom = $this->get(route('dashboard', [
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));
        $respCustom->assertStatus(200);

        // 6. Branch Filter
        $respBranch = $this->get(route('dashboard', ['branch_id' => $this->branch1->id]));
        $respBranch->assertStatus(200);
        $respBranch->assertSee($this->branch1->name);
    }

    public function test_dashboard_calculates_branch_overview_and_traffic(): void
    {
        $this->actingAs($this->adminUser);

        $now = now('Asia/Jakarta');

        // Create an order for branch 1
        $order = Order::create([
            'uuid' => (string) Str::uuid(),
            'order_no' => 'HRS-TEST-001',
            'branch_id' => $this->branch1->id,
            'user_id' => $this->adminUser->id,
            'order_type_id' => $this->orderType->id,
            'status' => 'completed',
            'business_date' => $now->toDateString(),
            'subtotal' => 35000,
            'tax_amount' => 3500,
            'total_amount' => 38500,
            'paid_amount' => 38500,
            'client_created_at' => $now->copy()->setTime(13, 30),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'order_type_name_snapshot' => $this->orderType->name,
            'quantity' => 1,
            'unit_price' => 35000,
            'subtotal' => 35000,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_method_name_snapshot' => $this->paymentMethod->name,
            'amount' => 38500,
            'paid_at' => $now,
        ]);

        // Create cashbook entry
        CashBook::create([
            'branch_id' => $this->branch1->id,
            'type' => 'out',
            'category' => 'Bahan Baku & Bumbu',
            'amount' => 15000,
            'transaction_date' => $now->toDateString(),
            'description' => 'Beli bumbu tambahan',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get(route('dashboard', ['preset' => 'today']));
        $response->assertStatus(200);

        // Verify metrics
        $response->assertSee('Rp 38.500');
        $response->assertSee('1 Struk');
        $response->assertSee($this->branch1->name);
        $response->assertSee($this->product->name);
    }
}
