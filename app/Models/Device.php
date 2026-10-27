<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @req FR-DEV-01, FR-DEV-02, FR-DEV-03
 */
class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'device_code',
        'device_name',
        'device_token_hash',
        'app_version',
        'last_sync_at',
        'is_active',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
