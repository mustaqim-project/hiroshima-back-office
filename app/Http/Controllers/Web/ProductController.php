<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\OrderType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FR-MENU-01, FR-MENU-02, FR-MENU-03, FR-PRICE-01, FSD-03
 */
class ProductController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $products = Product::with(['category', 'prices.orderType', 'modifierGroups.modifiers', 'variants'])->orderBy('id')->get();
        $orderTypes = OrderType::where('is_active', true)->orderBy('sort_order')->get();
        return view('backoffice.products.index', compact('products', 'orderTypes'));
    }

    public function create(): View
    {
        $categories = Category::where('is_active', true)->get();
        $orderTypes = OrderType::where('is_active', true)->orderBy('sort_order')->get();
        $modifierGroups = ModifierGroup::where('is_active', true)->with('modifiers')->get();
        return view('backoffice.products.create', compact('categories', 'orderTypes', 'modifierGroups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:100',
            'sku' => 'required|string|max:50|unique:products,sku',
            'base_price' => 'nullable|integer|min:0',
            'prices' => 'nullable|array',
            'prices.*' => 'nullable|integer|min:0',
            'manual_cost' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active' => 'required|boolean',
            'has_variants' => 'nullable|boolean',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required_with:variants|string|max:100',
            'variants.*.sku' => 'nullable|string|max:50',
            'variants.*.price' => 'nullable|integer|min:0',
            'variants.*.manual_cost' => 'nullable|integer|min:0',
            'modifier_group_ids' => 'nullable|array',
            'modifier_group_ids.*' => 'exists:modifier_groups,id',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('products', 'public');
            $validated['image_path'] = '/storage/' . $path;

            try {
                $target = public_path('storage/' . $path);
                if (!file_exists(dirname($target))) {
                    @mkdir(dirname($target), 0755, true);
                }
                @copy(storage_path('app/public/' . $path), $target);
            } catch (\Throwable) {}

            try {
                $target = public_path('storage/' . $path);
                if (!file_exists(dirname($target))) {
                    @mkdir(dirname($target), 0755, true);
                }
                @copy(storage_path('app/public/' . $path), $target);
            } catch (\Throwable) {}
        }

        $basePrice = $validated['base_price'] ?? null;
        $prices = $request->input('prices', []);

        if (! empty($prices)) {
            $defaultOrderType = OrderType::default();
            $basePrice = $prices[$defaultOrderType?->id] ?? reset($prices) ?? $basePrice ?? 0;
        }

        $hasVariants = $request->boolean('has_variants');
        $modifierGroupIds = $request->input('modifier_group_ids', []);
        $variantsData = $request->input('variants', []);

        unset($validated['base_price'], $validated['prices'], $validated['photo'], $validated['has_variants'], $validated['variants'], $validated['modifier_group_ids']);

        $product = Product::create(array_merge($validated, [
            'cost_method' => 'manual',
            'manual_cost' => $validated['manual_cost'] ?? 0,
            'has_variants' => $hasVariants,
        ]));

        $allOrderTypes = OrderType::all();

        // Populate multi-prices per order type
        foreach ($allOrderTypes as $ot) {
            $priceAmount = isset($prices[$ot->id]) && $prices[$ot->id] !== '' 
                ? (int) $prices[$ot->id] 
                : (int) ($basePrice ?? 0);

            ProductPrice::updateOrCreate(
                [
                    'sellable_type' => 'Product',
                    'sellable_id' => $product->id,
                    'order_type_id' => $ot->id,
                    'branch_id' => null, // Berlaku global seluruh cabang
                ],
                [
                    'price' => $priceAmount,
                ]
            );
        }

        // Attach modifier groups
        if (!empty($modifierGroupIds)) {
            $product->modifierGroups()->sync($modifierGroupIds);
        }

        // Save variants if enabled
        if ($hasVariants && !empty($variantsData)) {
            $index = 1;
            foreach ($variantsData as $v) {
                if (!empty(trim($v['name'] ?? ''))) {
                    $vSku = !empty($v['sku']) ? trim($v['sku']) : $product->sku . '-V' . $index;
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => trim($v['name']),
                        'sku' => $vSku,
                        'price' => (int) ($v['price'] ?? $basePrice ?? 0),
                        'manual_cost' => (int) ($v['manual_cost'] ?? 0),
                        'cost_method' => 'manual',
                        'is_active' => true,
                    ]);
                    $index++;
                }
            }
        }

        $this->auditLogger->record('product.created', $product, [], $product->toArray());

        return redirect()->route('products.index')->with('success', "Menu {$product->name} berhasil ditambahkan dengan multi-harga dan konfigurasi varian/modifier.");
    }

    public function edit(Product $product): View
    {
        $categories = Category::where('is_active', true)->get();
        $orderTypes = OrderType::where('is_active', true)->orderBy('sort_order')->get();
        $modifierGroups = ModifierGroup::where('is_active', true)->with('modifiers')->get();
        $attachedModifierGroupIds = $product->modifierGroups->pluck('id')->toArray();
        $variants = $product->variants;

        $priceMap = $product->prices->pluck('price', 'order_type_id')->toArray();
        $defaultOrderType = OrderType::default();
        $defaultPrice = $priceMap[$defaultOrderType?->id ?? 1] ?? ($product->prices->first()?->price ?? 0);

        return view('backoffice.products.edit', compact(
            'product', 
            'categories', 
            'orderTypes', 
            'priceMap', 
            'defaultPrice', 
            'modifierGroups', 
            'attachedModifierGroupIds', 
            'variants'
        ));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:100',
            'sku' => 'required|string|max:50|unique:products,sku,' . $product->id,
            'base_price' => 'nullable|integer|min:0',
            'prices' => 'nullable|array',
            'prices.*' => 'nullable|integer|min:0',
            'manual_cost' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_photo' => 'nullable|boolean',
            'is_active' => 'required|boolean',
            'has_variants' => 'nullable|boolean',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.name' => 'required_with:variants|string|max:100',
            'variants.*.sku' => 'nullable|string|max:50',
            'variants.*.price' => 'nullable|integer|min:0',
            'variants.*.manual_cost' => 'nullable|integer|min:0',
            'modifier_group_ids' => 'nullable|array',
            'modifier_group_ids.*' => 'exists:modifier_groups,id',
        ]);

        if ($request->boolean('remove_photo')) {
            if ($product->image_path && str_starts_with($product->image_path, '/storage/')) {
                $relative = str_replace('/storage/', '', $product->image_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($relative);
            }
            $validated['image_path'] = null;
        } elseif ($request->hasFile('photo')) {
            if ($product->image_path && str_starts_with($product->image_path, '/storage/')) {
                $relative = str_replace('/storage/', '', $product->image_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($relative);
            }
            $path = $request->file('photo')->store('products', 'public');
            $validated['image_path'] = '/storage/' . $path;

            try {
                $target = public_path('storage/' . $path);
                if (!file_exists(dirname($target))) {
                    @mkdir(dirname($target), 0755, true);
                }
                @copy(storage_path('app/public/' . $path), $target);
            } catch (\Throwable) {}
        }

        $basePrice = $validated['base_price'] ?? null;
        $prices = $request->input('prices', []);

        if (! empty($prices)) {
            $defaultOrderType = OrderType::default();
            $basePrice = $prices[$defaultOrderType?->id] ?? reset($prices) ?? $basePrice ?? 0;
        }

        $hasVariants = $request->boolean('has_variants');
        $modifierGroupIds = $request->input('modifier_group_ids', []);
        $variantsData = $request->input('variants', []);

        unset($validated['base_price'], $validated['prices'], $validated['photo'], $validated['remove_photo'], $validated['has_variants'], $validated['variants'], $validated['modifier_group_ids']);

        $before = $product->toArray();
        $product->update(array_merge($validated, [
            'has_variants' => $hasVariants,
        ]));
        $after = $product->toArray();

        $allOrderTypes = OrderType::all();

        // Update multi-prices per order type
        foreach ($allOrderTypes as $ot) {
            $priceAmount = isset($prices[$ot->id]) && $prices[$ot->id] !== '' 
                ? (int) $prices[$ot->id] 
                : (int) ($basePrice ?? ($product->prices->firstWhere('order_type_id', $ot->id)?->price ?? 0));

            ProductPrice::updateOrCreate(
                [
                    'sellable_type' => 'Product',
                    'sellable_id' => $product->id,
                    'order_type_id' => $ot->id,
                    'branch_id' => null,
                ],
                [
                    'price' => $priceAmount,
                ]
            );
        }

        // Sync modifier groups
        $product->modifierGroups()->sync($modifierGroupIds);

        // Sync product variants
        if ($hasVariants && !empty($variantsData)) {
            $submittedVariantIds = [];
            $index = 1;

            foreach ($variantsData as $v) {
                $name = trim($v['name'] ?? '');
                if (empty($name)) continue;

                $vSku = !empty($v['sku']) ? trim($v['sku']) : $product->sku . '-V' . $index;
                $vId = $v['id'] ?? null;

                if ($vId) {
                    $existing = ProductVariant::where('product_id', $product->id)->find($vId);
                    if ($existing) {
                        $existing->update([
                            'name' => $name,
                            'sku' => $vSku,
                            'price' => (int) ($v['price'] ?? $basePrice ?? 0),
                            'manual_cost' => (int) ($v['manual_cost'] ?? 0),
                            'is_active' => true,
                        ]);
                        $submittedVariantIds[] = $existing->id;
                        $index++;
                        continue;
                    }
                }

                $newVar = ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $name,
                    'sku' => $vSku,
                    'price' => (int) ($v['price'] ?? $basePrice ?? 0),
                    'manual_cost' => (int) ($v['manual_cost'] ?? 0),
                    'cost_method' => 'manual',
                    'is_active' => true,
                ]);
                $submittedVariantIds[] = $newVar->id;
                $index++;
            }

            ProductVariant::where('product_id', $product->id)->whereNotIn('id', $submittedVariantIds)->delete();
        } elseif (!$hasVariants) {
            $product->variants()->delete();
        }

        $this->auditLogger->record('product.updated', $product, $before, $after);

        return redirect()->route('products.index')->with('success', "Menu {$product->name} berhasil diperbarui beserta varian dan grup modifier.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $product->variants()->delete();
        $product->modifierGroups()->detach();
        $product->delete(); // Soft delete
        $this->auditLogger->record('product.deleted', $product, ['deleted' => false], ['deleted' => true]);

        return redirect()->route('products.index')->with('success', "Menu {$name} berhasil dihapus (soft delete).");
    }
}
