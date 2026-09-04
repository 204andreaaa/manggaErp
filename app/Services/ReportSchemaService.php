<?php

namespace App\Services;

class ReportSchemaService
{
    /**
     * Daftar Report Type / Dataset yang didukung
     */
    public static function getReportTypes(): array
    {
        return [
            'purchase_orders' => [
                'name'        => 'Purchase Orders (PO)',
                'icon'        => 'bx-receipt',
                'badge'       => 'Procurement',
                'description' => 'Laporan transaksi Purchase Order lengkap dengan supplier, termin, nilai belanja, dan status approval.',
                'date_fields' => [
                    'po_date'      => 'Tanggal PO',
                    'created_at'   => 'Tanggal Dibuat',
                    'approved_at'  => 'Tanggal Approved',
                ],
                'default_date_field' => 'po_date',
            ],
            'request_forms' => [
                'name'        => 'Request Forms (RF)',
                'icon'        => 'bx-file',
                'badge'       => 'Procurement & GA',
                'description' => 'Laporan pengajuan barang & jasa dari divisi/site, budget WID yang digunakan, dan progres pengadaan.',
                'date_fields' => [
                    'rf_date'      => 'Tanggal RF',
                    'created_at'   => 'Tanggal Dibuat',
                ],
                'default_date_field' => 'rf_date',
            ],
            'goods_receipts' => [
                'name'        => 'Goods Receipts (GRN)',
                'icon'        => 'bx-download',
                'badge'       => 'Logistik & GA',
                'description' => 'Laporan penerimaan barang di gudang/site, surat jalan vendor, dan verifikasi barang masuk.',
                'date_fields' => [
                    'received_date' => 'Tanggal Penerimaan',
                    'created_at'    => 'Tanggal Dibuat',
                ],
                'default_date_field' => 'received_date',
            ],
            'payment_advices' => [
                'name'        => 'Payment Advices & Invoices (PA/SID)',
                'icon'        => 'bx-money',
                'badge'       => 'Finance & Accounting',
                'description' => 'Laporan tagihan supplier, jatuh tempo invoice, pengajuan pembayaran, dan realisasi disbursement.',
                'date_fields' => [
                    'due_date'     => 'Tanggal Jatuh Tempo',
                    'advice_date'  => 'Tanggal Payment Advice',
                    'created_at'   => 'Tanggal Dibuat',
                ],
                'default_date_field' => 'due_date',
            ],
            'work_items' => [
                'name'        => 'Budget & Work Items (WID)',
                'icon'        => 'bx-wallet',
                'badge'       => 'Project Management',
                'description' => 'Laporan alokasi anggaran per pos pekerjaan (WID), realisasi pengeluaran, dan sisa pagu anggaran.',
                'date_fields' => [
                    'created_at'   => 'Tanggal Dibuat',
                ],
                'default_date_field' => 'created_at',
            ],
            'stocks' => [
                'name'        => 'Inventory Stocks & Catalog',
                'icon'        => 'bx-layer',
                'badge'       => 'Logistik & Gudang',
                'description' => 'Laporan stok barang, persediaan di gudang/site, status stok minimum, dan katalog produk.',
                'date_fields' => [
                    'updated_at'   => 'Tanggal Update Terakhir',
                ],
                'default_date_field' => 'updated_at',
            ],
            'employees' => [
                'name'        => 'Data Karyawan (HRIS)',
                'icon'        => 'bx-id-card',
                'badge'       => 'HR & Personalia',
                'description' => 'Laporan data induk karyawan, NIK, penempatan divisi, jabatan, status kerja, dan kontak.',
                'date_fields' => [
                    'join_date'    => 'Tanggal Bergabung',
                    'created_at'   => 'Tanggal Dibuat',
                ],
                'default_date_field' => 'join_date',
            ],
        ];
    }

    /**
     * Definisi Kolom / Field untuk masing-masing Report Type
     */
    public static function getFields(string $reportType): array
    {
        switch ($reportType) {
            case 'purchase_orders':
                return [
                    ['key' => 'po_number',         'label' => 'No. PO',                'type' => 'text',     'default' => true],
                    ['key' => 'po_date',           'label' => 'Tanggal PO',            'type' => 'date',     'default' => true],
                    ['key' => 'supplier_name',     'label' => 'Nama Supplier/Vendor',  'type' => 'text',     'default' => true],
                    ['key' => 'project_name',      'label' => 'Nama Project',          'type' => 'text',     'default' => false],
                    ['key' => 'sub_project_name',  'label' => 'Sub Project',           'type' => 'text',     'default' => false],
                    ['key' => 'work_item_name',    'label' => 'Pos Budget (WID)',      'type' => 'text',     'default' => false],
                    ['key' => 'subtotal',          'label' => 'Sub Total (DPP)',       'type' => 'currency', 'default' => true],
                    ['key' => 'tax_amount',        'label' => 'PPN / Pajak',           'type' => 'currency', 'default' => true],
                    ['key' => 'grand_total',       'label' => 'Total PO (Termasuk Pajak)', 'type' => 'currency', 'default' => true],
                    ['key' => 'payment_term',      'label' => 'Termin Pembayaran (TOP)', 'type' => 'text',   'default' => false],
                    ['key' => 'delivery_location', 'label' => 'Lokasi Pengiriman',     'type' => 'text',     'default' => false],
                    ['key' => 'created_by_name',   'label' => 'Dibuat Oleh',           'type' => 'text',     'default' => false],
                    ['key' => 'status',            'label' => 'Status PO',             'type' => 'badge',    'default' => true],
                    ['key' => 'notes',             'label' => 'Catatan / Keterangan',  'type' => 'text',     'default' => false],
                ];

            case 'request_forms':
                return [
                    ['key' => 'rf_number',         'label' => 'No. RF',                'type' => 'text',     'default' => true],
                    ['key' => 'rf_date',           'label' => 'Tanggal RF',            'type' => 'date',     'default' => true],
                    ['key' => 'department',        'label' => 'Divisi / Departemen',   'type' => 'text',     'default' => true],
                    ['key' => 'requestor_name',    'label' => 'Pemohon (Requestor)',   'type' => 'text',     'default' => true],
                    ['key' => 'work_item_name',    'label' => 'Alokasi Budget (WID)',  'type' => 'text',     'default' => true],
                    ['key' => 'total_estimated_cost', 'label' => 'Total Estimasi Biaya', 'type' => 'currency', 'default' => true],
                    ['key' => 'status',            'label' => 'Status Pengajuan',      'type' => 'badge',    'default' => true],
                    ['key' => 'notes',             'label' => 'Keperluan / Catatan',   'type' => 'text',     'default' => false],
                ];

            case 'goods_receipts':
                return [
                    ['key' => 'grn_number',        'label' => 'No. GRN',               'type' => 'text',     'default' => true],
                    ['key' => 'received_date',     'label' => 'Tanggal Penerimaan',    'type' => 'date',     'default' => true],
                    ['key' => 'po_number',         'label' => 'No. PO Terkait',        'type' => 'text',     'default' => true],
                    ['key' => 'supplier_name',     'label' => 'Supplier / Vendor',     'type' => 'text',     'default' => true],
                    ['key' => 'delivery_order_number', 'label' => 'No. Surat Jalan Vendor', 'type' => 'text', 'default' => true],
                    ['key' => 'received_by',       'label' => 'Penerima Barang',       'type' => 'text',     'default' => true],
                    ['key' => 'status',            'label' => 'Status GRN',            'type' => 'badge',    'default' => true],
                    ['key' => 'notes',             'label' => 'Keterangan Fisik',      'type' => 'text',     'default' => false],
                ];

            case 'payment_advices':
                return [
                    ['key' => 'invoice_number',    'label' => 'No. Invoice / Tagihan', 'type' => 'text',     'default' => true],
                    ['key' => 'po_number',         'label' => 'No. PO Terkait',        'type' => 'text',     'default' => true],
                    ['key' => 'supplier_name',     'label' => 'Supplier / Vendor',     'type' => 'text',     'default' => true],
                    ['key' => 'due_date',          'label' => 'Tanggal Jatuh Tempo',   'type' => 'date',     'default' => true],
                    ['key' => 'amount_to_pay',     'label' => 'Nominal Tagihan (IDR)', 'type' => 'currency', 'default' => true],
                    ['key' => 'payment_type',      'label' => 'Tipe Pembayaran (Termin)', 'type' => 'text',   'default' => true],
                    ['key' => 'pa_number',         'label' => 'No. Dokumen PA',        'type' => 'text',     'default' => false],
                    ['key' => 'status',            'label' => 'Status Pembayaran',     'type' => 'badge',    'default' => true],
                ];

            case 'work_items':
                return [
                    ['key' => 'budget_parent_name', 'label' => 'Budget Parent',        'type' => 'text',     'default' => true],
                    ['key' => 'sub_project_name',  'label' => 'Sub Project',           'type' => 'text',     'default' => true],
                    ['key' => 'wid_code',          'label' => 'Kode WID',              'type' => 'text',     'default' => true],
                    ['key' => 'wid_name',          'label' => 'Nama Pos Pekerjaan',    'type' => 'text',     'default' => true],
                    ['key' => 'allocated_budget',  'label' => 'Pagu Anggaran (IDR)',   'type' => 'currency', 'default' => true],
                    ['key' => 'realized_budget',   'label' => 'Realisasi Serapan (IDR)', 'type' => 'currency', 'default' => true],
                    ['key' => 'remaining_budget',  'label' => 'Sisa Anggaran (IDR)',   'type' => 'currency', 'default' => true],
                    ['key' => 'status',            'label' => 'Status Pos',            'type' => 'badge',    'default' => true],
                ];

            case 'stocks':
                return [
                    ['key' => 'product_code',      'label' => 'Kode Produk/Barang',    'type' => 'text',     'default' => true],
                    ['key' => 'product_name',      'label' => 'Nama Produk',           'type' => 'text',     'default' => true],
                    ['key' => 'category_name',     'label' => 'Kategori / Family',     'type' => 'text',     'default' => true],
                    ['key' => 'uom_name',          'label' => 'Satuan (UOM)',          'type' => 'text',     'default' => true],
                    ['key' => 'current_stock',     'label' => 'Stok Tersedia',         'type' => 'number',   'default' => true],
                    ['key' => 'min_stock',         'label' => 'Batas Min. Stok',       'type' => 'number',   'default' => false],
                    ['key' => 'warehouse_name',    'label' => 'Gudang / Lokasi',       'type' => 'text',     'default' => true],
                ];

            case 'employees':
                return [
                    ['key' => 'nik',               'label' => 'NIP / NIK Karyawan',    'type' => 'text',     'default' => true],
                    ['key' => 'name',              'label' => 'Nama Lengkap',          'type' => 'text',     'default' => true],
                    ['key' => 'department',        'label' => 'Divisi / Departemen',   'type' => 'text',     'default' => true],
                    ['key' => 'position',          'label' => 'Jabatan',               'type' => 'text',     'default' => true],
                    ['key' => 'employment_status', 'label' => 'Status Karyawan',       'type' => 'text',     'default' => true],
                    ['key' => 'email',             'label' => 'Email',                 'type' => 'text',     'default' => false],
                    ['key' => 'phone',             'label' => 'No. Telepon / WA',      'type' => 'text',     'default' => true],
                    ['key' => 'join_date',         'label' => 'Tanggal Bergabung',     'type' => 'date',     'default' => true],
                    ['key' => 'status',            'label' => 'Status Akun',           'type' => 'badge',    'default' => true],
                ];

            default:
                return [];
        }
    }
}
