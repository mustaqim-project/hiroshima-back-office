<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete(); // Nullable = Global (Pusat)
            $table->string('type', 10); // in (uang masuk), out (uang keluar)
            $table->string('category', 100);
            $table->unsignedBigInteger('amount'); // Nominal Rupiah
            $table->date('transaction_date');
            $table->text('description')->nullable(); // Wajib diisi bila category == 'Lainnya'
            $table->string('receipt_photo_path')->nullable(); // Upload bukti foto (opsional)
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes(); // Soft delete per user request

            $table->index(['branch_id', 'transaction_date']);
            $table->index(['type', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_books');
    }
};
