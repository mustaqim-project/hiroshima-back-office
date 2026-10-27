<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FR-MST-03, FR-MST-04, FR-POS-05
 */
class PaymentMethodController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $methods = PaymentMethod::orderBy('id')->get();
        return view('backoffice.payment_methods.index', compact('methods'));
    }

    public function create(): View
    {
        return view('backoffice.payment_methods.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:payment_methods,code',
            'type' => 'required|in:cash,debit_card,credit_card,qris_static',
            'is_cash' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        $method = PaymentMethod::create($validated);
        $this->auditLogger->record('payment_method.created', $method, [], $method->toArray());

        return redirect()->route('payment-methods.index')->with('success', "Metode pembayaran {$method->name} berhasil ditambahkan.");
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('backoffice.payment_methods.edit', compact('paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:payment_methods,code,' . $paymentMethod->id,
            'type' => 'required|in:cash,debit_card,credit_card,qris_static',
            'is_cash' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        $before = $paymentMethod->toArray();
        $paymentMethod->update($validated);
        $after = $paymentMethod->toArray();

        $this->auditLogger->record('payment_method.updated', $paymentMethod, $before, $after);

        return redirect()->route('payment-methods.index')->with('success', "Metode pembayaran {$paymentMethod->name} berhasil diperbarui.");
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $name = $paymentMethod->name;
        $paymentMethod->delete(); // Soft delete
        $this->auditLogger->record('payment_method.deleted', $paymentMethod, ['deleted' => false], ['deleted' => true]);

        return redirect()->route('payment-methods.index')->with('success', "Metode pembayaran {$name} berhasil dihapus (soft delete).");
    }
}
