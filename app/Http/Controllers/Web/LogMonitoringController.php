<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\TransactionBackupLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogMonitoringController extends Controller
{
    /**
     * Display the System Error Logs and Diagnostic Console (Monolog).
     */
    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $logDir = storage_path('logs');
        $files = $this->getAvailableLogFiles($logDir);
        $filesMetadata = $this->formatLogFilesMetadata($files);

        // Determine active log file safely
        $selectedFile = basename((string) $request->input('file', $files[0] ?? ('laravel-' . date('Y-m-d') . '.log')));
        if (!in_array($selectedFile, $files, true)) {
            $selectedFile = $files[0] ?? ('laravel-' . date('Y-m-d') . '.log');
        }

        $activeFilePath = $logDir . DIRECTORY_SEPARATOR . $selectedFile;
        $parsedLogs = $this->parseLogFile($activeFilePath);

        // Pre-filter statistics for Bento KPI cards
        $stats = [
            'total' => count($parsedLogs),
            'errors' => count(array_filter($parsedLogs, fn(array $l): bool => in_array($l['level'], ['ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT'], true))),
            'warnings' => count(array_filter($parsedLogs, fn(array $l): bool => $l['level'] === 'WARNING')),
            'info' => count(array_filter($parsedLogs, fn(array $l): bool => in_array($l['level'], ['INFO', 'NOTICE', 'DEBUG'], true))),
        ];

        // Apply Level Filter
        if ($request->filled('level') && $request->input('level') !== 'all') {
            $filterLevel = strtoupper((string) $request->input('level'));
            if ($filterLevel === 'ERROR') {
                $parsedLogs = array_filter($parsedLogs, fn(array $l): bool => in_array($l['level'], ['ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT'], true));
            } else {
                $parsedLogs = array_filter($parsedLogs, fn(array $l): bool => $l['level'] === $filterLevel);
            }
        }

        // Apply Search Filter (message, exception class, stack trace, timestamp)
        if ($request->filled('search')) {
            $search = strtolower((string) $request->input('search'));
            $parsedLogs = array_filter($parsedLogs, static function (array $l) use ($search): bool {
                return str_contains(strtolower($l['message']), $search)
                    || str_contains(strtolower($l['stack']), $search)
                    || str_contains(strtolower($l['timestamp']), $search)
                    || str_contains(strtolower($l['level']), $search);
            });
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'file' => $selectedFile,
                'files' => $files,
                'files_metadata' => $filesMetadata,
                'stats' => $stats,
                'logs' => array_values($parsedLogs),
            ]);
        }

        $fileSize = File::exists($activeFilePath) ? round(File::size($activeFilePath) / 1024, 2) : 0.0;

        return view('backoffice.logs.index', [
            'logs' => array_values($parsedLogs),
            'files' => $files,
            'filesMetadata' => $filesMetadata,
            'selectedFile' => $selectedFile,
            'stats' => $stats,
            'fileSize' => $fileSize,
            'activeTab' => 'system',
        ]);
    }

    /**
     * Clear the content of the currently selected log file.
     */
    public function clear(Request $request): RedirectResponse
    {
        $logDir = storage_path('logs');
        $defaultFile = 'laravel-' . date('Y-m-d') . '.log';
        $selectedFile = basename((string) $request->input('file', $defaultFile));
        $filePath = $logDir . DIRECTORY_SEPARATOR . $selectedFile;

        if (File::exists($filePath)) {
            File::put($filePath, '');
            return back()->with('success', "Berkas log [{$selectedFile}] berhasil dikosongkan.");
        }

        return back()->with('error', "Berkas log [{$selectedFile}] tidak ditemukan.");
    }

    /**
     * Download the raw log file.
     */
    public function download(Request $request): BinaryFileResponse|RedirectResponse
    {
        $logDir = storage_path('logs');
        $defaultFile = 'laravel-' . date('Y-m-d') . '.log';
        $selectedFile = basename((string) $request->input('file', $defaultFile));
        $filePath = $logDir . DIRECTORY_SEPARATOR . $selectedFile;

        if (!File::exists($filePath)) {
            return back()->with('error', "Berkas log [{$selectedFile}] tidak ditemukan.");
        }

        return response()->download($filePath, $selectedFile, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * Display POS Transaction Backup Logs Monitoring View.
     */
    public function transactions(Request $request): View|JsonResponse
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        $query = TransactionBackupLog::with(['branch', 'user', 'device', 'shift'])
            ->latest('id');

        // Filter: Branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        // Filter: Status
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Filter: Date Range / Presets
        $datePreset = $request->input('preset', 'today');

        if ($datePreset === 'today') {
            $query->where('created_at', '>=', now('Asia/Jakarta')->subHours(24));
        } elseif ($datePreset === 'yesterday') {
            $query->where('created_at', '>=', now('Asia/Jakarta')->subHours(48))
                ->where('created_at', '<', now('Asia/Jakarta')->subHours(24));
        } elseif ($datePreset === '7days') {
            $query->where('created_at', '>=', now('Asia/Jakarta')->subDays(7));
        } elseif ($datePreset === 'custom' && $request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->input('end_date'));
            }
        }

        // Filter: Search (Order No, UUID, Customer, Table)
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                    ->orWhere('order_uuid', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('table_name', 'like', "%{$search}%");
            });
        }

        // Bento Stats
        $stats = [
            'total_all' => TransactionBackupLog::count(),
            'total_filtered' => (clone $query)->count(),
            'completed' => (clone $query)->whereIn('status', ['completed', 'accepted_flagged'])->count(),
            'flagged' => (clone $query)->where('status', 'accepted_flagged')->count(),
            'rejected' => (clone $query)->whereIn('status', ['rejected', 'failed'])->count(),
            'total_amount' => (float) (clone $query)->whereIn('status', ['completed', 'accepted_flagged'])->sum('total_amount'),
        ];

        $logs = $query->paginate(25)->withQueryString();

        // Check available daily JSONL files
        $jsonlDir = storage_path('logs/transactions');
        $jsonlFiles = [];
        if (File::isDirectory($jsonlDir)) {
            foreach (File::files($jsonlDir) as $f) {
                if ($f->getExtension() === 'jsonl') {
                    $jsonlFiles[] = $f->getFilename();
                }
            }
            rsort($jsonlFiles);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'logs' => $logs,
            ]);
        }

        return view('backoffice.logs.transactions', [
            'logs' => $logs,
            'stats' => $stats,
            'branches' => $branches,
            'jsonlFiles' => $jsonlFiles,
            'activeTab' => 'transactions',
            'datePreset' => $datePreset,
        ]);
    }

    /**
     * Get specific transaction raw payload for detail modal inspection.
     */
    public function transactionPayload(int $id): JsonResponse
    {
        $log = TransactionBackupLog::with(['branch', 'user', 'device', 'shift', 'order'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $log->id,
                'order_no' => $log->order_no,
                'order_uuid' => $log->order_uuid,
                'branch_name' => $log->branch?->name ?? 'Semua Cabang',
                'cashier_name' => $log->user?->name ?? 'Kasir',
                'status' => $log->status,
                'sync_status' => $log->sync_status,
                'source' => $log->source,
                'total_amount' => $log->total_amount,
                'paid_amount' => $log->paid_amount,
                'items_count' => $log->items_count,
                'error_message' => $log->error_message,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'client_created_at' => $log->client_created_at?->format('d/m/Y H:i:s') ?? '-',
                'created_at' => $log->created_at?->format('d/m/Y H:i:s') ?? '-',
                'raw_payload' => $log->raw_payload,
                'parsed_payload' => $log->getParsedPayload(),
            ],
        ]);
    }

    /**
     * Download daily transactions JSONL backup file.
     */
    public function downloadTransactions(Request $request): BinaryFileResponse|RedirectResponse
    {
        $dir = storage_path('logs/transactions');
        $defaultFile = 'transactions-' . date('Y-m-d') . '.jsonl';
        $selectedFile = basename((string) $request->input('file', $defaultFile));
        $filePath = $dir . DIRECTORY_SEPARATOR . $selectedFile;

        if (!File::exists($filePath)) {
            return back()->with('error', "Berkas backup transaksi [{$selectedFile}] belum tersedia.");
        }

        return response()->download($filePath, $selectedFile, [
            'Content-Type' => 'application/x-ndjson; charset=UTF-8',
        ]);
    }

    /**
     * List all log files in storage/logs sorted by date descending.
     *
     * @return list<string>
     */
    private function getAvailableLogFiles(string $dir): array
    {
        $todayFile = 'laravel-' . date('Y-m-d') . '.log';
        $logFiles = [];

        if (File::isDirectory($dir)) {
            $allFiles = File::files($dir);
            foreach ($allFiles as $file) {
                if ($file->getExtension() === 'log') {
                    $filename = $file->getFilename();
                    if (preg_match('/^laravel-(\d{4}-\d{2}-\d{2})\.log$/', $filename, $matches)) {
                        $logFiles[$filename] = strtotime($matches[1]) ?: $file->getMTime();
                    } else {
                        $logFiles[$filename] = $file->getMTime();
                    }
                }
            }
        }

        // Always ensure today's daily log file or laravel.log is listed
        if (!isset($logFiles[$todayFile]) && File::exists($dir . DIRECTORY_SEPARATOR . 'laravel.log')) {
            $logFiles['laravel.log'] = time();
        } elseif (!isset($logFiles[$todayFile])) {
            $logFiles[$todayFile] = strtotime(date('Y-m-d')) ?: time();
        }

        arsort($logFiles);

        return array_keys($logFiles);
    }

    /**
     * Format log filenames into humanized metadata for display in the UI.
     *
     * @param list<string> $files
     * @return array<string, array{name: string, label: string, is_today: bool, is_yesterday: bool}>
     */
    private function formatLogFilesMetadata(array $files): array
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $result = [];

        foreach ($files as $file) {
            $label = "📁 {$file}";
            $isToday = false;
            $isYesterday = false;

            if (preg_match('/^laravel-(\d{4}-\d{2}-\d{2})\.log$/', $file, $m)) {
                $dateStr = $m[1];
                $timestamp = strtotime($dateStr);
                $formattedDate = $timestamp !== false ? date('d M Y', $timestamp) : $dateStr;

                if ($dateStr === $today) {
                    $label = "📅 Hari Ini - {$formattedDate} ({$file})";
                    $isToday = true;
                } elseif ($dateStr === $yesterday) {
                    $label = "📅 Kemarin - {$formattedDate} ({$file})";
                    $isYesterday = true;
                } else {
                    $label = "📁 {$formattedDate} ({$file})";
                }
            } elseif ($file === 'laravel.log') {
                $label = '📁 laravel.log (Single Log Utama)';
            } elseif (str_starts_with($file, 'cron')) {
                $label = "⚙️ {$file} (Scheduler Log)";
            }

            $result[$file] = [
                'name' => $file,
                'label' => $label,
                'is_today' => $isToday,
                'is_yesterday' => $isYesterday,
            ];
        }

        return $result;
    }

    /**
     * Parse log file entries efficiently with streaming bounds to prevent OOM.
     *
     * @return list<array{id: int, timestamp: string, env: string, level: string, message: string, stack: string}>
     */
    private function parseLogFile(string $filePath): array
    {
        if (!File::exists($filePath) || File::size($filePath) === 0) {
            return [];
        }

        $logs = [];
        $currentLog = null;
        $logId = 1;

        try {
            $file = new \SplFileObject($filePath, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();

            // Limit to reading the last 3000 lines to guarantee optimal latency & prevent OOM
            $startLine = max(0, $totalLines - 3000);
            $file->rewind();
            if ($startLine > 0) {
                $file->seek($startLine);
            }

            while (!$file->eof()) {
                $line = (string) $file->fgets();
                $trimmedLine = rtrim($line, "\r\n");
                if (trim($trimmedLine) === '') {
                    continue;
                }

                // Standard Monolog Pattern: [YYYY-MM-DD HH:MM:SS] environment.LEVEL: message
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}(?:\.\d+)?)\]\s+([a-zA-Z0-9_\-]+)\.([A-Z]+):\s+(.*)$/', $trimmedLine, $matches)) {
                    if ($currentLog !== null) {
                        $logs[] = $currentLog;
                    }

                    $currentLog = [
                        'id' => $logId++,
                        'timestamp' => $matches[1],
                        'env' => $matches[2],
                        'level' => strtoupper($matches[3]),
                        'message' => trim($matches[4]),
                        'stack' => '',
                    ];
                } elseif ($currentLog !== null) {
                    $currentLog['stack'] .= $line;
                }
            }

            if ($currentLog !== null) {
                $logs[] = $currentLog;
            }
        } catch (\Throwable $e) {
            // Fallback gracefully on read error
        }

        // Return latest logs first
        return array_reverse($logs);
    }
}

