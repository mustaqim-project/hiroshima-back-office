<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ModifierAndVariantSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Grup Level Pedas (Wajib Pilih 1)
        $levelPedas = ModifierGroup::firstOrCreate(
            ['name' => 'Level Pedas (Pilihan Rasa)'],
            [
                'selection_type' => 'single',
                'min_select' => 1,
                'max_select' => 1,
                'is_required' => true,
                'is_active' => true,
            ]
        );

        $pedasOptions = [
            ['name' => 'Level 0 (Original Tidak Pedas)', 'price' => 0, 'cost' => 0],
            ['name' => 'Level 1 (Pedas Sedang Gurih)', 'price' => 0, 'cost' => 500],
            ['name' => 'Level 2 (Pedas Mantap)', 'price' => 0, 'cost' => 1000],
            ['name' => 'Level 3 (Hot Fire Hiroshima 🔥)', 'price' => 2000, 'cost' => 1200],
        ];

        foreach ($pedasOptions as $opt) {
            Modifier::firstOrCreate(
                ['modifier_group_id' => $levelPedas->id, 'name' => $opt['name']],
                ['price' => $opt['price'], 'cost' => $opt['cost'], 'is_active' => true]
            );
        }

        // 2. Grup Ekstra Topping & Saus (Bisa Pilih Banyak / Opsional)
        $toppingGroup = ModifierGroup::firstOrCreate(
            ['name' => 'Ekstra Topping & Saus Spesial'],
            [
                'selection_type' => 'multiple',
                'min_select' => 0,
                'max_select' => 5,
                'is_required' => false,
                'is_active' => true,
            ]
        );

        $toppingOptions = [
            ['name' => 'Ekstra Keju Mozzarella Melted', 'price' => 6000, 'cost' => 3500],
            ['name' => 'Ekstra Saus Mentai Torched', 'price' => 5000, 'cost' => 2800],
            ['name' => 'Ekstra Crispy Garlic Chili Oil', 'price' => 3000, 'cost' => 1500],
            ['name' => 'Ekstra Telur Mata Sapi / Onsen Egg', 'price' => 4000, 'cost' => 2200],
            ['name' => 'Ekstra Mayonnaise Gurih', 'price' => 2000, 'cost' => 1000],
        ];

        foreach ($toppingOptions as $opt) {
            Modifier::firstOrCreate(
                ['modifier_group_id' => $toppingGroup->id, 'name' => $opt['name']],
                ['price' => $opt['price'], 'cost' => $opt['cost'], 'is_active' => true]
            );
        }

        // 3. Grup Preferensi Minuman (Es & Gula)
        $drinkGroup = ModifierGroup::firstOrCreate(
            ['name' => 'Pilihan Es & Tingkat Manis'],
            [
                'selection_type' => 'single',
                'min_select' => 1,
                'max_select' => 1,
                'is_required' => true,
                'is_active' => true,
            ]
        );

        $drinkOptions = [
            ['name' => 'Normal Ice & Normal Sweet (Standar)', 'price' => 0, 'cost' => 0],
            ['name' => 'Less Ice (Sedikit Es)', 'price' => 0, 'cost' => 0],
            ['name' => 'Less Sugar (Sedikit Manis)', 'price' => 0, 'cost' => 0],
            ['name' => 'Tanpa Es (Suhu Normal/Hangat)', 'price' => 0, 'cost' => 0],
            ['name' => 'Extra Ice (Ekstra Dingin Segar)', 'price' => 0, 'cost' => 0],
        ];

        foreach ($drinkOptions as $opt) {
            Modifier::firstOrCreate(
                ['modifier_group_id' => $drinkGroup->id, 'name' => $opt['name']],
                ['price' => $opt['price'], 'cost' => $opt['cost'], 'is_active' => true]
            );
        }

        // 4. Hubungkan ke Produk Makanan (Bento, Katsu, Spicy Chicken, Karaage, dsb)
        $foodProducts = Product::whereHas('category', function ($q) {
            $q->whereIn('name', ['Makanan', 'PAKET HIRO', 'Promo']);
        })->get();

        foreach ($foodProducts as $fp) {
            $fp->modifierGroups()->syncWithoutDetaching([$levelPedas->id, $toppingGroup->id]);
        }

        // 5. Hubungkan ke Produk Minuman (Ocha, Lemon Tea, dll)
        $drinkProducts = Product::whereHas('category', function ($q) {
            $q->where('name', 'MINUMAN');
        })->get();

        foreach ($drinkProducts as $dp) {
            $dp->modifierGroups()->syncWithoutDetaching([$drinkGroup->id]);
        }

        // 6. Setup Sample Varian pada Menu Populer (CHICKEN KATSU & KARAGE ORIGINAL)
        $katsu = Product::where('sku', 'HRS-016')->first();
        if ($katsu) {
            $katsu->update(['has_variants' => true]);
            ProductVariant::firstOrCreate(
                ['product_id' => $katsu->id, 'name' => 'Porsi Reguler (1 Katsu Cutlet)'],
                ['sku' => 'HRS-016-REG', 'price' => 30000, 'manual_cost' => 15000, 'cost_method' => 'manual', 'is_active' => true]
            );
            ProductVariant::firstOrCreate(
                ['product_id' => $katsu->id, 'name' => 'Porsi Jumbo Double (2 Katsu Cutlet)'],
                ['sku' => 'HRS-016-JMB', 'price' => 45000, 'manual_cost' => 24000, 'cost_method' => 'manual', 'is_active' => true]
            );
        }

        $karage = Product::where('sku', 'HRS-032')->first();
        if ($karage) {
            $karage->update(['has_variants' => true]);
            ProductVariant::firstOrCreate(
                ['product_id' => $karage->id, 'name' => 'Porsi Sedang (5 Pcs Karaage)'],
                ['sku' => 'HRS-032-MED', 'price' => 30000, 'manual_cost' => 14000, 'cost_method' => 'manual', 'is_active' => true]
            );
            ProductVariant::firstOrCreate(
                ['product_id' => $karage->id, 'name' => 'Porsi Party Besar (10 Pcs Karaage)'],
                ['sku' => 'HRS-032-LRG', 'price' => 55000, 'manual_cost' => 26000, 'cost_method' => 'manual', 'is_active' => true]
            );
        }
    }
}
