<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print External DO {{ $goodsReceipt->do_no }}</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .page { max-width: 800px; margin: 0 auto; background: white; padding: 40px; box-sizing: border-box; }
        .header { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        .logo { width: 150px; text-align: center; }
        .title { text-align: left; font-size: 18px; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; }
        
        .info-row { display: flex; margin-bottom: 3px; }
        .info-label { width: 150px; }
        .info-colon { width: 15px; }
        .info-value { flex: 1; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 30px; }
        th, td { border: 1px solid #000; padding: 8px; vertical-align: top; }
        th { background-color: #d9d9d9; text-align: center; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .signature-section { display: flex; justify-content: flex-start; margin-top: 50px; flex-direction: column;}
        .signature-line { border-bottom: 1px dotted #000; width: 200px; display: inline-block; margin-left: 10px; }
        .signature-row { margin-top: 20px;}

        @media print {
            body { padding: 0; background-color: #fff; }
            .page { padding: 0; width: 100%; max-width: 100%; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="logo">
                <!-- Assuming logo exists in public/assets/img/logo.png -->
                <img src="/assets/img/logo.png" alt="Mandau Logo" style="max-width: 150px;">
                <div style="font-size: 9px; color: #f37021; margin-top: 5px; font-weight: bold;">PT Mandiri Daya Utama Nusantara</div>
            </div>
        </div>

        <div class="title">TANDA TERIMA (GR) / RECEIPT FORM</div>

        <div class="info-row"><div class="info-label">NO. Tanda Terima/Received No</div><div class="info-colon">:</div><div class="info-value">{{ $goodsReceipt->do_no }}</div></div>
        <div class="info-row"><div class="info-label">PO No</div><div class="info-colon">:</div><div class="info-value">{{ $goodsReceipt->purchaseOrder?->po_no }}</div></div>
        <div class="info-row"><div class="info-label">Tanggal/Date</div><div class="info-colon">:</div><div class="info-value">{{ $goodsReceipt->date?->format('Y/m/d') }}</div></div>
        <div class="info-row"><div class="info-label">No. Surat Jalan/DO No.</div><div class="info-colon">:</div><div class="info-value">-</div></div>
        <div class="info-row"><div class="info-label">Supplier</div><div class="info-colon">:</div><div class="info-value">{{ $goodsReceipt->supplier?->name ?: '-' }}</div></div>
        <div class="info-row"><div class="info-label">Telah diterima dari/Received from</div><div class="info-colon">:</div><div class="info-value">{{ $goodsReceipt->sending_contact ?: '-' }}</div></div>
        <div class="info-row"><div class="info-label">Untuk/For:</div><div class="info-colon">:</div><div class="info-value">Fatmawati Blok C16-17</div></div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 45%;">Nama Barang/Description</th>
                    <th style="width: 20%;">Harga</th>
                    <th style="width: 15%;">Delivered Qty</th>
                    <th style="width: 15%;">Received Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($goodsReceipt->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->requestFormItem?->product_name }}<br>
                        {{ $item->requestFormItem?->erpProduct?->productModel?->model_name ?: '3' }}<br>
                        {{ $goodsReceipt->supplier?->name }}<br>
                        {{ $item->remark ?: 'Biaya Penghemat Telepon Periode Juni 2026' }}
                    </td>
                    <td class="text-right">
                        @if($item->purchaseOrderItem)
                            IDR {{ number_format((float)$item->purchaseOrderItem->unit_cost, 2, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">{{ number_format((float)$item->delivered_qty, 1) }}</td>
                    <td class="text-right">{{ number_format((float)$item->received_qty, 1) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signature-section">
            <div style="font-weight: bold;">{{ $goodsReceipt->verifiedBy?->name ?: 'Administrator' }}</div>
            <div>Diterima oleh / received by</div>
            <div>{{ $goodsReceipt->status_receive_date?->format('Y/m/d') ?: date('Y/m/d') }}</div>
            <div class="signature-row">
                Tanggal / date <span class="signature-line" style="width: 100px;"></span> 
                Tanda tangan / signature <span class="signature-line"></span>
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
