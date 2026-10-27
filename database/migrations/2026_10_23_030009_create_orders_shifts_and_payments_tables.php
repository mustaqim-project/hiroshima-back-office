<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @req FR-POS-01, FR-POS-01a, FR-POS-01b, FR-POS-02, FR-POS-03, FR-POS-04, FR-POS-05, FR-POS-05a, FR-POS-06, FR-POS-07, FR-POS-08, FR-POS-09, FR-POS-10, FR-POS-11, FR-POS-12, FR-POS-13, FR-POS-14, FR-POS-15, FR-POS-16, FR-POS-17, FR-OFF-01, FR-OFF-02, FR-OFF-03, FR-OFF-04, FR-OFF-05, FR-OFF-06, FR-OFF-07, FSD-06, FSD-07, FSD-08, FSD-09, FSD-10
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('starting_cash')->default(0);
            $table->unsignedBigInteger('expected_cash')->nullable(); // Calculated on close: modal + cash - refund + in - out
            $table->unsignedBigInteger('actual_cash')->nullable();
            $table->bigInteger('cash_difference')->nullable(); // actual - expected
            $table->string('status')->default('open'); // open, closed, auto_closed, reconciled
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reconciled_at')->nullable();
            $table->text('reconciliation_notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status', 'opened_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // cash_in, cash_out
            $table->unsignedBigInteger('amount');
            $table->string('reason');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['shift_id', 'created_at']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // UUID from tablet client
            $table->string('order_no', 40)->unique(); // {branch_code}-{device_code}-{YYYYMMDD}-{seq}
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('order_type_id')->constrained('order_types')->cascadeOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete();
            $table->string('status')->default('completed'); // open, completed, voided
            $table->date('business_date'); // Calendar date WIB

            // Calculation breakdown in integer rupiah (FSD-06)
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('promo_discount_amount')->default(0);
            $table->unsignedBigInteger('voucher_discount_amount')->default(0);
            $table->unsignedBigInteger('loyalty_discount_amount')->default(0);
            $table->unsignedBigInteger('manual_discount_amount')->default(0);
            $table->string('manual_discount_reason')->nullable();
            $table->boolean('manual_discount_requires_pin')->default(false);
            $table->unsignedBigInteger('service_charge_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->bigInteger('rounding_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('change_amount')->default(0);
            $table->unsignedBigInteger('cost_amount')->default(0); // Total HPP

            // Offline Sync metadata (FSD-10)
            $table->dateTime('client_created_at');
            $table->dateTime('synced_at')->nullable();
            $table->boolean('clock_skew_flag')->default(false);
            $table->string('sync_status')->default('accepted'); // accepted, accepted_flagged, duplicate_ok, rejected

            // Void / Refund metadata (FSD-08)
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('voided_at')->nullable();

            $table->timestamps();

            $table->index(['branch_id', 'business_date']);
            $table->index(['shift_id', 'status']);
            $table->index(['client_created_at', 'synced_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('order_type_name_snapshot');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('cost_per_unit')->default(0);
            $table->unsignedBigInteger('subtotal');
            $table->text('notes')->nullable();
            $table->dateTime('kitchen_printed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('modifier_id')->constrained('modifiers')->cascadeOnDelete();
            $table->string('modifier_name');
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedBigInteger('cost_per_unit')->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('order_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('bill_no', 30);
            $table->string('status')->default('unpaid'); // unpaid, paid
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_bill_id')->nullable()->constrained('order_bills')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->string('payment_method_name_snapshot');
            $table->unsignedBigInteger('amount');
            $table->string('reference_no')->nullable(); // EDC approval code or static QRIS ref
            $table->dateTime('paid_at');
            $table->timestamps();

            $table->index(['shift_id', 'payment_method_id']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('reason');
            $table->boolean('authorized_by_pin')->default(true);
            $table->foreignId('authorized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('voucher_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->uuid('order_uuid');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->unsignedBigInteger('discount_amount');
            $table->dateTime('redeemed_at');
            $table->timestamps();

            $table->index(['voucher_id', 'order_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_redemptions');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_bills');
        Schema::dropIfExists('order_item_modifiers');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('shifts');
    }
};
