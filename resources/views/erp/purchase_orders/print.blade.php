<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order {{ $purchaseOrder->po_no }}</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11px; margin: 0; padding: 20px; }
        .page { max-width: 800px; margin: 0 auto; background: white; padding: 40px; box-sizing: border-box; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
        .logo { width: 150px; }
        .title { text-align: left; font-size: 20px; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; }
        
        .info-grid { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-left, .info-right { width: 48%; }
        .info-row { display: flex; margin-bottom: 3px; }
        .info-label { width: 80px; font-weight: bold; }
        .info-colon { width: 15px; }
        .info-value { flex: 1; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 5px; vertical-align: top; }
        th { background-color: #f0f0f0; text-align: center; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        .signature-section { display: flex; justify-content: space-between; margin-top: 50px; }
        .signature-box { width: 40%; text-align: left; }
        .signature-line { border-bottom: 1px solid #000; margin-top: 50px; width: 80%; display: inline-block; margin-bottom: 5px; }

        @media print {
            body { padding: 0; background-color: #fff; }
            .page { padding: 0; width: 100%; max-width: 100%; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="title">PURCHASE ORDER</div>
            <div class="logo">
                <!-- Assuming logo exists in public/assets/img/logo.png -->
                <img src="/assets/img/logo.png" alt="Mandau Logo" style="max-width: 150px;">
                <div style="font-size: 9px; color: #f37021; margin-top: 5px;">PT Mandiri Daya Utama Nusantara</div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-left">
                <div class="info-row"><div class="info-label">VENDOR</div><div class="info-colon">:</div><div class="info-value">{{ $purchaseOrder->supplier?->name }}</div></div>
                <div class="info-row"><div class="info-label">P.I.C.</div><div class="info-colon">:</div><div class="info-value">{{ $purchaseOrder->supplier?->pic ?: '-' }}</div></div>
                <div class="info-row"><div class="info-label">EMAIL</div><div class="info-colon">:</div><div class="info-value">{{ $purchaseOrder->supplier?->email ?: '-' }}</div></div>
                <div class="info-row"><div class="info-label">ADDRESS</div><div class="info-colon">:</div><div class="info-value">{{ $purchaseOrder->address }}</div></div>
                <div class="info-row"><div class="info-label">PHONE</div><div class="info-colon">:</div><div class="info-value">{{ $purchaseOrder->supplier?->phone ?: '-' }}</div></div>
            </div>
            <div class="info-right">
                <div class="info-row"><div class="info-label">NUMBER</div><div class="info-colon">:</div><div class="info-value">{{ $purchaseOrder->po_no }}</div></div>
                <div class="info-row"><div class="info-label">DATE</div><div class="info-colon">:</div><div class="info-value">{{ $purchaseOrder->date?->format('Y/m/d') }}</div></div>
                <div class="info-row"><div class="info-label">PAGE</div><div class="info-colon">:</div><div class="info-value">1 of 1</div></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 33%;">DESTINATION</th>
                    <th style="width: 33%;">PAYMENT DETAILS</th>
                    <th style="width: 34%;">OTHER INSTRUCTIONS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $purchaseOrder->warehouse?->name ?: 'PT. Mandiri Daya Utama' }}</td>
                    <td>{{ $purchaseOrder->payment_terms ?? '100% after received invoice dan dokumen complete' }}</td>
                    <td>{{ $purchaseOrder->other_instructions ?? '-' }}</td>
                </tr>
            </tbody>
        </table>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">NO.</th>
                    <th style="width: 15%;">ITEM</th>
                    <th style="width: 30%;">DESCRIPTION</th>
                    <th style="width: 10%;">QUANTITY</th>
                    <th style="width: 10%;">UOM</th>
                    <th style="width: 15%;">UNIT PRICE</th>
                    <th style="width: 15%;">TOTAL PRICE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->requestFormItem?->product_name }}</td>
                    <td>{{ $item->requestFormItem?->product_description }}</td>
                    <td class="text-center">{{ number_format((float)$item->qty, 2) }}</td>
                    <td class="text-center">{{ $item->requestFormItem?->erpProduct?->uom?->name ?: '-' }}</td>
                    <td class="text-right">IDR {{ number_format((float)$item->unit_cost, 2, ',', '.') }}</td>
                    <td class="text-right">IDR {{ number_format((float)$item->total_cost, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="border-right: none; vertical-align: top;">
                        <div class="text-bold">INVOICE DETAILS</div><br>
                        INVOICE TO : PT. Mandiri Daya Utama<br>
                        ATTENTION : Mandau<br>
                        Golden Fatmawati<br>
                        Jl. RS Fatmawati No. 15<br>
                        Blok C17<br>
                        Jakarta 12420, Indonesia
                    </td>
                    <td colspan="2" class="text-bold text-right" style="border-left: none; vertical-align: bottom; padding-bottom: 10px;">
                        SUB TOTAL<br><br>TOTAL
                    </td>
                    <td class="text-right text-bold" style="vertical-align: bottom; padding-bottom: 10px;">
                        IDR {{ number_format((float)$purchaseOrder->total_po_amount, 2, ',', '.') }}<br><br>
                        IDR {{ number_format((float)$purchaseOrder->total_po_amount_with_tax, 2, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="signature-section">
            <div class="signature-box">
                <div>VENDOR APPROVAL :</div>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <div>MANDAU APPROVAL :</div>
                <div class="signature-line">
                    <div style="text-align: center; margin-bottom: -15px;">{{ $purchaseOrder->approvals->last()?->actualApprover?->name ?: 'Administrator' }}</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
