<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCrossBranchShiftTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchTebet;
    protected Branch $branchCikini;
    protected User $cashier;
    protected ShiftService $shiftService;

    protected function setUp(): void
    {
        parent::setUp();

        $business = \App\Models\Business::firstOrCreate(
            ['code' => 'HRS'],
            ['name' => 'Hiroshima Japanese Food']
        );

        $this->branchTebet = Branch::create([
            'business_id' => $business->id,
            'name' => 'Hiroshima Tebet',
            'code' => 'TBT',
            'address' => 'Jl. Tebet Raya No. 10',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $this->branchCikini = Branch::create([
            'business_id' => $business->id,
            'name' => 'Hiroshima Cikini',
            'code' => 'CKN',
            'address' => 'Jl. Cikini Raya No. 20',
            'phone' => '081234567891',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'name' => 'Superadmin Cashier',
            'email' => 'superadmin@hiroshima.co.id',
            'role' => 'central_admin',
            'branch_id' => $this->branchTebet->id,
        ]);

        $this->shiftService = app(ShiftService::class);
    }

    public function test_cashier_shift_detected_across_branches_and_can_be_closed(): void
    {
        // 1. Cashier opens shift at Cikini
        $shiftCikini = $this->shiftService->open($this->branchCikini->id, $this->cashier->id, null, 100000);
        $this->assertEquals('open', $shiftCikini->status);

        // 2. In POS app, cashier switches view to Tebet
        $response = $this->actingAs($this->cashier, 'sanctum')
            ->withHeader('X-Branch-Id', (string)$this->branchTebet->id)
            ->getJson('/api/v1/shifts/current');

        $response->assertStatus(200);
        $this->assertTrue($response->json('has_active_shift'));
        $this->assertEquals($shiftCikini->id, $response->json('shift.id'));
        $this->assertEquals($this->branchCikini->id, $response->json('shift.branch_id'));
        $this->assertTrue($response->json('shift.is_other_branch'));
        $this->assertEquals('Hiroshima Cikini', $response->json('shift.branch_name'));

        // 3. Trying to open another shift at Tebet will fail with clear branch info
        $openAgain = $this->actingAs($this->cashier, 'sanctum')
            ->withHeader('X-Branch-Id', (string)$this->branchTebet->id)
            ->postJson('/api/v1/shifts/open', [
                'starting_cash' => 200000,
            ]);
        $openAgain->assertStatus(409);
        $this->assertStringContainsString('Hiroshima Cikini', $openAgain->json('message'));

        // 4. Cashier closes shift while viewing Tebet
        $closeResponse = $this->actingAs($this->cashier, 'sanctum')
            ->withHeader('X-Branch-Id', (string)$this->branchTebet->id)
            ->postJson('/api/v1/shifts/close', [
                'actual_cash' => 100000,
            ]);
        $closeResponse->assertStatus(200);
        $this->assertEquals('manual', $closeResponse->json('shift.close_type'));

        // 5. Check current shift again at Tebet -> now has_active_shift is false
        $checkAfter = $this->actingAs($this->cashier, 'sanctum')
            ->withHeader('X-Branch-Id', (string)$this->branchTebet->id)
            ->getJson('/api/v1/shifts/current');
        $checkAfter->assertStatus(200);
        $this->assertFalse($checkAfter->json('has_active_shift'));

        // 6. Cashier can now open a new shift at Tebet cleanly
        $openTebet = $this->actingAs($this->cashier, 'sanctum')
            ->withHeader('X-Branch-Id', (string)$this->branchTebet->id)
            ->postJson('/api/v1/shifts/open', [
                'starting_cash' => 150000,
            ]);
        $openTebet->assertStatus(201);
        $this->assertEquals($this->branchTebet->id, $openTebet->json('shift.branch_id'));
    }

    public function test_expired_shift_older_than_15_hours_is_auto_closed_on_open_attempt(): void
    {
        // 1. Create a stale shift opened 16 hours ago
        $oldShift = Shift::create([
            'branch_id' => $this->branchTebet->id,
            'user_id' => $this->cashier->id,
            'opened_at' => Carbon::now('Asia/Jakarta')->subHours(16),
            'starting_cash' => 100000,
            'status' => 'open',
        ]);

        // 2. Cashier checks current shift -> auto-closed and returns has_active_shift: false
        $resCurrent = $this->actingAs($this->cashier, 'sanctum')
            ->withHeader('X-Branch-Id', (string)$this->branchTebet->id)
            ->getJson('/api/v1/shifts/current');

        $resCurrent->assertStatus(200);
        $this->assertFalse($resCurrent->json('has_active_shift'));

        $oldShift->refresh();
        $this->assertEquals('auto_closed', $oldShift->status);

        // 3. Cashier can directly open a fresh shift without conflict
        $resOpen = $this->actingAs($this->cashier, 'sanctum')
            ->withHeader('X-Branch-Id', (string)$this->branchTebet->id)
            ->postJson('/api/v1/shifts/open', [
                'starting_cash' => 250000,
            ]);

        $resOpen->assertStatus(201);
        $this->assertEquals('open', $resOpen->json('shift.status'));
    }

    public function test_current_shift_report_does_not_leak_other_branch_shift_or_transactions(): void
    {
        // 1. Cashier opens shift at Cikini
        $shiftCikini = Shift::create([
            'branch_id' => $this->branchCikini->id,
            'user_id' => $this->cashier->id,
            'opened_at' => Carbon::now('Asia/Jakarta'),
            'starting_cash' => 100000,
            'status' => 'open',
        ]);

        // 2. Requesting current shift report for Tebet (where cashier has NO shift) returns 404, never Cikini's data
        $reportTebet = $this->actingAs($this->cashier, 'sanctum')
            ->withHeader('X-Branch-Id', (string)$this->branchTebet->id)
            ->getJson('/api/v1/shifts/current/report');

        $reportTebet->assertStatus(404);
        $this->assertFalse($reportTebet->json('success'));
        $this->assertStringContainsString('Data shift tidak ditemukan pada cabang ini', $reportTebet->json('message'));
    }

    public function test_superadmin_can_preview_branch_shift_and_history_across_accounts_in_same_branch(): void
    {
        // 1. Regular cashier opens shift at Tebet
        $cashierTebet = User::factory()->create([
            'name' => 'Kasir Tebet 1',
            'email' => 'kasirtebet@hiroshima.co.id',
            'role' => 'cashier',
            'branch_id' => $this->branchTebet->id,
        ]);

        $shiftTebet = $this->shiftService->open($this->branchTebet->id, $cashierTebet->id, null, 150000);
        $this->assertEquals('open', $shiftTebet->status);

        $orderType = \App\Models\OrderType::firstOrCreate(
            ['code' => 'dine_in'],
            ['name' => 'Dine In', 'is_default' => true, 'is_active' => true]
        );

        // Record an order processed by the cashier under this shift
        $order = \App\Models\Order::create([
            'uuid' => (string)\Illuminate\Support\Str::uuid(),
            'order_no' => 'HRS-TBT-POS-001',
            'business_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'branch_id' => $this->branchTebet->id,
            'user_id' => $cashierTebet->id,
            'shift_id' => $shiftTebet->id,
            'order_type_id' => $orderType->id,
            'subtotal' => 50000,
            'tax_amount' => 5000,
            'total_amount' => 55000,
            'status' => 'completed',
            'client_created_at' => Carbon::now('Asia/Jakarta'),
        ]);

        // 2. Superadmin / Owner logs in and views Tebet branch
        $superadmin = User::factory()->create([
            'name' => 'Superadmin Owner',
            'email' => 'owner@hiroshima.co.id',
            'role' => 'owner',
            'branch_id' => null, // Multi-branch owner
        ]);

        // Superadmin checks current shift at Tebet -> returns the active branch shift (opened by cashier)
        $resCurrent = $this->actingAs($superadmin, 'sanctum')
            ->withHeader('X-Branch-Id', (string)$this->branchTebet->id)
            ->getJson('/api/v1/shifts/current');

        $resCurrent->assertStatus(200);
        $this->assertTrue($resCurrent->json('has_active_shift'));
        $this->assertEquals($shiftTebet->id, $resCurrent->json('shift.id'));
        $this->assertEquals('Kasir Tebet 1', $resCurrent->json('shift.cashier_name'));

        // Superadmin previews current shift report -> sees cashier's transactions
        $resReport = $this->actingAs($superadmin, 'sanctum')
            ->withHeader('X-Branch-Id', (string)$this->branchTebet->id)
            ->getJson('/api/v1/shifts/current/report');

        $resReport->assertStatus(200);
        $this->assertTrue($resReport->json('success'));
        $this->assertEquals($shiftTebet->id, $resReport->json('report.shift_info.shift_id'));
        $this->assertEquals(1, $resReport->json('report.transaction_summary.completed_transaction_count'));
        $this->assertEquals(50000, $resReport->json('report.transaction_summary.gross_sales'));

        // Superadmin checks recent orders history for Tebet branch -> sees the cashier's order
        $resRecent = $this->actingAs($superadmin, 'sanctum')
            ->withHeader('X-Branch-Id', (string)$this->branchTebet->id)
            ->getJson('/api/v1/orders/recent');

        $resRecent->assertStatus(200);
        $this->assertTrue($resRecent->json('success'));
        $this->assertCount(1, $resRecent->json('orders'));
        $this->assertEquals('HRS-TBT-POS-001', $resRecent->json('orders.0.order_no'));

        // Attempting to open another duplicate shift at Tebet while shift is already running is rejected
        $resDuplicateOpen = $this->actingAs($superadmin, 'sanctum')
            ->withHeader('X-Branch-Id', (string)$this->branchTebet->id)
            ->postJson('/api/v1/shifts/open', [
                'starting_cash' => 200000,
            ]);
        $resDuplicateOpen->assertStatus(409);
        $this->assertStringContainsString('sudah memiliki shift aktif', $resDuplicateOpen->json('message'));
    }
}
