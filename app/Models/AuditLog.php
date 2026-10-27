<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * @req FSD-18, RPT-23
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action',
        'user_id',
        'branch_id',
        'device_id',
        'subject_type',
        'subject_id',
        'before',
        'after',
        'occurred_at',
        'ip',
        'prev_hash',
        'hash',
        'created_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('Audit logs are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('Audit logs are immutable and cannot be deleted.');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
