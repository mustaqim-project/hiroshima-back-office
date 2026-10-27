<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @req FR-AUTH-01, FR-AUTH-02, FR-AUTH-03, FR-AUTH-05, FR-AUTH-06, FR-DEV-01, FR-DEV-02, FR-DEV-03, FSD-01
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('pin_hash')->nullable()->after('password'); // 6-digit PIN hashed with Argon2id
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->default('cashier')->after('phone'); // owner, central_admin, branch_manager, warehouse_staff, cashier
            $table->boolean('is_active')->default(true)->after('role');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->dateTime('locked_until')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->softDeletes();

            $table->index(['branch_id', 'role']);
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('device_code', 20)->unique();
            $table->string('device_name');
            $table->string('device_token_hash')->nullable();
            $table->string('app_version')->nullable();
            $table->dateTime('last_sync_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'is_active']);
        });

        Schema::create('device_activation_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('code', 10)->unique();
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->foreignId('used_by_device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['code', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_activation_codes');
        Schema::dropIfExists('devices');
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'branch_id', 'username', 'pin_hash', 'phone', 'role',
                'is_active', 'failed_login_attempts', 'locked_until', 'must_change_password'
            ]);
        });
    }
};
