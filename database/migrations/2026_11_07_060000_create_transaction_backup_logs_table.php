<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_backup_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('order_uuid', 64)->index();
            $table->string('order_no', 64)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('device_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('shift_id')->nullable()->index();
            $table->unsignedBigInteger('table_id')->nullable()->index();
            $table->string('table_name', 100)->nullable();
            $table->string('customer_name', 150)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->unsignedInteger('items_count')->default(0);
            $table->string('status', 32)->default('completed')->index();
            $table->string('sync_status', 32)->nullable()->index();
            $table->string('source', 32)->default('pos_sync')->index();
            $table->text('error_message')->nullable();
            $table->longText('raw_payload');
            $table->timestamp('client_created_at')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_backup_logs');
    }
};

