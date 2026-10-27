<?php

namespace App\Services\Fraud;

use App\Models\AuditLog;
use App\Models\AuthorizationLog;
use App\Models\CashMovement;
use App\Models\FraudAlert;
use App\Models\Notification;
use App\Models\Order;
use App\Models\RiskScore;
use App\Models\Shift;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * @req FSD-07, FSD-08, FSD-09, FSD-10, FSD-11, FSD-12, FSD-18, RPT-24, RPT-25
 */
class FraudScanner
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function scan(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $to = $to ?? now('Asia/Jakarta');
        $from = $from ?? $to->copy()->subHours(24);

        $alertsCreated = 0;
        $rulesConfig = config('fraud.rules', []);

        // FRD-01: Void Beruntun (> 3 per shift)
        if (isset($rulesConfig['FRD-01'])) {
            $maxVoids = $rulesConfig['FRD-01']['params']['max_voids_per_shift'] ?? 3;
            $voids = Order::where('status', 'voided')
                ->whereBetween('voided_at', [$from, $to])
                ->groupBy('shift_id', 'voided_by', 'branch_id')
                ->selectRaw('shift_id, voided_by, branch_id, count(*) as total_voids')
                ->having('total_voids', '>', $maxVoids)
                ->get();

            foreach ($voids as $v) {
                $dedup = "FRD-01:shift_{$v->shift_id}:user_{$v->voided_by}";
                $this->createAlert('FRD-01', $v->branch_id, $v->voided_by, null, $v->shift_id, 'medium', [
                    'total_voids' => $v->total_voids,
                    'threshold' => $maxVoids,
                ], $dedup);
                $alertsCreated++;
            }
        }

        // FRD-02: Void Setelah Pembayaran Tunai (<= 15 menit pasca bayar)
        if (isset($rulesConfig['FRD-02'])) {
            $recentCashVoids = Order::where('status', 'voided')
                ->whereBetween('voided_at', [$from, $to])
                ->whereHas('payments.paymentMethod', fn ($q) => $q->where('is_cash', true))
                ->get();

            foreach ($recentCashVoids as $order) {
                if ($order->voided_at && $order->client_created_at) {
                    $diffMinutes = Carbon::parse($order->client_created_at)->diffInMinutes(Carbon::parse($order->voided_at));
                    if ($diffMinutes <= 15) {
                        $dedup = "FRD-02:order_{$order->id}";
                        $this->createAlert('FRD-02', $order->branch_id, $order->voided_by ?? $order->user_id, $order->id, $order->shift_id, 'high', [
                            'diff_minutes' => $diffMinutes,
                            'total_amount' => $order->total_amount,
                            'order_no' => $order->order_no,
                        ], $dedup);
                        $alertsCreated++;
                    }
                }
            }
        }

        // FRD-03: Diskon Tepat di Bawah Ambang (>= 5 kali per shift)
        if (isset($rulesConfig['FRD-03'])) {
            $nearThresholdOrders = Order::where('manual_discount_amount', '>', 0)
                ->where('manual_discount_requires_pin', false)
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('shift_id', 'user_id', 'branch_id')
                ->selectRaw('shift_id, user_id, branch_id, count(*) as count_near_threshold')
                ->having('count_near_threshold', '>=', 5)
                ->get();

            foreach ($nearThresholdOrders as $item) {
                $dedup = "FRD-03:shift_{$item->shift_id}:user_{$item->user_id}";
                $this->createAlert('FRD-03', $item->branch_id, $item->user_id, null, $item->shift_id, 'high', [
                    'count' => $item->count_near_threshold,
                ], $dedup);
                $alertsCreated++;
            }
        }

        // FRD-05: Selisih Kas Berulang (>= 3 dari 5 shift)
        if (isset($rulesConfig['FRD-05'])) {
            $shortageShifts = Shift::whereIn('status', ['closed', 'auto_closed', 'reconciled'])
                ->where('cash_difference', '<', 0)
                ->whereBetween('closed_at', [$from, $to])
                ->groupBy('user_id', 'branch_id')
                ->selectRaw('user_id, branch_id, count(*) as total_shortages, sum(cash_difference) as total_deficit')
                ->having('total_shortages', '>=', 3)
                ->get();

            foreach ($shortageShifts as $s) {
                $dedup = "FRD-05:user_{$s->user_id}:window_" . $to->format('Ymd');
                $this->createAlert('FRD-05', $s->branch_id, $s->user_id, null, null, 'high', [
                    'total_shortages' => $s->total_shortages,
                    'total_deficit' => $s->total_deficit,
                ], $dedup);
                $alertsCreated++;
            }
        }

        // FRD-06: Auto-Close Berulang (>= 2 per 14 hari)
        if (isset($rulesConfig['FRD-06'])) {
            $autoCloseShifts = Shift::where('status', 'auto_closed')
                ->whereBetween('closed_at', [$from, $to])
                ->groupBy('user_id', 'branch_id')
                ->selectRaw('user_id, branch_id, count(*) as total_auto_closes')
                ->having('total_auto_closes', '>=', 2)
                ->get();

            foreach ($autoCloseShifts as $ac) {
                $dedup = "FRD-06:user_{$ac->user_id}:window_" . $to->format('Ymd');
                $this->createAlert('FRD-06', $ac->branch_id, $ac->user_id, null, null, 'low', [
                    'total_auto_closes' => $ac->total_auto_closes,
                ], $dedup);
                $alertsCreated++;
            }
        }

        // FRD-07: Percobaan PIN Gagal Berkelompok (>= 3 gagal)
        if (isset($rulesConfig['FRD-07'])) {
            $failedAuth = AuthorizationLog::where('success', false)
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('branch_id', 'user_id', 'type')
                ->selectRaw('branch_id, user_id, type, count(*) as fail_count')
                ->having('fail_count', '>=', 3)
                ->get();

            foreach ($failedAuth as $fa) {
                $dedup = "FRD-07:branch_{$fa->branch_id}:user_{$fa->user_id}:type_{$fa->type}";
                $this->createAlert('FRD-07', $fa->branch_id, $fa->user_id, null, null, 'medium', [
                    'fail_count' => $fa->fail_count,
                    'type' => $fa->type,
                ], $dedup);
                $alertsCreated++;
            }
        }

        // FRD-10: Transaksi Offline Mencurigakan (Clock skew > 5 menit)
        if (isset($rulesConfig['FRD-10'])) {
            $skewOrders = Order::where('clock_skew_flag', true)
                ->whereBetween('synced_at', [$from, $to])
                ->get();

            foreach ($skewOrders as $order) {
                $dedup = "FRD-10:order_{$order->id}";
                $this->createAlert('FRD-10', $order->branch_id, $order->user_id, $order->id, $order->shift_id, 'medium', [
                    'order_no' => $order->order_no,
                    'client_created_at' => $order->client_created_at,
                    'synced_at' => $order->synced_at,
                ], $dedup);
                $alertsCreated++;
            }
        }

        // FRD-20: Integritas Audit Log (Hash Chain Verification)
        $auditCheck = $this->auditLogger->verifyIntegrity();
        if (! $auditCheck['valid']) {
            $dedup = "FRD-20:integrity_failed:" . now('Asia/Jakarta')->format('YmdH');
            $this->createAlert('FRD-20', 1, null, null, null, 'critical', [
                'error' => 'Audit log hash chain broken!',
                'corrupted_details' => $auditCheck['corrupted'],
            ], $dedup);
            $alertsCreated++;
        }

        // Recalculate Risk Scores per user for current month
        $this->updateRiskScores();

        return [
            'alerts_created' => $alertsCreated,
            'scanned_period' => ['from' => $from->toIso8601String(), 'to' => $to->toIso8601String()],
        ];
    }

    protected function createAlert(
        string $ruleCode,
        int $branchId,
        ?int $userId,
        ?int $orderId,
        ?int $shiftId,
        string $severity,
        array $evidence,
        string $dedupKey
    ): void {
        if (FraudAlert::where('dedup_key', $dedupKey)->exists()) {
            return;
        }

        $alert = FraudAlert::create([
            'fraud_rule_code' => $ruleCode,
            'branch_id' => $branchId,
            'user_id' => $userId,
            'order_id' => $orderId,
            'shift_id' => $shiftId,
            'severity' => $severity,
            'evidence' => $evidence,
            'status' => 'new',
            'detected_at' => now('Asia/Jakarta'),
            'dedup_key' => $dedupKey,
        ]);

        // Create internal in-app notification for Store Manager & Owner (Zero 3rd party)
        Notification::create([
            'branch_id' => $branchId,
            'user_id' => null, // broadcasts to branch managers & owner
            'title' => "Peringatan Anomali Operasional ({$ruleCode})",
            'message' => "Terdeteksi indikasi anomali tingkat {$severity} pada cabang #{$branchId}. Segera lakukan peninjauan.",
            'type' => 'fraud_alert',
            'payload' => ['alert_id' => $alert->id, 'rule' => $ruleCode],
        ]);
    }

    public function updateRiskScores(): void
    {
        $currentMonth = now('Asia/Jakarta')->format('Y-m');
        $weights = config('fraud.risk_weights', ['low' => 1, 'medium' => 3, 'high' => 7, 'critical' => 15]);

        // Group alerts by user and branch for the month
        $userAlerts = FraudAlert::where('status', '!=', 'dismissed')
            ->whereNotNull('user_id')
            ->get()
            ->groupBy(fn ($a) => "{$a->user_id}:{$a->branch_id}");

        foreach ($userAlerts as $key => $alerts) {
            [$userId, $branchId] = explode(':', $key);

            $weightedScore = 0;
            foreach ($alerts as $alert) {
                $weightedScore += $weights[$alert->severity] ?? 1;
            }

            $totalTx = Order::where('user_id', $userId)->where('branch_id', $branchId)->count();
            $normalizedScore = $totalTx > 0 ? min(100.0, ($weightedScore / $totalTx) * 100) : (float) $weightedScore;

            RiskScore::updateOrCreate(
                ['user_id' => $userId, 'branch_id' => $branchId, 'period_month' => $currentMonth],
                [
                    'score' => round($normalizedScore, 2),
                    'total_transactions' => $totalTx,
                    'finding_count' => $alerts->count(),
                ]
            );
        }
    }
}
