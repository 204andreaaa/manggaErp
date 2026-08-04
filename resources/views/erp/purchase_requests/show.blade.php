@extends('layouts.home')

@section('title', 'PR Detail: ' . $purchaseRequest->pr_no)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Header & Actions --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
      <h4 class="mb-1 fw-bold text-dark"><i class="bx bx-book text-primary me-2"></i>Detail Purchase Request (PR)</h4>
      <div class="text-muted small">
        PR No: <span class="fw-bold text-primary">{{ $purchaseRequest->pr_no }}</span> • 
        RF Induk: <a href="{{ route('erp.request-form.show', $purchaseRequest->requestForm) }}" class="fw-bold text-decoration-none">{{ $purchaseRequest->requestForm->rf_no }}</a>
      </div>
    </div>
    
    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('erp.request-form.show', $purchaseRequest->requestForm) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bx bx-arrow-back me-1"></i>Back to Request Form
      </a>

      @if(auth()->user()->hasRole('superadmin'))
        <button class="btn btn-sm btn-outline-warning" disabled>
          <i class="bx bx-lock-open-alt me-1"></i>Unlock Record
        </button>
      @endif

      <button class="btn btn-sm btn-outline-primary" disabled>
        <i class="bx bx-edit me-1"></i>Edit PR
      </button>

      <button class="btn btn-sm btn-primary" disabled>
        <i class="bx bx-paper-plane me-1"></i>Submit for Approval
      </button>
    </div>
  </div>

  {{-- Main Content Container --}}
  <div class="card shadow-sm border-0 rounded-3 overflow-hidden mb-4">
    
    {{-- Top Header Banner Widget --}}
    <div class="card-header bg-primary bg-opacity-10 py-3 border-bottom">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <span class="text-uppercase fw-bold text-primary small">Nomor PR</span>
          <h4 class="mb-0 fw-extrabold text-primary">{{ $purchaseRequest->pr_no }}</h4>
        </div>
        <div class="d-flex align-items-center gap-3">
          <div class="text-end">
            <span class="text-muted small d-block">Total PR Amount</span>
            @php
              $totalPrAmount = $purchaseRequest->items->sum(function($item) {
                return $item->pr_requested_qty * ($item->requestFormItem->unit_cost ?? 0);
              });
            @endphp
            <span class="fw-extrabold text-primary fs-5">IDR {{ number_format($totalPrAmount, 0, ',', '.') }}</span>
          </div>
          <div>
            @if($purchaseRequest->status === 'Approved' || $purchaseRequest->status === 'Completed')
              <span class="badge bg-success px-3 py-2 fs-7"><i class="bx bx-check-circle me-1"></i>{{ $purchaseRequest->status }}</span>
            @elseif($purchaseRequest->status === 'Submitted')
              <span class="badge bg-warning px-3 py-2 fs-7"><i class="bx bx-time-five me-1"></i>Submitted</span>
            @else
              <span class="badge bg-secondary px-3 py-2 fs-7">{{ $purchaseRequest->status }}</span>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="card-body p-4">
      {{-- Section 1: PR Information --}}
      <div class="row g-4 mb-4">
        {{-- Left Column --}}
        <div class="col-lg-6">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="bg-primary bg-opacity-10 rounded p-1">
              <i class="bx bx-info-circle text-primary fs-5"></i>
            </div>
            <h6 class="fw-bold mb-0 text-primary">Informasi Utama PR</h6>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">RF Induk</label>
              <input class="form-control bg-white fw-bold text-primary" value="{{ $purchaseRequest->requestForm->rf_no }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">PR No</label>
              <input class="form-control bg-white fw-bold" value="{{ $purchaseRequest->pr_no }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Project Code</label>
              <input class="form-control bg-white" value="{{ $purchaseRequest->requestForm->project_code ?: '-' }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Requestor</label>
              <input class="form-control bg-white" value="{{ $purchaseRequest->requestor }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">PR Type</label>
              <input class="form-control bg-white" value="{{ $purchaseRequest->requestForm->rf_type ?: '-' }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Status</label>
              <input class="form-control bg-white fw-bold" value="{{ $purchaseRequest->status }}" readonly>
            </div>
          </div>
        </div>

        {{-- Right Column --}}
        <div class="col-lg-6">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="bg-primary bg-opacity-10 rounded p-1">
              <i class="bx bx-calendar text-primary fs-5"></i>
            </div>
            <h6 class="fw-bold mb-0 text-primary">Tanggal & Catatan</h6>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Owner</label>
              <input class="form-control bg-white" value="{{ $purchaseRequest->requestForm->owner ?: $purchaseRequest->requestor }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">PR Date</label>
              <input class="form-control bg-white" value="{{ $purchaseRequest->pr_date?->format('Y-m-d') ?: '-' }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Submitted Date</label>
              <input class="form-control bg-white" value="{{ $purchaseRequest->created_at?->format('Y-m-d') ?: '-' }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-uppercase text-muted">Approved Date</label>
              <input class="form-control bg-white" value="{{ $purchaseRequest->status === 'Approved' || $purchaseRequest->status === 'Completed' ? $purchaseRequest->updated_at?->format('Y-m-d') : '-' }}" readonly>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold small text-uppercase text-muted">Remark</label>
              <textarea class="form-control bg-white" rows="2" readonly>{{ $purchaseRequest->requestForm->remark ?: '-' }}</textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- Section 2: Expense Categories --}}
      <div class="mt-4 pt-3 border-top">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="bg-warning bg-opacity-10 rounded p-1">
            <i class="bx bx-purchase-tag text-warning fs-5"></i>
          </div>
          <h6 class="fw-bold mb-0 text-dark">Expense Categories / Jenis Pengeluaran</h6>
        </div>

        <div class="border rounded-3 p-3 bg-light">
          <div class="row g-3">
            @foreach([
              'expense_material_equipment' => 'Material-Equipment',
              'expense_material_subcon' => 'Material-Subcon',
              'expense_transportation' => 'Transportation & Telecommunication',
              'expense_personnel' => 'Personnel',
              'expense_office' => 'Office',
              'expense_other' => 'Other Expense',
              'expense_utilities' => 'Utilities',
            ] as $field => $label)
              <div class="col-md-4 col-sm-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" disabled @checked($purchaseRequest->{$field})>
                  <label class="form-check-label fw-medium text-secondary">{{ $label }}</label>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- Section 3: PR Line Items --}}
      <div class="mt-4 pt-3 border-top">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="d-flex align-items-center gap-2">
            <div class="bg-primary bg-opacity-10 rounded p-1">
              <i class="bx bx-package text-primary fs-5"></i>
            </div>
            <h6 class="fw-bold mb-0 text-primary">Rincian Barang PR (PR Line Items)</h6>
          </div>
        </div>

        <div class="table-responsive border rounded-3 overflow-hidden shadow-sm">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr class="small text-uppercase text-muted">
                <th>PR Detail No</th>
                <th>RF Detail No</th>
                <th>Product Name</th>
                <th>Brand</th>
                <th>Model</th>
                <th class="text-end">Qty Requested</th>
                <th class="text-end">Unit Cost</th>
                <th class="text-end">Total Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($purchaseRequest->items as $prItem)
                @php
                  $rfItem = $prItem->requestFormItem;
                  $unitCost = $rfItem?->unit_cost ?? 0;
                  $total = $prItem->pr_requested_qty * $unitCost;
                @endphp
                <tr>
                  <td class="fw-bold text-primary">{{ $prItem->pr_detail_no ?: '-' }}</td>
                  <td>
                    @if($rfItem)
                      <a href="{{ route('erp.request-form-items.show', $rfItem) }}" class="text-decoration-none fw-semibold">{{ $rfItem->rf_detail_no }}</a>
                    @else
                      -
                    @endif
                  </td>
                  <td class="fw-bold text-dark">{{ $rfItem?->product_name ?: '-' }}</td>
                  <td>{{ $rfItem?->erpProduct?->brand?->name ?: '-' }}</td>
                  <td>{{ $rfItem?->erpProduct?->productModel?->name ?: '-' }}</td>
                  <td class="text-end fw-bold text-primary">{{ number_format((float)$prItem->pr_requested_qty, 2, ',', '.') }}</td>
                  <td class="text-end">IDR {{ number_format($unitCost, 0, ',', '.') }}</td>
                  <td class="text-end fw-bold text-success">IDR {{ number_format($total, 0, ',', '.') }}</td>
                  <td><span class="badge bg-label-info">{{ $purchaseRequest->status }}</span></td>
                </tr>
              @empty
                <tr><td colspan="9" class="text-center text-muted py-5">Belum ada item dalam PR ini.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>

    {{-- Footer Actions --}}
    <div class="card-footer border-top bg-light p-3 d-flex align-items-center justify-content-between">
      <a href="{{ route('erp.request-form.show', $purchaseRequest->requestForm) }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i>Back to Request Form
      </a>
    </div>

  </div>
</div>
@endsection
