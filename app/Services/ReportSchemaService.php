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
            'procurement_flow' => [
                'name'        => 'All-in-One: End-to-End Procurement Flow',
                'icon'        => 'bx-git-merge',
                'badge'       => 'Cross-Module (Joined)',
                'description' => 'Laporan gabungan lintas divisi: Budget WID ➡️ Request Form ➡️ Purchase Order ➡️ Penerimaan Gudang (GRN) ➡️ Invoice / Pembayaran Finance.',
                'date_fields' => [
                    'po_date'       => 'Tanggal PO',
                    'rf_date'       => 'Tanggal RF',
                    'grn_date'      => 'Tanggal Terima Gudang',
                    'due_date'      => 'Jatuh Tempo Invoice',
                    'created_at'    => 'Tanggal Dibuat',
                ],
                'default_date_field' => 'po_date',
            ],
            'purchase_orders' => [
                'name'        => 'Purchase Orders (PO)',
                'icon'        => 'bx-receipt',
                'badge'       => 'Procurement',
                'description' => 'Laporan transaksi Purchase Order lengkap dengan supplier, termin, nilai belanja, dan status approval.',
                'date_fields' => [
                    'date'          => 'Tanggal PO',
                    'created_at'    => 'Tanggal Dibuat',
                    'approved_date' => 'Tanggal Approved',
                ],
                'default_date_field' => 'date',
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
                    'date'          => 'Tanggal Penerimaan',
                    'created_at'    => 'Tanggal Dibuat',
                ],
                'default_date_field' => 'date',
            ],
            'payment_advices' => [
                'name'        => 'Payment Advices & Invoices (PA/SID)',
                'icon'        => 'bx-money',
                'badge'       => 'Finance & Accounting',
                'description' => 'Laporan tagihan supplier, jatuh tempo invoice, pengajuan pembayaran, dan realisasi disbursement.',
                'date_fields' => [
                    'due_date'     => 'Tanggal Jatuh Tempo',
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
            case 'procurement_flow':
                return [
                    // PO Fields
                    ['key' => 'po_no',                     'label' => '[PO] No. PO',                 'type' => 'text',     'default' => true],
                    ['key' => 'supplier_name',             'label' => '[PO] Supplier / Vendor',      'type' => 'text',     'default' => true],
                    ['key' => 'po_date',                   'label' => '[PO] Tanggal PO',             'type' => 'date',     'default' => true],
                    ['key' => 'total_po_amount_with_tax',  'label' => '[PO] Total Nilai PO (IDR)',   'type' => 'currency', 'default' => true],
                    ['key' => 'po_status',                 'label' => '[PO] Status PO',              'type' => 'badge',    'default' => true],
                    ['key' => 'payment_terms',             'label' => '[PO] Termin (TOP)',           'type' => 'text',     'default' => false],
                    
                    // RF Fields
                    ['key' => 'rf_no',                     'label' => '[RF] No. Request Form',       'type' => 'text',     'default' => true],
                    ['key' => 'rf_date',                   'label' => '[RF] Tanggal Pengajuan RF',   'type' => 'date',     'default' => false],
                    ['key' => 'requestor',                 'label' => '[RF] Pemohon (Requestor)',    'type' => 'text',     'default' => false],
                    ['key' => 'rf_type',                   'label' => '[RF] Tipe Pengajuan',         'type' => 'text',     'default' => false],

                    // Project & Budget Fields
                    ['key' => 'wid_code',                  'label' => '[Budget] Kode WID',           'type' => 'text',     'default' => true],
                    ['key' => 'wid_name',                  'label' => '[Budget] Pos Pekerjaan',      'type' => 'text',     'default' => false],
                    ['key' => 'allocated_budget',          'label' => '[Budget] Pagu Anggaran (IDR)','type' => 'currency', 'default' => false],
                    ['key' => 'remaining_budget',          'label' => '[Budget] Sisa Budget (IDR)',  'type' => 'currency', 'default' => false],

                    // GRN Fields
                    ['key' => 'do_no',                     'label' => '[Gudang] No. GRN',            'type' => 'text',     'default' => false],
                    ['key' => 'grn_date',                  'label' => '[Gudang] Tgl Terima Barang',  'type' => 'date',     'default' => false],
                    ['key' => 'supplier_do_no',            'label' => '[Gudang] No. Surat Jalan',    'type' => 'text',     'default' => false],
                    ['key' => 'receiving_contact',         'label' => '[Gudang] Penerima Gudang',    'type' => 'text',     'default' => false],
                    ['key' => 'grn_status',                'label' => '[Gudang] Status Penerimaan',  'type' => 'badge',    'default' => false],

                    // Finance Invoice Fields
                    ['key' => 'supplier_invoice_no',       'label' => '[Finance] No. Invoice Tagihan','type' => 'text',     'default' => false],
                    ['key' => 'due_date',                  'label' => '[Finance] Jatuh Tempo Bayar', 'type' => 'date',     'default' => false],
                    ['key' => 'total_invoice_amount_with_tax', 'label' => '[Finance] Tagihan + Pajak', 'type' => 'currency', 'default' => false],
                    ['key' => 'outstanding',               'label' => '[Finance] Sisa Tagihan (IDR)','type' => 'currency', 'default' => false],
                    ['key' => 'pa_status',                 'label' => '[Finance] Status Pembayaran', 'type' => 'badge',    'default' => false],
                ];

            case 'purchase_orders':
                return [
                    ['key' => 'po_no',                     'label' => 'PO: PO No',               'type' => 'text',     'default' => true],
                    ['key' => 'supplier_name',             'label' => 'Supplier Name',           'type' => 'text',     'default' => true],
                    ['key' => 'date',                      'label' => 'PO Date',                 'type' => 'date',     'default' => true],
                    ['key' => 'total_po_amount',           'label' => 'Total PO Amount (DPP)',   'type' => 'currency', 'default' => true],
                    ['key' => 'tax',                       'label' => 'Tax',                     'type' => 'currency', 'default' => true],
                    ['key' => 'total_po_amount_with_tax',  'label' => 'Total PO Amount With Tax', 'type' => 'currency', 'default' => true],
                    ['key' => 'balance_amount',            'label' => 'Balance Amount',          'type' => 'currency', 'default' => false],
                    ['key' => 'amount_paid',               'label' => 'Amount Paid',             'type' => 'currency', 'default' => false],
                    ['key' => 'status',                    'label' => 'Status',                  'type' => 'badge',    'default' => true],
                    ['key' => 'description',               'label' => 'Description',            'type' => 'text',     'default' => false],
                    ['key' => 'destination',               'label' => 'Destination',            'type' => 'text',     'default' => false],
                    ['key' => 'payment_terms',             'label' => 'Payment Terms',           'type' => 'text',     'default' => false],
                    ['key' => 'attention_to',              'label' => 'Attention To',            'type' => 'text',     'default' => false],
                    ['key' => 'invoice_to',                'label' => 'Invoice To',              'type' => 'text',     'default' => false],
                    ['key' => 'approved_date',             'label' => 'Approved Date',           'type' => 'date',     'default' => false],
                ];

            case 'request_forms':
                return [
                    ['key' => 'rf_no',             'label' => 'RF Number',             'type' => 'text',     'default' => true],
                    ['key' => 'rf_date',           'label' => 'Tanggal RF',            'type' => 'date',     'default' => true],
                    ['key' => 'requestor',         'label' => 'Pemohon (Requestor)',   'type' => 'text',     'default' => true],
                    ['key' => 'rf_type',           'label' => 'Tipe RF',               'type' => 'text',     'default' => true],
                    ['key' => 'work_item_name',    'label' => 'Alokasi Budget (WID)',  'type' => 'text',     'default' => true],
                    ['key' => 'total_amount',      'label' => 'Total Biaya (IDR)',     'type' => 'currency', 'default' => true],
                    ['key' => 'status',            'label' => 'Status Pengajuan',      'type' => 'badge',    'default' => true],
                    ['key' => 'remark',            'label' => 'Keperluan / Remark',    'type' => 'text',     'default' => false],
                ];

            case 'goods_receipts':
                return [
                    ['key' => 'do_no',             'label' => 'No. GRN (DO No)',       'type' => 'text',     'default' => true],
                    ['key' => 'date',              'label' => 'Tanggal Penerimaan',    'type' => 'date',     'default' => true],
                    ['key' => 'po_no',             'label' => 'No. PO Terkait',        'type' => 'text',     'default' => true],
                    ['key' => 'supplier_name',     'label' => 'Supplier / Vendor',     'type' => 'text',     'default' => true],
                    ['key' => 'supplier_do_no',    'label' => 'Surat Jalan Vendor',    'type' => 'text',     'default' => true],
                    ['key' => 'receiving_contact', 'label' => 'Penerima Barang',       'type' => 'text',     'default' => true],
                    ['key' => 'total_received_qty','label' => 'Total Qty Masuk',       'type' => 'number',   'default' => true],
                    ['key' => 'status',            'label' => 'Status GRN',            'type' => 'badge',    'default' => true],
                    ['key' => 'remarks',           'label' => 'Catatan Fisik',         'type' => 'text',     'default' => false],
                ];

            case 'payment_advices':
                return [
                    ['key' => 'supplier_invoice_no', 'label' => 'No. Invoice Supplier', 'type' => 'text',     'default' => true],
                    ['key' => 'po_no',               'label' => 'No. PO Terkait',      'type' => 'text',     'default' => true],
                    ['key' => 'supplier_name',       'label' => 'Supplier / Vendor',   'type' => 'text',     'default' => true],
                    ['key' => 'due_date',            'label' => 'Jatuh Tempo',         'type' => 'date',     'default' => true],
                    ['key' => 'total_invoice_amount','label' => 'Total Invoice (DPP)', 'type' => 'currency', 'default' => true],
                    ['key' => 'total_invoice_amount_with_tax', 'label' => 'Total Tagihan + PPN', 'type' => 'currency', 'default' => true],
                    ['key' => 'outstanding',         'label' => 'Sisa Tagihan (Outstanding)', 'type' => 'currency', 'default' => true],
                    ['key' => 'status',              'label' => 'Status PA',           'type' => 'badge',    'default' => true],
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
                ];

            case 'stocks':
                return [
                    ['key' => 'product_code',      'label' => 'Kode Produk/Barang',    'type' => 'text',     'default' => true],
                    ['key' => 'product_name',      'label' => 'Nama Produk',           'type' => 'text',     'default' => true],
                    ['key' => 'part_number',       'label' => 'Part Number',           'type' => 'text',     'default' => false],
                    ['key' => 'uom_name',          'label' => 'Satuan (UOM)',          'type' => 'text',     'default' => true],
                    ['key' => 'qty_on_hand',       'label' => 'Stok Tersedia',         'type' => 'number',   'default' => true],
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
