<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FR-PRICE-01, FR-PRICE-02, FR-PRICE-03, FSD-03
 */
class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'sellable_type',
        'sellable_id',
        'order_type_id',
        'branch_id',
        'price',
    ];

    protected $casts = [
        'price' => 'integer',
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
