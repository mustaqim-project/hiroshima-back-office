<?php

namespace App\Models;

use App\Models\Concerns\SerializesWibDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @req FR-MST-05, FR-MST-06
 */
class Unit extends Model
{
    use HasFactory, SoftDeletes, SerializesWibDates;

    protected $fillable = [
        'name',
        'symbol',
    ];
}
