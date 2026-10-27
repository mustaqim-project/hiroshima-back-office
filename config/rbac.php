<?php

/**
 * @req FR-AUTH-03, FR-AUTH-04, C2, FSD-01
 */

return [
    'roles' => [
        'owner' => 'Owner',
        'central_admin' => 'Admin Pusat',
        'branch_manager' => 'Kepala Cabang',
        'warehouse_staff' => 'Staf Gudang',
        'cashier' => 'Kasir',
    ],

    'permissions' => [
        // Cabang, Perangkat, Pengguna & Modul
        'branch.manage' => 'Kelola Data Cabang',
        'device.manage' => 'Kelola & Aktivasi Tablet POS',
        'user.manage' => 'Kelola Akun & Hak Akses',
        'inventory-module.toggle' => 'Saklar Nyala/Mati Modul Stok Cabang',

        // Master Data
        'master.view' => 'Lihat Master Data',
        'master.manage' => 'Kelola Master Data',

        // Menu & Katalog
        'menu.view' => 'Lihat Katalog Menu',
        'menu.manage' => 'Kelola Produk, Varian & Modifier',

        // Harga
        'price.view' => 'Lihat Hirarki Harga',
        'price.manage' => 'Kelola Harga & Override Cabang',

        // HPP & Resep
        'costing.view' => 'Lihat Resep & HPP',
        'costing.manage' => 'Kelola Bahan Mentah & Resep',

        // Promosi & Loyalty
        'promo.view' => 'Lihat Promo & Voucher',
        'promo.manage' => 'Kelola Promo, Voucher & Loyalty',
        'promo.apply' => 'Menerapkan Promo & Voucher di Transaksi',

        // Transaksi & Kasir
        'pos.transaction' => 'Akses Kasir & Penjualan POS',
        'discount.manual' => 'Input Diskon Manual',
        'discount.above-threshold' => 'Diskon Manual Melebihi Ambang Batas',
        'void.pos' => 'Void Transaksi di Tablet POS',
        'void.web' => 'Void Transaksi dari Web Back-office',
        'branch-security.manage' => 'Kelola PIN Void/Diskon & Ambang Cabang',
        'cash.pos' => 'Kelola Kas Masuk & Kas Keluar Kasir',
        'expense.manage' => 'Kelola Pengeluaran Operasional',

        // Inventori
        'purchase.manage' => 'Kelola Pembelian & Penerimaan Barang',
        'transfer.manage' => 'Kelola Transfer Stok Antar Cabang',
        'opname.input' => 'Input Fisik Stok Opname & Waste',
        'opname.approve' => 'Menyetujui Hasil Stok Opname',
        'stock.adjust' => 'Penyesuaian Manual Stok',

        // Shift & Kas
        'shift.reconcile' => 'Rekonsiliasi Shift Kasir',

        // Laporan & Ekspor
        'report.global' => 'Akses 23 Laporan Global',
        'report.branch' => 'Akses Laporan Cabang',
        'report.stock' => 'Akses Laporan Stok & HPP',
        'report.shift-own' => 'Akses Laporan Shift Sendiri',
        'export.global' => 'Ekspor Laporan Global (.xlsx)',
        'export.branch' => 'Ekspor Laporan Cabang (.xlsx)',
        'export.stock' => 'Ekspor Laporan Stok (.xlsx)',

        // Audit Log & Integritas
        'audit.global' => 'Akses Audit Log Global',
        'audit.branch' => 'Akses Audit Log Cabang',

        // Deteksi Fraud (EXT)
        'fraud.view' => 'Lihat Peringatan Fraud',
        'fraud.manage' => 'Kelola & Disposisi Kasus Fraud',
    ],

    'matrix' => [
        'owner' => [
            'branch.manage', 'device.manage', 'user.manage', 'inventory-module.toggle',
            'master.view', 'master.manage', 'menu.view', 'menu.manage', 'price.view', 'price.manage',
            'costing.view', 'costing.manage', 'promo.view', 'promo.manage',
            'void.web', 'branch-security.manage', 'expense.manage',
            'purchase.manage', 'transfer.manage', 'opname.input', 'opname.approve', 'stock.adjust',
            'shift.reconcile',
            'report.global', 'report.branch', 'report.stock', 'export.global', 'export.branch', 'export.stock',
            'audit.global', 'audit.branch', 'fraud.view', 'fraud.manage'
        ],
        'central_admin' => [
            'branch.manage', 'device.manage', 'user.manage', 'inventory-module.toggle',
            'master.view', 'master.manage', 'menu.view', 'menu.manage', 'price.view', 'price.manage',
            'costing.view', 'costing.manage', 'promo.view', 'promo.manage',
            'void.web', 'branch-security.manage', 'expense.manage',
            'purchase.manage', 'transfer.manage', 'opname.input', 'opname.approve', 'stock.adjust',
            'shift.reconcile',
            'report.global', 'report.branch', 'report.stock', 'export.global', 'export.branch', 'export.stock',
            'audit.global', 'audit.branch', 'fraud.view'
        ],
        'branch_manager' => [
            'master.view', 'menu.view', 'costing.view', 'price.view', 'promo.view',
            'promo.apply', 'pos.transaction', 'discount.manual', 'discount.above-threshold', 'void.pos',
            'branch-security.manage', 'cash.pos', 'expense.manage',
            'purchase.manage', 'transfer.manage', 'opname.input', 'opname.approve', 'stock.adjust',
            'shift.reconcile',
            'report.branch', 'report.stock', 'report.shift-own', 'export.branch', 'export.stock',
            'audit.branch', 'fraud.view'
        ],
        'warehouse_staff' => [
            'master.view', 'costing.view',
            'purchase.manage', 'transfer.manage', 'opname.input',
            'report.stock', 'export.stock'
        ],
        'cashier' => [
            'menu.view', 'promo.apply', 'pos.transaction', 'discount.manual', 'discount.above-threshold',
            'void.pos', 'cash.pos', 'report.shift-own'
        ],
    ]
];
