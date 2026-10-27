<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @req FR-MENU-03, FSD-03
 */
class Modifier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'modifier_group_id',
        'name',
        'price',
        'cost',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'cost' => 'integer',
        'is_active' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }

    public function productPrices()
    {
        return $this->morphMany(ProductPrice::class, 'sellable');
    }
}
