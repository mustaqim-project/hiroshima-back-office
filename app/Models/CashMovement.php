<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FR-POS-13, FSD-09
 */
class CashMovement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'shift_id',
        'branch_id',
        'user_id',
        'type',
        'amount',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'created_at' => 'datetime',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
