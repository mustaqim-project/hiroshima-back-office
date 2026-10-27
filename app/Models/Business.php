<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @req FR-BR-01, FR-BR-02, FR-BR-03, FSD-02
 */
class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'email',
        'address',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
}
