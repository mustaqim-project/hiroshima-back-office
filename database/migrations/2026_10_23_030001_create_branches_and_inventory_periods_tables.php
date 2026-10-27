<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @req FR-BR-01, FR-BR-02, FR-BR-03, FR-INV-00, FR-INV-12, FR-INV-13, FSD-02, FSD-05, FSD-12
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('tax_percent', 5, 2)->default(11.00); // FR-BR-03: default 11%
            $table->decimal('service_charge_percent', 5, 2)->default(0.00); // FR-BR-03: default 0%
            $table->boolean('tax_included')->default(false); // FR-BR-03: default false
            $table->string('rounding_mode')->default('round_nearest_100'); // FR-BR-03: round_nearest_100
            $table->string('pin_void_hash')->nullable(); // FSD-12: Argon2id hash PIN Void
            $table->string('pin_discount_hash')->nullable(); // FSD-12: Argon2id hash PIN Diskon
            $table->decimal('discount_percent_threshold', 5, 2)->default(10.00); // FSD-07: 10%
            $table->unsignedBigInteger('discount_amount_threshold')->default(20000); // FSD-07: Rp 20.000
            $table->boolean('inventory_enabled')->default(false); // FR-INV-00, FSD-05: default false
            $table->string('negative_stock_policy')->default('allow_flagged'); // allow_flagged
            $table->boolean('auto_sold_out')->default(false);
            $table->boolean('print_kitchen_copy')->default(true); // FSD-14
            $table->string('printer_paper_width')->default('58mm'); // 58mm or 80mm
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branch_inventory_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->dateTime('activated_at')->nullable(); // Set when initial opname approved
            $table->foreignId('activated_by')->nullable();
            $table->foreignId('deactivated_by')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'started_at', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_inventory_periods');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('businesses');
    }
};
