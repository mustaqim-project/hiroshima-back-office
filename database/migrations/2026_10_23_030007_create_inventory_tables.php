<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @req FR-INV-00, FR-INV-01, FR-INV-02, FR-INV-03, FR-INV-04, FR-INV-05, FR-INV-06, FR-INV-07, FR-INV-08, FR-INV-09, FR-INV-10, FR-INV-11, FR-INV-12, FR-INV-13, FSD-05
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('current_stock', 15, 4)->default(0);
            $table->dateTime('last_opname_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'ingredient_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->string('type'); // purchase, transfer_in, transfer_out, sale_deduction, opname_adjustment, waste
            $table->decimal('quantity', 15, 4); // positive for in, negative for out
            $table->unsignedBigInteger('cost_per_unit')->default(0);
            $table->string('reference_type')->nullable(); // Order, GoodsReceipt, StockTransfer, StockOpname, Waste
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['branch_id', 'ingredient_id', 'created_at']);
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('po_no', 30)->unique();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->string('status')->default('submitted'); // draft, submitted, partial, received, cancelled
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('quantity_ordered', 15, 4);
            $table->decimal('quantity_received', 15, 4)->default(0);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('receipt_no', 30)->unique();
            $table->date('received_date');
            $table->string('delivery_note_no')->nullable();
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('quantity_received', 15, 4);
            $table->unsignedBigInteger('unit_price'); // Purchase price used to recalculate Moving Average
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('target_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('transfer_no', 30)->unique();
            $table->string('status')->default('pending'); // pending, in_transit, received, cancelled
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('quantity_sent', 15, 4);
            $table->decimal('quantity_received', 15, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('opname_no', 30)->unique();
            $table->date('opname_date');
            $table->boolean('is_initial')->default(false); // Initial opname for module activation
            $table->string('status')->default('draft'); // draft, submitted, approved, rejected
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('system_stock', 15, 4);
            $table->decimal('physical_stock', 15, 4);
            $table->decimal('difference', 15, 4);
            $table->unsignedBigInteger('unit_cost')->default(0);
            $table->timestamps();
        });

        Schema::create('wastes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->unsignedBigInteger('cost')->default(0);
            $table->string('reason'); // Expired, Spilled, Bad Quality
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wastes');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('ingredient_stocks');
    }
};
