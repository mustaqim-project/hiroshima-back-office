<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Promo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelDataService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Get module configuration (headers, mapping, dummy data)
     */
    public function getModuleConfig(string $module): array
    {
        return match ($module) {
            'branches' => [
                'filename' => 'Data_Cabang_Hiroshima',
                'title' => 'DATA CABANG & PARAMETER FINANSIAL',
                'headers' => [
                    'Kode Cabang', 'Nama Cabang', 'Telepon', 'Alamat',
                    'PPN (%)', 'Service Charge (%)', 'Termasuk Pajak (1/0)',
                    'Metode Pembulatan', 'Ambang Diskon (%)', 'Ambang Diskon (Rp)',
                    'Lacak Inventori (1/0)', 'Status Aktif (1/0)'
                ],
                'dummy' => [
                    ['HRS-CKN', 'Hiroshima Cikini Utama', '081234567890', 'Jl. Cikini Raya No. 45, Jakarta Pusat', 11, 0, 0, 'nearest_100', 20, 50000, 1, 1],
                    ['HRS-TBT', 'Hiroshima Tebet', '081298765432', 'Jl. Tebet Raya No. 12, Jakarta Selatan', 11, 5, 0, 'nearest_100', 15, 30000, 1, 1],
                ],
            ],

            'products' => [
                'filename' => 'Data_Menu_Produk_Hiroshima',
                'title' => 'DATA MENU & PRODUK POS',
                'headers' => [
                    'SKU', 'Nama Produk', 'Nama Kategori', 'Harga Pokok HPP (Rp)',
                    'Harga Jual Default (Rp)', 'Barcode', 'Deskripsi', 'Status Aktif (1/0)'
                ],
                'dummy' => [
                    ['HRS-RAMEN-01', 'Tori Paitan Ramen', 'Ramen & Noodle', 22000, 45000, '899123456701', 'Ramen kuah kaldu ayam kental gurih khas Hiroshima', 1],
                    ['HRS-GYOZA-01', 'Gyoza Panggang (5 pcs)', 'Sides & Snack', 12000, 25000, '899123456702', 'Gyoza isi ayam cincang dan daun bawang lezat', 1],
                    ['HRS-OCHA-01', 'Cold Ocha Green Tea', 'Beverages', 3000, 10000, '899123456703', 'Teh hijau Jepang dingin menyegarkan', 1],
                ],
            ],

            'modifier-groups' => [
                'filename' => 'Data_Grup_Modifier_Hiroshima',
                'title' => 'DATA GRUP MODIFIER & PILIHAN MENU',
                'headers' => [
                    'Nama Grup Modifier', 'Tipe Pilihan (single/multiple)', 'Wajib Dipilih (1/0)',
                    'Min Pilihan', 'Max Pilihan', 'Daftar Opsi (Format: Nama:Harga, Nama:Harga)', 'Status Aktif (1/0)'
                ],
                'dummy' => [
                    ['Tingkat Kepedasan', 'single', 1, 1, 1, 'Level 0 (Normal):0, Level 1:0, Level 2:3000, Level 3:5000', 1],
                    ['Ekstra Topping Ramen', 'multiple', 0, 0, 4, 'Ekstra Ajitama Egg:7000, Ekstra Chashu:12000, Nori Sheet:3000, Jagung Manis:3000', 1],
                ],
            ],

            'categories' => [
                'filename' => 'Data_Kategori_Menu_Hiroshima',
                'title' => 'DATA KATEGORI MENU',
                'headers' => [
                    'Kode Kategori', 'Nama Kategori', 'Target Printer (kitchen/bar/checker)', 'Urutan Tampil', 'Status Aktif (1/0)'
                ],
                'dummy' => [
                    ['RAMEN', 'Ramen & Noodle', 'kitchen', 1, 1],
                    ['DONBURI', 'Rice Bowl & Donburi', 'kitchen', 2, 1],
                    ['BEVERAGES', 'Minuman & Ocha', 'bar', 3, 1],
                    ['SIDES', 'Sides & Snack', 'kitchen', 4, 1],
                ],
            ],

            'order-types' => [
                'filename' => 'Data_Jenis_Pesanan_Hiroshima',
                'title' => 'DATA JENIS PESANAN (ORDER TYPES)',
                'headers' => [
                    'Kode Pesanan', 'Nama Jenis Pesanan', 'Perlu Nomor Meja (1/0)', 'Pesanan Utama / Default (1/0)', 'Urutan Tampil', 'Status Aktif (1/0)'
                ],
                'dummy' => [
                    ['DINE_IN', 'Dine In (Makan di Tempat)', 1, 1, 1, 1],
                    ['TAKE_AWAY', 'Take Away (Bungkus)', 0, 0, 2, 1],
                    ['DELIVERY', 'Delivery Online', 0, 0, 3, 1],
                ],
            ],

            'payment-methods' => [
                'filename' => 'Data_Metode_Bayar_Hiroshima',
                'title' => 'DATA METODE PEMBAYARAN',
                'headers' => [
                    'Kode Metode', 'Nama Metode Pembayaran', 'Kategori Tipe (cash/qris/debit/credit/transfer/ewallet)', 'Merupakan Tunai (1/0)', 'Status Aktif (1/0)'
                ],
                'dummy' => [
                    ['CASH', 'Uang Tunai (Cash)', 'cash', 1, 1],
                    ['QRIS', 'QRIS Dinamis / Statis', 'qris', 0, 1],
                    ['DEBIT_BCA', 'Kartu Debit BCA', 'debit', 0, 1],
                    ['GOPAY', 'GoPay E-Wallet', 'ewallet', 0, 1],
                ],
            ],

            'users' => [
                'filename' => 'Data_Pengguna_Hiroshima',
                'title' => 'DATA PENGGUNA & HAK AKSES',
                'headers' => [
                    'Nama Lengkap', 'Username', 'Email', 'Nomor Telepon', 'Role (admin/owner/supervisor/cashier)',
                    'Kode Cabang', 'Kata Sandi Awal', 'PIN Kasir (6 Angka)', 'Status Aktif (1/0)'
                ],
                'dummy' => [
                    ['Ahmad Supervisor', 'ahmad_spv', 'ahmad@hiroshima.id', '081234567890', 'supervisor', 'HRS-CKN', 'password123', '123456', 1],
                    ['Siti Kasir', 'siti_kasir', 'siti@hiroshima.id', '081298765432', 'cashier', 'HRS-CKN', 'password123', '654321', 1],
                ],
            ],

            'promos' => [
                'filename' => 'Data_Promo_Diskon_Hiroshima',
                'title' => 'DATA PROMO & DISKON',
                'headers' => [
                    'Kode Promo', 'Nama Promo', 'Tipe Diskon (percentage/fixed_amount)', 'Nilai Diskon',
                    'Min. Pembelian (Rp)', 'Maks. Diskon (Rp)', 'Bisa Digabung (1/0)', 'Prioritas',
                    'Tanggal Mulai (YYYY-MM-DD)', 'Tanggal Berakhir (YYYY-MM-DD)', 'Kuota Penggunaan', 'Status Aktif (1/0)'
                ],
                'dummy' => [
                    ['OPENING20', 'Diskon Grand Opening 20%', 'percentage', 20, 50000, 25000, 0, 1, date('Y-m-01'), date('Y-12-31'), 200, 1],
                    ['HEMAT10K', 'Potongan Rp 10.000', 'fixed_amount', 10000, 75000, 10000, 0, 2, date('Y-m-01'), date('Y-12-31'), 500, 1],
                ],
            ],

            default => throw new \InvalidArgumentException("Modul {$module} tidak dikenali."),
        };
    }

    /**
     * Export existing data as Excel (.xlsx).
     * If no data exists, outputs the template with dummy data.
     */
    /**
     * Column index (1-based) to Excel column letter ('A', 'Z', 'AA', etc.)
     */
    public static function columnLetter(int $colIndex): string
    {
        $letter = '';
        while ($colIndex > 0) {
            $mod = ($colIndex - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $colIndex = intval(($colIndex - $mod) / 26);
        }
        return $letter;
    }

    /**
     * Export existing data as Excel (.xlsx).
     * If no data exists, outputs the template with dummy data.
     * Uses zero-dependency Native OpenXML Engine with automatic CSV fallback.
     */
    public function export(string $module, bool $isTemplateOnly = false): StreamedResponse
    {
        $config = $this->getModuleConfig($module);
        $headers = $config['headers'];

        $rows = [];
        if (!$isTemplateOnly) {
            $rows = $this->fetchModuleData($module);
        }

        // Rule: Jika data kosong, isikan data dummy template
        if (empty($rows)) {
            $rows = $config['dummy'];
        }

        $filenameSuffix = $isTemplateOnly ? 'Template' : date('Ymd_His');
        $filename = "{$config['filename']}_{$filenameSuffix}.xlsx";

        // 1. Native OpenXML (.xlsx) generation via PHP ZipArchive (Standard PHP extension)
        if (class_exists(\ZipArchive::class)) {
            try {
                $tempPath = $this->createOpenXmlSpreadsheet($config['filename'], $headers, $rows);

                return new StreamedResponse(function () use ($tempPath) {
                    $stream = fopen($tempPath, 'rb');
                    if ($stream) {
                        fpassthru($stream);
                        fclose($stream);
                    }
                    @unlink($tempPath);
                }, 200, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                    'Cache-Control' => 'max-age=0, no-cache, must-revalidate',
                    'Pragma' => 'public',
                ]);
            } catch (\Throwable $e) {
                // If native generation fails, smoothly proceed to UTF-8 CSV fallback
            }
        }

        // 2. High-compatibility UTF-8 CSV fallback with BOM (Opens perfectly in Excel)
        $csvFilename = str_replace('.xlsx', '.csv', $filename);
        return new StreamedResponse(function () use ($headers, $rows) {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM so Microsoft Excel renders accented characters properly
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($output, $headers);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$csvFilename}\"",
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Generate genuine OpenXML (.xlsx) package using PHP ZipArchive.
     */
    protected function createOpenXmlSpreadsheet(string $sheetTitle, array $headers, array $rows): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'hrs_xlsx_') . '.xlsx';
        $zip = new \ZipArchive();
        if ($zip->open($tempPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat file spreadsheet sementara.');
        }

        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // xl/workbook.xml
        $safeTitle = htmlspecialchars(substr($sheetTitle, 0, 31), ENT_XML1, 'UTF-8');
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            . '<sheet name="' . $safeTitle . '" sheetId="1" r:id="rId1"/>'
            . '</sheets>'
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbookXml);

        // xl/styles.xml (Professional Dark Navy Header + Clean Borders)
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><name val="Segoe UI"/><sz val="11"/></font>'
            . '<font><name val="Segoe UI"/><sz val="11"/><b/><color rgb="FFFFFFFF"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
            . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
            . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
            . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            . '</cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $stylesXml);

        // xl/worksheets/sheet1.xml
        $colWidths = [];
        foreach ($headers as $cIdx => $h) {
            $maxLen = mb_strlen((string)$h, 'UTF-8');
            foreach ($rows as $r) {
                $valLen = isset($r[$cIdx]) ? mb_strlen((string)$r[$cIdx], 'UTF-8') : 0;
                if ($valLen > $maxLen) $maxLen = $valLen;
            }
            $colWidths[$cIdx + 1] = max(14, min(48, $maxLen + 4));
        }

        $colsXml = '<cols>';
        foreach ($colWidths as $colNum => $width) {
            $colsXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $sheetDataXml = '<sheetData>';
        // Header row (Dark navy fill, bold white text, height 28)
        $sheetDataXml .= '<row r="1" ht="28" customHeight="1">';
        foreach ($headers as $cIdx => $headerText) {
            $cellRef = self::columnLetter($cIdx + 1) . '1';
            $escaped = htmlspecialchars((string)$headerText, ENT_XML1, 'UTF-8');
            $sheetDataXml .= '<c r="' . $cellRef . '" s="1" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
        }
        $sheetDataXml .= '</row>';

        // Data rows (Height 22, border)
        $rowNum = 2;
        foreach ($rows as $row) {
            $sheetDataXml .= '<row r="' . $rowNum . '" ht="22" customHeight="1">';
            foreach ($row as $cIdx => $val) {
                $cellRef = self::columnLetter($cIdx + 1) . $rowNum;
                if (is_null($val) || $val === '') {
                    $sheetDataXml .= '<c r="' . $cellRef . '" s="2"/>';
                } elseif (is_int($val) || (is_numeric($val) && !preg_match('/^0[0-9]+$/', (string)$val))) {
                    $sheetDataXml .= '<c r="' . $cellRef . '" s="2"><v>' . $val . '</v></c>';
                } else {
                    $escaped = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');
                    $sheetDataXml .= '<c r="' . $cellRef . '" s="2" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
                }
            }
            $sheetDataXml .= '</row>';
            $rowNum++;
        }
        $sheetDataXml .= '</sheetData>';

        $worksheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $colsXml
            . $sheetDataXml
            . '</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $worksheetXml);

        $zip->close();
        return $tempPath;
    }

    /**
     * Fetch existing rows for export
     */
    protected function fetchModuleData(string $module): array
    {
        $rows = [];

        switch ($module) {
            case 'branches':
                $items = Branch::orderBy('id')->get();
                foreach ($items as $b) {
                    $rows[] = [
                        $b->code,
                        $b->name,
                        $b->phone ?? '',
                        $b->address ?? '',
                        $b->tax_percent,
                        $b->service_charge_percent,
                        $b->tax_included ? 1 : 0,
                        $b->rounding_mode,
                        $b->discount_percent_threshold,
                        $b->discount_amount_threshold,
                        $b->inventory_enabled ? 1 : 0,
                        $b->is_active ? 1 : 0,
                    ];
                }
                break;

            case 'products':
                $items = Product::with(['category', 'prices'])->orderBy('id')->get();
                $defaultOrderType = OrderType::default();
                foreach ($items as $p) {
                    $price = 0;
                    if ($defaultOrderType) {
                        $pPrice = $p->prices->firstWhere('order_type_id', $defaultOrderType->id);
                        if ($pPrice) $price = $pPrice->price;
                    }
                    if (!$price && $p->prices->isNotEmpty()) {
                        $price = $p->prices->first()->price;
                    }

                    $rows[] = [
                        $p->sku,
                        $p->name,
                        $p->category->name ?? '',
                        $p->manual_cost ?? 0,
                        $price,
                        $p->barcode ?? '',
                        $p->description ?? '',
                        $p->is_active ? 1 : 0,
                    ];
                }
                break;

            case 'modifier-groups':
                $items = ModifierGroup::with('modifiers')->orderBy('id')->get();
                foreach ($items as $g) {
                    $modifiersStr = $g->modifiers->map(fn($m) => "{$m->name}:{$m->price}")->implode(', ');
                    $rows[] = [
                        $g->name,
                        $g->selection_type,
                        $g->is_required ? 1 : 0,
                        $g->min_select,
                        $g->max_select,
                        $modifiersStr,
                        $g->is_active ? 1 : 0,
                    ];
                }
                break;

            case 'categories':
                $items = Category::orderBy('sort_order')->orderBy('id')->get();
                foreach ($items as $c) {
                    $rows[] = [
                        $c->code,
                        $c->name,
                        $c->target_printer ?? 'kitchen',
                        $c->sort_order,
                        $c->is_active ? 1 : 0,
                    ];
                }
                break;

            case 'order-types':
                $items = OrderType::orderBy('sort_order')->orderBy('id')->get();
                foreach ($items as $ot) {
                    $rows[] = [
                        $ot->code,
                        $ot->name,
                        $ot->requires_table ? 1 : 0,
                        $ot->is_default ? 1 : 0,
                        $ot->sort_order,
                        $ot->is_active ? 1 : 0,
                    ];
                }
                break;

            case 'payment-methods':
                $items = PaymentMethod::orderBy('id')->get();
                foreach ($items as $pm) {
                    $rows[] = [
                        $pm->code,
                        $pm->name,
                        $pm->type ?? 'cash',
                        $pm->is_cash ? 1 : 0,
                        $pm->is_active ? 1 : 0,
                    ];
                }
                break;

            case 'users':
                $items = User::with('branch')->orderBy('id')->get();
                foreach ($items as $u) {
                    $rows[] = [
                        $u->name,
                        $u->username,
                        $u->email,
                        $u->phone ?? '',
                        $u->role,
                        $u->branch->code ?? '',
                        '', // kata sandi dirahasiakan saat export
                        '', // PIN dirahasiakan saat export
                        $u->is_active ? 1 : 0,
                    ];
                }
                break;

            case 'promos':
                $items = Promo::orderBy('id')->get();
                foreach ($items as $pr) {
                    $rows[] = [
                        $pr->code,
                        $pr->name,
                        $pr->type,
                        $pr->value,
                        $pr->min_purchase,
                        $pr->max_discount,
                        $pr->is_stackable ? 1 : 0,
                        $pr->priority,
                        $pr->starts_at ? $pr->starts_at->format('Y-m-d') : '',
                        $pr->ends_at ? $pr->ends_at->format('Y-m-d') : '',
                        $pr->quota ?? '',
                        $pr->is_active ? 1 : 0,
                    ];
                }
                break;
        }

        return $rows;
    }

    /**
     * Import spreadsheet from uploaded file (.xlsx, .xls, .csv)
     */
    public function import(string $module, UploadedFile $file): array
    {
        $rawRows = $this->parseUploadedSpreadsheet($file);

        if (count($rawRows) < 2) {
            return [
                'success' => false,
                'created' => 0,
                'updated' => 0,
                'errors' => ['File spreadsheet kosong atau tidak memiliki baris data.'],
                'message' => 'File spreadsheet tidak berisi data.',
            ];
        }

        // Shift header row
        array_shift($rawRows);

        $created = 0;
        $updated = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rawRows as $idx => $row) {
                $rowNum = $idx + 2;

                // Check if row is completely empty
                $nonEmpty = array_filter($row, fn($v) => !is_null($v) && trim((string)$v) !== '');
                if (empty($nonEmpty)) {
                    continue;
                }

                try {
                    $result = match ($module) {
                        'branches' => $this->importBranchRow($row),
                        'products' => $this->importProductRow($row),
                        'modifier-groups' => $this->importModifierGroupRow($row),
                        'categories' => $this->importCategoryRow($row),
                        'order-types' => $this->importOrderTypeRow($row),
                        'payment-methods' => $this->importPaymentMethodRow($row),
                        'users' => $this->importUserRow($row),
                        'promos' => $this->importPromoRow($row),
                        default => 'skipped',
                    };

                    if ($result === 'created') $created++;
                    elseif ($result === 'updated') $updated++;
                } catch (\Throwable $e) {
                    $errors[] = "Baris #{$rowNum}: " . $e->getMessage();
                }
            }

            DB::commit();

            $this->auditLogger->record(
                "excel.import.{$module}",
                null,
                [],
                [
                    'created' => $created,
                    'updated' => $updated,
                    'error_count' => count($errors),
                ]
            );

            $msg = "Impor {$module} selesai: {$created} data baru ditambahkan, {$updated} data diperbarui.";
            if (!empty($errors)) {
                $msg .= " (" . count($errors) . " baris dilewati karena format tidak sesuai)";
            }

            return [
                'success' => true,
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
                'message' => $msg,
            ];
        } catch (\Throwable $fatal) {
            DB::rollBack();
            return [
                'success' => false,
                'created' => 0,
                'updated' => 0,
                'errors' => [$fatal->getMessage()],
                'message' => 'Terjadi kesalahan sistem saat memproses impor: ' . $fatal->getMessage(),
            ];
        }
    }

    // --- ROW PROCESSORS ---

    protected function importBranchRow(array $row): string
    {
        $code = trim((string)($row[0] ?? ''));
        $name = trim((string)($row[1] ?? ''));

        if (empty($code) || empty($name)) {
            throw new \Exception('Kode Cabang dan Nama Cabang wajib diisi.');
        }

        $branch = Branch::withTrashed()->firstOrNew(['code' => $code]);
        $isNew = !$branch->exists;
        if ($branch->trashed()) $branch->restore();

        if ($isNew && empty($branch->business_id)) {
            $business = \App\Models\Business::firstOrCreate(
                ['code' => 'HIROSHIMA'],
                ['name' => 'Hiroshima F&B']
            );
            $branch->business_id = $business->id;
        }

        $branch->name = $name;
        $branch->phone = !empty($row[2]) ? trim((string)$row[2]) : null;
        $branch->address = !empty($row[3]) ? trim((string)$row[3]) : null;
        $branch->tax_percent = is_numeric($row[4] ?? null) ? (float)$row[4] : 11;
        $branch->service_charge_percent = is_numeric($row[5] ?? null) ? (float)$row[5] : 0;
        $branch->tax_included = in_array(strtolower(trim((string)($row[6] ?? '0'))), ['1', 'true', 'ya']);
        $branch->rounding_mode = !empty($row[7]) ? trim((string)$row[7]) : 'nearest_100';
        $branch->discount_percent_threshold = is_numeric($row[8] ?? null) ? (float)$row[8] : 20;
        $branch->discount_amount_threshold = is_numeric($row[9] ?? null) ? (int)$row[9] : 50000;
        $branch->inventory_enabled = in_array(strtolower(trim((string)($row[10] ?? '1'))), ['1', 'true', 'ya']);
        $branch->is_active = in_array(strtolower(trim((string)($row[11] ?? '1'))), ['1', 'true', 'ya']);

        $branch->save();
        return $isNew ? 'created' : 'updated';
    }

    protected function importProductRow(array $row): string
    {
        $sku = trim((string)($row[0] ?? ''));
        $name = trim((string)($row[1] ?? ''));
        $categoryName = trim((string)($row[2] ?? ''));

        if (empty($sku) || empty($name)) {
            throw new \Exception('SKU dan Nama Produk wajib diisi.');
        }

        // Find or create category
        $category = null;
        if (!empty($categoryName)) {
            $category = Category::where('name', $categoryName)->orWhere('code', $categoryName)->first();
            if (!$category) {
                $category = Category::create([
                    'name' => $categoryName,
                    'code' => strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $categoryName), 0, 10)),
                    'is_active' => true,
                ]);
            }
        }

        $product = Product::withTrashed()->firstOrNew(['sku' => $sku]);
        $isNew = !$product->exists;
        if ($product->trashed()) $product->restore();

        $product->name = $name;
        if ($category) $product->category_id = $category->id;
        $product->manual_cost = is_numeric($row[3] ?? null) ? (int)$row[3] : 0;
        $product->barcode = !empty($row[5]) ? trim((string)$row[5]) : null;
        $product->description = !empty($row[6]) ? trim((string)$row[6]) : null;
        $product->is_active = in_array(strtolower(trim((string)($row[7] ?? '1'))), ['1', 'true', 'ya']);
        $product->save();

        // Update default price
        $price = is_numeric($row[4] ?? null) ? (int)$row[4] : 0;
        if ($price > 0) {
            $defaultOrderType = OrderType::default() ?? OrderType::first();
            if ($defaultOrderType) {
                ProductPrice::updateOrCreate(
                    [
                        'sellable_type' => Product::class,
                        'sellable_id' => $product->id,
                        'order_type_id' => $defaultOrderType->id,
                        'branch_id' => null,
                    ],
                    ['price' => $price]
                );
            }
        }

        return $isNew ? 'created' : 'updated';
    }

    protected function importModifierGroupRow(array $row): string
    {
        $name = trim((string)($row[0] ?? ''));
        if (empty($name)) {
            throw new \Exception('Nama Grup Modifier wajib diisi.');
        }

        $group = ModifierGroup::withTrashed()->firstOrNew(['name' => $name]);
        $isNew = !$group->exists;
        if ($group->trashed()) $group->restore();

        $group->selection_type = in_array(strtolower(trim((string)($row[1] ?? 'single'))), ['multiple']) ? 'multiple' : 'single';
        $group->is_required = in_array(strtolower(trim((string)($row[2] ?? '0'))), ['1', 'true', 'ya']);
        $group->min_select = is_numeric($row[3] ?? null) ? (int)$row[3] : 0;
        $group->max_select = is_numeric($row[4] ?? null) ? (int)$row[4] : 1;
        $group->is_active = in_array(strtolower(trim((string)($row[6] ?? '1'))), ['1', 'true', 'ya']);
        $group->save();

        // Parse modifiers list: "Nama:Harga, Nama:Harga"
        $optionsStr = trim((string)($row[5] ?? ''));
        if (!empty($optionsStr)) {
            $items = explode(',', $optionsStr);
            foreach ($items as $item) {
                $parts = explode(':', trim($item));
                $optName = trim($parts[0] ?? '');
                $optPrice = is_numeric($parts[1] ?? null) ? (int)$parts[1] : 0;
                if (!empty($optName)) {
                    Modifier::updateOrCreate(
                        [
                            'modifier_group_id' => $group->id,
                            'name' => $optName,
                        ],
                        [
                            'price' => $optPrice,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        return $isNew ? 'created' : 'updated';
    }

    protected function importCategoryRow(array $row): string
    {
        $code = trim((string)($row[0] ?? ''));
        $name = trim((string)($row[1] ?? ''));

        if (empty($code) || empty($name)) {
            throw new \Exception('Kode Kategori dan Nama Kategori wajib diisi.');
        }

        $category = Category::withTrashed()->firstOrNew(['code' => $code]);
        $isNew = !$category->exists;
        if ($category->trashed()) $category->restore();

        $category->name = $name;
        $category->target_printer = !empty($row[2]) ? trim((string)$row[2]) : 'kitchen';
        $category->sort_order = is_numeric($row[3] ?? null) ? (int)$row[3] : 0;
        $category->is_active = in_array(strtolower(trim((string)($row[4] ?? '1'))), ['1', 'true', 'ya']);
        $category->save();

        return $isNew ? 'created' : 'updated';
    }

    protected function importOrderTypeRow(array $row): string
    {
        $code = trim((string)($row[0] ?? ''));
        $name = trim((string)($row[1] ?? ''));

        if (empty($code) || empty($name)) {
            throw new \Exception('Kode Pesanan dan Nama Jenis Pesanan wajib diisi.');
        }

        $ot = OrderType::withTrashed()->firstOrNew(['code' => $code]);
        $isNew = !$ot->exists;
        if ($ot->trashed()) $ot->restore();

        $ot->name = $name;
        $ot->requires_table = in_array(strtolower(trim((string)($row[2] ?? '0'))), ['1', 'true', 'ya']);
        $ot->is_default = in_array(strtolower(trim((string)($row[3] ?? '0'))), ['1', 'true', 'ya']);
        $ot->sort_order = is_numeric($row[4] ?? null) ? (int)$row[4] : 0;
        $ot->is_active = in_array(strtolower(trim((string)($row[5] ?? '1'))), ['1', 'true', 'ya']);
        $ot->save();

        return $isNew ? 'created' : 'updated';
    }

    protected function importPaymentMethodRow(array $row): string
    {
        $code = trim((string)($row[0] ?? ''));
        $name = trim((string)($row[1] ?? ''));

        if (empty($code) || empty($name)) {
            throw new \Exception('Kode Metode dan Nama Metode Pembayaran wajib diisi.');
        }

        $pm = PaymentMethod::withTrashed()->firstOrNew(['code' => $code]);
        $isNew = !$pm->exists;
        if ($pm->trashed()) $pm->restore();

        $pm->name = $name;
        $pm->type = !empty($row[2]) ? trim((string)$row[2]) : 'cash';
        $pm->is_cash = in_array(strtolower(trim((string)($row[3] ?? '0'))), ['1', 'true', 'ya']);
        $pm->is_active = in_array(strtolower(trim((string)($row[4] ?? '1'))), ['1', 'true', 'ya']);
        $pm->save();

        return $isNew ? 'created' : 'updated';
    }

    protected function importUserRow(array $row): string
    {
        $name = trim((string)($row[0] ?? ''));
        $username = trim((string)($row[1] ?? ''));
        $email = trim((string)($row[2] ?? ''));

        if (empty($name) || empty($username) || empty($email)) {
            throw new \Exception('Nama, Username, dan Email wajib diisi.');
        }

        $user = User::withTrashed()->where('username', $username)->orWhere('email', $email)->first();
        $isNew = !$user;
        if (!$user) {
            $user = new User();
            $user->username = $username;
            $user->email = $email;
        } elseif ($user->trashed()) {
            $user->restore();
        }

        $user->name = $name;
        $user->phone = !empty($row[3]) ? trim((string)$row[3]) : null;
        $user->role = in_array(strtolower(trim((string)($row[4] ?? 'cashier'))), ['admin', 'owner', 'supervisor', 'cashier'])
            ? strtolower(trim((string)$row[4]))
            : 'cashier';

        // Branch mapping
        $branchCode = trim((string)($row[5] ?? ''));
        if (!empty($branchCode)) {
            $branch = Branch::where('code', $branchCode)->first();
            if ($branch) $user->branch_id = $branch->id;
        }

        // Password & PIN (only update if provided)
        $password = trim((string)($row[6] ?? ''));
        if (!empty($password)) {
            $user->password = Hash::make($password);
        } elseif ($isNew) {
            $user->password = Hash::make('password123');
        }

        $pin = trim((string)($row[7] ?? ''));
        if (!empty($pin)) {
            $user->pin_hash = Hash::make($pin);
        } elseif ($isNew) {
            $user->pin_hash = Hash::make('123456');
        }

        $user->is_active = in_array(strtolower(trim((string)($row[8] ?? '1'))), ['1', 'true', 'ya']);
        $user->save();

        return $isNew ? 'created' : 'updated';
    }

    protected function importPromoRow(array $row): string
    {
        $code = trim((string)($row[0] ?? ''));
        $name = trim((string)($row[1] ?? ''));

        if (empty($code) || empty($name)) {
            throw new \Exception('Kode Promo dan Nama Promo wajib diisi.');
        }

        $promo = Promo::withTrashed()->firstOrNew(['code' => $code]);
        $isNew = !$promo->exists;
        if ($promo->trashed()) $promo->restore();

        $promo->name = $name;
        $promo->type = in_array(strtolower(trim((string)($row[2] ?? 'percentage'))), ['percentage', 'fixed_amount'])
            ? strtolower(trim((string)$row[2]))
            : 'percentage';
        $promo->value = is_numeric($row[3] ?? null) ? (float)$row[3] : 0;
        $promo->min_purchase = is_numeric($row[4] ?? null) ? (int)$row[4] : 0;
        $promo->max_discount = is_numeric($row[5] ?? null) ? (int)$row[5] : 0;
        $promo->is_stackable = in_array(strtolower(trim((string)($row[6] ?? '0'))), ['1', 'true', 'ya']);
        $promo->priority = is_numeric($row[7] ?? null) ? (int)$row[7] : 1;
        $promo->starts_at = !empty($row[8]) ? date('Y-m-d 00:00:00', strtotime($row[8])) : null;
        $promo->ends_at = !empty($row[9]) ? date('Y-m-d 23:59:59', strtotime($row[9])) : null;
        $promo->quota = is_numeric($row[10] ?? null) ? (int)$row[10] : null;
        $promo->is_active = in_array(strtolower(trim((string)($row[11] ?? '1'))), ['1', 'true', 'ya']);
        $promo->save();

        return $isNew ? 'created' : 'updated';
    }

    /**
     * Unified spreadsheet parser supporting both Native OpenXML, CSV, and optional PhpSpreadsheet.
     */
    protected function parseUploadedSpreadsheet(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        // 1. Try PhpSpreadsheet if available
        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($realPath);
                return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            } catch (\Throwable $e) {
                // Silently fall back to native parsers
            }
        }

        // 2. CSV file handler
        if ($extension === 'csv' || $extension === 'txt') {
            return $this->parseCsv($realPath);
        }

        // 3. Native OpenXML (.xlsx) parser via standard PHP ZipArchive
        if (class_exists(\ZipArchive::class)) {
            $parsed = $this->parseNativeXlsx($realPath);
            if (!empty($parsed)) {
                return $parsed;
            }
        }

        // 4. Fallback attempt via CSV
        return $this->parseCsv($realPath);
    }

    /**
     * Native parser for OpenXML (.xlsx) without external libraries.
     */
    protected function parseNativeXlsx(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        // Parse shared strings table (xl/sharedStrings.xml)
        $sharedStrings = [];
        $ssContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssContent !== false) {
            $xml = @simplexml_load_string($ssContent);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string)$r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // Parse worksheet 1 (xl/worksheets/sheet1.xml)
        $parsedRows = [];
        $sheetContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetContent !== false) {
            $xml = @simplexml_load_string($sheetContent);
            if ($xml && isset($xml->sheetData->row)) {
                foreach ($xml->sheetData->row as $row) {
                    $rowCells = [];
                    foreach ($row->c as $c) {
                        $ref = (string)$c['r'];
                        preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
                        $colStr = $m[1] ?? 'A';
                        $colIdx = 0;
                        $len = strlen($colStr);
                        for ($i = 0; $i < $len; $i++) {
                            $colIdx = $colIdx * 26 + (ord($colStr[$i]) - 64);
                        }
                        $colIdx -= 1;

                        $type = (string)$c['t'];
                        $val = '';
                        if ($type === 's') {
                            $sId = (int)$c->v;
                            $val = $sharedStrings[$sId] ?? '';
                        } elseif ($type === 'inlineStr') {
                            $val = (string)($c->is->t ?? '');
                        } else {
                            $val = (string)($c->v ?? '');
                        }
                        $rowCells[$colIdx] = $val;
                    }

                    if (!empty($rowCells)) {
                        $max = max(array_keys($rowCells));
                        $filled = [];
                        for ($i = 0; $i <= $max; $i++) {
                            $filled[] = $rowCells[$i] ?? '';
                        }
                        $parsedRows[] = $filled;
                    }
                }
            }
        }

        $zip->close();
        return $parsedRows;
    }

    /**
     * Native CSV parser with auto-delimiter detection and UTF-8 BOM stripping.
     */
    protected function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $firstLine = fgets($handle);
            rewind($handle);

            // Strip UTF-8 BOM if present
            if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
                fseek($handle, 3);
            }

            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        return $rows;
    }
}
