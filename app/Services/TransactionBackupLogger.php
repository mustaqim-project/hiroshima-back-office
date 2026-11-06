<?php

namespace App\Services;

use App\Models\Order;
use App\Models\TransactionBackupLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class TransactionBackupLogger
{
    /**
     * Permanent transaction logging to both database and daily rotated JSONL disk files.
     */
    public static function record(
        array $payload,
        ?Order $order = null,
        string $status = 'completed',
        string $source = 'pos_sync',
        ?string $errorMessage = null
    ): ?TransactionBackupLog {
        $now = now('Asia/Jakarta');
        $dateStr = $now->format('Y-m-d');

        $uuid = $payload['uuid'] ?? ($order?->uuid ?? (string) Str::uuid());
        $orderNo = $payload['order_no'] ?? ($order?->order_no ?? 'TRX-' . strtoupper(substr(str_replace('-', '', $uuid), 0, 8)));
        $branchId = $payload['branch_id'] ?? ($order?->branch_id ?? null);
        $deviceId = $payload['device_id'] ?? ($order?->device_id ?? null);
        $userId = $payload['user_id'] ?? ($order?->user_id ?? auth()->id());
        $shiftId = $payload['shift_id'] ?? ($order?->shift_id ?? null);
        $tableId = $payload['table_id'] ?? ($order?->table_id ?? null);
        $tableName = $payload['table_name'] ?? ($payload['hold_reference'] ?? $payload['customer_name'] ?? null);
        $customerName = $payload['customer_name'] ?? ($payload['hold_reference'] ?? null);
        $totalAmount = (float) ($payload['total_amount'] ?? ($order?->total_amount ?? 0));
        $paidAmount = (float) ($payload['paid_amount'] ?? ($order?->paid_amount ?? $totalAmount));
        $itemsCount = count($payload['items'] ?? ($order?->items ?? []));

        $clientCreatedAt = null;
        if (!empty($payload['client_created_at'])) {
            try {
                $clientCreatedAt = Carbon::parse($payload['client_created_at']);
            } catch (\Throwable $e) {
                $clientCreatedAt = $now;
            }
        } elseif ($order?->client_created_at) {
            $clientCreatedAt = $order->client_created_at;
        } else {
            $clientCreatedAt = $now;
        }

        $ip = request()?->ip() ?? '127.0.0.1';
        $userAgent = request()?->userAgent() ?? 'Hiroshima-POS-Mobile';

        // 1. Dual-redundancy: append to daily rotated JSONL file in storage/logs/transactions/
        try {
            $dir = storage_path('logs/transactions');
            File::ensureDirectoryExists($dir);
            $filePath = $dir . DIRECTORY_SEPARATOR . "transactions-{$dateStr}.jsonl";

            $jsonLine = json_encode([
                'timestamp' => $now->format('Y-m-d H:i:s'),
                'order_uuid' => $uuid,
                'order_no' => $orderNo,
                'branch_id' => $branchId,
                'device_id' => $deviceId,
                'user_id' => $userId,
                'shift_id' => $shiftId,
                'table_name' => $tableName,
                'customer_name' => $customerName,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'items_count' => $itemsCount,
                'status' => $status,
                'source' => $source,
                'error_message' => $errorMessage,
                'payload' => $payload,
            ], JSON_UNESCAPED_UNICODE) . PHP_EOL;

            file_put_contents($filePath, $jsonLine, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            Log::warning("[TransactionBackupLogger] Disk JSONL backup write failed: " . $e->getMessage());
        }

        // 2. Dual-redundancy: persist into database table transaction_backup_logs
        try {
            return TransactionBackupLog::create([
                'order_id' => $order?->id,
                'order_uuid' => $uuid,
                'order_no' => $orderNo,
                'branch_id' => $branchId,
                'device_id' => $deviceId,
                'user_id' => $userId,
                'shift_id' => $shiftId,
                'table_id' => $tableId,
                'table_name' => $tableName,
                'customer_name' => $customerName,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'items_count' => $itemsCount,
                'status' => $status,
                'sync_status' => $order?->sync_status ?? $status,
                'source' => $source,
                'error_message' => $errorMessage,
                'raw_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'client_created_at' => $clientCreatedAt,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            Log::error("[TransactionBackupLogger] Database backup insertion failed: " . $e->getMessage(), [
                'uuid' => $uuid,
                'order_no' => $orderNo,
            ]);
            return null;
        }
    }
}

