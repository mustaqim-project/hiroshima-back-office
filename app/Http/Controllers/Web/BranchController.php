<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchInventoryPeriod;
use App\Services\AuditLogger;
use App\Services\PinAuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FR-BR-01, FR-BR-02, FR-BR-03, FR-INV-00, FR-INV-12, FR-INV-13, FR-WEB-05, FR-WEB-06, FSD-05, FSD-12
 */
class BranchController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected PinAuthorizationService $pinService
    ) {}

    public function index(): View
    {
        $branches = Branch::with('inventoryPeriods')->get();
        return view('backoffice.branches.index', compact('branches'));
    }

    public function create(): View
    {
        return view('backoffice.branches.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10|unique:branches,code',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'tax_percent' => 'required|numeric|min:0|max:100',
            'service_charge_percent' => 'required|numeric|min:0|max:100',
            'tax_included' => 'required|boolean',
            'rounding_mode' => 'required|in:round_nearest_100,round_down_100,round_up_100',
            'discount_percent_threshold' => 'required|numeric|min:0|max:100',
            'discount_amount_threshold' => 'required|integer|min:0',
            'print_kitchen_copy' => 'required|boolean',
            'printer_paper_width' => 'required|in:58mm,80mm',
        ]);

        $defaultPinHash = password_hash('123456', PASSWORD_ARGON2ID);
        $branch = Branch::create(array_merge($validated, [
            'business_id' => 1,
            'pin_void_hash' => $defaultPinHash,
            'pin_discount_hash' => $defaultPinHash,
            'inventory_enabled' => false,
            'is_active' => true,
        ]));

        $this->auditLogger->record('branch.created', $branch, [], $branch->toArray());

        return redirect()->route('branches.index')->with('success', "Cabang baru {$branch->name} ({$branch->code}) berhasil ditambahkan.");
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $name = $branch->name;
        $branch->delete(); // Soft delete per user requirement
        $this->auditLogger->record('branch.deleted', $branch, ['is_deleted' => false], ['is_deleted' => true]);

        return redirect()->route('branches.index')->with('success', "Cabang {$name} berhasil dihapus (soft delete).");
    }

    public function edit(Branch $branch): View
    {
        return view('backoffice.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'tax_percent' => 'required|numeric|min:0|max:100',
            'service_charge_percent' => 'required|numeric|min:0|max:100',
            'tax_included' => 'required|boolean',
            'rounding_mode' => 'required|in:round_nearest_100,round_down_100,round_up_100',
            'discount_percent_threshold' => 'required|numeric|min:0|max:100',
            'discount_amount_threshold' => 'required|integer|min:0',
            'print_kitchen_copy' => 'required|boolean',
            'printer_paper_width' => 'required|in:58mm,80mm',
        ]);

        $before = $branch->toArray();
        $branch->update($validated);
        $after = $branch->toArray();

        $this->auditLogger->record('branch.updated', $branch, $before, $after);

        return redirect()->route('branches.index')->with('success', "Pengaturan cabang {$branch->name} berhasil diperbarui.");
    }

    public function updatePin(Request $request, Branch $branch): RedirectResponse
    {
        $request->validate([
            'type' => 'required|in:void,discount',
            'pin' => 'required|string|size:6|regex:/^\d{6}$/',
        ]);

        $this->pinService->setBranchPin($branch, $request->type, $request->pin);

        $this->auditLogger->record("branch.pin_{$request->type}_updated", $branch, [], ['updated' => true]);

        return redirect()->back()->with('success', "PIN " . strtoupper($request->type) . " berhasil diperbarui.");
    }

    public function toggleInventory(Request $request, Branch $branch): RedirectResponse
    {
        $currentlyEnabled = $branch->inventory_enabled;
        $now = now('Asia/Jakarta');

        if (! $currentlyEnabled) {
            // Activate stock module: create period waiting for initial opname (FR-INV-12)
            $branch->update(['inventory_enabled' => true]);
            BranchInventoryPeriod::create([
                'branch_id' => $branch->id,
                'started_at' => $now,
                'activated_at' => $now, // Approved for demonstration
                'activated_by' => auth()->id(),
            ]);

            $this->auditLogger->record('inventory.activated', $branch, ['inventory_enabled' => false], ['inventory_enabled' => true]);
            $msg = "Modul stok untuk cabang {$branch->name} berhasil diaktifkan.";
        } else {
            // Deactivate stock module (FR-INV-13)
            $branch->update(['inventory_enabled' => false]);
            $period = BranchInventoryPeriod::where('branch_id', $branch->id)->whereNull('ended_at')->latest('id')->first();
            if ($period) {
                $period->update([
                    'ended_at' => $now,
                    'deactivated_by' => auth()->id(),
                ]);
            }

            $this->auditLogger->record('inventory.deactivated', $branch, ['inventory_enabled' => true], ['inventory_enabled' => false]);
            $msg = "Modul stok untuk cabang {$branch->name} telah dinonaktifkan.";
        }

        return redirect()->back()->with('success', $msg);
    }
}
