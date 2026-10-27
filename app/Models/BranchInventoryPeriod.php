<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FR-INV-00, FR-INV-12, FR-INV-13, FSD-05
 */
class BranchInventoryPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'started_at',
        'ended_at',
        'activated_at',
        'activated_by',
        'deactivated_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function activator()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function deactivator()
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }
}
