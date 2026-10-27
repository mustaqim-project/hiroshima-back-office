<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\CashBook;
use App\Models\Category;
use App\Models\FraudAlert;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Shift;
use App\Models\User;
use App\Services\ReportExcelService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class ReportsComprehensiveTest extends TestCase
{
    protected User $adminUser;
    protected Branch $branchA;
    protected Branch $branchB;
    protected ReportExcelService $reportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportService = app(ReportExcelService::class);

        $business = Business::firstOrCreate(
            ['code' => 'HRS'],
            ['name' => 'Hiroshima Japanese Food']
        );

        $this->branchA = Branch::firstOrCreate(
            ['code' => 'HRS-PST'],
            [
                'business_id' => $business->id,
                'name' => 'Hiroshima Pusat',
                'tax_percent' => 10,
                'service_charge_percent' => 5,
                'tax_included' => false,
                'rounding_mode' => 'round_nearest_100',
            ]
        );

        $this->branchB = Branch::firstOrCreate(
            ['code' => 'HRS-TBT'],
            [
                'business_id' => $business->id,
                'name' => 'Hiroshima Tebet',
                'tax_percent' => 10,
                'service_charge_percent' => 0,
                'tax_included' => false,
                'rounding_mode' => 'round_nearest_100',
            ]
        );

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_test_rpt@hiroshima.id'],
            [
                'name' => 'Admin Testing Laporan',
                'username' => 'admin_rpt_test',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $this->actingAs($this->adminUser);
    }

    public function test_all_18_report_codes_render_on_web_view_successfully(): void
    {
        $availableReports = ReportExcelService::getAvailableReports();
        $this->assertCount(18, $availableReports);

        foreach ($availableReports as $code => $def) {
            $response = $this->get('/backoffice/reports?type=' . $code);
            $response->assertStatus(200);
            $response->assertSee($code);
            $response->assertSee($def['name']);
        }
    }

    public function test_all_18_report_codes_export_to_individual_xlsx_successfully(): void
    {
        $availableReports = ReportExcelService::getAvailableReports();

        foreach ($availableReports as $code => $def) {
            $response = $this->get('/backoffice/reports?type=' . $code . '&export=single');
            $response->assertStatus(200);
            $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('Content-Type'));

            $content = $response->streamedContent();
            // Verify OpenXML Zip magic bytes: PK\x03\x04
            $this->assertStringStartsWith("PK\x03\x04", $content, "Export for {$code} must be a valid zip archive");

            // Verify zip can be parsed and has sheet1, sheet2
            $tempFile = tempnam(sys_get_temp_dir(), 'test_rpt_single_') . '.xlsx';
            file_put_contents($tempFile, $content);

            $zip = new ZipArchive();
            $this->assertTrue($zip->open($tempFile) === true, "Zip archive failed to open for {$code}");
            $this->assertNotEmpty($zip->getFromName('[Content_Types].xml'));
            $this->assertNotEmpty($zip->getFromName('xl/workbook.xml'));
            $this->assertNotEmpty($zip->getFromName('xl/styles.xml'));
            $this->assertNotEmpty($zip->getFromName('xl/worksheets/sheet1.xml'));
            $this->assertNotEmpty($zip->getFromName('xl/worksheets/sheet2.xml'));
            $zip->close();
            @unlink($tempFile);
        }
    }

    public function test_full_package_exports_xlsx_with_19_worksheets(): void
    {
        $response = $this->get('/backoffice/reports?export=package');
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("PK\x03\x04", $content);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_rpt_pkg_') . '.xlsx';
        file_put_contents($tempFile, $content);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tempFile) === true, 'Package zip failed to open');
        
        $wbXml = $zip->getFromName('xl/workbook.xml');
        $this->assertNotEmpty($wbXml);

        // Verify all 19 sheets exist
        for ($s = 1; $s <= 19; $s++) {
            $sheetFile = "xl/worksheets/sheet{$s}.xml";
            $this->assertNotEmpty($zip->getFromName($sheetFile), "Missing worksheet file {$sheetFile}");
        }

        // Verify sticky freeze panes on row 5 and styles
        $sheet2Xml = $zip->getFromName('xl/worksheets/sheet2.xml');
        $this->assertStringContainsString('ySplit="5"', $sheet2Xml, 'Sheet must have frozen header at row 5');
        $this->assertStringContainsString('state="frozen"', $sheet2Xml);

        $stylesXml = $zip->getFromName('xl/styles.xml');
        $this->assertStringContainsString('numFmtId="164"', $stylesXml, 'Styles must include Indonesian Rupiah currency format');

        $zip->close();
        @unlink($tempFile);
    }

    public function test_formula_injection_protection(): void
    {
        $this->assertSame("'=SUM(A1:A10)", ReportExcelService::sanitizeCellText('=SUM(A1:A10)'));
        $this->assertSame("'+cmd|' /C calc", ReportExcelService::sanitizeCellText('+cmd|\' /C calc'));
        $this->assertSame("'-100", ReportExcelService::sanitizeCellText('-100'));
        $this->assertSame("'@malicious", ReportExcelService::sanitizeCellText('@malicious'));
        $this->assertSame("Normal Ramen", ReportExcelService::sanitizeCellText('Normal Ramen'));
    }

    public function test_cash_book_report_calculates_saldo_awal_and_running_balance(): void
    {
        $yesterday = now('Asia/Jakarta')->subDays(2)->toDateString();
        $today = now('Asia/Jakarta')->toDateString();

        // 1. Transaction yesterday (Saldo Awal)
        CashBook::create([
            'branch_id' => $this->branchA->id,
            'type' => 'in',
            'category' => 'Modal Awal',
            'amount' => 500000,
            'transaction_date' => $yesterday,
            'description' => 'Suntikan modal kemarin',
            'created_by' => $this->adminUser->id,
        ]);

        // 2. Transaction today
        CashBook::create([
            'branch_id' => $this->branchA->id,
            'type' => 'out',
            'category' => 'Belanja Bahan Baku',
            'amount' => 150000,
            'transaction_date' => $today,
            'description' => 'Beli bumbu dan telur',
            'created_by' => $this->adminUser->id,
        ]);

        $dataset = $this->reportService->getCashBookData($today, $today, (string) $this->branchA->id);

        $this->assertNotEmpty($dataset['rows']);
        // Row 1 is Saldo Awal (500.000)
        $this->assertSame('Saldo Awal', $dataset['rows'][0][3]);
        $this->assertEquals(500000, $dataset['rows'][0][8]);

        // Row 2 is Uang Keluar (150.000), running balance becomes 350.000
        $this->assertSame('Uang Keluar', $dataset['rows'][1][3]);
        $this->assertEquals(350000, $dataset['rows'][1][8]);

        // Total ending balance is 350.000
        $this->assertEquals(350000, $dataset['total'][8]);
    }

    public function test_branch_scoping_for_restricted_user(): void
    {
        $cashierBranchA = User::create([
            'name' => 'Kasir Cabang A',
            'username' => 'kasir_a_' . Str::random(5),
            'email' => 'kasir_a_' . Str::random(5) . '@hiroshima.id',
            'password' => Hash::make('password123'),
            'role' => 'cashier',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);

        $this->actingAs($cashierBranchA);

        // Attempt to request branch B
        $response = $this->get('/backoffice/reports?type=RPT-01&branch_id=' . $this->branchB->id);
        $response->assertStatus(200);

        // Verify the view received branch A id instead
        $response->assertViewHas('branchId', (string) $this->branchA->id);
    }

    public function test_empty_dataset_renders_and_exports_gracefully(): void
    {
        // Future date where no orders or cash exist
        $futureDate = '2030-01-01';

        $response = $this->get('/backoffice/reports?type=RPT-01&start_date=' . $futureDate . '&end_date=' . $futureDate);
        $response->assertStatus(200);
        $response->assertSee('Tidak ada data ditemukan untuk laporan RPT-01');

        $exportRes = $this->get('/backoffice/reports?type=RPT-01&export=single&start_date=' . $futureDate . '&end_date=' . $futureDate);
        $exportRes->assertStatus(200);
        $this->assertStringStartsWith("PK\x03\x04", $exportRes->streamedContent());
    }
}
