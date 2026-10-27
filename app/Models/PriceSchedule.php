<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FR-PRICE-06, FSD-03
 */
class PriceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'sellable_type',
        'sellable_id',
        'order_type_id',
        'branch_id',
        'scheduled_price',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'scheduled_price' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function orderType()
    {
        return $this->belongsTo(OrderType::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
