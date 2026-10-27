<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Promo;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FR-PROMO-01, FR-PROMO-02, FR-PROMO-03, FSD-06
 */
class PromoController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $promos = Promo::with('branches')->orderBy('priority', 'desc')->get();
        return view('backoffice.promos.index', compact('promos'));
    }

    public function create(): View
    {
        $branches = Branch::where('is_active', true)->get();
        return view('backoffice.promos.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:promos,code',
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0',
            'min_purchase' => 'required|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'priority' => 'required|integer|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
            'is_active' => 'required|boolean',
        ]);

        $branchIds = $validated['branch_ids'] ?? [];
        unset($validated['branch_ids']);

        $promo = Promo::create(array_merge($validated, [
            'is_stackable' => false,
        ]));

        if (! empty($branchIds)) {
            $promo->branches()->sync($branchIds);
        } else {
            // Default apply to all branches
            $promo->branches()->sync(Branch::pluck('id'));
        }

        $this->auditLogger->record('promo.created', $promo, [], $promo->toArray());

        return redirect()->route('promos.index')->with('success', "Promosi {$promo->name} berhasil ditambahkan.");
    }

    public function edit(Promo $promo): View
    {
        $branches = Branch::where('is_active', true)->get();
        return view('backoffice.promos.edit', compact('promo', 'branches'));
    }

    public function update(Request $request, Promo $promo): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:promos,code,' . $promo->id,
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0',
            'min_purchase' => 'required|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'priority' => 'required|integer|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
            'is_active' => 'required|boolean',
        ]);

        $branchIds = $validated['branch_ids'] ?? [];
        unset($validated['branch_ids']);

        $before = $promo->toArray();
        $promo->update($validated);
        $after = $promo->toArray();

        if (! empty($branchIds)) {
            $promo->branches()->sync($branchIds);
        }

        $this->auditLogger->record('promo.updated', $promo, $before, $after);

        return redirect()->route('promos.index')->with('success', "Promosi {$promo->name} berhasil diperbarui.");
    }

    public function destroy(Promo $promo): RedirectResponse
    {
        $name = $promo->name;
        $promo->delete(); // Soft delete
        $this->auditLogger->record('promo.deleted', $promo, ['deleted' => false], ['deleted' => true]);

        return redirect()->route('promos.index')->with('success', "Promosi {$name} berhasil dihapus (soft delete).");
    }
}
