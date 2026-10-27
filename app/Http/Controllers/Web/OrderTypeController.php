<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OrderType;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FR-MST-02, FSD-03
 */
class OrderTypeController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $orderTypes = OrderType::orderBy('sort_order')->orderBy('id')->get();
        return view('backoffice.order_types.index', compact('orderTypes'));
    }

    public function create(): View
    {
        return view('backoffice.order_types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:order_types,code',
            'is_default' => 'nullable|boolean',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);
        $validated['is_default'] = $request->boolean('is_default');

        if ($validated['is_default']) {
            OrderType::where('is_default', true)->update(['is_default' => false]);
        }

        $orderType = OrderType::create(array_merge($validated, [
            'requires_table' => false, // Street food kaki lima tidak menggunakan meja fisik
        ]));

        $this->auditLogger->record('order_type.created', $orderType, [], $orderType->toArray());

        return redirect()->route('order-types.index')->with('success', "Jenis Pesanan {$orderType->name} berhasil ditambahkan.");
    }

    public function edit(OrderType $orderType): View
    {
        return view('backoffice.order_types.edit', compact('orderType'));
    }

    public function update(Request $request, OrderType $orderType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:order_types,code,' . $orderType->id,
            'is_default' => 'nullable|boolean',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);
        $validated['is_default'] = $request->boolean('is_default');

        if ($validated['is_default']) {
            OrderType::where('id', '!=', $orderType->id)->where('is_default', true)->update(['is_default' => false]);
        }

        $before = $orderType->toArray();
        $orderType->update(array_merge($validated, [
            'requires_table' => false,
        ]));
        $after = $orderType->toArray();

        $this->auditLogger->record('order_type.updated', $orderType, $before, $after);

        return redirect()->route('order-types.index')->with('success', "Jenis Pesanan {$orderType->name} berhasil diperbarui.");
    }

    public function destroy(OrderType $orderType): RedirectResponse
    {
        if ($orderType->is_default) {
            return redirect()->route('order-types.index')->with('error', 'Jenis pesanan default tidak dapat dihapus.');
        }

        $name = $orderType->name;
        $orderType->delete(); // Soft delete

        $this->auditLogger->record('order_type.deleted', $orderType, ['deleted' => false], ['deleted' => true]);

        return redirect()->route('order-types.index')->with('success', "Jenis Pesanan {$name} berhasil dihapus (soft delete).");
    }
}
