<?php

/**
 * @req FSD-07, FSD-08, FSD-09, FSD-10, FSD-11, FSD-12, FSD-18, RPT-24, RPT-25
 */

return [
    'enabled' => true,
    'scan_interval_minutes' => 15,
    'risk_weights' => [
        'low' => 1,
        'medium' => 3,
        'high' => 7,
        'critical' => 15,
    ],
    'rules' => [
        'FRD-01' => [
            'name' => 'Void Beruntun',
            'severity' => 'medium',
            'params' => ['max_voids_per_shift' => 3],
        ],
        'FRD-02' => [
            'name' => 'Void Setelah Pembayaran Tunai',
            'severity' => 'high',
            'params' => ['max_minutes_after_cash_payment' => 15],
        ],
        'FRD-03' => [
            'name' => 'Diskon Tepat di Bawah Ambang',
            'severity' => 'high',
            'params' => ['min_count_per_shift' => 5, 'lower_bound_percent' => 90],
        ],
        'FRD-04' => [
            'name' => 'Konsentrasi Diskon Kasir',
            'severity' => 'medium',
            'params' => ['multiplier_above_median' => 2.0],
        ],
        'FRD-05' => [
            'name' => 'Selisih Kas Berulang',
            'severity' => 'high',
            'params' => ['min_shortage_shifts' => 3, 'window_shifts' => 5],
        ],
        'FRD-06' => [
            'name' => 'Auto-Close Berulang',
            'severity' => 'low',
            'params' => ['min_auto_closes' => 2, 'window_days' => 14],
        ],
        'FRD-07' => [
            'name' => 'Percobaan PIN Gagal Berkelompok',
            'severity' => 'medium',
            'params' => ['min_failed_attempts' => 3],
        ],
        'FRD-08' => [
            'name' => 'PIN Dipakai di Luar Jam Ramai',
            'severity' => 'medium',
            'params' => ['flag_outside_business_hours' => true],
        ],
        'FRD-09' => [
            'name' => 'Cetak Ulang Struk Berlebih',
            'severity' => 'low',
            'params' => ['max_reprints_per_shift' => 3],
        ],
        'FRD-10' => [
            'name' => 'Transaksi Offline Mencurigakan',
            'severity' => 'medium',
            'params' => ['max_clock_skew_minutes' => 5],
        ],
        'FRD-11' => [
            'name' => 'Backdating Transaksi',
            'severity' => 'high',
            'params' => ['max_delay_hours' => 24],
        ],
        'FRD-12' => [
            'name' => 'Pemakaian Voucher Berlebih',
            'severity' => 'medium',
            'params' => ['flag_quota_exceeded' => true],
        ],
        'FRD-13' => [
            'name' => 'Penyesuaian Poin Manual Janggal',
            'severity' => 'medium',
            'params' => ['max_manual_points' => 1000],
        ],
        'FRD-14' => [
            'name' => 'Opname / Waste Tak Wajar',
            'severity' => 'high',
            'params' => ['difference_percentage_threshold' => 15.0],
        ],
        'FRD-15' => [
            'name' => 'Fluktuasi Harga Cepat',
            'severity' => 'medium',
            'params' => ['window_hours' => 24],
        ],
        'FRD-16' => [
            'name' => 'Buka/Tutup Modul Stok Berulang',
            'severity' => 'medium',
            'params' => ['max_toggles_per_month' => 2],
        ],
        'FRD-17' => [
            'name' => 'Pola Diskon Angka Bulat',
            'severity' => 'low',
            'params' => ['flag_round_numbers' => true],
        ],
        'FRD-18' => [
            'name' => 'Kas Keluar Tanpa Kategori Jelas',
            'severity' => 'medium',
            'params' => ['amount_threshold' => 200000],
        ],
        'FRD-19' => [
            'name' => 'Upaya Akses Data Lintas Cabang',
            'severity' => 'high',
            'params' => ['min_denied_attempts' => 3],
        ],
        'FRD-20' => [
            'name' => 'Kerusakan Integritas Audit Log',
            'severity' => 'critical',
            'params' => ['alert_on_hash_mismatch' => true],
        ],
    ]
];
