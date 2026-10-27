<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Promo;
use App\Models\User;
use App\Services\ExcelDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExcelExportImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ExcelDataService $excelService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Excel',
            'username' => 'adminexcel',
            'email' => 'adminexcel@hiroshima.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->excelService = app(ExcelDataService::class);
    }

    public function test_all_modules_can_download_template(): void
    {
        $modules = ['branches', 'products', 'modifier-groups', 'categories', 'order-types', 'payment-methods', 'users', 'promos'];

        foreach ($modules as $mod) {
            $response = $this->actingAs($this->admin)->get(route("{$mod}.template"));
            $response->assertStatus(200);
            $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $disposition = $response->headers->get('content-disposition');
            $this->assertStringContainsString('attachment', $disposition);
            $this->assertStringContainsString('Template.xlsx', $disposition);
        }
    }

    public function test_all_modules_can_export_when_empty_and_with_data(): void
    {
        $modules = ['branches', 'products', 'modifier-groups', 'categories', 'order-types', 'payment-methods', 'users', 'promos'];

        // 1. Export when empty (returns template with dummy rows)
        foreach ($modules as $mod) {
            $response = $this->actingAs($this->admin)->get(route("{$mod}.export"));
            $response->assertStatus(200);
            $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }

        // 2. Populate some real data
        $business = Business::firstOrCreate(['code' => 'HIROSHIMA'], ['name' => 'Hiroshima F&B']);
        Branch::create(['business_id' => $business->id, 'code' => 'TEST-01', 'name' => 'Cabang Test Ekspor']);
        $category = Category::create(['code' => 'CAT-01', 'name' => 'Kategori Test']);
        OrderType::create(['code' => 'ORD-01', 'name' => 'Dine In Test']);
        Product::create(['category_id' => $category->id, 'sku' => 'PRD-01', 'name' => 'Produk Test']);
        ModifierGroup::create(['name' => 'Topping Test']);
        PaymentMethod::create(['code' => 'PAY-01', 'name' => 'Metode Test', 'type' => 'cash']);
        Promo::create(['code' => 'PRM-01', 'name' => 'Promo Test', 'type' => 'percentage', 'value' => 10, 'starts_at' => now(), 'ends_at' => now()->addDays(30)]);

        // 3. Export with data
        foreach ($modules as $mod) {
            $response = $this->actingAs($this->admin)->get(route("{$mod}.export"));
            $response->assertStatus(200);
            $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }
    }

    public function test_import_branches_from_excel(): void
    {
        $file = $this->createExcelFile([
            ['Kode Cabang', 'Nama Cabang', 'Telepon', 'Alamat', 'PPN (%)', 'Service Charge (%)', 'Termasuk Pajak (1/0)', 'Metode Pembulatan', 'Ambang Diskon (%)', 'Ambang Diskon (Rp)', 'Lacak Inventori (1/0)', 'Status Aktif (1/0)'],
            ['IMPOR-01', 'Cabang Impor Baru', '0811223344', 'Jl. Merdeka No. 1', 11, 0, 0, 'nearest_100', 20, 50000, 1, 1],
        ]);

        $response = $this->actingAs($this->admin)->post(route('branches.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('branches', [
            'code' => 'IMPOR-01',
            'name' => 'Cabang Impor Baru',
        ]);
    }

    public function test_import_products_and_categories_from_excel(): void
    {
        $file = $this->createExcelFile([
            ['SKU', 'Nama Produk', 'Nama Kategori', 'Harga Pokok HPP (Rp)', 'Harga Jual Default (Rp)', 'Barcode', 'Deskripsi', 'Status Aktif (1/0)'],
            ['RAMEN-IMP-01', 'Ramen Spesial Impor', 'Mie Jepang', 20000, 48000, '899999999', 'Ramen lezat', 1],
        ]);

        $response = $this->actingAs($this->admin)->post(route('products.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'sku' => 'RAMEN-IMP-01',
            'name' => 'Ramen Spesial Impor',
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Mie Jepang',
        ]);
    }

    public function test_import_modifier_groups_from_excel(): void
    {
        $file = $this->createExcelFile([
            ['Nama Grup Modifier', 'Tipe Pilihan (single/multiple)', 'Wajib Dipilih (1/0)', 'Min Pilihan', 'Max Pilihan', 'Daftar Opsi (Format: Nama:Harga, Nama:Harga)', 'Status Aktif (1/0)'],
            ['Level Kepedasan Ekstra', 'single', 1, 1, 1, 'Level Rendah:0, Level Sedang:2000, Level Dewa:5000', 1],
        ]);

        $response = $this->actingAs($this->admin)->post(route('modifier-groups.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $group = ModifierGroup::where('name', 'Level Kepedasan Ekstra')->first();
        $this->assertNotNull($group);
        $this->assertDatabaseHas('modifiers', [
            'modifier_group_id' => $group->id,
            'name' => 'Level Dewa',
            'price' => 5000,
        ]);
    }

    public function test_import_order_types_from_excel(): void
    {
        $file = $this->createExcelFile([
            ['Kode Pesanan', 'Nama Jenis Pesanan', 'Perlu Nomor Meja (1/0)', 'Pesanan Utama / Default (1/0)', 'Urutan Tampil', 'Status Aktif (1/0)'],
            ['VIP_ROOM', 'VIP Private Room', 1, 0, 4, 1],
        ]);

        $response = $this->actingAs($this->admin)->post(route('order-types.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('order_types', [
            'code' => 'VIP_ROOM',
            'name' => 'VIP Private Room',
            'requires_table' => 1,
        ]);
    }

    public function test_import_payment_methods_from_excel(): void
    {
        $file = $this->createExcelFile([
            ['Kode Metode', 'Nama Metode Pembayaran', 'Kategori Tipe (cash/qris/debit/credit/transfer/ewallet)', 'Merupakan Tunai (1/0)', 'Status Aktif (1/0)'],
            ['SHOPEEPAY', 'ShopeePay QRIS', 'ewallet', 0, 1],
        ]);

        $response = $this->actingAs($this->admin)->post(route('payment-methods.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payment_methods', [
            'code' => 'SHOPEEPAY',
            'type' => 'ewallet',
        ]);
    }

    public function test_import_users_from_excel(): void
    {
        $file = $this->createExcelFile([
            ['Nama Lengkap', 'Username', 'Email', 'Nomor Telepon', 'Role (admin/owner/supervisor/cashier)', 'Kode Cabang', 'Kata Sandi Awal', 'PIN Kasir (6 Angka)', 'Status Aktif (1/0)'],
            ['Doni Pratama', 'doni_cashier', 'doni@hiroshima.id', '081233445566', 'cashier', '', 'pass12345', '789123', 1],
        ]);

        $response = $this->actingAs($this->admin)->post(route('users.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'username' => 'doni_cashier',
            'email' => 'doni@hiroshima.id',
            'role' => 'cashier',
        ]);
    }

    public function test_import_promos_from_excel(): void
    {
        $file = $this->createExcelFile([
            ['Kode Promo', 'Nama Promo', 'Tipe Diskon (percentage/fixed_amount)', 'Nilai Diskon', 'Min. Pembelian (Rp)', 'Maks. Diskon (Rp)', 'Bisa Digabung (1/0)', 'Prioritas', 'Tanggal Mulai (YYYY-MM-DD)', 'Tanggal Berakhir (YYYY-MM-DD)', 'Kuota Penggunaan', 'Status Aktif (1/0)'],
            ['FLASHSALE50', 'Diskon Kilat 50%', 'percentage', 50, 100000, 50000, 0, 1, '2026-11-01', '2026-11-30', 100, 1],
        ]);

        $response = $this->actingAs($this->admin)->post(route('promos.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('promos', [
            'code' => 'FLASHSALE50',
            'value' => 50,
            'min_purchase' => 100000,
        ]);
    }

    protected function createExcelFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rIdx => $row) {
            foreach ($row as $cIdx => $val) {
                $colLetter = Coordinate::stringFromColumnIndex($cIdx + 1);
                $rowNum = $rIdx + 1;
                $sheet->setCellValue("{$colLetter}{$rowNum}", $val);
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile(
            $tempPath,
            'test_upload.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}
