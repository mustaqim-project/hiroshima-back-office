<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\OrderType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\Sync\SyncEngine;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderTypeAndMultiPriceTest extends TestCase
{
    protected Business $business;
    protected Branch $branch;
    protected User $adminUser;
    protected Category $category;

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
            ['code' => 'RMN'],
            ['name' => 'Ramen & Mie', 'target_printer' => 'kitchen', 'sort_order' => 1, 'is_active' => true]
        );
    }

    public function test_can_view_order_types_index_and_crud(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Index
        $response = $this->get('/backoffice/order-types');
        $response->assertStatus(200);
        $response->assertSee('Master Data Jenis Pesanan');
        $response->assertSee('Dine In');

        // 2. Create new OrderType (e.g. GrabFood)
        $storeResponse = $this->post('/backoffice/order-types', [
            'name' => 'GrabFood',
            'code' => 'grab_food',
            'sort_order' => 4,
            'is_default' => 0,
            'is_active' => 1,
        ]);
        $storeResponse->assertRedirect('/backoffice/order-types');

        $this->assertDatabaseHas('order_types', [
            'code' => 'grab_food',
            'name' => 'GrabFood',
            'requires_table' => false,
        ]);

        $grabFood = OrderType::where('code', 'grab_food')->first();

        // 3. Edit
        $editResponse = $this->get("/backoffice/order-types/{$grabFood->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Jenis Pesanan');

        // 4. Update
        $updateResponse = $this->put("/backoffice/order-types/{$grabFood->id}", [
            'name' => 'GrabFood Online',
            'code' => 'grab_food',
            'sort_order' => 5,
            'is_default' => 0,
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect('/backoffice/order-types');
        $this->assertDatabaseHas('order_types', ['name' => 'GrabFood Online']);

        // 5. Delete (Soft delete)
        $deleteResponse = $this->delete("/backoffice/order-types/{$grabFood->id}");
        $deleteResponse->assertRedirect('/backoffice/order-types');
        $this->assertSoftDeleted('order_types', ['id' => $grabFood->id]);
    }

    public function test_can_update_order_type_when_is_default_checkbox_unchecked(): void
    {
        $this->actingAs($this->adminUser);

        $delivery = OrderType::firstOrCreate(
            ['code' => 'delivery'],
            ['name' => 'Delivery', 'is_default' => false, 'sort_order' => 3, 'is_active' => true]
        );

        // Simulate submitting the edit form when the is_default checkbox is unchecked (browser does not send is_default)
        $response = $this->put("/backoffice/order-types/{$delivery->id}", [
            'name' => 'Delivery Ojol Update',
            'code' => 'delivery',
            'sort_order' => 3,
            'is_active' => 1,
            // is_default omitted intentionally
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/backoffice/order-types');
        $this->assertDatabaseHas('order_types', [
            'id' => $delivery->id,
            'name' => 'Delivery Ojol Update',
            'is_default' => false,
        ]);
    }

    public function test_can_create_product_with_multi_prices_per_order_type(): void
    {
        $this->actingAs($this->adminUser);

        $dineIn = OrderType::firstOrCreate(['code' => 'dine_in'], ['name' => 'Dine In', 'is_default' => true]);
        $takeAway = OrderType::firstOrCreate(['code' => 'take_away'], ['name' => 'Take Away', 'is_default' => false]);
        $delivery = OrderType::firstOrCreate(['code' => 'delivery'], ['name' => 'Delivery', 'is_default' => false]);

        $sku = 'TEST-MP-' . time();

        // Create product with multi-prices
        $response = $this->post('/backoffice/products', [
            'category_id' => $this->category->id,
            'name' => 'Spicy Miso Ramen Multi',
            'sku' => $sku,
            'manual_cost' => 14000,
            'description' => 'Ramen pedas dengan multi-harga',
            'is_active' => 1,
            'prices' => [
                $dineIn->id => 28000,    // Dine In: Rp 28.000
                $takeAway->id => 30000,  // Take Away: Rp 30.000 (kemasan)
                $delivery->id => 33000,  // Delivery: Rp 33.000 (ojol)
            ],
        ]);

        $response->assertRedirect('/backoffice/products');

        $product = Product::where('sku', $sku)->first();
        $this->assertNotNull($product);

        // Verify prices in product_prices table
        $this->assertDatabaseHas('product_prices', [
            'sellable_id' => $product->id,
            'sellable_type' => 'Product',
            'order_type_id' => $dineIn->id,
            'price' => 28000,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'sellable_id' => $product->id,
            'sellable_type' => 'Product',
            'order_type_id' => $takeAway->id,
            'price' => 30000,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'sellable_id' => $product->id,
            'sellable_type' => 'Product',
            'order_type_id' => $delivery->id,
            'price' => 33000,
        ]);

        // Verify SyncEngine pulls the multi-prices
        $syncEngine = app(SyncEngine::class);
        $catalog = $syncEngine->pull($this->branch);
        $this->assertArrayHasKey('product_prices', $catalog);
        $this->assertArrayHasKey('order_types', $catalog);

        // Check index view displays multi-pricing
        $indexResponse = $this->get('/backoffice/products');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Multi-Harga per Jenis Pesanan');
        $indexResponse->assertSee('28.000');
        $indexResponse->assertSee('30.000');
        $indexResponse->assertSee('33.000');
    }
}
