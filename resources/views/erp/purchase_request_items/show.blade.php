@extends('layouts.home')

@section('title', 'PR Detail: ' . $purchaseRequestItem->pr_detail_no)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <div class="text-muted small">PR Detail</div>
      <h4 class="mb-0 fw-bold">{{ $purchaseRequestItem->pr_detail_no }}</h4>
    </div>
    <a href="{{ route('erp.purchase-requests.show', $purchaseRequestItem->purchaseRequest) }}" class="btn btn-outline-secondary btn-sm">
      <i class="bx bx-arrow-back me-1"></i>Back to PR
    </a>
  </div>

  <div class="card mb-4">
    <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold">PR Detail Detail</h6>
      <div>
        <button class="btn btn-sm btn-outline-secondary" disabled>Edit</button>
        <button class="btn btn-sm btn-outline-secondary" disabled>Clone</button>
      </div>
    </div>
    <div class="card-body mt-3">
      <div class="row">
        <div class="col-md-6 mb-2">
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">PR Detail No</div>
            <div class="col-8">{{ $purchaseRequestItem->pr_detail_no }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">PR</div>
            <div class="col-8">
              <a href="{{ route('erp.purchase-requests.show', $purchaseRequestItem->purchaseRequest) }}">
                {{ $purchaseRequestItem->purchaseRequest->pr_no }}
              </a>
            </div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">RF Detail</div>
            <div class="col-8">
              @if($purchaseRequestItem->requestFormItem)
                <a href="{{ route('erp.request-form-items.show', $purchaseRequestItem->requestFormItem) }}">
                  {{ $purchaseRequestItem->requestFormItem->rf_detail_no }}
                </a>
              @else
                -
              @endif
            </div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">WID</div>
            <div class="col-8">{{ $purchaseRequestItem->requestFormItem?->wid ?: '-' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Remark</div>
            <div class="col-8">{{ $purchaseRequestItem->requestFormItem?->remark ?: '-' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Product</div>
            <div class="col-8">
              @if($purchaseRequestItem->requestFormItem)
                <a href="#">{{ $purchaseRequestItem->requestFormItem->product_name ?: '-' }}</a>
              @else
                -
              @endif
            </div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Product Description</div>
            <div class="col-8">{{ $purchaseRequestItem->requestFormItem?->product_description ?: '-' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Brand</div>
            <div class="col-8">{{ $brand }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Model</div>
            <div class="col-8">{{ $model }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Original Total Cost</div>
            <div class="col-8">
              {{ $purchaseRequestItem->requestFormItem?->currency ?: 'IDR' }} 
              {{ number_format((float)($purchaseRequestItem->requestFormItem?->original_total_cost), 0, ',', '.') }}
            </div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Unit Cost</div>
            <div class="col-8">
              @php
                $rfItem = $purchaseRequestItem->requestFormItem;
                $effCost = ($rfItem?->actual_cost > 0 ? $rfItem?->actual_cost : $rfItem?->unit_cost) ?? 0;
              @endphp
              {{ $rfItem?->currency ?: 'IDR' }} 
              {{ number_format((float)$effCost, 0, ',', '.') }}
            </div>
          </div>
        </div>
        
        <div class="col-md-6 mb-2">
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Qty</div>
            <div class="col-8">{{ number_format((float)$purchaseRequestItem->pr_requested_qty, 2, ',', '.') }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">UOM</div>
            <div class="col-8">{{ $uom }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Status</div>
            <div class="col-8">{{ $purchaseRequestItem->requestFormItem?->status ?: '-' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Total Cost</div>
            <div class="col-8">
              {{ $rfItem?->currency ?: 'IDR' }} 
              {{ number_format((float)($purchaseRequestItem->pr_requested_qty * $effCost), 0, ',', '.') }}
            </div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Actual Cost (Real)</div>
            <div class="col-8 text-success fw-bold">
              {{ $rfItem?->currency ?: 'IDR' }} 
              {{ number_format((float)$effCost, 0, ',', '.') }}
            </div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">PR Type</div>
            <div class="col-8">{{ $purchaseRequestItem->purchaseRequest->requestForm->rf_type ?: '-' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Created By</div>
            <div class="col-8">
              {{ $purchaseRequestItem->purchaseRequest->requestor }}, 
              {{ $purchaseRequestItem->created_at->format('Y/m/d H:i') }}
            </div>
          </div>
          <div class="row mb-1">
            <div class="col-4 fw-bold text-end text-muted">Last Modified By</div>
            <div class="col-8">
              {{ $purchaseRequestItem->purchaseRequest->requestor }}, 
              {{ $purchaseRequestItem->updated_at->format('Y/m/d H:i') }}
            </div>
          </div>
        </div>
      </div>
      
      <div class="text-center mt-3 border-top pt-3">
        <button class="btn btn-sm btn-outline-secondary" disabled>Edit</button>
        <button class="btn btn-sm btn-outline-secondary" disabled>Clone</button>
      </div>
    </div>
  </div>
  <!-- Related List: PO Detail -->
  <div class="card mb-4">
    <div class="card-header border-bottom py-2 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold">PO Detail</h6>
      <span class="text-muted small">PO Detail Help <i class="bx bx-help-circle"></i></span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th>Action</th>
            <th>PO Detail No</th>
            <th>PO No</th>
            <th>Status</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Unit Cost</th>
            <th class="text-end">Total Cost</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody>
          @forelse($purchaseRequestItem->purchaseOrderItems as $poItem)
            <tr>
              <td>
                <a href="{{ route('erp.purchase-orders.show', $poItem->purchaseOrder) }}" class="text-primary text-decoration-none">
                  View
                </a>
              </td>
              <td>
                <span class="fw-semibold">{{ $poItem->po_detail_no }}</span>
              </td>
              <td>
                <a href="{{ route('erp.purchase-orders.show', $poItem->purchaseOrder) }}" class="text-primary text-decoration-none">
                  {{ $poItem->purchaseOrder->po_no }}
                </a>
              </td>
              <td>
                @if($poItem->purchaseOrder->status === 'Approved')
                  <span class="badge bg-label-success">Approved</span>
                @elseif($poItem->purchaseOrder->status === 'Submitted')
                  <span class="badge bg-label-warning">Submitted</span>
                @elseif($poItem->purchaseOrder->status === 'Rejected')
                  <span class="badge bg-label-danger">Rejected</span>
                @else
                  <span class="badge bg-label-secondary">{{ $poItem->purchaseOrder->status }}</span>
                @endif
              </td>
              <td class="text-end">{{ number_format((float)$poItem->qty, 2, ',', '.') }}</td>
              <td class="text-end">IDR {{ number_format($poItem->unit_cost, 0, ',', '.') }}</td>
              <td class="text-end fw-semibold">IDR {{ number_format($poItem->total_cost, 0, ',', '.') }}</td>
              <td>{{ $poItem->remarks ?: '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-3">No records to display</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
