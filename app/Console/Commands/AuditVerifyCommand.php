<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
use Illuminate\Console\Command;

/**
 * @req FSD-18, RPT-23
 */
class AuditVerifyCommand extends Command
{
    protected $signature = 'audit:verify';
    protected $description = 'Verifikasi keutuhan rantai hash SHA-256 pada seluruh riwayat audit log';

    public function handle(AuditLogger $logger): int
    {
        $this->info('Memulai verifikasi integritas audit log...');

        $result = $logger->verifyIntegrity();

        if ($result['valid']) {
            $this->info("✅ Sukses: Seluruh {$result['verified_count']} log terverifikasi utuh tanpa anomali.");
            return 0;
        }

        $this->error("❌ PERINGATAN: Ditemukan manipulasi atau kerusakan rantai hash pada audit log!");
        foreach ($result['corrupted'] as $c) {
            $this->line("Log ID #{$c['id']}: {$c['error']}");
        }

        return 1;
    }
}
