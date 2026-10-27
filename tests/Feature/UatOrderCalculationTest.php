<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\OrderType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Shift;
use App\Models\User;
use App\Services\OrderCalculator;
use App\Services\PinAuthorizationService;
use App\Services\PriceResolver;
use Database\Seeders\RolePermissionSeeder;
use Exception;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @req FR-PRICE-01, FR-PRICE-02, FR-PRICE-03, FSD-03, FSD-06, FSD-07, FSD-12, FSD-20
 */
class UatOrderCalculationTest extends TestCase
{
    protected Branch $branch1;
    protected Branch $branch2;
    protected OrderType $dineIn;
    protected OrderType $takeAway;
    protected Product $ramen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $business = Business::create(['name' => 'Hiroshima F&B', 'code' => 'HRS']);
        $pinHash = password_hash('123456', PASSWORD_ARGON2ID);

        $this->branch1 = Branch::create([
            'business_id' => $business->id,
            'name' => 'Hiroshima Pusat',
            'code' => 'HRS01',
            'tax_percent' => 11.00,
            'service_charge_percent' => 5.00,
            'tax_included' => false,
            'rounding_mode' => 'round_nearest_100',
            'pin_void_hash' => $pinHash,
            'pin_discount_hash' => $pinHash,
            'discount_percent_threshold' => 10.00,
            'discount_amount_threshold' => 20000,
        ]);

        $this->branch2 = Branch::create([
            'business_id' => $business->id,
            'name' => 'Hiroshima Cabang 2',
            'code' => 'HRS02',
            'tax_percent' => 11.00,
            'service_charge_percent' => 0.00,
            'tax_included' => false,
            'rounding_mode' => 'round_nearest_100',
            'pin_void_hash' => $pinHash,
            'pin_discount_hash' => $pinHash,
            'discount_percent_threshold' => 10.00,
            'discount_amount_threshold' => 20000,
        ]);

        $this->dineIn = OrderType::create(['name' => 'Dine In', 'code' => 'dine_in', 'is_default' => true]);
        $this->takeAway = OrderType::create(['name' => 'Take Away', 'code' => 'take_away', 'is_default' => false]);

        $cat = Category::create(['name' => 'Food', 'code' => 'FD']);
        $this->ramen = Product::create(['category_id' => $cat->id, 'name' => 'Ramen Spesial', 'sku' => 'RAMEN-01']);

        // Central price Dine In: Rp 40.000
        ProductPrice::create([
            'sellable_type' => 'Product',
            'sellable_id' => $this->ramen->id,
            'order_type_id' => $this->dineIn->id,
            'branch_id' => null,
            'price' => 40000,
        ]);

        // Branch 2 Override Dine In: Rp 45.000
        ProductPrice::create([
            'sellable_type' => 'Product',
            'sellable_id' => $this->ramen->id,
            'order_type_id' => $this->dineIn->id,
            'branch_id' => $this->branch2->id,
            'price' => 45000,
        ]);
    }

    /**
     * UAT-03: Resolusi Hirarki Harga (Branch Override -> Central -> Default).
     */
    public function test_uat_03_hierarchical_price_resolution(): void
    {
        $resolver = new PriceResolver();

        // Branch 1 has no override -> uses Central price (Rp 40.000)
        $priceBranch1 = $resolver->resolve($this->ramen, $this->dineIn, $this->branch1);
        $this->assertEquals(40000, $priceBranch1);

        // Branch 2 has override -> uses Branch price (Rp 45.000)
        $priceBranch2 = $resolver->resolve($this->ramen, $this->dineIn, $this->branch2);
        $this->assertEquals(45000, $priceBranch2);
    }

    /**
     * UAT-06: Perhitungan Transaksi FSD-06 (Subtotal, Diskon, Service, Pajak, Pembulatan 100).
     */
    public function test_uat_06_order_calculation_fsd06(): void
    {
        // Subtotal: 100.000
        // Promo: 10.000, Manual Discount: 5.000 -> Taxable Base: 85.000
        // Service 5%: 4.250 -> Taxable with service: 89.250
        // Tax 11%: 89.250 * 0.11 = 9.817.5 -> round = 9.818
        // Unrounded Total: 85.000 + 4.250 + 9.818 = 99.068
        // Round to nearest 100: 99.068 % 100 = 68 (>= 50) -> add 32 -> 99.100
        $result = OrderCalculator::calculate(
            subtotal: 100000,
            promoDiscount: 10000,
            voucherDiscount: 0,
            loyaltyDiscount: 0,
            manualDiscount: 5000,
            branch: $this->branch1
        );

        $this->assertEquals(85000, $result['taxable_base']);
        $this->assertEquals(4250, $result['service_charge_amount']);
        $this->assertEquals(9818, $result['tax_amount']);
        $this->assertEquals(99068, $result['unrounded_total']);
        $this->assertEquals(32, $result['rounding_amount']);
        $this->assertEquals(99100, $result['total_amount']);
    }

    /**
     * UAT-14: Diskon manual di atas ambang (10% atau Rp 20.000) wajib verifikasi PIN Diskon.
     */
    public function test_uat_14_discount_above_threshold_requires_pin(): void
    {
        $pinService = new PinAuthorizationService();

        // Valid PIN '123456' succeeds
        $verified = $pinService->verify($this->branch1, 'discount', '123456');
        $this->assertTrue($verified);

        // Invalid PIN throws exception
        $this->expectException(Exception::class);
        $pinService->verify($this->branch1, 'discount', '999999');
    }

    /**
     * UAT-16: Lockout 15 menit setelah 5 kali salah input PIN beruntun.
     */
    public function test_uat_16_pin_lockout_after_five_failed_attempts(): void
    {
        $pinService = new PinAuthorizationService();

        for ($i = 1; $i <= 4; $i++) {
            try {
                $pinService->verify($this->branch1, 'void', '000000');
            } catch (Exception $e) {
                // Expected
            }
        }

        // 5th failed attempt triggers lockout
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Batas 5 kali percobaan terlampaui');
        $pinService->verify($this->branch1, 'void', '000000');
    }
}
