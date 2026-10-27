<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FR-POS-02, FR-POS-03, FSD-06
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'order_type_name_snapshot',
        'quantity',
        'unit_price',
        'cost_per_unit',
        'subtotal',
        'notes',
        'kitchen_printed_at',
    ];

    protected $casts = [
        'unit_price' => 'integer',
        'cost_per_unit' => 'integer',
        'subtotal' => 'integer',
        'quantity' => 'integer',
        'kitchen_printed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function modifiers()
    {
        return $this->hasMany(OrderItemModifier::class);
    }
}
