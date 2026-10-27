<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @req FR-RCP-01, FR-HPP-01, FSD-04
 */
class Ingredient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'unit_id',
        'standard_cost',
        'minimum_stock',
        'is_active',
    ];

    protected $casts = [
        'standard_cost' => 'integer',
        'minimum_stock' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
