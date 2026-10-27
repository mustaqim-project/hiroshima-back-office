<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @req FR-MENU-02, FSD-03
 */
class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'cost_method',
        'manual_cost',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'manual_cost' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productPrices()
    {
        return $this->morphMany(ProductPrice::class, 'sellable');
    }
}
