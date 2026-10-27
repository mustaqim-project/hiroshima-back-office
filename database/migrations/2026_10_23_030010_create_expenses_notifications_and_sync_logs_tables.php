<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @req FR-OFF-01, FR-OFF-02, FR-OFF-03, FR-OFF-04, FR-OFF-05, FR-WEB-08, FR-WEB-09, FR-WEB-10, FSD-10, FSD-13, RPT-21
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('date');
            $table->text('description');
            $table->string('receipt_image_path')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'date']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('system'); // fraud_alert, low_stock, auto_close, system
            $table->boolean('is_read')->default(false);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'is_read']);
            $table->index(['branch_id', 'created_at']);
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('type'); // pull, push
            $table->unsignedInteger('batch_size')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('conflict_count')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['branch_id', 'created_at']);
        });

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status')->default('success');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('expenses');
    }
};
