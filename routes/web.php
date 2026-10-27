<?php

use App\Http\Controllers\Web\AuditController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BranchController;
use App\Http\Controllers\Web\CashBookController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ExcelDataController;
use App\Http\Controllers\Web\ForgotPasswordController;
use App\Http\Controllers\Web\ForgotPinController;
use App\Http\Controllers\Web\FraudController;
use App\Http\Controllers\Web\GeneralSettingController;
use App\Http\Controllers\Web\IngredientController;
use App\Http\Controllers\Web\ModifierGroupController;
use App\Http\Controllers\Web\OrderTypeController;
use App\Http\Controllers\Web\PaymentMethodController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\PromoController;
use App\Http\Controllers\Web\ReconciliationController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\SmtpSettingController;
use App\Http\Controllers\Web\StorageFileController;
use App\Http\Controllers\Web\TableController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

/**
 * @req FR-WEB-01, FR-WEB-02, FR-WEB-03, FR-WEB-04, FR-WEB-05, FR-WEB-06, FR-WEB-07, FR-WEB-08, FR-WEB-09, FR-WEB-10, FR-AUTH-01, FR-AUTH-03, RPT-01, RPT-25
 */

// Storage Asset Route (Seamless fallback for shared hosting / missing symlink)
Route::get('/storage/{path}', [StorageFileController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.file');

// Public Web Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password Reset via Database SMTP
Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');

// Cashier PIN Reset via Database SMTP & 6-Digit OTP
Route::get('/forgot-pin', [ForgotPinController::class, 'showForgotPinForm'])->name('pin.request');
Route::post('/forgot-pin', [ForgotPinController::class, 'sendPinResetOtp'])->name('pin.email');
Route::get('/verify-pin', [ForgotPinController::class, 'showVerifyPinForm'])->name('pin.verify');
Route::post('/verify-pin', [ForgotPinController::class, 'verifyAndResetPin'])->name('pin.update');

// Authenticated Back-office Operations
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/backoffice', fn() => redirect('/'))->name('backoffice.root');

    Route::prefix('backoffice')->group(function () {
        // Sistem Ekspor, Unduh Template, dan Impor Spreadsheet (Excel / CSV)
        foreach (['branches', 'products', 'modifier-groups', 'categories', 'order-types', 'payment-methods', 'users', 'promos'] as $excelMod) {
            Route::get("{$excelMod}/export", [ExcelDataController::class, 'export'])->defaults('module', $excelMod)->name("{$excelMod}.export");
            Route::get("{$excelMod}/template", [ExcelDataController::class, 'template'])->defaults('module', $excelMod)->name("{$excelMod}.template");
            Route::post("{$excelMod}/import", [ExcelDataController::class, 'import'])->defaults('module', $excelMod)->name("{$excelMod}.import");
        }

        // Master Data & Operasional (Full CRUD + Soft Delete)
        Route::resource('cash-books', CashBookController::class);
        Route::resource('branches', BranchController::class);
        Route::post('branches/{branch}/pin', [BranchController::class, 'updatePin'])->name('branches.pin.update');
        Route::post('branches/{branch}/toggle-inventory', [BranchController::class, 'toggleInventory'])->name('branches.toggle-inventory');

        Route::resource('categories', CategoryController::class);
        Route::resource('order-types', OrderTypeController::class);
        Route::resource('products', ProductController::class);
        Route::resource('modifier-groups', ModifierGroupController::class);
        Route::resource('tables', TableController::class);
        Route::resource('payment-methods', PaymentMethodController::class);
        Route::resource('users', UserController::class);
        Route::resource('ingredients', IngredientController::class);
        Route::resource('promos', PromoController::class);

        // Rekonsiliasi Shift & Cetak Laporan Close Shift
        Route::get('/reconciliation', [ReconciliationController::class, 'index'])->name('reconciliation.index');
        Route::get('/reconciliation/{shift}/report', [ReconciliationController::class, 'report'])->name('reconciliation.report');
        Route::post('/reconciliation/{shift}', [ReconciliationController::class, 'reconcile'])->name('reconciliation.reconcile');

        // 23 Laporan Komprehensif & Ekspor Excel (Single & Multi-Sheet Full Package)
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-single', [ReportController::class, 'exportSingle'])->name('reports.export.single');
        Route::get('/reports/export-package', [ReportController::class, 'exportPackage'])->name('reports.export.package');

        // Deteksi Fraud & Kasus Kecurangan (EXT)
        Route::get('/fraud', [FraudController::class, 'index'])->name('fraud.index');
        Route::post('/fraud/{alert}/review', [FraudController::class, 'review'])->name('fraud.review');

        // Audit Log Viewer & Integritas Hash Chain
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

        // Pengaturan Sistem (Identitas & Branding)
        Route::get('/settings/general', [GeneralSettingController::class, 'index'])->name('settings.general');
        Route::post('/settings/general', [GeneralSettingController::class, 'update'])->name('settings.general.update');

        // Pengaturan Sistem (Database SMTP Configuration)
        Route::get('/settings/smtp', [SmtpSettingController::class, 'index'])->name('settings.smtp');
        Route::post('/settings/smtp', [SmtpSettingController::class, 'update'])->name('settings.smtp.update');
        Route::post('/settings/smtp/test', [SmtpSettingController::class, 'testMail'])->name('settings.smtp.test');
    });
});
