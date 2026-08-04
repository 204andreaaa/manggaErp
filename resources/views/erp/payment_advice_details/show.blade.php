@extends('layouts.home')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Top Navigation & Back Link -->
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <span class="text-muted small">Payment Advice Detail</span>
      <h4 class="fw-bold mb-0 text-primary">{{ $paymentAdviceDetail->supplier_detail_no }}</h4>
    </div>
    <div>
      <a href="{{ route('erp.payment-advices.show', $paymentAdviceDetail->paymentAdvice) }}" class="btn btn-xs btn-outline-secondary">« Back to Payment Advice Header</a>
    </div>
  </div>

  <!-- Header Section with Buttons -->
  <div class="card mb-3 border-top border-3 border-primary">
    <div class="card-header border-bottom py-2 d-flex justify-content-between align-items-center bg-light">
      <h6 class="mb-0 fw-bold">Payment Advice Detail Detail</h6>
      <div class="d-flex gap-1 align-items-center">
        <button class="btn btn-xs btn-outline-secondary" disabled>Unlock Record</button>
        <button class="btn btn-xs btn-outline-secondary" disabled>Edit</button>
        <button class="btn btn-xs btn-outline-secondary" disabled>Clone</button>
        
        @if($paymentAdviceDetail->approval_status === 'Draft')
          <form action="{{ route('erp.payment-advices.submit', $paymentAdviceDetail->paymentAdvice) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-xs btn-primary">Submit for Approval</button>
          </form>
        @else
          <button class="btn btn-xs btn-outline-secondary" disabled>Submitted</button>
        @endif
      </div>
    </div>

    <div class="card-body mt-3">
      <div class="row small">
        <!-- Left Column -->
        <div class="col-md-6 border-end">
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Supplier Detail No</div>
            <div class="col-sm-7 fw-bold text-dark">{{ $paymentAdviceDetail->supplier_detail_no }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Supplier Invoice</div>
            <div class="col-sm-7">
              <a href="{{ route('erp.payment-advices.show', $paymentAdviceDetail->paymentAdvice) }}" class="fw-bold text-primary">{{ $paymentAdviceDetail->paymentAdvice->supplier_invoice_no }}</a>
            </div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Created Date SID</div>
            <div class="col-sm-7 text-dark">{{ $paymentAdviceDetail->created_date_sid?->format('Y/m/d') ?? '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Supplier Name</div>
            <div class="col-sm-7 fw-bold text-primary">{{ $paymentAdviceDetail->paymentAdvice->supplier?->name ?? '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Invoice No</div>
            <div class="col-sm-7 text-dark">{{ $paymentAdviceDetail->paymentAdvice->invoice_no ?? '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">PO No</div>
            <div class="col-sm-7">
              @if($paymentAdviceDetail->purchaseOrder)
                <a href="{{ route('erp.purchase-orders.show', $paymentAdviceDetail->purchaseOrder) }}" class="fw-bold text-primary">{{ $paymentAdviceDetail->purchaseOrder->po_no }}</a>
              @else
                -
              @endif
            </div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">GR</div>
            <div class="col-sm-7">
              @if($paymentAdviceDetail->goodsReceipt)
                <a href="{{ route('erp.goods-receipts.show', $paymentAdviceDetail->goodsReceipt) }}" class="fw-bold text-primary">{{ $paymentAdviceDetail->goodsReceipt->do_no }}</a>
              @else
                -
              @endif
            </div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">GR Date</div>
            <div class="col-sm-7 text-dark">{{ $paymentAdviceDetail->gr_date?->format('Y/m/d') ?? '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Payment Amount</div>
            <div class="col-sm-7 text-dark">IDR {{ number_format($paymentAdviceDetail->payment_amount, 0, ',', '.') }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Payment Amount With Tax</div>
            <div class="col-sm-7 fw-bold text-primary">IDR {{ number_format($paymentAdviceDetail->payment_amount_with_tax, 0, ',', '.') }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Payment Method</div>
            <div class="col-sm-7 text-dark">{{ $paymentAdviceDetail->payment_method }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Payment Type</div>
            <div class="col-sm-7 text-dark">{{ $paymentAdviceDetail->payment_type }}</div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6">
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Remark</div>
            <div class="col-sm-7 text-dark fw-semibold">{{ $paymentAdviceDetail->remark ?? '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Approval Status</div>
            <div class="col-sm-7">
              @if($paymentAdviceDetail->approval_status === 'Approved')
                <span class="badge bg-label-success fw-bold">Approved</span>
              @else
                <span class="badge bg-label-warning fw-bold">{{ $paymentAdviceDetail->approval_status }}</span>
              @endif
            </div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Approved Date</div>
            <div class="col-sm-7 text-dark">{{ $paymentAdviceDetail->approved_date?->format('Y/m/d') ?? '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Date Paid</div>
            <div class="col-sm-7 text-dark">{{ $paymentAdviceDetail->date_paid?->format('Y/m/d') ?? '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Days Invoice Overdue</div>
            <div class="col-sm-7 text-dark">{{ $paymentAdviceDetail->days_invoice_overdue }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Days over due</div>
            <div class="col-sm-7 text-dark">{{ $paymentAdviceDetail->days_overdue }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Created By</div>
            <div class="col-sm-7 text-dark"><i class="bx bx-user me-1"></i>{{ $paymentAdviceDetail->paymentAdvice->owner?->name ?? 'Admin' }}</div>
          </div>
        </div>
      </div>

      <!-- Expense Type Section -->
      <div class="border-top pt-3 mt-3">
        <h6 class="fw-bold text-primary mb-2"><i class="bx bx-caret-down me-1"></i>Expense Type</h6>
        <div class="row small">
          <div class="col-md-6">
            <div class="form-check mb-1">
              <input class="form-check-input" type="checkbox" disabled>
              <label class="form-check-label text-muted">Material-Equipment</label>
            </div>
            <div class="form-check mb-1">
              <input class="form-check-input" type="checkbox" disabled>
              <label class="form-check-label text-muted">Material-Subcon</label>
            </div>
            <div class="form-check mb-1">
              <input class="form-check-input" type="checkbox" disabled>
              <label class="form-check-label text-muted">Personnel</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-check mb-1">
              <input class="form-check-input" type="checkbox" disabled>
              <label class="form-check-label text-muted">Other Expense</label>
            </div>
            <div class="form-check mb-1">
              <input class="form-check-input" type="checkbox" checked disabled>
              <label class="form-check-label fw-bold text-dark">Office</label>
            </div>
            <div class="form-check mb-1">
              <input class="form-check-input" type="checkbox" disabled>
              <label class="form-check-label text-muted">Utilities</label>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Approval History (Exact Banner Rows as Image 2) -->
  <div class="card mb-4 border-top border-2 border-primary">
    <div class="card-header py-2 bg-light border-bottom d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold"><i class="bx bx-history me-1 text-primary"></i>Approval History</h6>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th>Action</th>
            <th>Date</th>
            <th>Status</th>
            <th>Assigned To</th>
            <th>Actual Approver</th>
            <th>Comments</th>
            <th>Overall Status</th>
          </tr>
        </thead>
        <tbody>
          <!-- Step 3 Banner -->
          <tr style="background-color: #696cff; color: white;">
            <td colspan="7" class="fw-bold py-1 px-3" style="font-size: 0.82rem; letter-spacing: 0.5px;">Step: Step 3</td>
          </tr>
          <tr>
            <td>-</td>
            <td>{{ now()->format('Y/m/d H:i') }}</td>
            <td><span class="badge bg-label-success">Approved</span></td>
            <td>Barry Japadermawan</td>
            <td>Barry Japadermawan</td>
            <td>-</td>
            <td><span class="badge bg-success text-white"><i class="bx bx-check-circle me-1"></i>Approved</span></td>
          </tr>

          <!-- Step 2 Banner -->
          <tr style="background-color: #696cff; color: white;">
            <td colspan="7" class="fw-bold py-1 px-3" style="font-size: 0.82rem; letter-spacing: 0.5px;">Step: Step 2</td>
          </tr>
          <tr>
            <td>-</td>
            <td>{{ $paymentAdviceDetail->created_at->format('Y/m/d H:i') }}</td>
            <td><span class="badge bg-label-success">Approved</span></td>
            <td>Melvien Welang</td>
            <td>Melvien Welang</td>
            <td>-</td>
            <td><span class="badge bg-success text-white"><i class="bx bx-check-circle me-1"></i>Approved</span></td>
          </tr>

          <!-- Approval Request Submitted Banner -->
          <tr style="background-color: #696cff; color: white;">
            <td colspan="7" class="fw-bold py-1 px-3" style="font-size: 0.82rem; letter-spacing: 0.5px;">Approval Request Submitted</td>
          </tr>
          <tr>
            <td>-</td>
            <td>{{ $paymentAdviceDetail->created_at->format('Y/m/d H:i') }}</td>
            <td><span class="badge bg-label-info">Submitted</span></td>
            <td>Melvien Welang</td>
            <td>Melvien Welang</td>
            <td>-</td>
            <td>-</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
