<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Shift;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Fraud\FraudScanner;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @req FSD-18, RPT-23, RPT-24, RPT-25, FSD-20
 */
class UatAuditAndFraudTest extends TestCase
{
    protected Branch $branch;
    protected User $cashier;
    protected AuditLogger $auditLogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditLogger = new AuditLogger();

        $business = Business::create(['name' => 'Hiroshima F&B', 'code' => 'HRS']);
        $this->branch = Branch::create(['business_id' => $business->id, 'name' => 'Hiroshima Pusat', 'code' => 'HRS01']);
        \App\Models\OrderType::create(['name' => 'Dine In', 'code' => 'dine_in', 'is_default' => true]);
        $this->cashier = User::create(['branch_id' => $this->branch->id, 'name' => 'Siti', 'email' => 'siti@hiroshima.id', 'password' => 'pass']);
    }

    /**
     * UAT-23: Integritas Rantai Hash Audit Log (Tamper-Evident SHA-256).
     */
    public function test_uat_23_tamper_evident_audit_log_hash_chain(): void
    {
        // 1. Record series of sensitive actions
        $this->auditLogger->record('user.created', $this->cashier, [], ['name' => 'Siti']);
        $this->auditLogger->record('price.changed', null, ['old' => 40000], ['new' => 45000]);
        $this->auditLogger->record('shift.opened', null, [], ['cash' => 200000]);

        // 2. Verification succeeds on untampered log chain
        $verify = $this->auditLogger->verifyIntegrity();
        $this->assertTrue($verify['valid']);
        $this->assertEquals(3, $verify['verified_count']);

        // 3. Tamper with second log directly in database (simulating malicious DB admin)
        DB::table('audit_logs')->where('id', 2)->update([
            'action' => 'price.changed_tampered',
        ]);

        // 4. Verification immediately catches the tampering and reports corrupted row
        $verifyTampered = $this->auditLogger->verifyIntegrity();
        $this->assertFalse($verifyTampered['valid']);
        $this->assertEquals(2, $verifyTampered['corrupted'][0]['id']);
    }

    /**
     * UAT-24: Deteksi Fraud Otomatis (FRD-02: Void Tunai Cepat).
     */
    public function test_uat_24_fraud_rules_detection(): void
    {
        $shift = Shift::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'opened_at' => now('Asia/Jakarta')->subHours(2),
            'starting_cash' => 100000,
            'status' => 'open',
        ]);

        $cashMethod = PaymentMethod::create(['name' => 'Tunai', 'code' => 'cash', 'type' => 'cash', 'is_cash' => true]);

        // Create cash order created 5 minutes ago and voided 2 minutes ago (diff <= 15 min)
        $order = Order::create([
            'uuid' => '00000000-0000-0000-0000-000000000099',
            'order_no' => 'HRS01-POS-FRD-02',
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => 1,
            'business_date' => now('Asia/Jakarta')->toDateString(),
            'total_amount' => 50000,
            'status' => 'voided',
            'void_reason' => 'Pelanggan membatalkan pesanan setelah bayar',
            'voided_by' => $this->cashier->id,
            'client_created_at' => now('Asia/Jakarta')->subMinutes(5),
            'voided_at' => now('Asia/Jakarta')->subMinutes(2),
        ]);

        Payment::create([
            'order_id' => $order->id,
            'shift_id' => $shift->id,
            'payment_method_id' => $cashMethod->id,
            'payment_method_name_snapshot' => 'Tunai',
            'amount' => 50000,
            'paid_at' => now('Asia/Jakarta')->subMinutes(5),
        ]);

        $scanner = new FraudScanner($this->auditLogger);
        $result = $scanner->scan();

        $this->assertGreaterThanOrEqual(1, $result['alerts_created']);
        $this->assertDatabaseHas('fraud_alerts', [
            'fraud_rule_code' => 'FRD-02',
            'order_id' => $order->id,
        ]);
    }
}
