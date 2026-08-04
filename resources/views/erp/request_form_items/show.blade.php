@extends('layouts.home')

@section('title', 'RF Line Item Detail: ' . $requestFormItem->rf_detail_no)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Header & Actions --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
      <h4 class="mb-1 fw-bold text-dark"><i class="bx bx-package text-primary me-2"></i>Detail RF Line Item (Produk)</h4>
      <div class="text-muted small">
        Detail No: <span class="fw-bold text-primary">{{ $requestFormItem->rf_detail_no }}</span> • 
        RF Induk: <a href="{{ route('erp.request-form.show', $requestFormItem->requestForm) }}" class="fw-bold text-decoration-none">{{ $requestFormItem->requestForm->rf_no }}</a>
      </div>
    </div>
    
    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('erp.request-form.show', $requestFormItem->requestForm) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bx bx-arrow-back me-1"></i>Back to Request Form
      </a>

      <button class="btn btn-sm btn-outline-primary" disabled>
        <i class="bx bx-edit me-1"></i>Edit
      </button>

      <button class="btn btn-sm btn-outline-danger" disabled>
        <i class="bx bx-trash me-1"></i>Delete
      </button>

      <button class="btn btn-sm btn-outline-secondary" disabled>
        <i class="bx bx-copy me-1"></i>Clone
      </button>
    </div>
  </div>

  {{-- Main Content Container --}}
  <div class="card shadow-sm border-0 rounded-3 overflow-hidden mb-4">
    
    {{-- Top Header Banner Widget --}}
    <div class="card-header bg-primary bg-opacity-10 py-3 border-bottom">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <span class="text-uppercase fw-bold text-primary small">Product Name</span>
          <h4 class="mb-0 fw-extrabold text-primary">{{ $requestFormItem->product_name ?: '-' }}</h4>
        </div>
        <div class="d-flex align-items-center gap-3">
          <div class="text-end">
            <span class="text-muted small d-block">Original Total Cost</span>
            <span class="fw-extrabold text-primary fs-5">{{ $requestFormItem->currency }} {{ number_format((float)$requestFormItem->original_total_cost, 0, ',', '.') }}</span>
          </div>
          <div>
            <span class="badge bg-label-info px-3 py-2 fs-7">{{ $requestFormItem->status ?: 'Requested' }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body p-4">
      {{-- Section 1: Item Information --}}
      <div class="row g-4 mb-4">
        {{-- Left Column --}}
        <div class="col-lg-6">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="bg-primary bg-opacity-10 rounded p-1">
              <i class="bx bx-info-circle text-primary fs-5"></i>
            </div>
            <h6 class="fw-bold mb-0 text-primary">Informasi Barang / Produk</h6>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">RF Detail No</label>
              <input class="form-control bg-white fw-bold" value="{{ $requestFormItem->rf_detail_no ?: '-' }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">RF Induk</label>
              <input class="form-control bg-white fw-bold text-primary" value="{{ $requestFormItem->requestForm->rf_no }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product ID</label>
              <input class="form-control bg-white" value="{{ $requestFormItem->product_id_text ?: '-' }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Name</label>
              <input class="form-control bg-white fw-bold" value="{{ $requestFormItem->product_name ?: '-' }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Brand</label>
              <input class="form-control bg-white" value="{{ $brand }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Model</label>
              <input class="form-control bg-white" value="{{ $model }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">WID</label>
              <input class="form-control bg-white" value="{{ $requestFormItem->wid ?: '-' }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">UOM</label>
              <input class="form-control bg-white" value="{{ $uom }}" readonly>
            </div>
          </div>
        </div>

        {{-- Right Column --}}
        <div class="col-lg-6">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="bg-primary bg-opacity-10 rounded p-1">
              <i class="bx bx-calculator text-primary fs-5"></i>
            </div>
            <h6 class="fw-bold mb-0 text-primary">Biaya, Qty & Penanggung Jawab</h6>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Qty Diminta</label>
              <input class="form-control bg-white fw-bold text-primary" value="{{ number_format((float)$requestFormItem->qty, 2, ',', '.') }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Qty Terpenuhi</label>
              <input class="form-control bg-white text-muted" value="{{ number_format((float)$requestFormItem->qty_fulfilled, 2, ',', '.') }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Unit Cost</label>
              <input class="form-control bg-white" value="{{ $requestFormItem->currency }} {{ number_format((float)$requestFormItem->unit_cost, 0, ',', '.') }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Original Total Cost</label>
              <input class="form-control bg-white fw-bold text-success" value="{{ $requestFormItem->currency }} {{ number_format((float)$requestFormItem->original_total_cost, 0, ',', '.') }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Date Required</label>
              <input class="form-control bg-white" value="{{ $requestFormItem->date_required?->format('Y-m-d') ?: '-' }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">PIC</label>
              <input class="form-control bg-white" value="{{ $requestFormItem->pic ?: '-' }}" readonly>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold small text-uppercase text-muted">Remark</label>
              <textarea class="form-control bg-white" rows="2" readonly>{{ $requestFormItem->remark ?: '-' }}</textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- Section 2: Related PR Details --}}
      <div class="mt-4 pt-3 border-top">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="d-flex align-items-center gap-2">
            <div class="bg-primary bg-opacity-10 rounded p-1">
              <i class="bx bx-book text-primary fs-5"></i>
            </div>
            <h6 class="fw-bold mb-0 text-primary">Riwayat Purchase Request (PR) Terkait</h6>
          </div>
        </div>

        <div class="table-responsive border rounded-3 overflow-hidden shadow-sm">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr class="small text-uppercase text-muted">
                <th>PR Detail No</th>
                <th class="text-end">Qty Order PR</th>
                <th>Status PR</th>
              </tr>
            </thead>
            <tbody>
              @forelse($requestFormItem->purchaseRequestItems as $prItem)
                <tr>
                  <td>
                    <a href="{{ route('erp.purchase-requests.show', $prItem->purchaseRequest) }}" class="fw-bold text-primary text-decoration-none">
                      {{ $prItem->pr_detail_no ?: 'PR Detail' }}
                    </a>
                  </td>
                  <td class="text-end fw-bold text-primary">{{ number_format((float)$prItem->pr_requested_qty, 2, ',', '.') }}</td>
                  <td><span class="badge bg-label-info">{{ $prItem->purchaseRequest?->status ?: '-' }}</span></td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted py-5">Belum ada Purchase Request yang terhubung dengan item ini.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>

    {{-- Footer Actions --}}
    <div class="card-footer border-top bg-light p-3 d-flex align-items-center justify-content-between">
      <a href="{{ route('erp.request-form.show', $requestFormItem->requestForm) }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i>Back to Request Form
      </a>
    </div>

  </div>
</div>
@endsection
