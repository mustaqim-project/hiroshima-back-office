<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Command to repair legacy payments where tendered amount was recorded instead of net sales,
 * and recalculate expected cash and cash differences for affected shifts (e.g. Shift #10).
 * 
 * @req FR-POS-06, FR-POS-07, FSD-06, FSD-09, RPT-09
 */
class RepairPaymentsCommand extends Command
{
    protected $signature = 'pos:repair-payments 
                            {--shift= : Hanya perbaiki ID shift tertentu (contoh: --shift=10)}
                            {--dry-run : Hanya tampilkan data yang perlu diperbaiki tanpa menyimpan perubahan}';

    protected $aliases = ['shift:repair-payments', 'reconciliation:repair'];

    protected $description = 'Memperbaiki transaksi lama yang mencatat uang diterima tanpa memotong kembalian, serta merekonsiliasi ulang kas shift';

    public function handle(ShiftService $shiftService): int
    {
        $shiftFilter = $this->option('shift');
        $dryRun = $this->option('dry-run');

        $this->info('===============================================================');
        $this->info('  PERBAIKAN TRANSAKSI POS & REKONSILIASI KAS LACI KASIR       ');
        $this->info('===============================================================');

        if ($dryRun) {
            $this->warn('MODE SIMULASI (DRY-RUN): Tidak ada perubahan yang disimpan ke database.');
        }

        $query = Payment::with(['order', 'shift']);

        if ($shiftFilter) {
            $query->where('shift_id', (int) $shiftFilter);
            $this->line("Menyaring untuk Shift ID: #{$shiftFilter}");
        }

        $payments = $query->get();

        $repairedCount = 0;
        $affectedShiftIds = [];
        $rows = [];

        foreach ($payments as $payment) {
            $order = $payment->order;
            if (! $order) {
                continue;
            }

            $orderTotal = (int) $order->total_amount;
            $orderChange = (int) $order->change_amount;
            $orderPaid = (int) $order->paid_amount;
            $currentAmount = (int) $payment->amount;

            $needsFix = false;

            // Kasus bermasalah: Ada kembalian, tapi payment amount mencatat nilai kotor/uang diterima (>= total belanja)
            if ($orderChange > 0 && ($currentAmount === $orderPaid || $currentAmount > $orderTotal || (int)$payment->change_amount === 0)) {
                $needsFix = true;
                $newNetAmount = $orderTotal;
                $newTendered = $orderPaid > 0 ? $orderPaid : ($currentAmount > $orderTotal ? $currentAmount : $orderTotal + $orderChange);
                $newChange = $orderChange;
            } elseif ($payment->tendered_amount === null) {
                // Lengkapi field tendered_amount jika null
                $needsFix = true;
                $newNetAmount = $currentAmount;
                $newTendered = $currentAmount;
                $newChange = 0;
            }

            if ($needsFix) {
                $repairedCount++;
                if ($payment->shift_id) {
                    $affectedShiftIds[$payment->shift_id] = true;
                }

                $rows[] = [
                    'Shift' => '#' . ($payment->shift_id ?? '-'),
                    'Order No' => $order->order_no,
                    'Belanja Bersih' => 'Rp ' . number_format($orderTotal, 0, ',', '.'),
                    'Uang Diterima' => 'Rp ' . number_format($newTendered, 0, ',', '.'),
                    'Kembalian' => '- Rp ' . number_format($newChange, 0, ',', '.'),
                    'Nominal Tercatat Lama' => 'Rp ' . number_format($currentAmount, 0, ',', '.'),
                    'Nominal Bersih Baru' => 'Rp ' . number_format($newNetAmount, 0, ',', '.'),
                ];

                if (! $dryRun) {
                    $payment->update([
                        'amount' => $newNetAmount,
                        'tendered_amount' => $newTendered,
                        'change_amount' => $newChange,
                    ]);
                }
            }
        }

        if (empty($rows)) {
            $this->info('Semua data pembayaran sudah akurat dan tidak ditemukan transaksi yang salah catat.');
        } else {
            $this->table(
                ['ID Shift', 'No. Order', 'Belanja Bersih', 'Uang Diterima', 'Kembalian', 'Pencatatan Lama', 'Pencatatan Bersih Baru'],
                $rows
            );
            $this->info("Total {$repairedCount} record pembayaran yang " . ($dryRun ? 'akan diperbaiki' : 'berhasil diperbaiki') . ".");
        }

        // Rekonsiliasi ulang nilai kas yang diharapkan (expected cash) pada shift yang terdampak
        if (! empty($affectedShiftIds)) {
            $this->line('');
            $this->info('Memperbarui Rekonsiliasi Kas Laci (Expected Cash & Cash Difference) pada Shift:');

            $shiftRows = [];
            foreach (array_keys($affectedShiftIds) as $sId) {
                $shift = Shift::find($sId);
                if (! $shift) {
                    continue;
                }

                $oldExpected = (int) $shift->expected_cash;
                $oldDiff = (int) $shift->cash_difference;
                $newExpected = $shiftService->calculateExpectedCash($shift);
                $newDiff = $shift->actual_cash !== null ? ((int) $shift->actual_cash - $newExpected) : null;

                $shiftRows[] = [
                    'Shift' => '#' . $shift->id,
                    'Kasir' => $shift->user?->name ?? 'Kasir',
                    'Status' => strtoupper($shift->status),
                    'Expected Lama' => 'Rp ' . number_format($oldExpected, 0, ',', '.'),
                    'Expected Baru' => 'Rp ' . number_format($newExpected, 0, ',', '.'),
                    'Kas Fisik Aktual' => $shift->actual_cash !== null ? 'Rp ' . number_format($shift->actual_cash, 0, ',', '.') : '-',
                    'Selisih Baru' => $newDiff !== null ? ($newDiff < 0 ? '-' : '+') . ' Rp ' . number_format(abs($newDiff), 0, ',', '.') : '-',
                ];

                if (! $dryRun) {
                    $shift->update([
                        'expected_cash' => $newExpected,
                        'cash_difference' => $newDiff,
                    ]);
                }
            }

            $this->table(
                ['ID Shift', 'Kasir', 'Status', 'Expected Lama', 'Expected Baru', 'Kas Fisik Aktual', 'Selisih Baru'],
                $shiftRows
            );

            if (! $dryRun) {
                $this->info('Semua shift yang terdampak berhasil direkonsiliasi ulang!');
            }
        }

        $this->line('');
        $this->info('Perbaikan selesai dengan sukses.');

        return 0;
    }
}
