<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FR-POS-02, FR-POS-03, FR-POS-04, FR-POS-05, FR-POS-06, FR-POS-07, FR-POS-10, FSD-06, FSD-08, FSD-10
 */
class Order extends Model
{
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'uuid',
        'order_no',
        'branch_id',
        'device_id',
        'shift_id',
        'user_id',
        'customer_id',
        'order_type_id',
        'table_id',
        'status',
        'business_date',
        'subtotal',
        'promo_discount_amount',
        'voucher_discount_amount',
        'loyalty_discount_amount',
        'manual_discount_amount',
        'manual_discount_reason',
        'manual_discount_requires_pin',
        'service_charge_amount',
        'tax_amount',
        'rounding_amount',
        'total_amount',
        'paid_amount',
        'change_amount',
        'cost_amount',
        'client_created_at',
        'synced_at',
        'held_at',
        'hold_reference',
        'hold_notes',
        'held_by',
        'is_unresolved_hold',
        'clock_skew_flag',
        'sync_status',
        'void_reason',
        'voided_by',
        'voided_at',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'promo_discount_amount' => 'integer',
        'voucher_discount_amount' => 'integer',
        'loyalty_discount_amount' => 'integer',
        'manual_discount_amount' => 'integer',
        'service_charge_amount' => 'integer',
        'tax_amount' => 'integer',
        'rounding_amount' => 'integer',
        'total_amount' => 'integer',
        'paid_amount' => 'integer',
        'change_amount' => 'integer',
        'cost_amount' => 'integer',
        'manual_discount_requires_pin' => 'boolean',
        'clock_skew_flag' => 'boolean',
        'client_created_at' => 'datetime',
        'synced_at' => 'datetime',
        'held_at' => 'datetime',
        'is_unresolved_hold' => 'boolean',
        'voided_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function orderType()
    {
        return $this->belongsTo(OrderType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function heldByUser()
    {
        return $this->belongsTo(User::class, 'held_by');
    }
}
