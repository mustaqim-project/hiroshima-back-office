<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @req FR-MENU-01, FR-MENU-02, FR-PRICE-01, FSD-03
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'barcode',
        'description',
        'image_path',
        'cost_method',
        'manual_cost',
        'has_variants',
        'is_active',
    ];

    protected $casts = [
        'has_variants' => 'boolean',
        'is_active' => 'boolean',
        'manual_cost' => 'integer',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }
        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }
        return url($this->image_path);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function modifierGroups()
    {
        return $this->belongsToMany(ModifierGroup::class, 'product_modifier_groups');
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class, 'sellable_id')->where('sellable_type', 'Product');
    }
}
