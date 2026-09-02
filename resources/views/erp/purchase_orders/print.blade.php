<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $purchaseOrder->po_no }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <style>
        :root {
            --primary-color: #f37021;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #334155;
            --table-border: #1e293b;
            --header-bg: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #0f172a;
            background-color: #f1f5f9;
            line-height: 1.4;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Top Action Bar (Screen Only) */
        .no-print-bar {
            background: #1e293b;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .no-print-bar .doc-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s;
        }

        .btn-print {
            background: #f37021;
            color: #fff;
        }
        .btn-print:hover {
            background: #d95a10;
        }

        .btn-back {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }
        .btn-back:hover {
            background: rgba(255,255,255,0.25);
        }

        /* Printable Page Sheet */
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #ffffff;
            padding: 18mm 18mm 15mm 18mm;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative;
            box-sizing: border-box;
        }

        /* Header Area */
        .po-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .po-title {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #0f172a;
            font-family: 'Space Grotesk', sans-serif;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .company-brand {
            text-align: right;
        }

        .company-logo {
            max-height: 50px;
            width: auto;
            object-fit: contain;
            margin-bottom: 3px;
        }

        .company-name {
            font-size: 9.5px;
            font-weight: 700;
            color: #f37021;
            letter-spacing: 0.2px;
        }

        /* Metadata Two Columns */
        .meta-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            gap: 20px;
        }

        .meta-col {
            flex: 1;
        }

        .meta-col-right {
            max-width: 260px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 11px;
        }

        .meta-label {
            font-weight: 700;
            color: #0f172a;
            width: 85px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .meta-colon {
            width: 14px;
            font-weight: 700;
            text-align: center;
        }

        .meta-value {
            color: #1e293b;
            font-weight: 500;
        }

        /* 3-Column Mid Block */
        .block-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border: 1.5px solid #0f172a;
        }

        .block-table th {
            background-color: #cbd5e1;
            color: #0f172a;
            font-weight: 800;
            text-align: center;
            padding: 5px 8px;
            font-size: 11px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border: 1.5px solid #0f172a;
        }

        .block-table td {
            border: 1.5px solid #0f172a;
            padding: 8px 10px;
            vertical-align: top;
            font-size: 10.5px;
            line-height: 1.45;
        }

        /* Items Main Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            border: 1.5px solid #0f172a;
        }

        .items-table th {
            background-color: #cbd5e1;
            color: #0f172a;
            font-weight: 800;
            text-align: center;
            padding: 6px 6px;
            font-size: 11px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            border: 1.5px solid #0f172a;
        }

        .items-table td {
            border: 1.5px solid #0f172a;
            padding: 6px 8px;
            vertical-align: top;
            font-size: 10.5px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: 700; }

        /* Bottom Invoice & Summary Box */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #0f172a;
            border-top: none;
            margin-bottom: 35px;
        }

        .summary-table td {
            border: 1.5px solid #0f172a;
            padding: 8px 10px;
            vertical-align: top;
        }

        .invoice-details-box {
            font-size: 10.5px;
            line-height: 1.45;
        }

        .invoice-details-title {
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }

        .calc-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 3px 0;
            font-size: 11px;
        }

        .calc-label {
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .calc-value {
            font-weight: 800;
            font-size: 11.5px;
            text-align: right;
        }

        /* Signatures Section */
        .signatures-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding: 0 10px;
        }

        .sig-block {
            width: 42%;
        }

        .sig-title {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.3px;
            margin-bottom: 50px;
        }

        .sig-line-wrapper {
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 3px;
            text-align: center;
        }

        .sig-name {
            font-weight: 700;
            font-size: 11px;
            color: #0f172a;
        }

        /* Print Media Settings */
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .sheet {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border: none;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 12mm 10mm 12mm;
            }
        }
    </style>
</head>
<body>

    <!-- SCREEN ONLY TOP BAR -->
    <div class="no-print-bar">
        <div class="doc-info">
            <i class="bx bx-receipt text-warning" style="font-size: 20px;"></i>
            <span>PURCHASE ORDER: {{ $purchaseOrder->po_no }}</span>
            <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                Status: {{ $purchaseOrder->status }}
            </span>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('erp.purchase-orders.show', $purchaseOrder) }}" class="btn-action btn-back">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
            <button onclick="window.print()" class="btn-action btn-print">
                <i class="bx bx-printer"></i> Cetak / Print PO
            </button>
        </div>
    </div>

    <!-- PRINTABLE SHEET -->
    <div class="sheet">
        
        <!-- HEADER -->
        <div class="po-header">
            <div class="po-title">PURCHASE ORDER</div>
            <div class="company-brand">
                <img src="{{ asset('ImageAsset/logo-mandau.png') }}" alt="Mandau" class="company-logo" onerror="this.style.display='none'">
                <div class="company-name">PT Mandiri Daya Utama Nusantara</div>
            </div>
        </div>

        <!-- METADATA 2 COLS -->
        <div class="meta-container">
            <div class="meta-col">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">VENDOR</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-value"><strong>{{ $purchaseOrder->supplier?->name ?: '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="meta-label">P.I.C.</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-value">{{ $purchaseOrder->contact_person ?: ($purchaseOrder->supplier?->pic ?: ($purchaseOrder->supplier?->contact_person ?: '-')) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">EMAIL</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-value">{{ $purchaseOrder->supplier?->email ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">ADDRESS</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-value">{{ $purchaseOrder->address ?: ($purchaseOrder->supplier?->address ?: '-') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">PHONE</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-value">{{ $purchaseOrder->phone ?: ($purchaseOrder->supplier?->phone ?: '-') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">FAX</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-value">{{ $purchaseOrder->fax ?: ($purchaseOrder->supplier?->fax ?: '-') }}</td>
                    </tr>
                </table>
            </div>

            <div class="meta-col meta-col-right">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">NUMBER</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-value"><strong>{{ $purchaseOrder->po_no }}</strong></td>
                    </tr>
                    <tr>
                        <td class="meta-label">DATE</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-value">{{ $purchaseOrder->date ? $purchaseOrder->date->format('Y/m/d') : date('Y/m/d') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">ETA</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-value">{{ $purchaseOrder->eta ? \Carbon\Carbon::parse($purchaseOrder->eta)->format('Y/m/d') : ($purchaseOrder->date ? $purchaseOrder->date->format('Y/m/d') : date('Y/m/d')) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">PAGE</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-value">1 of 1</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- 3-COLUMNS MID TABLE -->
        <table class="block-table">
            <thead>
                <tr>
                    <th style="width: 34%;">DESTINATION</th>
                    <th style="width: 33%;">PAYMENT DETAILS</th>
                    <th style="width: 33%;">OTHER INSTRUCTIONS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $purchaseOrder->destination ?: ($purchaseOrder->warehouse?->name ?: 'PT. Mandiri Daya Utama Nusantara') }}</strong><br>
                        {{ $purchaseOrder->warehouse?->address ?: 'Komp Golden Plaza Jl. RS Fatmawati No.15, Blok C 16 - 17' }}<br>
                        Jakarta Selatan - 12420<br>
                        Ph. : {{ $purchaseOrder->warehouse?->phone ?: '+6221-7599 7945' }}<br>
                        Fax : {{ $purchaseOrder->warehouse?->fax ?: '+6221-7599 9888' }}
                    </td>
                    <td>
                        {{ $purchaseOrder->payment_terms ?: '100% after received invoice dan dokumen complete' }}<br>
                        Bank Transfer<br>
                        @if($purchaseOrder->supplier?->bank_name || $purchaseOrder->supplier?->bank_account)
                            {{ $purchaseOrder->supplier?->bank_name }} {{ $purchaseOrder->supplier?->bank_account }}
                        @else
                            VA BCA 00112112099300 PT Badan Mandiri Daya
                        @endif
                    </td>
                    <td>
                        {{ $purchaseOrder->other_instructions ?: ($purchaseOrder->remarks ?: '-') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- ITEMS TABLE -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">NO.</th>
                    <th style="width: 22%;">ITEM</th>
                    <th style="width: 31%;">DESCRIPTION</th>
                    <th style="width: 10%;">QUANTITY</th>
                    <th style="width: 8%;">UOM</th>
                    <th style="width: 12%;">UNIT PRICE</th>
                    <th style="width: 12%;">TOTAL PRICE</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrder->items as $index => $item)
                    @php
                        $uomName = $item->uom ?: ($item->requestFormItem?->erpProduct?->uom?->name ?: 'Unit');
                        $unitCost = (float)($item->unit_cost ?? 0);
                        $totalCost = (float)($item->total_cost ?? ($unitCost * (float)($item->qty ?? 1)));
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}.</td>
                        <td class="text-bold">{{ $item->requestFormItem?->product_name ?: ($item->product_name ?: 'Item ' . ($index+1)) }}</td>
                        <td>{{ $item->requestFormItem?->product_description ?: ($item->remarks ?: ($item->description ?: '-')) }}</td>
                        <td class="text-center text-bold">{{ number_format((float)$item->qty, 2, '.', '') }}</td>
                        <td class="text-center">{{ $uomName }}</td>
                        <td class="text-right">
                            <span style="float: left; font-size: 9px; color: #475569;">IDR</span>
                            {{ number_format($unitCost, 0, ',', '.') }}
                        </td>
                        <td class="text-right text-bold">
                            <span style="float: left; font-size: 9px; color: #475569;">IDR</span>
                            {{ number_format($totalCost, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 20px; color: #64748b;">Tidak ada rincian item PO.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- INVOICE DETAILS & TOTAL FOOTER -->
        <table class="summary-table">
            <tr>
                <td style="width: 58%; vertical-align: top;">
                    <div class="invoice-details-box">
                        <div class="invoice-details-title">PROJECT CODE : {{ $purchaseOrder->requestForm?->workItem?->code ?: ($purchaseOrder->requestForm?->project_code ?: ($purchaseOrder->project_code ?: '-')) }}</div>
                        <div class="invoice-details-title" style="margin-top: 6px;">INVOICE DETAILS</div>
                        <div>INVOICE TO : PT. Mandiri Daya Utama Nusantara</div>
                        <div>ATTENTION : {{ $purchaseOrder->attention_to ?: 'Mandau' }}</div>
                        <div>Golden Fatmawati</div>
                        <div>Jl. RS Fatmawati No. 15 Blok C17</div>
                        <div>Jakarta 12420, Indonesia</div>
                        <div>Ph : +6221-7590 9945 &nbsp;|&nbsp; Fax : +6221-7590 9133</div>
                    </div>
                </td>
                <td style="width: 42%; vertical-align: middle; background-color: #f8fafc; padding: 12px 14px;">
                    <div class="calc-row" style="border-bottom: 1px dashed #cbd5e1; padding-bottom: 6px; margin-bottom: 6px;">
                        <span class="calc-label">SUB TOTAL</span>
                        <span class="calc-value">
                            <span style="font-size: 9px; font-weight: normal; color: #64748b; margin-right: 4px;">IDR</span>
                            {{ number_format((float)$purchaseOrder->total_po_amount, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="calc-row" style="padding-top: 4px;">
                        <span class="calc-label" style="font-size: 13px; color: #0f172a;">TOTAL</span>
                        <span class="calc-value" style="font-size: 14px; color: #0f172a;">
                            <span style="font-size: 10px; font-weight: normal; color: #64748b; margin-right: 4px;">IDR</span>
                            {{ number_format((float)($purchaseOrder->total_po_amount_with_tax ?: $purchaseOrder->total_po_amount), 0, ',', '.') }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- SIGNATURES -->
        <div class="signatures-container">
            <div class="sig-block">
                <div class="sig-title">VENDOR APPROVAL :</div>
                <div class="sig-line-wrapper">
                    <div class="sig-name" style="visibility: hidden;">-</div>
                </div>
            </div>

            <div class="sig-block">
                <div class="sig-title">MANDAU APPROVAL :</div>
                <div class="sig-line-wrapper">
                    @php
                        $approver = $purchaseOrder->signature ?: 'Barry Japadarmawan';
                    @endphp
                    <div class="sig-name">{{ $approver }}</div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>

