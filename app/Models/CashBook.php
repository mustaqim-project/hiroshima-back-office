<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashBook extends Model
{
    use BelongsToBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'type',
        'category',
        'amount',
        'transaction_date',
        'description',
        'receipt_photo_path',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'transaction_date' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getBranchNameAttribute(): string
    {
        return $this->branch?->name ?? 'Global (Kantor Pusat)';
    }

    public function getIsGlobalAttribute(): bool
    {
        return is_null($this->branch_id);
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getReceiptPhotoUrlAttribute(): ?string
    {
        return $this->receipt_photo_path ? asset('storage/' . $this->receipt_photo_path) : null;
    }
}
