<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashBook;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CashBookFinanceTest extends TestCase
{
    protected Business $business;
    protected Branch $branch;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->business = Business::firstOrCreate(
            ['code' => 'FIN-BIZ'],
            ['name' => 'Hiroshima Street Food', 'phone' => '0812345678', 'email' => 'finance@hiroshima.id']
        );

        $this->branch = Branch::firstOrCreate(
            ['code' => 'HRS-ST1'],
            [
                'business_id' => $this->business->id,
                'name' => 'Hiroshima Gerobak 01',
                'tax_percent' => 10,
                'service_charge_percent' => 0,
                'tax_included' => false,
                'rounding_mode' => 'round_nearest_100',
                'discount_percent_threshold' => 10,
                'discount_amount_threshold' => 20000,
                'printer_paper_width' => '58mm',
                'is_active' => true,
            ]
        );

        $this->adminUser = User::firstOrCreate(
            ['username' => 'financeadmin'],
            [
                'name' => 'Finance Admin',
                'email' => 'finadmin@hiroshima.id',
                'password' => Hash::make('password123'),
                'role' => 'central_admin',
                'is_active' => true,
            ]
        );
    }

    public function test_can_view_cash_book_index(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/backoffice/cash-books');
        $response->assertStatus(200);
        $response->assertSee('Buku Kas Keuangan');
    }

    public function test_can_create_cash_in_and_cash_out(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Record Uang Masuk (Suntikan Modal)
        $responseIn = $this->post('/backoffice/cash-books', [
            'branch_id' => $this->branch->id,
            'type' => 'in',
            'category' => 'Suntikan Modal / Kas Awal',
            'amount' => 500000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Modal awal kasir street food',
        ]);
        $responseIn->assertRedirect('/backoffice/cash-books');

        $this->assertDatabaseHas('cash_books', [
            'type' => 'in',
            'amount' => 500000,
            'category' => 'Suntikan Modal / Kas Awal',
        ]);

        // 2. Record Uang Keluar (Belanja Gas LPG)
        $responseOut = $this->post('/backoffice/cash-books', [
            'branch_id' => $this->branch->id,
            'type' => 'out',
            'category' => 'Gas LPG, Es Batu & Air Galon',
            'amount' => 45000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Beli 2 tabung gas melon di warung sebelah',
        ]);
        $responseOut->assertRedirect('/backoffice/cash-books');

        $this->assertDatabaseHas('cash_books', [
            'type' => 'out',
            'amount' => 45000,
            'category' => 'Gas LPG, Es Batu & Air Galon',
        ]);
    }

    public function test_category_lainnya_requires_description(): void
    {
        $this->actingAs($this->adminUser);

        // Category Lainnya WITHOUT description must fail validation
        $responseFail = $this->post('/backoffice/cash-books', [
            'branch_id' => $this->branch->id,
            'type' => 'out',
            'category' => 'Lainnya',
            'amount' => 25000,
            'transaction_date' => now()->toDateString(),
            'description' => '', // Empty
        ]);

        $responseFail->assertSessionHasErrors(['description']);

        // Category Lainnya WITH description must succeed
        $responseSuccess = $this->post('/backoffice/cash-books', [
            'branch_id' => $this->branch->id,
            'type' => 'out',
            'category' => 'Lainnya',
            'amount' => 25000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Beli terpal plastik pelindung hujan gerobak',
        ]);

        $responseSuccess->assertRedirect('/backoffice/cash-books');
        $this->assertDatabaseHas('cash_books', [
            'category' => 'Lainnya',
            'description' => 'Beli terpal plastik pelindung hujan gerobak',
        ]);
    }

    public function test_can_upload_receipt_photo_optional(): void
    {
        Storage::fake('public');
        $this->actingAs($this->adminUser);

        $file = UploadedFile::fake()->image('nota_belanja.jpg', 600, 600);

        $response = $this->post('/backoffice/cash-books', [
            'branch_id' => $this->branch->id,
            'type' => 'out',
            'category' => 'Belanja Bahan Baku / Bumbu Harian',
            'amount' => 120000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Belanja cabai, bawang, dan bumbu kuah ramen',
            'receipt_photo' => $file,
        ]);

        $response->assertRedirect('/backoffice/cash-books');

        $entry = CashBook::where('description', 'Belanja cabai, bawang, dan bumbu kuah ramen')->first();
        $this->assertNotNull($entry);
        $this->assertNotNull($entry->receipt_photo_path);
        Storage::disk('public')->assertExists($entry->receipt_photo_path);
    }

    public function test_cash_book_soft_delete(): void
    {
        $this->actingAs($this->adminUser);

        $entry = CashBook::create([
            'branch_id' => $this->branch->id,
            'type' => 'out',
            'category' => 'Uang Makan & Upah Harian Staf',
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Uang makan kasir',
            'created_by' => $this->adminUser->id,
        ]);

        $deleteResponse = $this->delete("/backoffice/cash-books/{$entry->id}");
        $deleteResponse->assertRedirect('/backoffice/cash-books');

        $this->assertSoftDeleted('cash_books', ['id' => $entry->id]);
        $this->assertNull(CashBook::find($entry->id));
        $this->assertNotNull(CashBook::withTrashed()->find($entry->id));
    }

    public function test_dashboard_displays_finance_cash_book_overview(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Uang Masuk Buku Kas');
        $response->assertSee('Uang Keluar Buku Kas');
        $response->assertSee('Saldo Kas Bersih');
    }

    public function test_admin_can_create_global_cash_book_entry(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create Global Cash In (Modal Pusat / Investasi Owner) with branch_id = null
        $response = $this->post('/backoffice/cash-books', [
            'branch_id' => '', // Empty for global
            'type' => 'in',
            'category' => 'Modal Pusat / Investasi Owner',
            'amount' => 5000000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Suntikan modal kerja dari kantor pusat untuk seluruh cabang',
        ]);

        $response->assertRedirect('/backoffice/cash-books');

        $globalEntry = CashBook::where('category', 'Modal Pusat / Investasi Owner')->first();
        $this->assertNotNull($globalEntry);
        $this->assertNull($globalEntry->branch_id);
        $this->assertTrue($globalEntry->is_global);
        $this->assertEquals('Global (Kantor Pusat)', $globalEntry->branch_name);

        // 2. View in index and verify Global Pusat badge
        $indexResponse = $this->get('/backoffice/cash-books');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Global Pusat');
    }

    public function test_admin_can_filter_cash_book_by_global(): void
    {
        $this->actingAs($this->adminUser);

        // Create 1 branch entry and 1 global entry
        CashBook::create([
            'branch_id' => $this->branch->id,
            'type' => 'out',
            'category' => 'Belanja Bahan Baku / Bumbu Harian',
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->adminUser->id,
        ]);

        CashBook::create([
            'branch_id' => null, // Global
            'type' => 'out',
            'category' => 'Software, Server & Sistem',
            'amount' => 150000,
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->adminUser->id,
        ]);

        // Filter only global
        $response = $this->get('/backoffice/cash-books?branch_id=global');
        $response->assertStatus(200);
        $response->assertSee('Software, Server & Sistem');
        $response->assertDontSee('Belanja Bahan Baku / Bumbu Harian');
    }

    public function test_reports_displays_and_exports_cash_book_report(): void
    {
        $this->actingAs($this->adminUser);

        $entry = CashBook::create([
            'branch_id' => null,
            'type' => 'in',
            'category' => 'Modal Pusat / Investasi Owner',
            'amount' => 2000000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Kas pusat',
            'created_by' => $this->adminUser->id,
        ]);

        // dd($entry->toArray(), CashBook::all()->toArray());

        // 1. View Report RPT-FIN
        $response = $this->get('/backoffice/reports?type=RPT-FIN');
        $response->assertStatus(200);
        $response->assertSee('Laporan Buku Kas');
        $response->assertSee('Global Pusat');

        // 2. Export Report
        $exportResponse = $this->get('/backoffice/reports?type=RPT-FIN&export=single');
        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml', (string) $exportResponse->headers->get('Content-Type'));
    }
}
