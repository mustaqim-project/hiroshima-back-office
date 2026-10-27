<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\OrderType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Sync\SyncEngine;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModifierAndVariantTest extends TestCase
{
    protected Business $business;
    protected Branch $branch;
    protected User $adminUser;
    protected Category $category;
    protected OrderType $orderType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->business = Business::firstOrCreate(
            ['code' => 'TEST-BIZ'],
            ['name' => 'Hiroshima Street Food', 'phone' => '0812345678', 'email' => 'test@hiroshima.id']
        );

        $this->branch = Branch::firstOrCreate(
            ['code' => 'TEST-BR1'],
            [
                'business_id' => $this->business->id,
                'name' => 'Hiroshima Gerobak 01',
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
            ['code' => 'FOOD'],
            ['name' => 'Makanan Utama', 'target_printer' => 'kitchen', 'sort_order' => 1, 'is_active' => true]
        );

        $this->orderType = OrderType::firstOrCreate(
            ['code' => 'dine_in'],
            ['name' => 'Dine In (Makan di Tempat)', 'sort_order' => 1, 'is_active' => true, 'is_default' => true]
        );

        \App\Models\PaymentMethod::firstOrCreate(
            ['id' => 1],
            ['code' => 'cash', 'name' => 'Tunai (Cash)', 'type' => 'cash', 'is_active' => true]
        );
    }

    public function test_can_access_modifier_groups_index_and_create(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('modifier-groups.index'));
        $response->assertStatus(200);
        $response->assertSee('Grup Modifier & Varian');

        $createResponse = $this->get(route('modifier-groups.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Tambah Grup Modifier Baru');
    }

    public function test_can_create_modifier_group_with_items_and_sync_products(): void
    {
        $this->actingAs($this->adminUser);

        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Gyoza Panggang Test',
            'sku' => 'TEST-GYZ-' . Str::random(4),
            'manual_cost' => 15000,
            'has_variants' => false,
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Pilihan Saus Gyoza ' . Str::random(4),
            'selection_type' => 'single',
            'min_select' => 1,
            'max_select' => 1,
            'is_required' => 1,
            'is_active' => 1,
            'modifiers' => [
                ['name' => 'Saus Shoyu Gurih', 'price' => 0, 'cost' => 500, 'is_active' => 1],
                ['name' => 'Saus Spicy Mayo', 'price' => 3000, 'cost' => 1000, 'is_active' => 1],
            ],
            'product_ids' => [$product->id],
        ];

        $response = $this->post(route('modifier-groups.store'), $payload);
        $response->assertRedirect(route('modifier-groups.index'));

        $this->assertDatabaseHas('modifier_groups', ['name' => $payload['name']]);
        $group = ModifierGroup::where('name', $payload['name'])->first();

        $this->assertDatabaseHas('modifiers', [
            'modifier_group_id' => $group->id,
            'name' => 'Saus Shoyu Gurih',
            'price' => 0,
        ]);

        $this->assertDatabaseHas('modifiers', [
            'modifier_group_id' => $group->id,
            'name' => 'Saus Spicy Mayo',
            'price' => 3000,
        ]);

        $this->assertTrue($group->products->contains($product->id));
    }

    public function test_can_update_modifier_group_and_items(): void
    {
        $this->actingAs($this->adminUser);

        $group = ModifierGroup::create([
            'name' => 'Tingkat Kepedasan Test ' . Str::random(4),
            'selection_type' => 'single',
            'min_select' => 1,
            'max_select' => 1,
            'is_required' => true,
            'is_active' => true,
        ]);

        $mod1 = Modifier::create([
            'modifier_group_id' => $group->id,
            'name' => 'Level 1 Ringan',
            'price' => 0,
            'cost' => 0,
            'is_active' => true,
        ]);

        $payload = [
            'name' => $group->name . ' Updated',
            'selection_type' => 'single',
            'min_select' => 1,
            'max_select' => 1,
            'is_required' => 1,
            'is_active' => 1,
            'modifiers' => [
                ['id' => $mod1->id, 'name' => 'Level 1 Ringan (Ubah)', 'price' => 500, 'cost' => 100, 'is_active' => 1],
                ['id' => null, 'name' => 'Level 2 Sedang Baru', 'price' => 1500, 'cost' => 200, 'is_active' => 1],
            ],
            'product_ids' => [],
        ];

        $response = $this->put(route('modifier-groups.update', $group), $payload);
        $response->assertRedirect(route('modifier-groups.index'));

        $this->assertDatabaseHas('modifier_groups', ['id' => $group->id, 'name' => $payload['name']]);
        $this->assertDatabaseHas('modifiers', ['id' => $mod1->id, 'name' => 'Level 1 Ringan (Ubah)', 'price' => 500]);
        $this->assertDatabaseHas('modifiers', ['modifier_group_id' => $group->id, 'name' => 'Level 2 Sedang Baru', 'price' => 1500]);
    }

    public function test_product_with_variants_and_modifier_groups(): void
    {
        $this->actingAs($this->adminUser);

        $group = ModifierGroup::create([
            'name' => 'Extra Topping ' . Str::random(4),
            'selection_type' => 'multiple',
            'min_select' => 0,
            'max_select' => 3,
            'is_required' => false,
            'is_active' => true,
        ]);

        $payload = [
            'category_id' => $this->category->id,
            'name' => 'Karaage Roll ' . Str::random(4),
            'sku' => 'ROLL-' . Str::random(4),
            'base_price' => 25000,
            'manual_cost' => 12000,
            'is_active' => 1,
            'has_variants' => 1,
            'variants' => [
                ['name' => 'Porsi Normal (4 pcs)', 'sku' => 'ROLL-4P', 'price' => 25000, 'manual_cost' => 12000],
                ['name' => 'Porsi Jumbo (8 pcs)', 'sku' => 'ROLL-8P', 'price' => 45000, 'manual_cost' => 22000],
            ],
            'modifier_group_ids' => [$group->id],
        ];

        $response = $this->post(route('products.store'), $payload);
        $response->assertRedirect(route('products.index'));

        $product = Product::where('sku', $payload['sku'])->first();
        $this->assertNotNull($product);
        $this->assertTrue((bool) $product->has_variants);
        $this->assertEquals(2, $product->variants()->count());
        $this->assertTrue($product->modifierGroups->contains($group->id));

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => 'Porsi Jumbo (8 pcs)',
            'price' => 45000,
        ]);
    }

    public function test_can_create_product_without_variants_even_if_empty_variant_array_submitted(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'category_id' => $this->category->id,
            'name' => 'Ocha Hijau ' . Str::random(4),
            'sku' => 'OCHA-' . Str::random(4),
            'base_price' => 8000,
            'manual_cost' => 2000,
            'is_active' => 1,
            'has_variants' => 0,
            'variants' => [
                ['name' => '', 'sku' => '', 'price' => '', 'manual_cost' => ''],
            ],
        ];

        $response = $this->post(route('products.store'), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('products.index'));

        $product = Product::where('sku', $payload['sku'])->first();
        $this->assertNotNull($product);
        $this->assertFalse((bool) $product->has_variants);
        $this->assertEquals(0, $product->variants()->count());
    }

    public function test_can_update_product_without_variants_even_if_empty_variant_array_submitted(): void
    {
        $this->actingAs($this->adminUser);

        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Ocha Dingin',
            'sku' => 'OCHA-01',
            'manual_cost' => 1500,
            'has_variants' => false,
            'is_active' => true,
        ]);

        $payload = [
            'category_id' => $this->category->id,
            'name' => 'Ocha Dingin Segar',
            'sku' => 'OCHA-01',
            'base_price' => 10000,
            'manual_cost' => 2000,
            'is_active' => 1,
            'has_variants' => 0,
            'variants' => [
                ['name' => '', 'sku' => '', 'price' => '', 'manual_cost' => ''],
            ],
        ];

        $response = $this->put(route('products.update', $product), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertEquals('Ocha Dingin Segar', $product->name);
        $this->assertFalse((bool) $product->has_variants);
        $this->assertEquals(0, $product->variants()->count());
    }

    public function test_sync_engine_pull_and_push_with_modifiers(): void
    {
        $syncEngine = app(SyncEngine::class);

        $group = ModifierGroup::create([
            'name' => 'Level Kepedasan ' . Str::random(3),
            'selection_type' => 'single',
            'min_select' => 1,
            'max_select' => 1,
            'is_required' => true,
            'is_active' => true,
        ]);

        $mod = Modifier::create([
            'modifier_group_id' => $group->id,
            'name' => 'Level 2 Mantap',
            'price' => 2000,
            'cost' => 500,
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Ramen Super ' . Str::random(4),
            'sku' => 'RMN-' . Str::random(4),
            'manual_cost' => 18000,
            'has_variants' => true,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Mangkok Jumbo',
            'sku' => $product->sku . '-JMB',
            'price' => 38000,
            'manual_cost' => 18000,
            'cost_method' => 'manual',
            'is_active' => true,
        ]);

        $product->modifierGroups()->sync([$group->id]);

        // 1. Verify Pull contains variants and modifier groups
        $catalog = $syncEngine->pull($this->branch);
        $this->assertNotEmpty($catalog['products']);

        $pulledProduct = collect($catalog['products'])->firstWhere('id', $product->id);
        $this->assertNotNull($pulledProduct);
        $this->assertNotEmpty($pulledProduct->variants);
        $this->assertEquals('Mangkok Jumbo', $pulledProduct->variants->first()->name);
        $this->assertNotEmpty($pulledProduct->modifierGroups);
        $this->assertEquals($group->name, $pulledProduct->modifierGroups->first()->name);

        // 2. Verify Push ingests items and order_item_modifiers
        $shift = \App\Models\Shift::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->adminUser->id,
            'status' => 'open',
            'opening_balance' => 100000,
            'opened_at' => now(),
        ]);

        $orderUuid = (string) Str::uuid();
        $pushPayload = [
            [
                'uuid' => $orderUuid,
                'order_no' => 'HRS-TEST-' . rand(1000, 9999),
                'shift_id' => $shift->id,
                'user_id' => $this->adminUser->id,
                'order_type_id' => $this->orderType->id,
                'client_created_at' => now()->toIso8601String(),
                'subtotal' => 40000,
                'total_amount' => 44000,
                'tax_amount' => 4000,
                'paid_amount' => 44000,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'product_name' => $product->name,
                        'variant_name' => $variant->name,
                        'quantity' => 1,
                        'unit_price' => 40000,
                        'subtotal' => 40000,
                        'modifiers' => [
                            [
                                'modifier_id' => $mod->id,
                                'modifier_name' => $mod->name,
                                'unit_price' => 2000,
                                'quantity' => 1,
                            ],
                        ],
                    ],
                ],
                'payments' => [
                    [
                        'payment_method_id' => 1,
                        'amount' => 44000,
                    ],
                ],
            ],
        ];

        $pushResults = $syncEngine->push($this->branch, null, $pushPayload);
        $this->assertNotEmpty($pushResults['results']);
        $this->assertEquals('accepted', $pushResults['results'][0]['status'], 'Push rejected with reason: ' . ($pushResults['results'][0]['reason'] ?? ''));

        $savedOrder = Order::where('uuid', $orderUuid)->first();
        $this->assertNotNull($savedOrder);
        $this->assertEquals(1, $savedOrder->items()->count());

        $firstItem = $savedOrder->items()->first();
        $this->assertEquals('Mangkok Jumbo', $firstItem->variant_name);
        $this->assertEquals(1, $firstItem->modifiers()->count());

        $firstMod = $firstItem->modifiers()->first();
        $this->assertEquals($mod->id, $firstMod->modifier_id);
        $this->assertEquals('Level 2 Mantap', $firstMod->modifier_name);
        $this->assertEquals(2000, $firstMod->unit_price);
    }
}
