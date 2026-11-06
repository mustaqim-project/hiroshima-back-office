<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Device;
use App\Models\Order;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Shift;
use App\Models\TransactionBackupLog;
use App\Models\User;
use App\Services\Sync\SyncEngine;
use App\Services\TransactionBackupLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class LogMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::create(['name' => 'Hiroshima F&B', 'code' => 'HRS']);

        $this->branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Hiroshima Pusat',
            'code' => 'PST',
            'address' => 'Jl. Merdeka No. 1',
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'name' => 'Owner Hiroshima',
            'email' => 'owner@hiroshima.web.id',
            'role' => 'owner',
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user);
    }

    public function test_transaction_backup_logger_saves_to_database_and_jsonl_file(): void
    {
        $uuid = (string) Str::uuid();
        $payload = [
            'uuid' => $uuid,
            'order_no' => 'PST-POS-20261107-0001',
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'customer_name' => 'Budi Santoso',
            'table_name' => 'Meja 05',
            'total_amount' => 125000,
            'paid_amount' => 150000,
            'items' => [
                ['product_name' => 'Ramen Spesial', 'quantity' => 2, 'unit_price' => 50000],
                ['product_name' => 'Ocha Dingin', 'quantity' => 2, 'unit_price' => 12500],
            ],
        ];

        $log = TransactionBackupLogger::record($payload, null, 'completed', 'pos_sync');

        $this->assertNotNull($log);
        $this->assertDatabaseHas('transaction_backup_logs', [
            'order_uuid' => $uuid,
            'order_no' => 'PST-POS-20261107-0001',
            'branch_id' => $this->branch->id,
            'customer_name' => 'Budi Santoso',
            'status' => 'completed',
            'total_amount' => 125000,
        ]);

        // Verify JSONL file exists and contains the UUID
        $dateStr = now('Asia/Jakarta')->format('Y-m-d');
        $filePath = storage_path("logs/transactions/transactions-{$dateStr}.jsonl");
        $this->assertTrue(File::exists($filePath));
        $content = File::get($filePath);
        $this->assertStringContainsString($uuid, $content);
        $this->assertStringContainsString('PST-POS-20261107-0001', $content);
    }

    public function test_log_monitoring_system_index_page_loads_with_bento_stats(): void
    {
        // Ensure a log file exists
        $todayFile = 'laravel-' . date('Y-m-d') . '.log';
        $logPath = storage_path('logs/' . $todayFile);
        File::ensureDirectoryExists(storage_path('logs'));
        File::put($logPath, "[2026-11-07 05:00:00] local.ERROR: Test error for diagnostic console.\n#0 trace...\n");

        $response = $this->get(route('logs.index', ['file' => $todayFile]));

        $response->assertStatus(200);
        $response->assertViewIs('backoffice.logs.index');
        $response->assertSee('Pusat Diagnostik', false);
        $response->assertSee('Total Baris Log');
        $response->assertSee('Test error for diagnostic console');
    }

    public function test_log_monitoring_transactions_page_loads_and_displays_records(): void
    {
        $uuid = (string) Str::uuid();
        TransactionBackupLog::create([
            'order_uuid' => $uuid,
            'order_no' => 'PST-POS-20261107-0099',
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'customer_name' => 'Dewi Lestari',
            'table_name' => 'Meja 12',
            'total_amount' => 85000,
            'paid_amount' => 100000,
            'items_count' => 3,
            'status' => 'completed',
            'source' => 'pos_sync',
            'raw_payload' => json_encode(['test' => 'data']),
            'client_created_at' => now('Asia/Jakarta'),
            'created_at' => now('Asia/Jakarta'),
            'updated_at' => now('Asia/Jakarta'),
        ]);

        $response = $this->get(route('logs.transactions'));

        $response->assertStatus(200);
        $response->assertViewIs('backoffice.logs.transactions');
        $response->assertSee('Log Backup Transaksi POS');
        $response->assertSee('PST-POS-20261107-0099');
        $response->assertSee('Dewi Lestari');
        $response->assertSee('85.000');
    }

    public function test_log_monitoring_transaction_payload_ajax_endpoint_returns_json(): void
    {
        $uuid = (string) Str::uuid();
        $log = TransactionBackupLog::create([
            'order_uuid' => $uuid,
            'order_no' => 'PST-POS-20261107-0088',
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'total_amount' => 50000,
            'paid_amount' => 50000,
            'items_count' => 1,
            'status' => 'completed',
            'source' => 'pos_sync',
            'raw_payload' => json_encode(['items' => [['name' => 'Gyoza', 'price' => 50000]]]),
            'client_created_at' => now('Asia/Jakarta'),
            'created_at' => now('Asia/Jakarta'),
            'updated_at' => now('Asia/Jakarta'),
        ]);

        $response = $this->getJson(route('logs.transactions.payload', ['id' => $log->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'order_no' => 'PST-POS-20261107-0088',
                'order_uuid' => $uuid,
                'status' => 'completed',
            ],
        ]);
    }

    public function test_sync_engine_push_automatically_creates_backup_log(): void
    {
        $device = Device::create([
            'branch_id' => $this->branch->id,
            'device_code' => 'POS-TABLET-001',
            'device_name' => 'Tablet Kasir 1',
            'app_version' => '1.0.7',
            'is_active' => true,
        ]);

        $shift = Shift::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'opened_at' => now('Asia/Jakarta')->subHours(2),
            'starting_cash' => 200000,
            'status' => 'open',
            'business_date' => now('Asia/Jakarta')->toDateString(),
        ]);

        $orderType = OrderType::create([
            'name' => 'Dine In',
            'code' => 'dine_in',
            'is_default' => true,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Food',
            'code' => 'food',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Takoyaki 6pcs',
            'sku' => 'TKO-001',
            'is_active' => true,
        ]);

        $paymentMethod = PaymentMethod::create([
            'name' => 'Tunai',
            'code' => 'cash',
            'type' => 'cash',
            'is_active' => true,
        ]);

        $uuid = (string) Str::uuid();
        $items = [
            [
                'uuid' => $uuid,
                'order_no' => 'PST-POS-20261107-7777',
                'branch_id' => $this->branch->id,
                'shift_id' => $shift->id,
                'user_id' => $this->user->id,
                'order_type_id' => $orderType->id,
                'customer_name' => 'Ahmad Kasir Test',
                'hold_reference' => 'Meja VIP 1',
                'subtotal' => 35000,
                'total_amount' => 35000,
                'paid_amount' => 50000,
                'change_amount' => 15000,
                'client_created_at' => now('Asia/Jakarta')->toIso8601String(),
                'status' => 'completed',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_name' => 'Takoyaki 6pcs',
                        'quantity' => 1,
                        'unit_price' => 35000,
                        'subtotal' => 35000,
                    ]
                ],
                'payments' => [
                    [
                        'payment_method_id' => $paymentMethod->id,
                        'amount' => 35000,
                    ]
                ]
            ]
        ];

        $syncEngine = app(SyncEngine::class);
        $result = $syncEngine->push($this->branch, $device, $items);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['success_count']);

        // Verify backup log was automatically created!
        $this->assertDatabaseHas('transaction_backup_logs', [
            'order_uuid' => $uuid,
            'order_no' => 'PST-POS-20261107-7777',
            'branch_id' => $this->branch->id,
            'status' => 'accepted',
            'source' => 'pos_sync',
        ]);
    }
}