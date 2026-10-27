<?php

namespace App\Console\Commands;

use App\Services\Fraud\FraudScanner;
use Illuminate\Console\Command;

/**
 * @req FSD-07, FSD-08, FSD-09, FSD-10, FSD-11, FSD-12, FSD-18, RPT-24
 */
class FraudScanCommand extends Command
{
    protected $signature = 'fraud:scan {--hours=24 : Window jam pemeriksaan ke belakang}';
    protected $description = 'Pindai anomali transaksi dan potensi fraud operasional kasir (FRD-01 s.d. FRD-20)';

    public function handle(FraudScanner $scanner): int
    {
        $hours = (int) $this->option('hours');
        $from = now('Asia/Jakarta')->subHours($hours);
        $to = now('Asia/Jakarta');

        $this->info("Menjalankan pemindaian anomali fraud ({$from->toDateTimeString()} s.d. {$to->toDateTimeString()})...");

        $result = $scanner->scan($from, $to);

        $this->info("✅ Pemindaian selesai: {$result['alerts_created']} peringatan anomali baru diterbitkan.");

        return 0;
    }
}
