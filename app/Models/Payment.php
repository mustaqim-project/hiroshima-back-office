<?php

namespace App\Models;

use App\Models\Concerns\SerializesWibDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FR-POS-07, FSD-06
 */
class Payment extends Model
{
    use HasFactory, SerializesWibDates;

    protected $fillable = [
        'order_id',
        'order_bill_id',
        'shift_id',
        'payment_method_id',
        'payment_method_name_snapshot',
        'amount',
        'tendered_amount',
        'change_amount',
        'reference_no',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'tendered_amount' => 'integer',
        'change_amount' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
