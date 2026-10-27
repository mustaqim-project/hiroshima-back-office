<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FR-MST-01, FSD-03
 */
class CategoryController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $categories = Category::withCount('products')->orderBy('sort_order')->get();
        return view('backoffice.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('backoffice.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:categories,code',
            'target_printer' => 'required|in:kitchen,bar,cashier',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $category = Category::create($validated);
        $this->auditLogger->record('category.created', $category, [], $category->toArray());

        return redirect()->route('categories.index')->with('success', "Kategori {$category->name} berhasil ditambahkan.");
    }

    public function edit(Category $category): View
    {
        return view('backoffice.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:categories,code,' . $category->id,
            'target_printer' => 'required|in:kitchen,bar,cashier',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $before = $category->toArray();
        $category->update($validated);
        $after = $category->toArray();

        $this->auditLogger->record('category.updated', $category, $before, $after);

        return redirect()->route('categories.index')->with('success', "Kategori {$category->name} berhasil diperbarui.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        $name = $category->name;
        $category->delete(); // Soft delete
        $this->auditLogger->record('category.deleted', $category, ['deleted' => false], ['deleted' => true]);

        return redirect()->route('categories.index')->with('success', "Kategori {$name} berhasil dihapus (soft delete).");
    }
}
