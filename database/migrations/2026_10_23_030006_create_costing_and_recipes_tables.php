<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @req FR-RCP-01, FR-RCP-02, FR-RCP-03, FR-HPP-01, FR-HPP-02, FSD-04
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku', 50)->unique();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->unsignedBigInteger('standard_cost')->default(0); // Standard cost in IDR
            $table->decimal('minimum_stock', 15, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ingredient_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->unsignedBigInteger('moving_average_cost')->default(0); // MA cost in IDR
            $table->unsignedBigInteger('last_purchase_price')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'ingredient_id']);
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('yield_quantity', 10, 2)->default(1.00);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('ingredient_costs');
        Schema::dropIfExists('ingredients');
    }
};
