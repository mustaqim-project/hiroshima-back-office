<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @req FR-PROMO-01, FR-PROMO-02, FR-PROMO-03, FR-PROMO-04, FR-PROMO-05, FR-VCH-01, FR-VCH-02, FR-VCH-03, FR-LOY-01, FR-LOY-02, FSD-06
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type'); // percentage, fixed_amount
            $table->decimal('value', 12, 2); // 10.00 (%) or 15000 (Rp)
            $table->unsignedBigInteger('min_purchase')->default(0);
            $table->unsignedBigInteger('max_discount')->nullable();
            $table->boolean('is_stackable')->default(false);
            $table->integer('priority')->default(0);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->time('start_time')->nullable(); // e.g. Happy Hour 14:00
            $table->time('end_time')->nullable();   // e.g. 17:00
            $table->json('days_of_week')->nullable(); // [1, 2, 3, 4, 5]
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('promo_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promos')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['promo_id', 'branch_id']);
        });

        Schema::create('promo_order_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promos')->cascadeOnDelete();
            $table->foreignId('order_type_id')->constrained('order_types')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['promo_id', 'order_type_id']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('type'); // percentage, fixed_amount
            $table->decimal('value', 12, 2);
            $table->unsignedBigInteger('min_purchase')->default(0);
            $table->unsignedBigInteger('max_discount')->nullable();
            $table->unsignedInteger('quota')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('loyalty_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->unsignedBigInteger('min_spend_per_point')->default(10000); // Rp 10.000 = 1 poin
            $table->unsignedBigInteger('point_value_idr')->default(100);       // 1 poin = Rp 100
            $table->decimal('max_redemption_percent', 5, 2)->default(20.00);   // Max 20% of subtotal
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('type'); // earned, redeemed, void_reversal, manual_adjustment
            $table->bigInteger('points'); // positive for earn, negative for redeem
            $table->uuid('order_uuid')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_rules');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('promo_order_types');
        Schema::dropIfExists('promo_branches');
        Schema::dropIfExists('promos');
    }
};
