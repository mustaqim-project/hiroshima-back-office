<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @req FR-POS-01, FR-POS-01a, FR-POS-01b, FR-POS-13, FSD-09, RPT-09, FSD-20
 */
class UatShiftLifecycleTest extends TestCase
{
    protected Branch $branch;
    protected User $cashier;
    protected ShiftService $shiftService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shiftService = new ShiftService();

        $business = Business::create(['name' => 'Hiroshima F&B', 'code' => 'HRS']);
        $this->branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Hiroshima Pusat',
            'code' => 'HRS01',
        ]);

        \App\Models\OrderType::create(['name' => 'Dine In', 'code' => 'dine_in', 'is_default' => true]);

        $this->cashier = User::create([
            'branch_id' => $this->branch->id,
            'name' => 'Kasir 1',
            'username' => 'kasir1',
            'email' => 'kasir1@hiroshima.id',
            'password' => Hash::make('password123'),
            'role' => 'cashier',
        ]);
    }

    /**
     * UAT-01: Buka Shift, Kas Masuk/Keluar, dan Formula Kas Diharapkan.
     * kas_diharapkan = modal_awal + tunai - refund_tunai + kas_masuk - kas_keluar
     */
    public function test_uat_01_shift_opening_and_cash_tracking(): void
    {
        // 1. Open shift with 200.000 starting cash
        $shift = $this->shiftService->open($this->branch->id, $this->cashier->id, null, 200000);
        $this->assertEquals('open', $shift->status);
        $this->assertEquals(200000, $shift->starting_cash);

        // 2. Add cash payments (150.000)
        $cashMethod = PaymentMethod::create(['name' => 'Tunai', 'code' => 'cash', 'type' => 'cash', 'is_cash' => true]);
        $order = Order::create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'order_no' => 'HRS01-POS-001',
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'order_type_id' => 1,
            'business_date' => now('Asia/Jakarta')->toDateString(),
            'total_amount' => 150000,
            'client_created_at' => now('Asia/Jakarta'),
        ]);

        Payment::create([
            'order_id' => $order->id,
            'shift_id' => $shift->id,
            'payment_method_id' => $cashMethod->id,
            'payment_method_name_snapshot' => 'Tunai',
            'amount' => 150000,
            'paid_at' => now('Asia/Jakarta'),
        ]);

        // 3. Cash movement: Kas Masuk 50.000, Kas Keluar 20.000
        CashMovement::create([
            'shift_id' => $shift->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'type' => 'cash_in',
            'amount' => 50000,
            'reason' => 'Tambah kembalian',
        ]);

        CashMovement::create([
            'shift_id' => $shift->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'type' => 'cash_out',
            'amount' => 20000,
            'reason' => 'Beli es batu darurat',
        ]);

        // Expected: 200.000 + 150.000 + 50.000 - 20.000 = 380.000
        $expectedCash = $this->shiftService->calculateExpectedCash($shift);
        $this->assertEquals(380000, $expectedCash);

        // Close shift with physical cash 380.000 (diff: 0)
        $closed = $this->shiftService->close($shift, 380000);
        $this->assertEquals('closed', $closed->status);
        $this->assertEquals(0, $closed->cash_difference);
    }

    /**
     * UAT-02: Shift Auto-Close tepat pada batas 23 jam dan penolakan transaksi baru.
     */
    public function test_uat_02_shift_auto_close_after_twenty_three_hours(): void
    {
        // Opened 24 hours ago
        $shift = Shift::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'opened_at' => now('Asia/Jakarta')->subHours(24),
            'starting_cash' => 100000,
            'status' => 'open',
        ]);

        // Auto close expired shifts
        $closedCount = $this->shiftService->autoCloseExpiredShifts();
        $this->assertEquals(1, $closedCount);

        $shift->refresh();
        $this->assertEquals('auto_closed', $shift->status);
        $this->assertNull($shift->actual_cash);

        // Attempting to assert open on expired shift throws exception
        $this->expectException(Exception::class);
        $this->shiftService->assertOpenAndFresh($shift);
    }
}
