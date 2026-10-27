<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FSD-18, RPT-24
 */
class FraudAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'fraud_rule_code',
        'branch_id',
        'user_id',
        'order_id',
        'shift_id',
        'severity',
        'evidence',
        'status',
        'detected_at',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
        'dedup_key',
    ];

    protected $casts = [
        'evidence' => 'array',
        'detected_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewedByUser()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
