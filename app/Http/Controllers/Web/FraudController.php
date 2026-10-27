<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FraudAlert;
use App\Models\RiskScore;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FSD-18, RPT-24, RPT-25
 */
class FraudController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $alerts = FraudAlert::with(['branch', 'user'])
            ->latest('id')
            ->paginate(15);

        $riskScores = RiskScore::with(['user', 'branch'])
            ->orderBy('score', 'desc')
            ->take(10)
            ->get();

        return view('backoffice.fraud.index', compact('alerts', 'riskScores'));
    }

    public function review(Request $request, FraudAlert $alert): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:in_review,confirmed,dismissed',
            'review_notes' => 'required|string|min:3',
        ]);

        $alert->update([
            'status' => $request->status,
            'review_notes' => $request->review_notes,
            'reviewed_by' => auth()->id() ?? 1,
            'reviewed_at' => now('Asia/Jakarta'),
        ]);

        $this->auditLogger->record('fraud.disposition', $alert, [], [
            'status' => $request->status,
            'notes' => $request->review_notes,
        ]);

        return redirect()->back()->with('success', "Peringatan fraud #{$alert->id} berhasil diperbarui menjadi {$request->status}.");
    }
}
