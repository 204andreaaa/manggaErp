@extends('layouts.home')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold py-1 mb-0">
      <span class="text-muted fw-light">Procurement ERP / Payment Advice /</span> New Payment Advice
    </h4>
    <a href="{{ route('erp.payment-advices.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bx bx-arrow-back me-1"></i>Kembali
    </a>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="mb-0 fw-bold"><i class="bx bx-receipt me-2 text-primary"></i>Form Pengajuan Payment Advice / Supplier Invoice</h6>
    </div>
    <div class="card-body mt-3">
      <form action="{{ route('erp.payment-advices.store') }}" method="POST">
        @csrf

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Purchase Order (PO) <span class="text-danger">*</span></label>
            @if($purchaseOrder)
              <input type="hidden" name="erp_purchase_order_id" value="{{ $purchaseOrder->id }}">
              @if($goodsReceipt)
                <input type="hidden" name="erp_goods_receipt_id" value="{{ $goodsReceipt->id }}">
              @endif
              <div class="form-control bg-light fw-bold text-primary d-flex align-items-center">
                <i class="bx bx-check-circle me-2 text-success" style="font-size: 1.2rem;"></i>
                <span>{{ $purchaseOrder->po_no }} - {{ $purchaseOrder->supplier?->name }} (Total: IDR {{ number_format($purchaseOrder->total_po_amount_with_tax, 0, ',', '.') }})</span>
              </div>
            @else
              <select name="erp_purchase_order_id" class="form-select @error('erp_purchase_order_id') is-invalid @enderror" required>
                <option value="">-- Pilih Purchase Order --</option>
                @foreach(\App\Models\Erp\ErpPurchaseOrder::whereIn('status', ['Approved', 'Completed'])->orderBy('po_no', 'desc')->get() as $po)
                  <option value="{{ $po->id }}" {{ (old('erp_purchase_order_id', $purchaseOrder?->id) == $po->id) ? 'selected' : '' }}>
                    {{ $po->po_no }} - {{ $po->supplier?->name }} (Total: IDR {{ number_format($po->total_po_amount_with_tax, 0, ',', '.') }})
                  </option>
                @endforeach
              </select>
              @error('erp_purchase_order_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            @endif
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Nomor Invoice Supplier <span class="text-danger">*</span></label>
            <input type="text" name="invoice_no" class="form-control @error('invoice_no') is-invalid @enderror" value="{{ old('invoice_no') }}" placeholder="Contoh: 0317/JL/UTM/1127" required>
            @error('invoice_no')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Contact Person / Penanggung Jawab</label>
            <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $purchaseOrder?->contact_person) }}" placeholder="Nama Kontak Supplier">
          </div>

          <div class="col-md-6">
            <label class="form-label">Tanggal Jatuh Tempo (Due Date)</label>
            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Total Nilai Tagihan Keseluruhan (IDR) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light">IDR</span>
              <input type="number" step="0.01" name="total_invoice_amount" class="form-control bg-light @error('total_invoice_amount') is-invalid @enderror" value="{{ old('total_invoice_amount', $purchaseOrder?->total_po_amount_with_tax) }}" readonly required>
            </div>
            <div class="form-text text-muted"><i class="bx bx-lock-alt me-1"></i>Terkunci otomatis sesuai total nilai PO.</div>
            @error('total_invoice_amount')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Nominal Pembayaran Termin 1 (IDR) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">IDR</span>
              <input type="number" step="0.01" name="initial_payment_amount" class="form-control @error('initial_payment_amount') is-invalid @enderror" value="{{ old('initial_payment_amount', $purchaseOrder?->total_po_amount_with_tax) }}" placeholder="9000000" required>
            </div>
            <div class="form-text">Nominal pembayaran untuk termin pertama ini (misal DP 1).</div>
            @error('initial_payment_amount')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Jenis Pembayaran (Payment Type) <span class="text-danger">*</span></label>
            <select name="payment_type" class="form-select" required>
              <option value="Final Payment (Pelunasan 100%)">Final Payment (Pelunasan 100%)</option>
              <option value="Partial Payment / DP (Cicilan / Bertahap)">Partial Payment / DP (Cicilan / Bertahap)</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Metode Pembayaran <span class="text-danger">*</span></label>
            <select name="payment_method" class="form-select" required>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Cash">Cash</option>
              <option value="Cheque">Cheque</option>
              <option value="Credit Card">Credit Card</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Keterangan / Remark</label>
            <textarea name="remark" class="form-control" rows="3" placeholder="Catatan pembayaran..."></textarea>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
          <a href="{{ route('erp.payment-advices.index') }}" class="btn btn-label-secondary">Batal</a>
          <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan Payment Advice</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
