<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @req FR-AUTH-03, FSD-07, FSD-08, FSD-12, FSD-18, RPT-23
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // e.g. price.change, void, stock.toggle, pin.update
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->dateTime('occurred_at');
            $table->string('ip', 45)->nullable();
            $table->string('prev_hash', 64);
            $table->string('hash', 64)->unique();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['branch_id', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('authorization_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // void, discount, login
            $table->boolean('success');
            $table->string('reason')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['branch_id', 'type', 'created_at']);
            $table->index(['user_id', 'success', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_logs');
        Schema::dropIfExists('audit_logs');
    }
};
