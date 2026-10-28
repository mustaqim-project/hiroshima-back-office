<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @req FR-POS-01, FR-WEB-07, FSD-09
 * Command untuk mengosongkan riwayat transaksi & finance dummy sebelum go-live produksi.
 * Menjaga seluruh Data Master (Cabang, Menu/Produk, Kategori, Order Type, Metode Bayar, Akun User).
 */
class ResetTransactionsCommand extends Command
{
    protected $signature = 'hiroshima:reset-transactions {--force : Eksekusi langsung tanpa prompt konfirmasi}';
    protected $description = 'Bersihkan riwayat transaksi POS dan finance untuk go-live produksi (Data Master aman)';

    public function handle(): int
    {
        $this->warn('=============================================================');
        $this->warn('  PERSIAPAN PRODUCTION GO-LIVE: PEMBERSIHAN DATA TRANSAKSI   ');
        $this->warn('=============================================================');
        $this->line('Perintah ini HANYA menghapus riwayat transaksi dummy & finance:');
        $this->line(' - orders, order_items, order_item_modifiers');
        $this->line(' - payments, refunds');
        $this->line(' - shifts, cash_movements');
        $this->line(' - cash_books');
        $this->line(' - fraud_alerts, risk_scores, sync_logs, authorization_logs, notifications, audit_logs');
        $this->newLine();
        $this->info('Data berikut TETAP AMAN dan TIDAK AKAN DISENTUH:');
        $this->info(' [v] Akun Pengguna / User / Kasir / Role & Permission');
        $this->info(' [v] Cabang & Bisnis (Hiroshima Cikini, Tebet, Cilandak)');
        $this->info(' [v] Kategori, Produk / Menu Makanan & Minuman');
        $this->info(' [v] Varian & Modifier Menu');
        $this->info(' [v] Tipe Pesanan (Dine In, Take Away, Delivery)');
        $this->info(' [v] Metode Pembayaran (Tunai, QRIS, Transfer, EDC, dll)');
        $this->info(' [v] Bahan Baku, Resep, Meja, dan Pengaturan Sistem');
        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('Apakah Anda yakin ingin mengosongkan seluruh riwayat transaksi ini?', false)) {
                $this->comment('Pembersihan data dibatalkan.');
                return 0;
            }
        }

        $tables = [
            'order_item_modifiers',
            'order_items',
            'payments',
            'refunds',
            'orders',
            'cash_movements',
            'shifts',
            'cash_books',
            'fraud_alerts',
            'risk_scores',
            'sync_logs',
            'authorization_logs',
            'audit_logs',
            'notifications',
        ];

        $driver = DB::getDriverName();
        $this->line("Menghubungkan ke database [driver: {$driver}]...");

        try {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF;');
            }

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $countBefore = DB::table($table)->count();
                    DB::table($table)->truncate();
                    $this->line(" - Tabel [{$table}]: {$countBefore} baris berhasil dikosongkan.");

                    if ($driver === 'mysql') {
                        DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1;");
                    }
                }
            }

            if ($driver === 'sqlite') {
                DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('" . implode("','", $tables) . "');");
            }

            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }

            $this->newLine();
            $this->info('=============================================================');
            $this->info('  SUKSES! DATABASE SIAP DIGUNAKAN UNTUK PENJUALAN REAL POS   ');
            $this->info('=============================================================');
            return 0;
        } catch (\Throwable $e) {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }

            $this->error("Gagal melakukan pembersihan: " . $e->getMessage());
            return 1;
        }
    }
}
