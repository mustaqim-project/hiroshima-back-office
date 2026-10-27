<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FR-MENU-03, FSD-03
 */
class ModifierGroupController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $modifierGroups = ModifierGroup::with(['modifiers', 'products'])
            ->orderBy('id')
            ->get();

        $totalGroups = $modifierGroups->count();
        $totalModifiers = Modifier::count();
        $totalProductsAttached = $modifierGroups->sum(fn ($g) => $g->products->count());

        return view('backoffice.modifiers.index', compact('modifierGroups', 'totalGroups', 'totalModifiers', 'totalProductsAttached'));
    }

    public function create(): View
    {
        $products = Product::where('is_active', true)->with('category')->orderBy('category_id')->orderBy('name')->get();
        return view('backoffice.modifiers.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'selection_type' => 'required|in:single,multiple',
            'min_select' => 'required|integer|min:0|max:50',
            'max_select' => 'required|integer|min:1|max:50',
            'is_required' => 'required|boolean',
            'is_active' => 'required|boolean',
            'modifiers' => 'required|array|min:1',
            'modifiers.*.name' => 'required|string|max:100',
            'modifiers.*.price' => 'nullable|integer|min:0',
            'modifiers.*.cost' => 'nullable|integer|min:0',
            'modifiers.*.is_active' => 'nullable|boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $group = ModifierGroup::create([
            'name' => $validated['name'],
            'selection_type' => $validated['selection_type'],
            'min_select' => $validated['min_select'],
            'max_select' => $validated['max_select'],
            'is_required' => $validated['is_required'],
            'is_active' => $validated['is_active'],
        ]);

        foreach ($validated['modifiers'] as $modData) {
            if (!empty(trim($modData['name'] ?? ''))) {
                Modifier::create([
                    'modifier_group_id' => $group->id,
                    'name' => trim($modData['name']),
                    'price' => (int) ($modData['price'] ?? 0),
                    'cost' => (int) ($modData['cost'] ?? 0),
                    'is_active' => (bool) ($modData['is_active'] ?? true),
                ]);
            }
        }

        if (!empty($validated['product_ids'])) {
            $group->products()->sync($validated['product_ids']);
        }

        $this->auditLogger->record('modifier_group.created', $group, [], $group->toArray());

        return redirect()->route('modifier-groups.index')->with('success', "Grup modifier '{$group->name}' berhasil ditambahkan beserta item opsinya.");
    }

    public function edit(ModifierGroup $modifierGroup): View
    {
        $modifierGroup->load(['modifiers', 'products']);
        $products = Product::where('is_active', true)->with('category')->orderBy('category_id')->orderBy('name')->get();
        $attachedProductIds = $modifierGroup->products->pluck('id')->toArray();

        return view('backoffice.modifiers.edit', compact('modifierGroup', 'products', 'attachedProductIds'));
    }

    public function update(Request $request, ModifierGroup $modifierGroup): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'selection_type' => 'required|in:single,multiple',
            'min_select' => 'required|integer|min:0|max:50',
            'max_select' => 'required|integer|min:1|max:50',
            'is_required' => 'required|boolean',
            'is_active' => 'required|boolean',
            'modifiers' => 'required|array|min:1',
            'modifiers.*.id' => 'nullable|integer',
            'modifiers.*.name' => 'required|string|max:100',
            'modifiers.*.price' => 'nullable|integer|min:0',
            'modifiers.*.cost' => 'nullable|integer|min:0',
            'modifiers.*.is_active' => 'nullable|boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $before = $modifierGroup->toArray();

        $modifierGroup->update([
            'name' => $validated['name'],
            'selection_type' => $validated['selection_type'],
            'min_select' => $validated['min_select'],
            'max_select' => $validated['max_select'],
            'is_required' => $validated['is_required'],
            'is_active' => $validated['is_active'],
        ]);

        $submittedModifierIds = [];

        foreach ($validated['modifiers'] as $modData) {
            $name = trim($modData['name'] ?? '');
            if (empty($name)) continue;

            $modId = $modData['id'] ?? null;
            if ($modId) {
                $existing = Modifier::where('modifier_group_id', $modifierGroup->id)->find($modId);
                if ($existing) {
                    $existing->update([
                        'name' => $name,
                        'price' => (int) ($modData['price'] ?? 0),
                        'cost' => (int) ($modData['cost'] ?? 0),
                        'is_active' => (bool) ($modData['is_active'] ?? true),
                    ]);
                    $submittedModifierIds[] = $existing->id;
                    continue;
                }
            }

            $newMod = Modifier::create([
                'modifier_group_id' => $modifierGroup->id,
                'name' => $name,
                'price' => (int) ($modData['price'] ?? 0),
                'cost' => (int) ($modData['cost'] ?? 0),
                'is_active' => (bool) ($modData['is_active'] ?? true),
            ]);
            $submittedModifierIds[] = $newMod->id;
        }

        // Soft delete removed modifiers
        Modifier::where('modifier_group_id', $modifierGroup->id)
            ->whereNotIn('id', $submittedModifierIds)
            ->delete();

        // Sync product relations
        $modifierGroup->products()->sync($validated['product_ids'] ?? []);

        $this->auditLogger->record('modifier_group.updated', $modifierGroup, $before, $modifierGroup->toArray());

        return redirect()->route('modifier-groups.index')->with('success', "Grup modifier '{$modifierGroup->name}' berhasil diperbarui.");
    }

    public function destroy(ModifierGroup $modifierGroup): RedirectResponse
    {
        $name = $modifierGroup->name;
        $modifierGroup->modifiers()->delete();
        $modifierGroup->delete();

        $this->auditLogger->record('modifier_group.deleted', $modifierGroup, ['deleted' => false], ['deleted' => true]);

        return redirect()->route('modifier-groups.index')->with('success', "Grup modifier '{$name}' berhasil dihapus.");
    }
}
