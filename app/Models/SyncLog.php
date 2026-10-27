<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FR-OFF-01, FR-OFF-02, FSD-10
 */
class SyncLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'device_id',
        'type',
        'batch_size',
        'success_count',
        'conflict_count',
        'duration_ms',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
