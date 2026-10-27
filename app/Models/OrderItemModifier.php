<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FR-POS-02, FR-POS-03, FSD-06
 */
class OrderItemModifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'modifier_id',
        'modifier_name',
        'unit_price',
        'cost_per_unit',
        'quantity',
    ];

    protected $casts = [
        'unit_price' => 'integer',
        'cost_per_unit' => 'integer',
        'quantity' => 'integer',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function modifier()
    {
        return $this->belongsTo(Modifier::class);
    }
}
