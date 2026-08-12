@extends('layouts.home')

@section('title', 'Create Goods Receipt (DO)')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Header & Breadcrumb --}}
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1 fw-bold text-dark"><i class="bx bx-package text-primary me-2"></i>Create Goods Receipt (DO)</h4>
      <div class="text-muted small">Reference PO No: <span class="badge bg-label-primary fs-7">{{ $purchaseOrder->po_no }}</span></div>
    </div>
    <a href="{{ route('erp.purchase-orders.show', $purchaseOrder) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
      <i class="bx bx-arrow-back me-1"></i>Back to PO Details
    </a>
  </div>

  @if(session('error'))
    <div class="alert alert-danger shadow-sm border-0 alert-dismissible mb-4" role="alert">
      <div class="d-flex align-items-center">
        <i class="bx bx-error-circle me-2 fs-4"></i>
        <div>{{ session('error') }}</div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger shadow-sm border-0 mb-4 rounded-3">
      <div class="fw-bold mb-1"><i class="bx bx-error-circle me-1"></i>Please check the required fields:</div>
      <ul class="mb-0 small ps-3">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form action="{{ route('erp.goods-receipts.store', $purchaseOrder) }}" method="POST" class="card shadow-sm border-0 rounded-4 overflow-hidden">
    @csrf

    {{-- Top Header Banner --}}
    <div class="card-header bg-primary bg-opacity-10 py-3 border-bottom">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <span class="text-uppercase fw-bold text-primary small">Reference Purchase Order</span>
          <h4 class="mb-0 fw-extrabold text-primary">{{ $purchaseOrder->po_no }}</h4>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-success px-3 py-2 fs-7">{{ $purchaseOrder->status }}</span>
          <span class="badge bg-label-info px-3 py-2 fs-7">Supplier: {{ $purchaseOrder->supplier?->name ?: '-' }}</span>
        </div>
      </div>
    </div>

    <div class="card-body p-4">
      <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-detail me-1"></i>Header & Delivery Information</h6>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label fw-semibold">P.I.C. / Person In Charge</label>
          <input type="text" class="form-control bg-light rounded-3" value="{{ auth()->user()->name }}" readonly>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Destination Warehouse</label>
          <input type="text" class="form-control bg-light rounded-3" value="{{ $purchaseOrder->warehouse?->name ?: '-' }}" readonly>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Supplier Name</label>
          <input type="text" class="form-control bg-light rounded-3" value="{{ $purchaseOrder->supplier?->name ?: '-' }}" readonly>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Supplier Address</label>
          <input type="text" class="form-control bg-light rounded-3" value="{{ $purchaseOrder->address ?: '-' }}" readonly>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">DO Date <span class="text-danger">*</span></label>
          <input type="date" name="date" class="form-control rounded-3 @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" required>
          @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Supplier DO No (Optional)</label>
          <input type="text" name="supplier_do_no" class="form-control rounded-3" value="{{ old('supplier_do_no') }}" placeholder="e.g. SURAT-JALAN-001">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Remarks / Note</label>
          <input type="text" name="remarks" class="form-control rounded-3" value="{{ old('remarks') }}" placeholder="Header note or remarks...">
        </div>
      </div>

      <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-package me-1"></i>Goods Receipt Items (Quantities Delivered & Received)</h6>
      <div class="table-responsive rounded-3 border">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr class="text-uppercase small fw-bold text-muted">
              <th>Product Name</th>
              <th>Description</th>
              <th>UOM</th>
              <th style="width: 130px;" class="text-end">Required Qty</th>
              <th style="width: 140px;" class="text-end">Delivered Qty <span class="text-danger">*</span></th>
              <th style="width: 140px;" class="text-end">Received Qty <span class="text-danger">*</span></th>
            </tr>
          </thead>
          <tbody>
            @foreach($purchaseOrder->items as $index => $item)
              @php
                $rfItem = $item->requestFormItem;
                $prodName = $rfItem?->product_name ?: 'Product Item';
                $prodDesc = $rfItem?->product_description ?: ($item->remarks ?: '-');
                $uomName = $rfItem?->erpProduct?->uom?->uom_name ?: ($rfItem?->erpProduct?->uom?->name ?: 'PCS');
              @endphp
              <tr>
                <td>
                  <input type="hidden" name="items[{{ $index }}][po_item_id]" value="{{ $item->id }}">
                  <input type="hidden" name="items[{{ $index }}][remark]" value="{{ $item->remarks ?: $prodDesc }}">
                  <div class="fw-bold text-dark">{{ $prodName }}</div>
                </td>
                <td class="small text-muted">{{ $prodDesc }}</td>
                <td><span class="badge bg-label-secondary">{{ $uomName }}</span></td>
                <td class="text-end fw-bold text-dark">{{ number_format($item->qty, 2, ',', '.') }}</td>
                <td class="text-end">
                  <input type="number" step="0.01" name="items[{{ $index }}][delivered_qty]" class="form-control form-control-sm rounded-2 text-end fw-bold" value="{{ old('items.'.$index.'.delivered_qty', $item->qty) }}" required min="0">
                </td>
                <td class="text-end">
                  <input type="number" step="0.01" name="items[{{ $index }}][received_qty]" class="form-control form-control-sm rounded-2 text-end fw-bold text-success border-success" value="{{ old('items.'.$index.'.received_qty', $item->qty) }}" required min="0">
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer bg-light border-top py-3 px-4 d-flex align-items-center justify-content-between">
      <a href="{{ route('erp.purchase-orders.show', $purchaseOrder) }}" class="btn btn-label-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary px-4 shadow-sm">
        <i class="bx bx-check-circle me-1"></i>Create Goods Receipt
      </button>
    </div>
  </form>
</div>
@endsection
