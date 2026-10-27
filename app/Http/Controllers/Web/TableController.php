<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Table;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FR-MST-08, FSD-03
 */
class TableController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $tables = Table::with('branch')->orderBy('branch_id')->orderBy('table_no')->get();
        return view('backoffice.tables.index', compact('tables'));
    }

    public function create(): View
    {
        $branches = Branch::where('is_active', true)->get();
        return view('backoffice.tables.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'table_no' => 'required|string|max:20',
            'section' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:available,occupied,reserved',
            'is_active' => 'required|boolean',
        ]);

        $table = Table::create($validated);
        $this->auditLogger->record('table.created', $table, [], $table->toArray());

        return redirect()->route('tables.index')->with('success', "Meja {$table->table_no} berhasil ditambahkan.");
    }

    public function edit(Table $table): View
    {
        $branches = Branch::where('is_active', true)->get();
        return view('backoffice.tables.edit', compact('table', 'branches'));
    }

    public function update(Request $request, Table $table): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'table_no' => 'required|string|max:20',
            'section' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:available,occupied,reserved',
            'is_active' => 'required|boolean',
        ]);

        $before = $table->toArray();
        $table->update($validated);
        $after = $table->toArray();

        $this->auditLogger->record('table.updated', $table, $before, $after);

        return redirect()->route('tables.index')->with('success', "Meja {$table->table_no} berhasil diperbarui.");
    }

    public function destroy(Table $table): RedirectResponse
    {
        $tableNo = $table->table_no;
        $table->delete(); // Soft delete
        $this->auditLogger->record('table.deleted', $table, ['deleted' => false], ['deleted' => true]);

        return redirect()->route('tables.index')->with('success', "Meja {$tableNo} berhasil dihapus (soft delete).");
    }
}
