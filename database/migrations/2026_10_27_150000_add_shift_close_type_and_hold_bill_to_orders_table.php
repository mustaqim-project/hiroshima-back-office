<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'close_type')) {
                $table->string('close_type')->default('manual')->after('status'); // manual, auto
            }
            if (!Schema::hasColumn('shifts', 'close_reason')) {
                $table->text('close_reason')->nullable()->after('close_type');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'held_at')) {
                $table->dateTime('held_at')->nullable()->after('business_date');
            }
            if (!Schema::hasColumn('orders', 'hold_reference')) {
                $table->string('hold_reference')->nullable()->after('held_at');
            }
            if (!Schema::hasColumn('orders', 'hold_notes')) {
                $table->text('hold_notes')->nullable()->after('hold_reference');
            }
            if (!Schema::hasColumn('orders', 'held_by')) {
                $table->foreignId('held_by')->nullable()->after('hold_notes')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'is_unresolved_hold')) {
                $table->boolean('is_unresolved_hold')->default(false)->after('held_by');
            }
        });

        // Set default hold_bill_enabled setting if not present
        if (Schema::hasTable('app_settings')) {
            $exists = DB::table('app_settings')->where('key', 'hold_bill_enabled')->exists();
            if (!$exists) {
                DB::table('app_settings')->insert([
                    'group' => 'general',
                    'key' => 'hold_bill_enabled',
                    'value' => '1',
                    'is_encrypted' => false,
                    'created_at' => now('Asia/Jakarta'),
                    'updated_at' => now('Asia/Jakarta'),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('orders', 'held_by')) {
                $table->dropForeign(['held_by']);
                $columns[] = 'held_by';
            }
            if (Schema::hasColumn('orders', 'held_at')) {
                $columns[] = 'held_at';
            }
            if (Schema::hasColumn('orders', 'hold_reference')) {
                $columns[] = 'hold_reference';
            }
            if (Schema::hasColumn('orders', 'hold_notes')) {
                $columns[] = 'hold_notes';
            }
            if (Schema::hasColumn('orders', 'is_unresolved_hold')) {
                $columns[] = 'is_unresolved_hold';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('shifts', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('shifts', 'close_type')) {
                $columns[] = 'close_type';
            }
            if (Schema::hasColumn('shifts', 'close_reason')) {
                $columns[] = 'close_reason';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
