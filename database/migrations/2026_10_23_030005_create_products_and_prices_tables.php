<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @req FR-MENU-01, FR-MENU-02, FR-MENU-03, FR-MENU-04, FR-MENU-05, FR-MENU-06, FR-MENU-07, FR-PRICE-01, FR-PRICE-02, FR-PRICE-03, FR-PRICE-04, FR-PRICE-05, FR-PRICE-06, FR-PRICE-07, FSD-03
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('sku', 50)->unique();
            $table->string('barcode', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('cost_method')->default('recipe'); // recipe or manual
            $table->unsignedBigInteger('manual_cost')->default(0); // in IDR
            $table->boolean('has_variants')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name'); // Regular, Large, etc.
            $table->string('sku', 50)->unique();
            $table->string('cost_method')->default('recipe');
            $table->unsignedBigInteger('manual_cost')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('modifier_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Level Pedas, Topping, Es
            $table->string('selection_type')->default('single'); // single, multiple
            $table->unsignedSmallInteger('min_select')->default(0);
            $table->unsignedSmallInteger('max_select')->default(1);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_group_id')->constrained('modifier_groups')->cascadeOnDelete();
            $table->string('name'); // Level 1, Ekstra Keju
            $table->unsignedBigInteger('cost')->default(0); // HPP modifier
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_modifier_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('modifier_group_id')->constrained('modifier_groups')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'modifier_group_id']);
        });

        Schema::create('bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('bundles')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });

        // Hirarki harga: sellable (Product/Variant/Bundle/Modifier) x OrderType x Branch (nullable = pusat)
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->string('sellable_type'); // Product, ProductVariant, Bundle, Modifier
            $table->unsignedBigInteger('sellable_id');
            $table->foreignId('order_type_id')->constrained('order_types')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete(); // Null = pusat
            $table->unsignedBigInteger('price'); // Integer rupiah
            $table->timestamps();

            $table->index(['sellable_type', 'sellable_id', 'order_type_id', 'branch_id'], 'idx_sellable_pricing');
        });

        Schema::create('price_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('sellable_type');
            $table->unsignedBigInteger('sellable_id');
            $table->foreignId('order_type_id')->constrained('order_types')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->unsignedBigInteger('scheduled_price');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_schedules');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('bundle_items');
        Schema::dropIfExists('bundles');
        Schema::dropIfExists('product_modifier_groups');
        Schema::dropIfExists('modifiers');
        Schema::dropIfExists('modifier_groups');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};
