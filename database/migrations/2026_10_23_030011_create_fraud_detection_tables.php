<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @req FSD-07, FSD-08, FSD-09, FSD-10, FSD-11, FSD-12, FSD-18, RPT-24, RPT-25
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // FRD-01 to FRD-20
            $table->string('name');
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->json('params')->nullable(); // Default threshold parameters
            $table->boolean('is_active')->default(true);
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete(); // Nullable for global rule
            $table->timestamps();
        });

        Schema::create('fraud_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('fraud_rule_code', 20);
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->string('severity');
            $table->json('evidence'); // Concrete data evidence
            $table->string('status')->default('new'); // new, in_review, confirmed, dismissed
            $table->dateTime('detected_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('dedup_key')->unique(); // rule_code:subject_id:window to prevent alert flooding
            $table->timestamps();

            $table->index(['branch_id', 'status', 'detected_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('fraud_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_no', 30)->unique();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open'); // open, investigating, resolved, closed
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('period_month', 7); // YYYY-MM
            $table->decimal('score', 6, 2)->default(0.00); // Normalized risk score
            $table->unsignedInteger('total_transactions')->default(0);
            $table->unsignedInteger('finding_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'branch_id', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_scores');
        Schema::dropIfExists('fraud_cases');
        Schema::dropIfExists('fraud_alerts');
        Schema::dropIfExists('fraud_rules');
    }
};
