<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @req FR-MST-03, FR-MST-04
 */
class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_cash',
        'is_active',
    ];

    protected $casts = [
        'is_cash' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function branchPaymentMethods()
    {
        return $this->hasMany(BranchPaymentMethod::class);
    }
}
