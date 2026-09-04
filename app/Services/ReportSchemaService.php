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
                'description' => 'Laporan gabungan komprehensif lintas divisi: Budget WID ➡️ Request Form ➡️ Purchase Order ➡️ Penerimaan Gudang (GRN) ➡️ Invoice / Pembayaran Finance.',
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
     * Definisi Kolom / Field untuk masing-masing Report Type (dengan Folder Group)
     */
    public static function getFields(string $reportType): array
    {
        switch ($reportType) {
            case 'procurement_flow':
                return [
                    // PO Fields
                    ['key' => 'po_no',                     'label' => 'No. Purchase Order (PO)',     'folder' => 'Purchase Order (PO)', 'type' => 'text',     'default' => true],
                    ['key' => 'supplier_name',             'label' => 'Nama Supplier / Vendor',      'folder' => 'Purchase Order (PO)', 'type' => 'text',     'default' => true],
                    ['key' => 'po_date',                   'label' => 'Tanggal Terbit PO',           'folder' => 'Purchase Order (PO)', 'type' => 'date',     'default' => true],
                    ['key' => 'total_po_amount_with_tax',  'label' => 'Total Nilai PO (+ PPN)',      'folder' => 'Purchase Order (PO)', 'type' => 'currency', 'default' => true],
                    ['key' => 'po_status',                 'label' => 'Status Approval PO',          'folder' => 'Purchase Order (PO)', 'type' => 'badge',    'default' => true],
                    ['key' => 'payment_terms',             'label' => 'Termin Pembayaran (TOP)',     'folder' => 'Purchase Order (PO)', 'type' => 'text',     'default' => false],
                    
                    // RF Fields
                    ['key' => 'rf_no',                     'label' => 'No. Request Form (RF)',       'folder' => 'Request Form (RF)',   'type' => 'text',     'default' => true],
                    ['key' => 'rf_date',                   'label' => 'Tanggal Pengajuan RF',        'folder' => 'Request Form (RF)',   'type' => 'date',     'default' => false],
                    ['key' => 'requestor',                 'label' => 'Pemohon (Requestor)',         'folder' => 'Request Form (RF)',   'type' => 'text',     'default' => false],
                    ['key' => 'rf_type',                   'label' => 'Tipe Pengajuan RF',           'folder' => 'Request Form (RF)',   'type' => 'text',     'default' => false],

                    // Project & Budget Fields
                    ['key' => 'wid_code',                  'label' => 'Kode Pos Anggaran (WID)',     'folder' => 'Project & Budget',    'type' => 'text',     'default' => true],
                    ['key' => 'wid_name',                  'label' => 'Nama Pos Pekerjaan',          'folder' => 'Project & Budget',    'type' => 'text',     'default' => false],
                    ['key' => 'allocated_budget',          'label' => 'Pagu Anggaran WID (IDR)',     'folder' => 'Project & Budget',    'type' => 'currency', 'default' => false],
                    ['key' => 'remaining_budget',          'label' => 'Sisa Budget WID (IDR)',       'folder' => 'Project & Budget',    'type' => 'currency', 'default' => false],

                    // GRN Fields
                    ['key' => 'do_no',                     'label' => 'No. Surat Masuk (GRN)',       'folder' => 'Logistik & Gudang',   'type' => 'text',     'default' => false],
                    ['key' => 'grn_date',                  'label' => 'Tanggal Barang Diterima',     'folder' => 'Logistik & Gudang',   'type' => 'date',     'default' => false],
                    ['key' => 'supplier_do_no',            'label' => 'No. Surat Jalan Vendor',      'folder' => 'Logistik & Gudang',   'type' => 'text',     'default' => false],
                    ['key' => 'receiving_contact',         'label' => 'Penerima di Gudang / Site',   'folder' => 'Logistik & Gudang',   'type' => 'text',     'default' => false],
                    ['key' => 'grn_status',                'label' => 'Status Penerimaan Gudang',    'folder' => 'Logistik & Gudang',   'type' => 'badge',    'default' => false],

                    // Finance Invoice Fields
                    ['key' => 'supplier_invoice_no',       'label' => 'No. Dokumen Tagihan (PA)',    'folder' => 'Finance & Tagihan',   'type' => 'text',     'default' => false],
                    ['key' => 'due_date',                  'label' => 'Tanggal Jatuh Tempo Bayar',   'folder' => 'Finance & Tagihan',   'type' => 'date',     'default' => false],
                    ['key' => 'total_invoice_amount_with_tax', 'label' => 'Total Tagihan Supplier',  'folder' => 'Finance & Tagihan',   'type' => 'currency', 'default' => false],
                    ['key' => 'outstanding',               'label' => 'Sisa Tagihan Belum Dibayar',  'folder' => 'Finance & Tagihan',   'type' => 'currency', 'default' => false],
                    ['key' => 'pa_status',                 'label' => 'Status Pembayaran Finance',   'folder' => 'Finance & Tagihan',   'type' => 'badge',    'default' => false],
                ];

            case 'purchase_orders':
                return [
                    ['key' => 'po_no',                     'label' => 'PO: PO No',               'folder' => 'PO: Info', 'type' => 'text',     'default' => true],
                    ['key' => 'supplier_name',             'label' => 'Supplier Name',           'folder' => 'PO: Info', 'type' => 'text',     'default' => true],
                    ['key' => 'date',                      'label' => 'PO Date',                 'folder' => 'PO: Info', 'type' => 'date',     'default' => true],
                    ['key' => 'total_po_amount',           'label' => 'Total PO Amount (DPP)',   'folder' => 'PO: Financial', 'type' => 'currency', 'default' => true],
                    ['key' => 'tax',                       'label' => 'Tax Amount',              'folder' => 'PO: Financial', 'type' => 'currency', 'default' => true],
                    ['key' => 'total_po_amount_with_tax',  'label' => 'Total PO Amount With Tax', 'folder' => 'PO: Financial', 'type' => 'currency', 'default' => true],
                    ['key' => 'balance_amount',            'label' => 'Balance Amount',          'folder' => 'PO: Financial', 'type' => 'currency', 'default' => false],
                    ['key' => 'amount_paid',               'label' => 'Amount Paid',             'folder' => 'PO: Financial', 'type' => 'currency', 'default' => false],
                    ['key' => 'status',                    'label' => 'Status PO',               'folder' => 'PO: Info', 'type' => 'badge',    'default' => true],
                    ['key' => 'description',               'label' => 'Description / Catatan',   'folder' => 'PO: Info', 'type' => 'text',     'default' => false],
                    ['key' => 'destination',               'label' => 'Destination Gudang/Site', 'folder' => 'PO: Logistics', 'type' => 'text',     'default' => false],
                    ['key' => 'payment_terms',             'label' => 'Payment Terms (TOP)',     'folder' => 'PO: Financial', 'type' => 'text',     'default' => false],
                    ['key' => 'attention_to',              'label' => 'Attention To',            'folder' => 'PO: Contact', 'type' => 'text',     'default' => false],
                    ['key' => 'invoice_to',                'label' => 'Invoice To PT',           'folder' => 'PO: Contact', 'type' => 'text',     'default' => false],
                    ['key' => 'approved_date',             'label' => 'Approved Date',           'folder' => 'PO: Info', 'type' => 'date',     'default' => false],
                ];

            case 'request_forms':
                return [
                    ['key' => 'rf_no',             'label' => 'RF Number',             'folder' => 'RF: Info', 'type' => 'text',     'default' => true],
                    ['key' => 'rf_date',           'label' => 'Tanggal RF',            'folder' => 'RF: Info', 'type' => 'date',     'default' => true],
                    ['key' => 'requestor',         'label' => 'Pemohon (Requestor)',   'folder' => 'RF: Info', 'type' => 'text',     'default' => true],
                    ['key' => 'rf_type',           'label' => 'Tipe RF',               'folder' => 'RF: Info', 'type' => 'text',     'default' => true],
                    ['key' => 'work_item_name',    'label' => 'Alokasi Budget (WID)',  'folder' => 'RF: Budget', 'type' => 'text',     'default' => true],
                    ['key' => 'total_amount',      'label' => 'Total Biaya (IDR)',     'folder' => 'RF: Budget', 'type' => 'currency', 'default' => true],
                    ['key' => 'status',            'label' => 'Status Pengajuan',      'folder' => 'RF: Info', 'type' => 'badge',    'default' => true],
                    ['key' => 'remark',            'label' => 'Keperluan / Remark',    'folder' => 'RF: Info', 'type' => 'text',     'default' => false],
                ];

            case 'goods_receipts':
                return [
                    ['key' => 'do_no',             'label' => 'No. GRN (DO No)',       'folder' => 'GRN: Info', 'type' => 'text',     'default' => true],
                    ['key' => 'date',              'label' => 'Tanggal Penerimaan',    'folder' => 'GRN: Info', 'type' => 'date',     'default' => true],
                    ['key' => 'po_no',             'label' => 'No. PO Terkait',        'folder' => 'GRN: PO',   'type' => 'text',     'default' => true],
                    ['key' => 'supplier_name',     'label' => 'Supplier / Vendor',     'folder' => 'GRN: PO',   'type' => 'text',     'default' => true],
                    ['key' => 'supplier_do_no',    'label' => 'Surat Jalan Vendor',    'folder' => 'GRN: Info', 'type' => 'text',     'default' => true],
                    ['key' => 'receiving_contact', 'label' => 'Penerima Barang',       'folder' => 'GRN: Info', 'type' => 'text',     'default' => true],
                    ['key' => 'total_received_qty','label' => 'Total Qty Masuk',       'folder' => 'GRN: Qty',  'type' => 'number',   'default' => true],
                    ['key' => 'status',            'label' => 'Status GRN',            'folder' => 'GRN: Info', 'type' => 'badge',    'default' => true],
                    ['key' => 'remarks',           'label' => 'Catatan Fisik',         'folder' => 'GRN: Info', 'type' => 'text',     'default' => false],
                ];

            case 'payment_advices':
                return [
                    ['key' => 'supplier_invoice_no', 'label' => 'No. Invoice Supplier', 'folder' => 'PA: Info', 'type' => 'text',     'default' => true],
                    ['key' => 'po_no',               'label' => 'No. PO Terkait',      'folder' => 'PA: PO',   'type' => 'text',     'default' => true],
                    ['key' => 'supplier_name',       'label' => 'Supplier / Vendor',   'folder' => 'PA: PO',   'type' => 'text',     'default' => true],
                    ['key' => 'due_date',            'label' => 'Jatuh Tempo',         'folder' => 'PA: Date', 'type' => 'date',     'default' => true],
                    ['key' => 'total_invoice_amount','label' => 'Total Invoice (DPP)', 'folder' => 'PA: Amount', 'type' => 'currency', 'default' => true],
                    ['key' => 'total_invoice_amount_with_tax', 'label' => 'Total Tagihan + PPN', 'folder' => 'PA: Amount', 'type' => 'currency', 'default' => true],
                    ['key' => 'outstanding',         'label' => 'Sisa Tagihan (Outstanding)', 'folder' => 'PA: Amount', 'type' => 'currency', 'default' => true],
                    ['key' => 'status',              'label' => 'Status PA',           'folder' => 'PA: Info', 'type' => 'badge',    'default' => true],
                ];

            case 'work_items':
                return [
                    ['key' => 'budget_parent_name', 'label' => 'Budget Parent',        'folder' => 'Budget: Parent', 'type' => 'text',     'default' => true],
                    ['key' => 'sub_project_name',  'label' => 'Sub Project',           'folder' => 'Budget: Parent', 'type' => 'text',     'default' => true],
                    ['key' => 'wid_code',          'label' => 'Kode WID',              'folder' => 'WID: Info',      'type' => 'text',     'default' => true],
                    ['key' => 'wid_name',          'label' => 'Nama Pos Pekerjaan',    'folder' => 'WID: Info',      'type' => 'text',     'default' => true],
                    ['key' => 'allocated_budget',  'label' => 'Pagu Anggaran (IDR)',   'folder' => 'WID: Budget',    'type' => 'currency', 'default' => true],
                    ['key' => 'realized_budget',   'label' => 'Realisasi Serapan (IDR)', 'folder' => 'WID: Budget',    'type' => 'currency', 'default' => true],
                    ['key' => 'remaining_budget',  'label' => 'Sisa Anggaran (IDR)',   'folder' => 'WID: Budget',    'type' => 'currency', 'default' => true],
                ];

            case 'stocks':
                return [
                    ['key' => 'product_code',      'label' => 'Kode Produk/Barang',    'folder' => 'Product: Info', 'type' => 'text',     'default' => true],
                    ['key' => 'product_name',      'label' => 'Nama Produk',           'folder' => 'Product: Info', 'type' => 'text',     'default' => true],
                    ['key' => 'part_number',       'label' => 'Part Number',           'folder' => 'Product: Info', 'type' => 'text',     'default' => false],
                    ['key' => 'uom_name',          'label' => 'Satuan (UOM)',          'folder' => 'Product: Unit', 'type' => 'text',     'default' => true],
                    ['key' => 'qty_on_hand',       'label' => 'Stok Tersedia',         'folder' => 'Stock: Qty',    'type' => 'number',   'default' => true],
                    ['key' => 'warehouse_name',    'label' => 'Gudang / Lokasi',       'folder' => 'Stock: Warehouse', 'type' => 'text',     'default' => true],
                ];

            case 'employees':
                return [
                    ['key' => 'nik',               'label' => 'NIP / NIK Karyawan',    'folder' => 'HR: Identity', 'type' => 'text',     'default' => true],
                    ['key' => 'name',              'label' => 'Nama Lengkap',          'folder' => 'HR: Identity', 'type' => 'text',     'default' => true],
                    ['key' => 'department',        'label' => 'Divisi / Departemen',   'folder' => 'HR: Position', 'type' => 'text',     'default' => true],
                    ['key' => 'position',          'label' => 'Jabatan',               'folder' => 'HR: Position', 'type' => 'text',     'default' => true],
                    ['key' => 'employment_status', 'label' => 'Status Karyawan',       'folder' => 'HR: Position', 'type' => 'text',     'default' => true],
                    ['key' => 'email',             'label' => 'Email',                 'folder' => 'HR: Contact',  'type' => 'text',     'default' => false],
                    ['key' => 'phone',             'label' => 'No. Telepon / WA',      'folder' => 'HR: Contact',  'type' => 'text',     'default' => true],
                    ['key' => 'join_date',         'label' => 'Tanggal Bergabung',     'folder' => 'HR: Identity', 'type' => 'date',     'default' => true],
                    ['key' => 'status',            'label' => 'Status Akun',           'folder' => 'HR: Identity', 'type' => 'badge',    'default' => true],
                ];

            default:
                return [];
        }
    }
}
