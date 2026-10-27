<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashBook;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CashBookController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public static function categories(): array
    {
        return [
            'in' => [
                'Suntikan Modal / Kas Awal',
                'Modal Pusat / Investasi Owner',
                'Penjualan Non-POS / Catering',
                'Penerimaan Piutang / Refund Supplier',
                'Pendapatan Kemitraan / Franchise',
                'Pendapatan Bunga / Finansial',
                'Pendapatan Jasa / Lain-lain',
                'Lainnya',
            ],
            'out' => [
                'Belanja Bahan Baku / Bumbu Harian',
                'Gas LPG, Es Batu & Air Galon',
                'Uang Makan & Upah Harian Staf',
                'Gaji Manajemen / Karyawan Tetap',
                'Transportasi & Bensin Operasional',
                'Retribusi, Kebersihan & Sewa Lapak',
                'Sewa Ruko / Kantor / Tempat Usaha',
                'Kemasan, Plastik & Perlengkapan Street Food',
                'Perbaikan Alat, Kompor & Gerobak',
                'Listrik, Token, Air & Internet',
                'Pemasaran, Iklan & Branding',
                'Legalitas, Perizinan & Pajak',
                'Software, Server & Sistem',
                'Lainnya',
            ],
        ];
    }

    public function index(Request $request): View
    {
        $branchId = $request->query('branch_id');
        $type = $request->query('type');
        $category = $request->query('category');
        $startDate = $request->query('start_date', now('Asia/Jakarta')->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now('Asia/Jakarta')->toDateString());

        $branches = Branch::where('is_active', true)->get();

        $query = CashBook::with(['branch', 'creator'])
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        if ($branchId === 'global') {
            $query->whereNull('branch_id');
        } elseif ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        if ($type && in_array($type, ['in', 'out'])) {
            $query->where('type', $type);
        }

        if ($category) {
            $query->where('category', $category);
        }

        // Summary calculation
        $summaryQuery = CashBook::whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);
        if ($branchId === 'global') {
            $summaryQuery->whereNull('branch_id');
        } elseif ($branchId && $branchId !== 'all') {
            $summaryQuery->where('branch_id', $branchId);
        }

        $totalIn = (clone $summaryQuery)->where('type', 'in')->sum('amount');
        $totalOut = (clone $summaryQuery)->where('type', 'out')->sum('amount');
        $netBalance = $totalIn - $totalOut;

        // Breakdown stats: Global vs Outlet
        $globalIn = (clone $summaryQuery)->whereNull('branch_id')->where('type', 'in')->sum('amount');
        $globalOut = (clone $summaryQuery)->whereNull('branch_id')->where('type', 'out')->sum('amount');
        $outletIn = (clone $summaryQuery)->whereNotNull('branch_id')->where('type', 'in')->sum('amount');
        $outletOut = (clone $summaryQuery)->whereNotNull('branch_id')->where('type', 'out')->sum('amount');

        $transactions = $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->paginate(25);
        $categories = self::categories();

        $isGlobalAllowed = in_array(auth()->user()?->role, ['owner', 'central_admin']);

        return view('backoffice.cash_book.index', compact(
            'transactions',
            'branches',
            'branchId',
            'type',
            'category',
            'startDate',
            'endDate',
            'totalIn',
            'totalOut',
            'netBalance',
            'globalIn',
            'globalOut',
            'outletIn',
            'outletOut',
            'categories',
            'isGlobalAllowed'
        ));
    }

    public function create(): View
    {
        $branches = Branch::where('is_active', true)->get();
        $categories = self::categories();
        $isGlobalAllowed = in_array(auth()->user()?->role, ['owner', 'central_admin']);

        return view('backoffice.cash_book.create', compact('branches', 'categories', 'isGlobalAllowed'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $isGlobalAllowed = in_array($user?->role, ['owner', 'central_admin']);
        $isLainnya = strtolower(trim((string) $request->input('category'))) === 'lainnya';

        $branchRule = $isGlobalAllowed ? 'nullable|exists:branches,id' : 'required|exists:branches,id';

        $validated = $request->validate([
            'branch_id' => $branchRule,
            'type' => 'required|in:in,out',
            'category' => 'required|string|max:100',
            'amount' => 'required|integer|min:1',
            'transaction_date' => 'required|date',
            'description' => [
                $isLainnya ? 'required' : 'nullable',
                'string',
                'max:1000',
            ],
            'receipt_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'description.required' => 'Kolom keterangan wajib diisi jika memilih kategori "Lainnya".',
            'receipt_photo.max' => 'Ukuran file foto bukti maksimal 5MB.',
        ]);

        $targetBranchId = $validated['branch_id'] ?? null;
        if (! $isGlobalAllowed && $user?->branch_id) {
            $targetBranchId = $user->branch_id;
        }

        $photoPath = null;
        if ($request->hasFile('receipt_photo') && $request->file('receipt_photo')->isValid()) {
            $photoPath = $request->file('receipt_photo')->store('cash_books', 'public');
        }

        $cashBook = CashBook::create([
            'branch_id' => $targetBranchId, // null = Global (Kantor Pusat)
            'type' => $validated['type'],
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transaction_date'],
            'description' => $validated['description'] ?? null,
            'receipt_photo_path' => $photoPath,
            'created_by' => auth()->id() ?? 1,
        ]);

        $this->auditLogger->record('cash_book.created', $cashBook, [], [
            'branch_id' => $cashBook->branch_id,
            'is_global' => is_null($cashBook->branch_id),
            'type' => $cashBook->type,
            'category' => $cashBook->category,
            'amount' => $cashBook->amount,
        ]);

        $scopeLabel = is_null($cashBook->branch_id) ? 'Global (Kantor Pusat)' : ($cashBook->branch->name ?? 'Cabang');
        $typeLabel = $cashBook->type === 'in' ? 'Pemasukan' : 'Pengeluaran';
        return redirect()->route('cash-books.index')
            ->with('success', "Catatan {$typeLabel} kas {$scopeLabel} sebesar {$cashBook->formatted_amount} berhasil disimpan.");
    }

    public function edit(CashBook $cashBook): View
    {
        $branches = Branch::where('is_active', true)->get();
        $categories = self::categories();
        $isGlobalAllowed = in_array(auth()->user()?->role, ['owner', 'central_admin']);

        return view('backoffice.cash_book.edit', compact('cashBook', 'branches', 'categories', 'isGlobalAllowed'));
    }

    public function update(Request $request, CashBook $cashBook): RedirectResponse
    {
        $user = auth()->user();
        $isGlobalAllowed = in_array($user?->role, ['owner', 'central_admin']);
        $isLainnya = strtolower(trim((string) $request->input('category'))) === 'lainnya';

        $branchRule = $isGlobalAllowed ? 'nullable|exists:branches,id' : 'required|exists:branches,id';

        $validated = $request->validate([
            'branch_id' => $branchRule,
            'type' => 'required|in:in,out',
            'category' => 'required|string|max:100',
            'amount' => 'required|integer|min:1',
            'transaction_date' => 'required|date',
            'description' => [
                $isLainnya ? 'required' : 'nullable',
                'string',
                'max:1000',
            ],
            'receipt_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'description.required' => 'Kolom keterangan wajib diisi jika memilih kategori "Lainnya".',
            'receipt_photo.max' => 'Ukuran file foto bukti maksimal 5MB.',
        ]);

        $targetBranchId = $validated['branch_id'] ?? null;
        if (! $isGlobalAllowed && $user?->branch_id) {
            $targetBranchId = $user->branch_id;
        }

        $photoPath = $cashBook->receipt_photo_path;
        if ($request->hasFile('receipt_photo') && $request->file('receipt_photo')->isValid()) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('receipt_photo')->store('cash_books', 'public');
        }

        $before = $cashBook->toArray();

        $cashBook->update([
            'branch_id' => $targetBranchId,
            'type' => $validated['type'],
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transaction_date'],
            'description' => $validated['description'] ?? null,
            'receipt_photo_path' => $photoPath,
        ]);

        $after = $cashBook->toArray();

        $this->auditLogger->record('cash_book.updated', $cashBook, $before, $after);

        return redirect()->route('cash-books.index')
            ->with('success', "Catatan buku kas #{$cashBook->id} berhasil diperbarui.");
    }

    public function destroy(CashBook $cashBook): RedirectResponse
    {
        $id = $cashBook->id;
        $amount = $cashBook->formatted_amount;
        $type = $cashBook->type === 'in' ? 'Pemasukan' : 'Pengeluaran';

        $cashBook->delete(); // Soft delete per user request

        $this->auditLogger->record('cash_book.deleted', $cashBook, ['deleted' => false], ['deleted' => true]);

        return redirect()->route('cash-books.index')
            ->with('success', "Transaksi {$type} {$amount} (#{$id}) berhasil dihapus (soft delete).");
    }
}
