<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @req FR-PROMO-01, FR-PROMO-02, FR-PROMO-03, FR-PROMO-04, FR-PROMO-05, FSD-06
 */
class Promo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'value',
        'min_purchase',
        'max_discount',
        'is_stackable',
        'priority',
        'starts_at',
        'ends_at',
        'start_time',
        'end_time',
        'days_of_week',
        'quota',
        'used_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_purchase' => 'integer',
            'max_discount' => 'integer',
            'is_stackable' => 'boolean',
            'priority' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'days_of_week' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'promo_branches');
    }

    public function orderTypes()
    {
        return $this->belongsToMany(OrderType::class, 'promo_order_types');
    }
}
