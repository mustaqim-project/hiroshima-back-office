<?php

namespace App\Models;

use App\Models\Concerns\SerializesWibDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionBackupLog extends Model
{
    use HasFactory, SerializesWibDates;

    protected $table = 'transaction_backup_logs';

    protected $fillable = [
        'order_id',
        'order_uuid',
        'order_no',
        'branch_id',
        'device_id',
        'user_id',
        'shift_id',
        'table_id',
        'table_name',
        'customer_name',
        'total_amount',
        'paid_amount',
        'items_count',
        'status',
        'sync_status',
        'source',
        'error_message',
        'raw_payload',
        'client_created_at',
        'ip_address',
        'user_agent',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'items_count' => 'integer',
        'client_created_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Decode the raw payload into a PHP associative array.
     */
    public function getParsedPayload(): array
    {
        if (empty($this->raw_payload)) {
            return [];
        }

        $decoded = json_decode($this->raw_payload, true);
        return is_array($decoded) ? $decoded : [];
    }
}

