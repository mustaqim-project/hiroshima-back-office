<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('general')->index();
            $table->string('key', 100)->unique();
            $table->longText('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        Schema::create('pin_reset_tokens', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token'); // 6-digit OTP or token hash
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('pin_reset_tokens');
    }
};
