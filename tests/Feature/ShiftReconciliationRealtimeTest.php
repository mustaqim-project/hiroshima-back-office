<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShiftReconciliationRealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected User $openerCashier;
    protected User $closerCashier;
    protected User $ownerUser;
    protected Branch $branch;
    protected ShiftService $shiftService;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::firstOrCreate(
            ['code' => 'HRS'],
            ['name' => 'Hiroshima Japanese Food']
        );

        $this->branch = Branch::firstOrCreate(
            ['code' => 'HRS01'],
            [
                'business_id' => $business->id,
                'name' => 'Hiroshima Pusat',
                'address' => 'Jl. Tebet Raya No. 1',
                'phone' => '08123456789',
                'is_active' => true,
            ]
        );

        $this->openerCashier = User::create([
            'name' => 'Kasir Pembuka Shift',
            'username' => 'kasir_buka',
            'email' => 'kasir_buka@example.com',
            'password' => bcrypt('password123'),
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->closerCashier = User::create([
            'name' => 'Kasir Penutup Shift',
            'username' => 'kasir_tutup',
            'email' => 'kasir_tutup@example.com',
            'password' => bcrypt('password123'),
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->ownerUser = User::create([
            'name' => 'Bapak Owner',
            'username' => 'owner_hiroshima',
            'email' => 'owner@example.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'branch_id' => null,
            'is_active' => true,
        ]);

        $this->shiftService = app(ShiftService::class);
    }

    public function test_shift_records_who_opened_and_who_closed(): void
    {
        // 1. Kasir A membuka shift
        $shift = $this->shiftService->open($this->branch->id, $this->openerCashier->id, null, 100000);

        $this->assertEquals($this->openerCashier->id, $shift->user_id);
        $this->assertNull($shift->closed_by);
        $this->assertEquals('open', $shift->status);
        $this->assertEquals($this->openerCashier->name, $shift->opener->name);

        // 2. Report saat shift masih open menampilkan opened_by dan status berjalan
        $openReport = $this->shiftService->generateCloseShiftReport($shift);
        $this->assertEquals($this->openerCashier->name, $openReport['shift_info']['opened_by_name']);
        $this->assertEquals('Shift Masih Berjalan', $openReport['shift_info']['closed_by_name']);

        // 3. Kasir B menutup shift
        $closedShift = $this->shiftService->close($shift, 100000, 'manual', 'Tutup shift oleh kasir pengganti', false, $this->closerCashier->id);
        $closedShift->refresh();

        $this->assertEquals('closed', $closedShift->status);
        $this->assertEquals($this->closerCashier->id, $closedShift->closed_by);
        $this->assertEquals($this->closerCashier->name, $closedShift->closer->name);

        // 4. Report setelah shift ditutup menampilkan Kasir Buka dan Kasir Tutup secara transparan
        $closedReport = $this->shiftService->generateCloseShiftReport($closedShift);
        $this->assertEquals($this->openerCashier->name, $closedReport['shift_info']['opened_by_name']);
        $this->assertEquals($this->closerCashier->name, $closedReport['shift_info']['closed_by_name']);
    }

    public function test_backoffice_reconciliation_displays_realtime_open_shifts_and_records(): void
    {
        // 1. Kasir A buka shift
        $shift = $this->shiftService->open($this->branch->id, $this->openerCashier->id, null, 200000);

        // 2. Ada transaksi realtime selama shift berjalan
        $orderType = OrderType::firstOrCreate(['code' => 'dine_in'], ['name' => 'Dine In']);
        $category = Category::firstOrCreate(['branch_id' => $this->branch->id, 'name' => 'Makanan'], ['code' => 'MKN']);

        $product = Product::create([
            'branch_id' => $this->branch->id,
            'sku' => 'TEST01',
            'name' => 'Ramen Spesial',
            'category_id' => $category->id,
            'base_price' => 50000,
            'is_active' => true,
        ]);

        $order = Order::create([
            'uuid' => Str::uuid()->toString(),
            'order_no' => 'HRS01-POS-001',
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'user_id' => $this->openerCashier->id,
            'order_type_id' => $orderType->id,
            'status' => 'completed',
            'business_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'subtotal' => 50000,
            'total_amount' => 50000,
            'paid_amount' => 50000,
            'client_created_at' => now('Asia/Jakarta'),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'order_type_name_snapshot' => 'Dine In',
            'quantity' => 1,
            'unit_price' => 50000,
            'cost_per_unit' => 25000,
            'subtotal' => 50000,
        ]);

        $cashMethod = PaymentMethod::firstOrCreate(['code' => 'cash'], [
            'name' => 'Tunai',
            'type' => 'cash',
            'is_cash' => true,
            'is_active' => true,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $cashMethod->id,
            'shift_id' => $shift->id,
            'amount' => 50000,
            'payment_method_name_snapshot' => 'Tunai',
            'status' => 'completed',
            'paid_at' => now('Asia/Jakarta'),
        ]);

        // 3. Akses /backoffice/reconciliation oleh Owner
        $response = $this->actingAs($this->ownerUser)
            ->get(route('reconciliation.index'));

        $response->assertOk();
        // Memastikan shift yang sedang berjalan (realtime) muncul di halaman rekonsiliasi
        $response->assertSee("#{$shift->id}");
        $response->assertSee('Sedang Berjalan');
        $response->assertSee($this->openerCashier->name);
        $response->assertSee('Rp 250.000'); // Modal 200k + Cash 50k

        // 4. Owner cek report realtime (X-Report) dari backoffice
        $reportResponse = $this->actingAs($this->ownerUser)
            ->get(route('reconciliation.report', $shift));

        $reportResponse->assertOk();
        $reportResponse->assertSee('RINGKASAN TRANSAKSI SHIFT REALTIME (X-REPORT)');
        $reportResponse->assertSee($this->openerCashier->name);
        $reportResponse->assertSee('Shift Masih Berjalan');
        $reportResponse->assertSee('Ramen Spesial');

        // 5. Mencoba merekonsiliasi shift saat masih berstatus open harus dicegah
        $reconcileWhileOpen = $this->actingAs($this->ownerUser)
            ->post(route('reconciliation.reconcile', $shift), [
                'notes' => 'Coba rekonsiliasi shift yang belum tutup',
            ]);

        $reconcileWhileOpen->assertSessionHas('error');
        $this->assertEquals('open', $shift->fresh()->status);

        // 6. Sekarang Kasir B menutup shift
        $this->shiftService->close($shift, 250000, 'manual', 'Shift ditutup oleh Kasir Penutup', false, $this->closerCashier->id);
        $this->assertEquals('closed', $shift->fresh()->status);
        $this->assertEquals($this->closerCashier->id, $shift->fresh()->closed_by);

        // 7. Setelah tutup, owner dapat menyetujui rekonsiliasi kas
        $reconcileAfterClose = $this->actingAs($this->ownerUser)
            ->post(route('reconciliation.reconcile', $shift), [
                'notes' => 'Kas fisik Rp 250.000 cocok dan diverifikasi oleh Owner',
            ]);

        $reconcileAfterClose->assertSessionHas('success');
        $this->assertEquals('reconciled', $shift->fresh()->status);
        $this->assertEquals('Kas fisik Rp 250.000 cocok dan diverifikasi oleh Owner', $shift->fresh()->reconciliation_notes);

        // 8. Tinjau kembali index rekonsiliasi, harus menampilkan closer cashier dan status direkonsiliasi
        $indexAfterReconcile = $this->actingAs($this->ownerUser)
            ->get(route('reconciliation.index'));

        $indexAfterReconcile->assertOk();
        $indexAfterReconcile->assertSee($this->closerCashier->name);
        $indexAfterReconcile->assertSee('Direkonsiliasi');
    }
}
