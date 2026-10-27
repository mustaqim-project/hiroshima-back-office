<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * @req FSD-18, RPT-23
 */
final class AuditLogger
{
    public function record(string $action, ?Model $subject, array $before = [], array $after = [], array $meta = []): AuditLog
    {
        return DB::transaction(function () use ($action, $subject, $before, $after, $meta) {
            $prev = AuditLog::query()->lockForUpdate()->latest('id')->value('hash') ?? str_repeat('0', 64);

            $payload = [
                'action' => $action,
                'user_id' => $meta['user_id'] ?? auth()->id(),
                'branch_id' => $meta['branch_id'] ?? (auth()->check() ? auth()->user()->branch_id : null),
                'device_id' => $meta['device_id'] ?? null,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'before' => $this->scrub($before),
                'after' => $this->scrub($after),
                'occurred_at' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'ip' => request()->ip() ?? '127.0.0.1',
            ];

            $hash = hash('sha256', $prev . json_encode($payload, JSON_UNESCAPED_UNICODE));

            return AuditLog::create([
                ...$payload,
                'prev_hash' => $prev,
                'hash' => $hash,
                'created_at' => now('Asia/Jakarta'),
            ]);
        });
    }

    public function verifyIntegrity(): array
    {
        $logs = AuditLog::orderBy('id', 'asc')->get();
        $expectedPrev = str_repeat('0', 64);
        $corrupted = [];

        foreach ($logs as $log) {
            if ($log->prev_hash !== $expectedPrev) {
                $corrupted[] = [
                    'id' => $log->id,
                    'error' => 'Prev hash mismatch',
                    'found' => $log->prev_hash,
                    'expected' => $expectedPrev,
                ];
                break;
            }

            $payload = [
                'action' => $log->action,
                'user_id' => $log->user_id,
                'branch_id' => $log->branch_id,
                'device_id' => $log->device_id,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'before' => $log->before,
                'after' => $log->after,
                'occurred_at' => $log->occurred_at->format('Y-m-d H:i:s'),
                'ip' => $log->ip,
            ];

            $recomputedHash = hash('sha256', $expectedPrev . json_encode($payload, JSON_UNESCAPED_UNICODE));
            if ($log->hash !== $recomputedHash) {
                $corrupted[] = [
                    'id' => $log->id,
                    'error' => 'Current hash mismatch (data altered)',
                    'found' => $log->hash,
                    'expected' => $recomputedHash,
                ];
                break;
            }

            $expectedPrev = $log->hash;
        }

        return [
            'valid' => empty($corrupted),
            'verified_count' => $logs->count(),
            'corrupted' => $corrupted,
        ];
    }

    private function scrub(array $data): array
    {
        // Confidential fields like PIN and passwords must never be stored in plain text audit logs
        return Arr::except($data, ['pin', 'pin_void', 'pin_discount', 'pin_void_hash', 'pin_discount_hash', 'pin_hash', 'password', 'token']);
    }
}
