<?php

namespace App\Console\Commands;

use App\Services\ShiftService;
use Illuminate\Console\Command;

/**
 * @req FR-POS-01, FR-POS-01a, FSD-09
 */
class AutoCloseShiftsCommand extends Command
{
    protected $signature = 'shifts:auto-close {--force}';
    protected $aliases = ['shift:auto-close'];
    protected $description = 'Menutup otomatis shift kasir yang telah beroperasi selama 15 jam';

    public function handle(ShiftService $shiftService): int
    {
        $closedCount = $shiftService->autoCloseExpiredShifts();

        if ($closedCount > 0) {
            $this->warn("Sebanyak {$closedCount} shift telah ditutup otomatis (usia >= 15 jam).");
        } else {
            $this->info("Tidak ada shift yang melebihi batas usia 15 jam.");
        }

        return 0;
    }
}
