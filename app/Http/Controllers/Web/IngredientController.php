<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FR-RCP-01, FR-HPP-01, FSD-04
 */
class IngredientController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $ingredients = Ingredient::with('unit')->orderBy('id')->get();
        return view('backoffice.ingredients.index', compact('ingredients'));
    }

    public function create(): View
    {
        $units = Unit::all();
        return view('backoffice.ingredients.create', compact('units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'sku' => 'required|string|max:50|unique:ingredients,sku',
            'unit_id' => 'required|exists:units,id',
            'standard_cost' => 'required|integer|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $ingredient = Ingredient::create($validated);
        $this->auditLogger->record('ingredient.created', $ingredient, [], $ingredient->toArray());

        return redirect()->route('ingredients.index')->with('success', "Bahan baku {$ingredient->name} berhasil ditambahkan.");
    }

    public function edit(Ingredient $ingredient): View
    {
        $units = Unit::all();
        return view('backoffice.ingredients.edit', compact('ingredient', 'units'));
    }

    public function update(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'sku' => 'required|string|max:50|unique:ingredients,sku,' . $ingredient->id,
            'unit_id' => 'required|exists:units,id',
            'standard_cost' => 'required|integer|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $before = $ingredient->toArray();
        $ingredient->update($validated);
        $after = $ingredient->toArray();

        $this->auditLogger->record('ingredient.updated', $ingredient, $before, $after);

        return redirect()->route('ingredients.index')->with('success', "Bahan baku {$ingredient->name} berhasil diperbarui.");
    }

    public function destroy(Ingredient $ingredient): RedirectResponse
    {
        $name = $ingredient->name;
        $ingredient->delete(); // Soft delete
        $this->auditLogger->record('ingredient.deleted', $ingredient, ['deleted' => false], ['deleted' => true]);

        return redirect()->route('ingredients.index')->with('success', "Bahan baku {$name} berhasil dihapus (soft delete).");
    }
}
