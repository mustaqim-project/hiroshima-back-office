<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @req FR-MST-01, FR-MST-02, FR-MST-03, FR-MST-04, FR-MST-05, FR-MST-06, FR-MST-07, FR-MST-08, FR-MST-09, FR-MST-10, FR-MST-11, FSD-03
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('target_printer')->default('kitchen'); // kitchen, bar, cashier
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Dine In, Take Away, Delivery
            $table->string('code', 20)->unique();
            $table->boolean('is_default')->default(false); // Exactly one default
            $table->boolean('requires_table')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Cash, Debit BCA, QRIS BCA Statis
            $table->string('code', 20)->unique();
            $table->string('type'); // cash, debit_card, credit_card, qris_static
            $table->boolean('is_cash')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branch_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'payment_method_id']);
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Gram, Kilogram, Mililiter, Liter, Pcs
            $table->string('symbol', 10)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('to_unit_id')->constrained('units')->cascadeOnDelete();
            $table->decimal('multiplier', 15, 6); // e.g. 1 kg = 1000 gram
            $table->timestamps();

            $table->unique(['from_unit_id', 'to_unit_id']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('table_no', 20);
            $table->string('section')->nullable(); // Lantai 1, Smoking, VIP
            $table->unsignedInteger('capacity')->default(4);
            $table->string('status')->default('available'); // available, occupied, reserved
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'table_no']);
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('loyalty_points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('tables');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('unit_conversions');
        Schema::dropIfExists('units');
        Schema::dropIfExists('branch_payment_methods');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('order_types');
        Schema::dropIfExists('categories');
    }
};
