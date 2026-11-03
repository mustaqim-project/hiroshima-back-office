<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @req FSD-11, BR-15
 * Audit & Verification test suite for system-wide Asia/Jakarta (WIB UTC+7) compliance.
 */
class TimezoneComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_and_php_default_timezone_is_wib_asia_jakarta(): void
    {
        $this->assertEquals('Asia/Jakarta', config('app.timezone'));
        $this->assertEquals('Asia/Jakarta', date_default_timezone_get());
        $this->assertEquals('Asia/Jakarta', now()->getTimezone()->getName());
    }

    public function test_eloquent_models_serialize_dates_with_wib_plus_seven_offset(): void
    {
        $business = Business::firstOrCreate(
            ['code' => 'HRS'],
            ['name' => 'Hiroshima Japanese Food']
        );

        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Hiroshima Test',
            'code' => 'TST',
            'phone' => '081234567890',
            'address' => 'Jakarta',
            'tax_percent' => 10,
            'service_charge_percent' => 5,
            'tax_included' => false,
            'rounding_mode' => 'none',
        ]);

        $user = User::create([
            'branch_id' => $branch->id,
            'name' => 'Kasir Test',
            'username' => 'kasirtst',
            'email' => 'kasirtst@example.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $shift = Shift::create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'opened_at' => now('Asia/Jakarta'),
            'starting_cash' => 100000,
            'status' => 'open',
        ]);

        $orderType = \App\Models\OrderType::create([
            'name' => 'Dine In',
            'code' => 'dine_in',
            'is_default' => true,
            'is_active' => true,
        ]);

        $order = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'order_no' => 'TST-POS-20261102-0001',
            'branch_id' => $branch->id,
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'order_type_id' => $orderType->id,
            'status' => 'completed',
            'business_date' => now('Asia/Jakarta')->toDateString(),
            'subtotal' => 50000,
            'total_amount' => 50000,
            'paid_amount' => 50000,
            'client_created_at' => now('Asia/Jakarta'),
            'synced_at' => now('Asia/Jakarta'),
        ]);

        $orderArray = $order->toArray();
        $this->assertArrayHasKey('created_at', $orderArray);
        $this->assertStringContainsString('+07:00', (string) $orderArray['created_at']);
        $this->assertStringNotContainsString('Z', (string) $orderArray['created_at']);

        $shiftArray = $shift->toArray();
        $this->assertArrayHasKey('opened_at', $shiftArray);
        $this->assertStringContainsString('+07:00', (string) $shiftArray['opened_at']);
        $this->assertStringNotContainsString('Z', (string) $shiftArray['opened_at']);
    }

    public function test_shift_auto_close_cutoff_uses_wib_timezone(): void
    {
        $business = Business::firstOrCreate(
            ['code' => 'HRS'],
            ['name' => 'Hiroshima Japanese Food']
        );

        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Hiroshima Test',
            'code' => 'TST',
            'phone' => '081234567890',
            'address' => 'Jakarta',
            'tax_percent' => 10,
            'service_charge_percent' => 5,
            'tax_included' => false,
            'rounding_mode' => 'none',
        ]);

        $user = User::create([
            'branch_id' => $branch->id,
            'name' => 'Kasir Test',
            'username' => 'kasirtst2',
            'email' => 'kasirtst2@example.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $nowWib = now('Asia/Jakarta');
        $shift = Shift::create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'opened_at' => $nowWib->copy()->subHours(24),
            'starting_cash' => 100000,
            'status' => 'open',
        ]);

        $shiftService = app(ShiftService::class);
        $closed = $shiftService->autoCloseExpiredShifts($nowWib);

        $this->assertEquals(1, $closed);
        $shift->refresh();
        $this->assertEquals('auto_closed', $shift->status);
        $this->assertEquals($nowWib->copy()->subHours(1)->format('Y-m-d H:i:s'), $shift->closed_at->format('Y-m-d H:i:s'));
    }
}
