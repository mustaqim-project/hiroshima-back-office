<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Table;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * @req FR-BR-01, FR-BR-02, FR-AUTH-01, FR-MST-01, FR-MST-02, FR-MST-03, FR-MENU-01, FR-PRICE-01, FSD-01, FSD-02, FSD-03
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // 1. Business & Real Branches (Cikini, Tebet, Cilandak)
        $business = Business::create([
            'name' => 'HIROSHIMA JAPANESE STREET FOOD',
            'code' => 'HRS-GRP',
            'phone' => '0812-3456-7890',
            'email' => 'hiroshimajapanese@gmail.com',
            'address' => 'Jl. Cikini Raya, Jakarta Pusat',
        ]);

        $pinHash = password_hash('123456', PASSWORD_ARGON2ID);

        // Cabang 1: Hiroshima Cikini
        $branchCikini = Branch::create([
            'business_id' => $business->id,
            'name' => 'Hiroshima Cikini',
            'code' => 'HRS-CKN',
            'phone' => '0812-1111-001',
            'address' => 'Jl. Cikini Raya No. 45, Menteng, Jakarta Pusat',
            'tax_percent' => 10.00,
            'service_charge_percent' => 0.00,
            'tax_included' => false,
            'rounding_mode' => 'round_nearest_100',
            'pin_void_hash' => $pinHash,
            'pin_discount_hash' => $pinHash,
            'discount_percent_threshold' => 10.00,
            'discount_amount_threshold' => 20000,
            'inventory_enabled' => false,
            'is_active' => true,
        ]);

        // Cabang 2: Hiroshima Tebet
        $branchTebet = Branch::create([
            'business_id' => $business->id,
            'name' => 'Hiroshima Tebet',
            'code' => 'HRS-TBT',
            'phone' => '0812-1111-002',
            'address' => 'Jl. Tebet Raya No. 28, Tebet, Jakarta Selatan',
            'tax_percent' => 10.00,
            'service_charge_percent' => 0.00,
            'tax_included' => false,
            'rounding_mode' => 'round_nearest_100',
            'pin_void_hash' => $pinHash,
            'pin_discount_hash' => $pinHash,
            'discount_percent_threshold' => 10.00,
            'discount_amount_threshold' => 20000,
            'inventory_enabled' => false,
            'is_active' => true,
        ]);

        // Cabang 3: Hiroshima Cilandak
        $branchCilandak = Branch::create([
            'business_id' => $business->id,
            'name' => 'Hiroshima Cilandak',
            'code' => 'HRS-CLD',
            'phone' => '0812-1111-003',
            'address' => 'Jl. Cilandak KKO No. 15, Pasar Minggu, Jakarta Selatan',
            'tax_percent' => 10.00,
            'service_charge_percent' => 0.00,
            'tax_included' => false,
            'rounding_mode' => 'round_nearest_100',
            'pin_void_hash' => $pinHash,
            'pin_discount_hash' => $pinHash,
            'discount_percent_threshold' => 10.00,
            'discount_amount_threshold' => 20000,
            'inventory_enabled' => false,
            'is_active' => true,
        ]);

        // 2. Users (Admin Utama Email: hiroshimajapanese@gmail.com)
        setPermissionsTeamId(0); // Central

        $admin = User::create([
            'name' => 'Admin Hiroshima Japanese Street Food',
            'username' => 'admin',
            'email' => 'hiroshimajapanese@gmail.com',
            'password' => Hash::make('password123'),
            'pin_hash' => $pinHash,
            'role' => 'central_admin',
            'is_active' => true,
        ]);
        $admin->assignRole('central_admin');

        $owner = User::create([
            'name' => 'Owner Hiroshima',
            'username' => 'owner',
            'email' => 'owner@hiroshima.id',
            'password' => Hash::make('password123'),
            'pin_hash' => $pinHash,
            'role' => 'owner',
            'is_active' => true,
        ]);
        $owner->assignRole('owner');

        // Cashiers per Branch
        foreach ([$branchCikini, $branchTebet, $branchCilandak] as $b) {
            setPermissionsTeamId($b->id);

            $cashier = User::create([
                'branch_id' => $b->id,
                'name' => 'Kasir ' . $b->name,
                'username' => strtolower(str_replace('-', '', $b->code)) . '_kasir',
                'email' => strtolower($b->code) . '@hiroshima.id',
                'password' => Hash::make('password123'),
                'pin_hash' => $pinHash,
                'role' => 'cashier',
                'is_active' => true,
            ]);
            $cashier->assignRole('cashier');

            $manager = User::create([
                'branch_id' => $b->id,
                'name' => 'Kepala Cabang ' . $b->name,
                'username' => strtolower(str_replace('-', '', $b->code)) . '_spv',
                'email' => strtolower($b->code) . '_spv@hiroshima.id',
                'password' => Hash::make('password123'),
                'pin_hash' => $pinHash,
                'role' => 'branch_manager',
                'is_active' => true,
            ]);
            $manager->assignRole('branch_manager');
        }

        // 3. Order Types (Master Data Tanpa Meja)
        $dineIn = OrderType::create([
            'name' => 'Dine In',
            'code' => 'dine_in',
            'is_default' => true,
            'requires_table' => false,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $takeAway = OrderType::create([
            'name' => 'Take Away',
            'code' => 'take_away',
            'is_default' => false,
            'requires_table' => false,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $delivery = OrderType::create([
            'name' => 'Delivery',
            'code' => 'delivery',
            'is_default' => false,
            'requires_table' => false,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // 4. Payment Methods
        $cash = PaymentMethod::create([
            'name' => 'Tunai (Cash)',
            'code' => 'cash',
            'type' => 'cash',
            'is_cash' => true,
            'is_active' => true,
        ]);

        $qris = PaymentMethod::create([
            'name' => 'QRIS Statis',
            'code' => 'qris_static',
            'type' => 'qris_static',
            'is_cash' => false,
            'is_active' => true,
        ]);

        $debitBca = PaymentMethod::create([
            'name' => 'Debit BCA',
            'code' => 'debit_bca',
            'type' => 'debit_card',
            'is_cash' => false,
            'is_active' => true,
        ]);

        // Hubungkan payment methods ke seluruh cabang
        foreach ([$branchCikini, $branchTebet, $branchCilandak] as $b) {
            $b->paymentMethods()->create(['payment_method_id' => $cash->id, 'is_active' => true]);
            $b->paymentMethods()->create(['payment_method_id' => $qris->id, 'is_active' => true]);
            $b->paymentMethods()->create(['payment_method_id' => $debitBca->id, 'is_active' => true]);
        }

        // 5. Categories Real Pawoon
        $catMap = [
            'PAKET HIRO' => Category::create(['name' => 'PAKET HIRO', 'code' => 'PKT', 'target_printer' => 'kitchen', 'sort_order' => 1, 'is_active' => true]),
            'Makanan'    => Category::create(['name' => 'Makanan', 'code' => 'MKN', 'target_printer' => 'kitchen', 'sort_order' => 2, 'is_active' => true]),
            'MINUMAN'    => Category::create(['name' => 'MINUMAN', 'code' => 'MNM', 'target_printer' => 'bar', 'sort_order' => 3, 'is_active' => true]),
            'Promo'      => Category::create(['name' => 'Promo', 'code' => 'PRM', 'target_printer' => 'kitchen', 'sort_order' => 4, 'is_active' => true]),
            'LAINNYA'    => Category::create(['name' => 'LAINNYA', 'code' => 'OTH', 'target_printer' => 'kitchen', 'sort_order' => 5, 'is_active' => true]),
        ];

        // 6. Products Real Pawoon (33 Menu Asli Hiroshima Japanese Street Food)
        $rawProducts = [
            ['name' => 'PAKET HIRO 1', 'category' => 'PAKET HIRO', 'price' => 29000, 'sku' => 'HRS-001', 'cost' => 14000],
            ['name' => 'PAKET HIRO 2', 'category' => 'PAKET HIRO', 'price' => 70000, 'sku' => 'HRS-002', 'cost' => 32000],
            ['name' => 'PAKET HIRO 3', 'category' => 'PAKET HIRO', 'price' => 65000, 'sku' => 'HRS-003', 'cost' => 30000],
            ['name' => 'PAKET HIRO 4', 'category' => 'PAKET HIRO', 'price' => 110000, 'sku' => 'HRS-004', 'cost' => 52000],
            ['name' => 'PAKET BEST HIRO', 'category' => 'PAKET HIRO', 'price' => 135000, 'sku' => 'HRS-005', 'cost' => 65000],
            ['name' => 'OCHA', 'category' => 'MINUMAN', 'price' => 6000, 'sku' => 'HRS-006', 'cost' => 1500],
            ['name' => 'LEMON TEA', 'category' => 'MINUMAN', 'price' => 8000, 'sku' => 'HRS-007', 'cost' => 2500],
            ['name' => 'MINERAL WATER', 'category' => 'MINUMAN', 'price' => 5000, 'sku' => 'HRS-008', 'cost' => 1500],
            ['name' => 'TEH PUCUK', 'category' => 'MINUMAN', 'price' => 6000, 'sku' => 'HRS-009', 'cost' => 2500],
            ['name' => 'BADAK', 'category' => 'MINUMAN', 'price' => 20000, 'sku' => 'HRS-010', 'cost' => 9000],
            ['name' => 'STEAM RICE', 'category' => 'Makanan', 'price' => 6000, 'sku' => 'HRS-011', 'cost' => 2000],
            ['name' => 'SALAD', 'category' => 'Makanan', 'price' => 12000, 'sku' => 'HRS-012', 'cost' => 4500],
            ['name' => 'SHRIMP ROLL', 'category' => 'Makanan', 'price' => 20000, 'sku' => 'HRS-013', 'cost' => 9000],
            ['name' => 'SPICY CHIKEN', 'category' => 'Makanan', 'price' => 20000, 'sku' => 'HRS-014', 'cost' => 9000],
            ['name' => 'EGG ROLL', 'category' => 'Makanan', 'price' => 20000, 'sku' => 'HRS-015', 'cost' => 8500],
            ['name' => 'CHICKEN KATSU', 'category' => 'Makanan', 'price' => 30000, 'sku' => 'HRS-016', 'cost' => 13500],
            ['name' => 'CHICKEN CURRY', 'category' => 'Makanan', 'price' => 35000, 'sku' => 'HRS-017', 'cost' => 16000],
            ['name' => 'KATSU MENTAI', 'category' => 'Makanan', 'price' => 35000, 'sku' => 'HRS-018', 'cost' => 16000],
            ['name' => 'KATSU CURRY', 'category' => 'Makanan', 'price' => 35000, 'sku' => 'HRS-019', 'cost' => 16000],
            ['name' => 'KATSU MOZARO', 'category' => 'Makanan', 'price' => 35000, 'sku' => 'HRS-020', 'cost' => 17000],
            ['name' => 'BBQ CHILI KATSU', 'category' => 'Makanan', 'price' => 35000, 'sku' => 'HRS-021', 'cost' => 16000],
            ['name' => 'CHILI CHEESE KATSU', 'category' => 'Makanan', 'price' => 35000, 'sku' => 'HRS-022', 'cost' => 17000],
            ['name' => 'CHICKEN TERIYAKI', 'category' => 'Makanan', 'price' => 30000, 'sku' => 'HRS-023', 'cost' => 13500],
            ['name' => 'CHICKEN YAKINIKU', 'category' => 'Makanan', 'price' => 30000, 'sku' => 'HRS-024', 'cost' => 13500],
            ['name' => 'BEEF TERIYAKI', 'category' => 'Makanan', 'price' => 37000, 'sku' => 'HRS-025', 'cost' => 18000],
            ['name' => 'BEEF YAKINIKU', 'category' => 'Makanan', 'price' => 37000, 'sku' => 'HRS-026', 'cost' => 18000],
            ['name' => 'BEEF CURRY', 'category' => 'Makanan', 'price' => 37000, 'sku' => 'HRS-027', 'cost' => 18000],
            ['name' => 'TAKE AWAY', 'category' => 'LAINNYA', 'price' => 2000, 'sku' => 'HRS-028', 'cost' => 500],
            ['name' => 'EXTRA MAYO', 'category' => 'Makanan', 'price' => 5000, 'sku' => 'HRS-029', 'cost' => 1500],
            ['name' => 'CHICKEN KATSU CHILI OIL', 'category' => 'Makanan', 'price' => 35000, 'sku' => 'HRS-030', 'cost' => 16000],
            ['name' => 'KARAGE HOT FIRE', 'category' => 'Makanan', 'price' => 30000, 'sku' => 'HRS-031', 'cost' => 14000],
            ['name' => 'KARAGE ORIGINAL', 'category' => 'Makanan', 'price' => 30000, 'sku' => 'HRS-032', 'cost' => 13500],
            ['name' => 'Paket Piala Dunia', 'category' => 'Promo', 'price' => 89000, 'sku' => 'HRS-033', 'cost' => 42000],
        ];

        foreach ($rawProducts as $item) {
            $cat = $catMap[$item['category']] ?? $catMap['Makanan'];

            $product = Product::create([
                'category_id' => $cat->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
                'cost_method' => 'manual',
                'manual_cost' => $item['cost'],
                'image_path' => null, // Default kosong (ditampilkan dengan ikon vektor)
                'has_variants' => false,
                'is_active' => true,
            ]);

            $dinePrice = $item['price'];
            $takeAwayPrice = $item['name'] === 'TAKE AWAY' ? $item['price'] : $item['price'];
            // Delivery harga ojol (misal +15% rounded ke ratusan terdekat)
            $deliveryPrice = (int) (ceil(($item['price'] * 1.15) / 100) * 100);

            // Multi-harga Dine In
            ProductPrice::create([
                'sellable_type' => 'Product',
                'sellable_id' => $product->id,
                'order_type_id' => $dineIn->id,
                'branch_id' => null,
                'price' => $dinePrice,
            ]);

            // Multi-harga Take Away
            ProductPrice::create([
                'sellable_type' => 'Product',
                'sellable_id' => $product->id,
                'order_type_id' => $takeAway->id,
                'branch_id' => null,
                'price' => $takeAwayPrice,
            ]);

            // Multi-harga Delivery (Ojol)
            ProductPrice::create([
                'sellable_type' => 'Product',
                'sellable_id' => $product->id,
                'order_type_id' => $delivery->id,
                'branch_id' => null,
                'price' => $deliveryPrice,
            ]);
        }

        // 7. Satuan Unit
        $units = [
            ['name' => 'Kilogram', 'symbol' => 'kg'],
            ['name' => 'Gram', 'symbol' => 'gr'],
            ['name' => 'Liter', 'symbol' => 'ltr'],
            ['name' => 'Mililiter', 'symbol' => 'ml'],
            ['name' => 'Pieces / Porsi', 'symbol' => 'pcs'],
            ['name' => 'Pack / Bungkus', 'symbol' => 'pack'],
        ];
        foreach ($units as $u) {
            Unit::firstOrCreate(['symbol' => $u['symbol']], $u);
        }
    }
}
