<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Mail\ResetPinMail;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailConfigService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SmtpAndResetAuthTest extends TestCase
{
    protected Business $business;
    protected Branch $branch;
    protected User $adminUser;
    protected User $cashierUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->business = Business::firstOrCreate(
            ['code' => 'TEST-BIZ'],
            ['name' => 'Hiroshima Street Food', 'phone' => '0812345678', 'email' => 'test@hiroshima.id']
        );

        $this->branch = Branch::firstOrCreate(
            ['code' => 'TEST-BR-SMTP'],
            [
                'business_id' => $this->business->id,
                'name' => 'Hiroshima Cikini Test',
                'tax_percent' => 10,
                'service_charge_percent' => 0,
                'is_active' => true,
            ]
        );

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin.test@hiroshima.id'],
            [
                'business_id' => $this->business->id,
                'branch_id' => $this->branch->id,
                'name' => 'Admin Test',
                'username' => 'admintest',
                'password' => Hash::make('password123'),
                'pin_hash' => password_hash('123456', PASSWORD_ARGON2ID),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $this->cashierUser = User::firstOrCreate(
            ['email' => 'kasir.test@hiroshima.id'],
            [
                'business_id' => $this->business->id,
                'branch_id' => $this->branch->id,
                'name' => 'Kasir Cikini Test',
                'username' => 'kasirtest',
                'password' => Hash::make('password123'),
                'pin_hash' => password_hash('112233', PASSWORD_ARGON2ID),
                'role' => 'cashier',
                'is_active' => true,
            ]
        );
    }

    public function test_smtp_settings_can_be_saved_in_database_and_applied(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('settings.smtp'));
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Server SMTP Email');

        $postData = [
            'mail_host' => 'smtp.gmail.com',
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'mail_username' => 'hiroshimajapanese@gmail.com',
            'mail_password' => 'secret_app_password_16',
            'mail_from_address' => 'hiroshimajapanese@gmail.com',
            'mail_from_name' => 'Hiroshima Japanese Street Food',
        ];

        $updateResponse = $this->post(route('settings.smtp.update'), $postData);
        $updateResponse->assertRedirect(route('settings.smtp'));
        $updateResponse->assertSessionHas('success');

        // Verifikasi tersimpan di tabel app_settings (database, bukan .env)
        $this->assertDatabaseHas('app_settings', [
            'key' => 'mail_host',
            'value' => 'smtp.gmail.com',
        ]);

        // Verifikasi password terenkripsi di database
        $passwordSetting = DB::table('app_settings')->where('key', 'mail_password')->first();
        $this->assertNotNull($passwordSetting);
        $this->assertTrue((bool) $passwordSetting->is_encrypted);
        $this->assertNotEquals('secret_app_password_16', $passwordSetting->value); // Harus ciphertext terenkripsi

        // Verifikasi dekripsi via Setting::get()
        $this->assertEquals('secret_app_password_16', Setting::get('mail_password'));

        // Verifikasi MailConfigService::apply() menginjeksi ke config runtime Laravel
        MailConfigService::apply();
        $this->assertEquals('smtp.gmail.com', config('mail.mailers.smtp.host'));
        $this->assertEquals(587, config('mail.mailers.smtp.port'));
        $this->assertEquals('hiroshimajapanese@gmail.com', config('mail.mailers.smtp.username'));
        $this->assertEquals('secret_app_password_16', config('mail.mailers.smtp.password'));
    }

    public function test_forgot_password_sends_email_with_token_and_resets_password(): void
    {
        Mail::fake();

        // 1. Request forgot password using username
        $response = $this->post(route('password.email'), [
            'email_or_username' => 'admintest',
        ]);

        $response->assertSessionHas('success');
        Mail::assertSent(ResetPasswordMail::class, function ($mail) {
            return $mail->hasTo('admin.test@hiroshima.id') && ! empty($mail->resetUrl);
        });

        // 2. Token tersimpan di password_reset_tokens
        $record = DB::table('password_reset_tokens')->where('email', 'admin.test@hiroshima.id')->first();
        $this->assertNotNull($record);

        // Simulasi token valid untuk form reset
        $token = 'test-token-1234567890abcdef';
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => 'admin.test@hiroshima.id'],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // 3. Render reset password form
        $resetForm = $this->get(route('password.reset', ['token' => $token, 'email' => 'admin.test@hiroshima.id']));
        $resetForm->assertStatus(200);
        $resetForm->assertSee('admin.test@hiroshima.id');

        // 4. Submit reset password
        $resetAction = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'admin.test@hiroshima.id',
            'password' => 'newPassword456',
            'password_confirmation' => 'newPassword456',
        ]);

        $resetAction->assertRedirect(route('login'));
        $resetAction->assertSessionHas('success');

        // Token telah dihapus setelah sukses
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'admin.test@hiroshima.id']);

        // User dapat login dengan password baru
        $this->adminUser->refresh();
        $this->assertTrue(Hash::check('newPassword456', $this->adminUser->password));
    }

    public function test_forgot_pin_sends_otp_and_resets_cashier_pin(): void
    {
        Mail::fake();

        // 1. Request PIN reset using cashier username
        $response = $this->post(route('pin.email'), [
            'email_or_username' => 'kasirtest',
        ]);

        $response->assertRedirect(route('pin.verify', ['email' => 'kasir.test@hiroshima.id']));
        $response->assertSessionHas('success');

        $capturedOtp = null;
        Mail::assertSent(ResetPinMail::class, function ($mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otpCode;
            return $mail->hasTo('kasir.test@hiroshima.id') && strlen($capturedOtp) === 6;
        });

        $this->assertNotNull($capturedOtp);

        // 2. Akses halaman verify PIN
        $verifyPage = $this->get(route('pin.verify', ['email' => 'kasir.test@hiroshima.id']));
        $verifyPage->assertStatus(200);
        $verifyPage->assertSee('Verifikasi OTP');

        // 3. Uji salah OTP
        $failAction = $this->post(route('pin.update'), [
            'email' => 'kasir.test@hiroshima.id',
            'otp' => '000000',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ]);
        $failAction->assertSessionHasErrors('otp');

        // 4. Submit OTP benar dan PIN baru 6 digit
        $successAction = $this->post(route('pin.update'), [
            'email' => 'kasir.test@hiroshima.id',
            'otp' => $capturedOtp,
            'pin' => '987654',
            'pin_confirmation' => '987654',
        ]);

        $successAction->assertRedirect(route('login'));
        $successAction->assertSessionHas('success');

        // Verifikasi pin_hash terupdate dan terverifikasi dengan password_verify
        $this->cashierUser->refresh();
        $this->assertTrue(password_verify('987654', $this->cashierUser->pin_hash));
    }

    public function test_api_forgot_pin_and_reset_pin_flow(): void
    {
        Mail::fake();

        // 1. API request OTP
        $reqResponse = $this->postJson('/api/v1/auth/forgot-pin', [
            'email_or_username' => 'kasirtest',
        ]);

        $reqResponse->assertStatus(200);
        $reqResponse->assertJson(['status' => 'success']);

        $otp = null;
        Mail::assertSent(ResetPinMail::class, function ($mail) use (&$otp) {
            $otp = $mail->otpCode;
            return true;
        });

        $this->assertNotNull($otp);

        // 2. API reset PIN with OTP
        $resetResponse = $this->postJson('/api/v1/auth/reset-pin', [
            'email' => 'kasir.test@hiroshima.id',
            'otp' => $otp,
            'pin' => '554433',
        ]);

        $resetResponse->assertStatus(200);
        $resetResponse->assertJson(['status' => 'success']);

        $this->cashierUser->refresh();
        $this->assertTrue(password_verify('554433', $this->cashierUser->pin_hash));
    }
}
