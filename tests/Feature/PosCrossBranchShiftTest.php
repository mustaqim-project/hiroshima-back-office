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

    public function test_expired_shift_older_than_23_hours_is_auto_closed_on_open_attempt(): void
    {
        // 1. Create a stale shift opened 24 hours ago
        $oldShift = Shift::create([
            'branch_id' => $this->branchTebet->id,
            'user_id' => $this->cashier->id,
            'opened_at' => Carbon::now('Asia/Jakarta')->subHours(24),
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
}
