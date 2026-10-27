<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modifiers', function (Blueprint $table) {
            if (!Schema::hasColumn('modifiers', 'price')) {
                $table->unsignedBigInteger('price')->default(0)->after('name');
            }
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'price')) {
                $table->unsignedBigInteger('price')->default(0)->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('modifiers', function (Blueprint $table) {
            if (Schema::hasColumn('modifiers', 'price')) {
                $table->dropColumn('price');
            }
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
