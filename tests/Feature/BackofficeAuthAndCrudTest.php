<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Promo;
use App\Models\Table;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @req FR-AUTH-01, FR-AUTH-03, FR-WEB-01, FR-WEB-02, FR-WEB-03, FR-WEB-04, FR-WEB-05, FR-MST-01, FR-MST-03, FR-MST-08, FR-MENU-01, FR-PROMO-01, FR-HPP-01
 */
class BackofficeAuthAndCrudTest extends TestCase
{
    protected Business $business;
    protected Branch $branch;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->business = Business::firstOrCreate(
            ['code' => 'TEST-BIZ'],
            ['name' => 'Test Business', 'phone' => '0812345678', 'email' => 'test@hiroshima.id']
        );

        $this->branch = Branch::firstOrCreate(
            ['code' => 'TBR01'],
            [
                'business_id' => $this->business->id,
                'name' => 'Cabang Test',
                'tax_percent' => 10,
                'service_charge_percent' => 5,
                'tax_included' => false,
                'rounding_mode' => 'round_nearest_100',
                'discount_percent_threshold' => 10,
                'discount_amount_threshold' => 50000,
                'printer_paper_width' => '80mm',
                'is_active' => true,
            ]
        );

        OrderType::firstOrCreate(['id' => 1], ['name' => 'Dine In', 'code' => 'dine_in', 'is_default' => true]);
        OrderType::firstOrCreate(['id' => 2], ['name' => 'Take Away', 'code' => 'take_away', 'is_default' => false]);

        $this->adminUser = User::firstOrCreate(
            ['username' => 'testadmin'],
            [
                'name' => 'Test Admin',
                'email' => 'testadmin@hiroshima.id',
                'password' => Hash::make('password123'),
                'role' => 'central_admin',
                'is_active' => true,
            ]
        );

        Unit::firstOrCreate(['symbol' => 'kg'], ['name' => 'Kilogram']);
        Unit::firstOrCreate(['symbol' => 'pcs'], ['name' => 'Pieces']);
    }

    public function test_guest_is_redirected_to_login_page(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');

        $crudResponse = $this->get('/backoffice/branches');
        $crudResponse->assertRedirect('/login');
    }

    public function test_user_can_view_login_page_and_authenticate(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('HIROSHIMA POS');

        $loginResponse = $this->post('/login', [
            'username_or_email' => 'testadmin@hiroshima.id',
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect('/');
        $this->assertAuthenticatedAs($this->adminUser);
    }

    public function test_branch_crud_and_soft_delete(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create Branch
        $response = $this->post('/backoffice/branches', [
            'name' => 'Hiroshima Sukajadi',
            'code' => 'SKJ01',
            'phone' => '022-777888',
            'address' => 'Jl. Sukajadi No. 101, Bandung',
            'tax_percent' => 10,
            'service_charge_percent' => 5,
            'tax_included' => 0,
            'rounding_mode' => 'round_nearest_100',
            'discount_percent_threshold' => 15,
            'discount_amount_threshold' => 50000,
            'print_kitchen_copy' => 1,
            'printer_paper_width' => '80mm',
        ]);
        $response->assertRedirect('/backoffice/branches');

        $branch = Branch::where('code', 'SKJ01')->first();
        $this->assertNotNull($branch);

        // 2. Read Index
        $indexResponse = $this->get('/backoffice/branches');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Hiroshima Sukajadi');

        // 3. Update Branch
        $updateResponse = $this->put("/backoffice/branches/{$branch->id}", [
            'name' => 'Hiroshima Sukajadi Grand',
            'phone' => '022-777888',
            'address' => 'Jl. Sukajadi No. 101-103, Bandung',
            'tax_percent' => 11,
            'service_charge_percent' => 5,
            'tax_included' => 1,
            'rounding_mode' => 'round_nearest_100',
            'discount_percent_threshold' => 20,
            'discount_amount_threshold' => 75000,
            'print_kitchen_copy' => 1,
            'printer_paper_width' => '80mm',
        ]);
        $updateResponse->assertRedirect('/backoffice/branches');
        $this->assertEquals('Hiroshima Sukajadi Grand', $branch->fresh()->name);

        // 4. Soft Delete
        $deleteResponse = $this->delete("/backoffice/branches/{$branch->id}");
        $deleteResponse->assertRedirect('/backoffice/branches');

        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
        $this->assertNull(Branch::find($branch->id));
        $this->assertNotNull(Branch::withTrashed()->find($branch->id));
    }

    public function test_category_crud_and_soft_delete(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create Category
        $response = $this->post('/backoffice/categories', [
            'name' => 'Ramen Kuah Gurih',
            'code' => 'TEST_RAMEN',
            'target_printer' => 'kitchen',
            'sort_order' => 1,
            'is_active' => 1,
        ]);
        $response->assertRedirect('/backoffice/categories');

        $category = Category::where('code', 'TEST_RAMEN')->first();
        $this->assertNotNull($category);

        // 2. Read Index
        $indexResponse = $this->get('/backoffice/categories');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Ramen Kuah Gurih');

        // 3. Update Category
        $updateResponse = $this->put("/backoffice/categories/{$category->id}", [
            'name' => 'Ramen Kuah Gurih Updated',
            'code' => 'TEST_RAMEN',
            'target_printer' => 'kitchen',
            'sort_order' => 2,
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect('/backoffice/categories');
        $this->assertEquals('Ramen Kuah Gurih Updated', $category->fresh()->name);

        // 4. Soft Delete
        $deleteResponse = $this->delete("/backoffice/categories/{$category->id}");
        $deleteResponse->assertRedirect('/backoffice/categories');

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_product_crud_and_soft_delete(): void
    {
        $this->actingAs($this->adminUser);

        $cat = Category::firstOrCreate(['code' => 'CAT_TEST'], ['name' => 'Kategori Menu Test', 'target_printer' => 'kitchen', 'sort_order' => 1, 'is_active' => 1]);

        // 1. Create Product
        $response = $this->post('/backoffice/products', [
            'category_id' => $cat->id,
            'name' => 'Spicy Miso Ramen',
            'sku' => 'TEST_SMR_01',
            'base_price' => 45000,
            'manual_cost' => 18000,
            'description' => 'Ramen pedas dengan kuah miso spesial',
            'is_active' => 1,
        ]);
        $response->assertRedirect('/backoffice/products');

        $product = Product::where('sku', 'TEST_SMR_01')->first();
        $this->assertNotNull($product);

        // 2. Read Index
        $indexResponse = $this->get('/backoffice/products');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Spicy Miso Ramen');

        // 3. Update Product
        $updateResponse = $this->put("/backoffice/products/{$product->id}", [
            'category_id' => $cat->id,
            'name' => 'Spicy Miso Ramen Extra Chashu',
            'sku' => 'TEST_SMR_01',
            'base_price' => 52000,
            'manual_cost' => 20000,
            'description' => 'Ramen pedas dengan extra chashu',
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect('/backoffice/products');
        $this->assertEquals('Spicy Miso Ramen Extra Chashu', $product->fresh()->name);

        // 4. Soft Delete
        $deleteResponse = $this->delete("/backoffice/products/{$product->id}");
        $deleteResponse->assertRedirect('/backoffice/products');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_table_crud_and_soft_delete(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create Table
        $response = $this->post('/backoffice/tables', [
            'branch_id' => $this->branch->id,
            'table_no' => 'T-TEST-99',
            'section' => 'VIP Room',
            'capacity' => 6,
            'status' => 'available',
            'is_active' => 1,
        ]);
        $response->assertRedirect('/backoffice/tables');

        $table = Table::where('table_no', 'T-TEST-99')->first();
        $this->assertNotNull($table);

        // 2. Read Index
        $indexResponse = $this->get('/backoffice/tables');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('T-TEST-99');

        // 3. Update Table
        $updateResponse = $this->put("/backoffice/tables/{$table->id}", [
            'branch_id' => $this->branch->id,
            'table_no' => 'T-TEST-99',
            'section' => 'VVIP Lounge',
            'capacity' => 8,
            'status' => 'available',
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect('/backoffice/tables');
        $this->assertEquals('VVIP Lounge', $table->fresh()->section);

        // 4. Soft Delete
        $deleteResponse = $this->delete("/backoffice/tables/{$table->id}");
        $deleteResponse->assertRedirect('/backoffice/tables');

        $this->assertSoftDeleted('tables', ['id' => $table->id]);
    }

    public function test_payment_method_crud_and_soft_delete(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create Payment Method
        $response = $this->post('/backoffice/payment-methods', [
            'name' => 'QRIS GoPay Test',
            'code' => 'QRIS_GOPAY',
            'type' => 'qris_static',
            'is_cash' => 0,
            'is_active' => 1,
        ]);
        $response->assertRedirect('/backoffice/payment-methods');

        $method = PaymentMethod::where('code', 'QRIS_GOPAY')->first();
        $this->assertNotNull($method);

        // 2. Read Index
        $indexResponse = $this->get('/backoffice/payment-methods');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('QRIS GoPay Test');

        // 3. Update Payment Method
        $updateResponse = $this->put("/backoffice/payment-methods/{$method->id}", [
            'name' => 'QRIS GoPay Updated',
            'code' => 'QRIS_GOPAY',
            'type' => 'qris_static',
            'is_cash' => 0,
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect('/backoffice/payment-methods');
        $this->assertEquals('QRIS GoPay Updated', $method->fresh()->name);

        // 4. Soft Delete
        $deleteResponse = $this->delete("/backoffice/payment-methods/{$method->id}");
        $deleteResponse->assertRedirect('/backoffice/payment-methods');

        $this->assertSoftDeleted('payment_methods', ['id' => $method->id]);
    }

    public function test_user_staff_crud_and_soft_delete(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create Staff User
        $response = $this->post('/backoffice/users', [
            'name' => 'Kasir Operasional',
            'username' => 'kasir_ops_test',
            'email' => 'kasir_ops@hiroshima.id',
            'phone' => '081299998888',
            'password' => 'kasir12345',
            'pin' => '654321',
            'branch_id' => $this->branch->id,
            'role' => 'cashier',
            'is_active' => 1,
        ]);
        $response->assertRedirect('/backoffice/users');

        $staff = User::where('username', 'kasir_ops_test')->first();
        $this->assertNotNull($staff);

        // 2. Read Index
        $indexResponse = $this->get('/backoffice/users');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Kasir Operasional');

        // 3. Update Staff User
        $updateResponse = $this->put("/backoffice/users/{$staff->id}", [
            'name' => 'Kasir Operasional Senior',
            'username' => 'kasir_ops_test',
            'email' => 'kasir_ops@hiroshima.id',
            'phone' => '081299998888',
            'branch_id' => $this->branch->id,
            'role' => 'cashier',
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect('/backoffice/users');
        $this->assertEquals('Kasir Operasional Senior', $staff->fresh()->name);

        // 4. Soft Delete
        $deleteResponse = $this->delete("/backoffice/users/{$staff->id}");
        $deleteResponse->assertRedirect('/backoffice/users');

        $this->assertSoftDeleted('users', ['id' => $staff->id]);
    }

    public function test_ingredient_crud_and_soft_delete(): void
    {
        $this->actingAs($this->adminUser);

        $unit = Unit::first();

        // 1. Create Ingredient
        $response = $this->post('/backoffice/ingredients', [
            'name' => 'Wagyu Beef Ribeye A5',
            'sku' => 'ING_WAGYU_TEST',
            'unit_id' => $unit->id,
            'standard_cost' => 120000,
            'minimum_stock' => 2.5,
            'is_active' => 1,
        ]);
        $response->assertRedirect('/backoffice/ingredients');

        $ingredient = Ingredient::where('sku', 'ING_WAGYU_TEST')->first();
        $this->assertNotNull($ingredient);

        // 2. Read Index
        $indexResponse = $this->get('/backoffice/ingredients');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Wagyu Beef Ribeye A5');

        // 3. Update Ingredient
        $updateResponse = $this->put("/backoffice/ingredients/{$ingredient->id}", [
            'name' => 'Wagyu Beef Ribeye A5 Premium',
            'sku' => 'ING_WAGYU_TEST',
            'unit_id' => $unit->id,
            'standard_cost' => 135000,
            'minimum_stock' => 3.0,
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect('/backoffice/ingredients');
        $this->assertEquals('Wagyu Beef Ribeye A5 Premium', $ingredient->fresh()->name);

        // 4. Soft Delete
        $deleteResponse = $this->delete("/backoffice/ingredients/{$ingredient->id}");
        $deleteResponse->assertRedirect('/backoffice/ingredients');

        $this->assertSoftDeleted('ingredients', ['id' => $ingredient->id]);
    }

    public function test_promo_crud_and_soft_delete(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create Promo
        $response = $this->post('/backoffice/promos', [
            'name' => 'Weekend Fiesta 15%',
            'code' => 'WKND15_TEST',
            'type' => 'percentage',
            'value' => 15,
            'min_purchase' => 100000,
            'max_discount' => 30000,
            'priority' => 5,
            'starts_at' => now()->format('Y-m-d'),
            'ends_at' => now()->addDays(7)->format('Y-m-d'),
            'branch_ids' => [$this->branch->id],
            'is_active' => 1,
        ]);
        $response->assertRedirect('/backoffice/promos');

        $promo = Promo::where('code', 'WKND15_TEST')->first();
        $this->assertNotNull($promo);

        // 2. Read Index
        $indexResponse = $this->get('/backoffice/promos');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Weekend Fiesta 15%');

        // 3. Update Promo
        $updateResponse = $this->put("/backoffice/promos/{$promo->id}", [
            'name' => 'Weekend Fiesta 20% Super',
            'code' => 'WKND15_TEST',
            'type' => 'percentage',
            'value' => 20,
            'min_purchase' => 100000,
            'max_discount' => 40000,
            'priority' => 10,
            'starts_at' => now()->format('Y-m-d'),
            'branch_ids' => [$this->branch->id],
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect('/backoffice/promos');
        $this->assertEquals('Weekend Fiesta 20% Super', $promo->fresh()->name);

        // 4. Soft Delete
        $deleteResponse = $this->delete("/backoffice/promos/{$promo->id}");
        $deleteResponse->assertRedirect('/backoffice/promos');

        $this->assertSoftDeleted('promos', ['id' => $promo->id]);
    }
}
