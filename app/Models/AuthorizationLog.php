<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @req FSD-07, FSD-08, FSD-12, FSD-18
 */
class AuthorizationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'device_id',
        'user_id',
        'type',
        'success',
        'reason',
        'ip',
        'created_at',
    ];

    protected $casts = [
        'success' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
