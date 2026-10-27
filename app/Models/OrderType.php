<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @req FR-MST-02, FSD-03
 */
class OrderType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'is_default',
        'requires_table',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'requires_table' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function default(): ?self
    {
        return static::where('is_default', true)->first();
    }
}
