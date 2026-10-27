<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Payment::with('paymentMethod')->whereNotNull('payment_method_id')->chunkById(200, function ($payments) {
            foreach ($payments as $p) {
                if ($p->paymentMethod && $p->payment_method_name_snapshot !== $p->paymentMethod->name) {
                    $p->update(['payment_method_name_snapshot' => $p->paymentMethod->name]);
                }
            }
        });
    }

    public function down(): void
    {
        // No-op
    }
};
