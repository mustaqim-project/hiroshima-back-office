<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FR-POS-10, FR-POS-11, FSD-08
 */
class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_id',
        'shift_id',
        'amount',
        'reason',
        'authorized_by_pin',
        'authorized_by_user_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'authorized_by_pin' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function authorizedByUser()
    {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }
}
