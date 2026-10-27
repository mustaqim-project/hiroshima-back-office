<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FSD-18, RPT-24
 */
class RiskScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'period_month',
        'score',
        'total_transactions',
        'finding_count',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'total_transactions' => 'integer',
        'finding_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
