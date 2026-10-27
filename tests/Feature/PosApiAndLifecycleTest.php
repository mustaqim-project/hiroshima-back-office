<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceActivationCode;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Order;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @req FR-AUTH-01, FR-AUTH-02, FR-AUTH-05, FR-AUTH-06, FR-DEV-01, FR-DEV-02, FR-POS-01, FR-POS-02, FR-POS-05, FR-POS-10, FR-OFF-01, FR-OFF-04, FSD-01, FSD-06, FSD-07, FSD-08, FSD-09, FSD-10, FSD-17, BR-25
 */
class PosApiAndLifecycleTest extends TestCase
{
    protected Business $business;
    protected Branch $branch;
    protected User $cashierUser;
    protected User $managerUser;
    protected OrderType $dineIn;
    protected PaymentMethod $cashPayment;
    protected Product $ramen;
    protected ModifierGroup $toppingGroup;
    protected Modifier $extraEgg;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->business = Business::create(['name' => 'Hiroshima F&B', 'code' => 'HRS']);
        $supervisorPin = password_hash('888888', PASSWORD_ARGON2ID);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Hiroshima Cikini',
            'code' => 'HRS-CKN',
            'tax_percent' => 10.00,
            'service_charge_percent' => 5.00,
            'tax_included' => false,
            'rounding_mode' => 'round_nearest_100',
            'pin_void_hash' => $supervisorPin,
            'pin_discount_hash' => $supervisorPin,
            'discount_percent_threshold' => 10.00,
            'discount_amount_threshold' => 20000,
            'is_active' => true,
        ]);

        $this->dineIn = OrderType::create([
            'name' => 'Dine In',
            'code' => 'dine_in',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->cashPayment = PaymentMethod::create([
            'name' => 'Tunai',
            'code' => 'cash',
            'type' => 'cash',
            'is_cash' => true,
            'is_active' => true,
        ]);

        \App\Models\BranchPaymentMethod::create([
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->cashPayment->id,
            'is_active' => true,
        ]);

        setPermissionsTeamId($this->branch->id);

        $cashierPin = password_hash('123456', PASSWORD_ARGON2ID);
        $this->cashierUser = User::create([
            'name' => 'Kasir Cikini',
            'username' => 'kasir_ckn',
            'email' => 'kasir@hiroshima.id',
            'password' => Hash::make('password123'),
            'pin_hash' => $cashierPin,
            'branch_id' => $this->branch->id,
            'role' => 'cashier',
            'is_active' => true,
        ]);
        $this->cashierUser->assignRole('cashier');

        $this->managerUser = User::create([
            'name' => 'Manager Cikini',
            'username' => 'spv_ckn',
            'email' => 'spv@hiroshima.id',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
            'role' => 'branch_manager',
            'is_active' => true,
        ]);
        $this->managerUser->assignRole('branch_manager');

        $category = Category::create([
            'name' => 'Ramen',
            'code' => 'RAMEN',
            'is_active' => true,
        ]);

        $this->ramen = Product::create([
            'category_id' => $category->id,
            'name' => 'Toripaitan Shio Ramen',
            'sku' => 'RAMEN-SHIO',
            'base_price' => 45000,
            'is_active' => true,
        ]);

        ProductPrice::create([
            'sellable_type' => 'Product',
            'sellable_id' => $this->ramen->id,
            'order_type_id' => $this->dineIn->id,
            'branch_id' => $this->branch->id,
            'price' => 45000,
        ]);

        $this->toppingGroup = ModifierGroup::create([
            'name' => 'Extra Topping',
            'selection_type' => 'multiple',
            'is_active' => true,
        ]);

        $this->extraEgg = Modifier::create([
            'modifier_group_id' => $this->toppingGroup->id,
            'name' => 'Ajitsuke Tamago',
            'price' => 8000,
            'is_active' => true,
        ]);

        $this->ramen->modifierGroups()->attach($this->toppingGroup->id);
    }

    public function test_api_login_with_email_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username_or_email' => 'kasir@hiroshima.id',
            'password_or_pin' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'token', 'user' => ['id', 'name', 'username', 'role', 'branch']]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_api_login_with_pin(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username_or_email' => 'kasir_ckn',
            'password_or_pin' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.username', 'kasir_ckn');
    }

    public function test_api_login_lockout_after_five_failed_attempts(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $res = $this->postJson('/api/v1/auth/login', [
                'username_or_email' => 'kasir_ckn',
                'password_or_pin' => 'wrongpin',
            ]);
            $res->assertStatus(401);
        }

        // 5th attempt must trigger 423 lockout
        $res5 = $this->postJson('/api/v1/auth/login', [
            'username_or_email' => 'kasir_ckn',
            'password_or_pin' => 'wrongpin',
        ]);
        $res5->assertStatus(423)
            ->assertJsonPath('success', false);

        $this->assertTrue($this->cashierUser->fresh()->isLocked());
    }

    public function test_api_device_activation(): void
    {
        $activationCode = DeviceActivationCode::create([
            'branch_id' => $this->branch->id,
            'code' => 'ACT-9999',
            'expires_at' => now()->addHours(24),
            'created_by' => $this->managerUser->id,
        ]);

        $response = $this->postJson('/api/v1/auth/device-activate', [
            'activation_code' => 'ACT-9999',
            'device_name' => 'Tablet Kasir 1',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('device.device_name', 'Tablet Kasir 1')
            ->assertJsonPath('device.branch_id', $this->branch->id);

        $this->assertNotNull($activationCode->fresh()->used_at);

        // Cannot reuse used activation code
        $reuse = $this->postJson('/api/v1/auth/device-activate', [
            'activation_code' => 'ACT-9999',
            'device_name' => 'Tablet Kasir 2',
        ]);
        $reuse->assertStatus(404);
    }

    public function test_authenticated_user_profile_and_logout(): void
    {
        $token = $this->cashierUser->createToken('test-token')->plainTextToken;

        $profileRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/user');

        $profileRes->assertStatus(200)
            ->assertJsonPath('id', $this->cashierUser->id)
            ->assertJsonPath('branch.id', $this->branch->id);

        $logoutRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $logoutRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Assert token is deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->cashierUser->id,
            'name' => 'test-token',
        ]);

        // Revoked token cannot access anymore (clear auth cache in test container)
        $this->app['auth']->forgetGuards();
        $afterLogout = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/user');
        $afterLogout->assertStatus(401);
    }

    public function test_cashier_login_assigned_to_branch_cannot_switch_branch(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username_or_email' => 'kasir_ckn',
            'password_or_pin' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.branch_id', $this->branch->id)
            ->assertJsonPath('user.branch.name', 'Hiroshima Cikini')
            ->assertJsonPath('user.can_switch_branch', false);

        $this->assertCount(1, $response->json('user.accessible_branches'));
    }

    public function test_owner_login_can_switch_branches(): void
    {
        $branch2 = \App\Models\Branch::create([
            'business_id' => $this->branch->business_id,
            'name' => 'Hiroshima Tebet',
            'code' => 'HRS-TBT',
            'tax_percent' => 10.00,
            'service_charge_percent' => 0.00,
            'tax_included' => false,
            'rounding_mode' => 'round_nearest_100',
            'pin_void_hash' => $this->branch->pin_void_hash,
            'pin_discount_hash' => $this->branch->pin_discount_hash,
            'is_active' => true,
        ]);

        $owner = User::create([
            'name' => 'Owner Hiroshima',
            'username' => 'owner',
            'email' => 'owner@hiroshima.id',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'branch_id' => null, // Multi branch access
            'is_active' => true,
        ]);
        $owner->assignRole('owner');

        $response = $this->postJson('/api/v1/auth/login', [
            'username_or_email' => 'owner',
            'password_or_pin' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.can_switch_branch', true);

        $this->assertGreaterThanOrEqual(2, count($response->json('user.accessible_branches')));

        $token = $response->json('token');

        // Check GET /branches endpoint
        $branchesRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/branches');

        $branchesRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('can_switch_branch', true);
    }

    public function test_api_supervisory_pin_authorization(): void
    {
        $token = $this->cashierUser->createToken('test-token')->plainTextToken;

        // Invalid PIN
        $failRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/authorize/pin', [
                'type' => 'void',
                'pin' => '000000',
                'reason' => 'Uji otorisasi salah',
            ]);
        $failRes->assertStatus(403);

        // Valid Supervisor PIN (888888)
        $passRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/authorize/pin', [
                'type' => 'void',
                'pin' => '888888',
                'reason' => 'Otorisasi void disetujui',
            ]);
        $passRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_api_shift_lifecycle_and_cash_movement(): void
    {
        $token = $this->cashierUser->createToken('test-token')->plainTextToken;

        // Initially no active shift
        $currRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/shifts/current');
        $currRes->assertStatus(200)->assertJsonPath('has_active_shift', false);

        // Open shift with Rp 200.000 starting cash
        $openRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/shifts/open', [
                'starting_cash' => 200000,
            ]);
        $openRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('shift.starting_cash', 200000);

        $shiftId = $openRes->json('shift.id');

        // Check active shift
        $currRes2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/shifts/current');
        $currRes2->assertStatus(200)
            ->assertJsonPath('has_active_shift', true)
            ->assertJsonPath('shift.id', $shiftId)
            ->assertJsonPath('shift.expected_cash', 200000);

        // Cash In Rp 50.000 (tukar uang kecil)
        $cashInRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/shifts/cash-movement', [
                'type' => 'cash_in',
                'amount' => 50000,
                'reason' => 'Tukar uang receh kasir',
            ]);
        $cashInRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('cash_movement.amount', 50000);

        // Cash Out Rp 20.000 (beli es batu darurat)
        $cashOutRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/shifts/cash-movement', [
                'type' => 'cash_out',
                'amount' => 20000,
                'reason' => 'Beli es batu',
            ]);
        $cashOutRes->assertStatus(201);

        // Expected cash now: 200.000 + 50.000 - 20.000 = 230.000
        $currRes3 = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/shifts/current');
        $currRes3->assertJsonPath('shift.expected_cash', 230000);

        // Close shift with actual physical cash Rp 230.000
        $closeRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/shifts/close', [
                'actual_cash' => 230000,
            ]);
        $closeRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('shift.expected_cash', 230000)
            ->assertJsonPath('shift.actual_cash', 230000)
            ->assertJsonPath('shift.cash_difference', 0)
            ->assertJsonPath('shift.status', 'closed');
    }

    public function test_api_order_create_and_void(): void
    {
        $token = $this->cashierUser->createToken('test-token')->plainTextToken;

        // Open shift first
        $openShift = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/shifts/open', ['starting_cash' => 100000]);
        $shiftId = $openShift->json('shift.id');

        // Create Order: 2x Ramen (45k each) + 1x Extra Egg (8k)
        // Subtotal = 2 * 45.000 + 8.000 = 98.000
        // Service (5% of 98.000) = 4.900
        // Tax (10% of 98.000 + 4.900 = 102.900) = 10.290
        // Total unrounded = 113.190
        // Round nearest 100 -> remainder 90 -> round up 10 -> Total = 113.200
        $orderPayload = [
            'uuid' => (string) Str::uuid(),
            'order_type_id' => $this->dineIn->id,
            'shift_id' => $shiftId,
            'items' => [
                [
                    'product_id' => $this->ramen->id,
                    'product_name' => $this->ramen->name,
                    'quantity' => 2,
                    'unit_price' => 45000,
                    'modifiers' => [
                        [
                            'modifier_id' => $this->extraEgg->id,
                            'modifier_name' => $this->extraEgg->name,
                            'unit_price' => 8000,
                            'quantity' => 1,
                        ],
                    ],
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashPayment->id,
                    'payment_method_name' => 'Tunai',
                    'amount' => 120000,
                ],
            ],
        ];

        $orderRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/orders', $orderPayload);

        $orderRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.subtotal', 98000)
            ->assertJsonPath('order.service_charge_amount', 4900)
            ->assertJsonPath('order.tax_amount', 10290)
            ->assertJsonPath('order.total_amount', 113200)
            ->assertJsonPath('order.paid_amount', 120000)
            ->assertJsonPath('order.change_amount', 6800)
            ->assertJsonPath('order.status', 'completed');

        $orderId = $orderRes->json('order.id');

        // Void the order with valid supervisor PIN (888888)
        $voidRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/orders/{$orderId}/void", [
                'pin_void' => '888888',
                'reason' => 'Pelanggan membatalkan pesanan karena salah pilih meja',
            ]);

        $voidRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $order = Order::find($orderId);
        $this->assertEquals('voided', $order->status);
        $this->assertEquals('Pelanggan membatalkan pesanan karena salah pilih meja', $order->void_reason);
        $this->assertCount(1, $order->refunds);
        $this->assertEquals(120000, $order->refunds->first()->amount);
    }

    public function test_api_sync_pull_and_push_idempotent(): void
    {
        $token = $this->cashierUser->createToken('test-token')->plainTextToken;

        // 1. Sync Pull
        $pullRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/sync/pull');

        $pullRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'server_time',
                    'branch',
                    'order_types',
                    'categories',
                    'payment_methods',
                    'products',
                    'product_prices',
                ],
            ]);

        // 2. Open shift for push test
        $openShift = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/shifts/open', ['starting_cash' => 50000]);
        $shiftId = $openShift->json('shift.id');

        // 3. Sync Push with offline transactions
        $orderUuid = (string) Str::uuid();
        $pushItems = [
            [
                'uuid' => $orderUuid,
                'order_no' => 'HRS-CKN-POS-20261026-0001',
                'user_id' => $this->cashierUser->id,
                'shift_id' => $shiftId,
                'order_type_id' => $this->dineIn->id,
                'client_created_at' => now('Asia/Jakarta')->toIso8601String(),
                'subtotal' => 45000,
                'service_charge_amount' => 2250,
                'tax_amount' => 4725,
                'rounding_amount' => 25,
                'total_amount' => 52000,
                'paid_amount' => 52000,
                'status' => 'completed',
                'items' => [
                    [
                        'product_id' => $this->ramen->id,
                        'product_name' => $this->ramen->name,
                        'quantity' => 1,
                        'unit_price' => 45000,
                        'subtotal' => 45000,
                    ],
                ],
                'payments' => [
                    [
                        'payment_method_id' => $this->cashPayment->id,
                        'payment_method_name' => 'Tunai',
                        'amount' => 52000,
                    ],
                ],
            ],
        ];

        $pushRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/sync/push', ['items' => $pushItems]);

        $pushRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('success_count', 1);

        $this->assertDatabaseHas('orders', ['uuid' => $orderUuid]);

        // 4. Test Idempotency: push the exact same item again -> returns duplicate_ok
        $pushDupRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/sync/push', ['items' => $pushItems]);

        $pushDupRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('results.0.status', 'duplicate_ok');
    }
}
