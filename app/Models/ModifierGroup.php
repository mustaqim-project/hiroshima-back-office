<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @req FR-MENU-03, FSD-03
 */
class ModifierGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'selection_type',
        'min_select',
        'max_select',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'min_select' => 'integer',
        'max_select' => 'integer',
    ];

    public function modifiers()
    {
        return $this->hasMany(Modifier::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_modifier_groups');
    }
}
