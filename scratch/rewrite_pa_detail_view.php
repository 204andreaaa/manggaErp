<?php

$file = 'C:/Users/itmdu/OneDrive/Documents/Mandau/project/project/kumpulan MP3/mp3/mp3/resources/views/erp/payment_advice_details/show.blade.php';

$content = <<<'BLADE'
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
        @if($paymentAdviceDetail->approval_status === 'Draft')
          <button type="button" class="btn btn-xs btn-primary" data-bs-toggle="modal" data-bs-target="#submitApprovalModal">Submit for Approval</button>
        @elseif($paymentAdviceDetail->approval_status === 'Submitted')
          @php
            $user = auth()->user();
            $canApprove = false;
            $activeApproval = $paymentAdviceDetail->approvals()->where('status', 'Pending')->first();
            if ($activeApproval) {
                if ($user->hasRole('superadmin')) {
                    $canApprove = true;
                } elseif ($activeApproval->assigned_to_user_id && $user->id == $activeApproval->assigned_to_user_id) {
                    $canApprove = true;
                } elseif ($activeApproval->assigned_to_role_id) {
                    $hasRole = \Illuminate\Support\Facades\DB::connection('master')
                        ->table('role_user')
                        ->where('user_id', $user->id)
                        ->where('role_id', $activeApproval->assigned_to_role_id)
                        ->exists();
                    if ($hasRole) {
                        $canApprove = true;
                    }
                }
            } else {
                if ($user->hasRole('finance') || $user->hasRole('ceo') || $user->hasRole('superadmin')) {
                    $canApprove = true;
                }
            }
          @endphp
          @if($canApprove)
            <button type="button" class="btn btn-success btn-xs" data-bs-toggle="modal" data-bs-target="#approveModal">
              <i class="bx bx-check me-1"></i>Approve
            </button>
            <button type="button" class="btn btn-danger btn-xs" data-bs-toggle="modal" data-bs-target="#rejectModal">
              <i class="bx bx-x me-1"></i>Reject
            </button>
          @else
            <button class="btn btn-xs btn-outline-secondary" disabled>Submitted</button>
          @endif
        @elseif($paymentAdviceDetail->approval_status === 'Approved' && !$paymentAdviceDetail->date_paid)
          @if(auth()->user()->hasRole('finance') || auth()->user()->hasRole('superadmin'))
            <form action="{{ route('erp.payment-advice-details.mark-paid', $paymentAdviceDetail) }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-primary btn-xs">
                <i class="bx bx-check-double me-1"></i>Mark as Paid
              </button>
            </form>
          @endif
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
            <div class="col-sm-7 text-dark">{{ $paymentAdviceDetail->invoice_no ?? '-' }}</div>
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
    </div>
  </div>

  <!-- Approval History -->
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
          @forelse($paymentAdviceDetail->approvals()->orderBy('level', 'desc')->get() as $approval)
            @php
              $statusColor = 'secondary';
              $icon = 'bx-time-five';
              if ($approval->status === 'Approved') { $statusColor = 'success'; $icon = 'bx-check-double'; }
              if ($approval->status === 'Rejected') { $statusColor = 'danger'; $icon = 'bx-x-circle'; }
              if ($approval->status === 'Pending') { $statusColor = 'warning'; $icon = 'bx-loader-circle bx-spin'; }
              if ($approval->status === 'Cancelled') { $statusColor = 'secondary'; $icon = 'bx-block'; }
            @endphp
            <tr style="background-color: #696cff; color: white;">
              <td colspan="7" class="fw-bold py-1 px-3" style="font-size: 0.82rem; letter-spacing: 0.5px;">Step: {{ $approval->level }}</td>
            </tr>
            <tr>
              <td>-</td>
              <td>{{ $approval->approved_at ? $approval->approved_at->format('Y/m/d H:i') : '-' }}</td>
              <td><span class="badge bg-label-{{ $statusColor }}"><i class="bx {{ $icon }} me-1"></i>{{ $approval->status }}</span></td>
              <td>{{ $approval->assignedUser?->name ?? $approval->assignedRole?->name ?? '-' }}</td>
              <td>{{ $approval->actualApprover?->name ?? '-' }}</td>
              <td>{{ $approval->comments ?? '-' }}</td>
              <td>
                @if($approval->status === 'Approved')
                  <span class="badge bg-success text-white"><i class="bx bx-check-circle me-1"></i>Approved</span>
                @else
                  -
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <div class="mb-3"><i class="bx bx-shield-x fs-1 text-light"></i></div>
                Belum ada riwayat approval.
              </td>
            </tr>
          @endforelse
          
          @if($paymentAdviceDetail->approval_status !== 'Draft')
          <!-- Approval Request Submitted Banner -->
          <tr style="background-color: #696cff; color: white;">
            <td colspan="7" class="fw-bold py-1 px-3" style="font-size: 0.82rem; letter-spacing: 0.5px;">Approval Request Submitted</td>
          </tr>
          <tr>
            <td>-</td>
            <td>{{ $paymentAdviceDetail->created_at->format('Y/m/d H:i') }}</td>
            <td><span class="badge bg-label-info">Submitted</span></td>
            <td>System</td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
          </tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Submit Approval -->
<div class="modal fade" id="submitApprovalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25">
        <h5 class="modal-title text-primary fw-bold"><i class="bx bx-paper-plane me-2"></i>Submit for Approval</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('erp.payment-advice-details.submit', $paymentAdviceDetail) }}" method="POST">
        @csrf
        <div class="modal-body p-4">
          <p class="mb-3 text-muted">Anda akan mengajukan <b>{{ $paymentAdviceDetail->payment_type }}</b> ini untuk proses persetujuan (approval).</p>
          <div class="mb-3">
            <label class="form-label fw-bold">Nomor Invoice Supplier Fisik <span class="text-danger">*</span></label>
            <input type="text" name="invoice_no" class="form-control" required placeholder="Misal: INV/2026/08/999">
            <small class="text-muted">Masukkan nomor invoice resmi dari vendor/supplier untuk cicilan/termin ini.</small>
          </div>
        </div>
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="bx bx-paper-plane me-1"></i>Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal Approve --}}
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success bg-opacity-10 border-bottom border-success border-opacity-25">
        <h5 class="modal-title text-success fw-bold"><i class="bx bx-check-shield me-2"></i>Approve Termin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('erp.payment-advice-details.approve', $paymentAdviceDetail) }}" method="POST">
        @csrf
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-bold">Comments (Opsional)</label>
            <textarea name="comments" class="form-control" rows="3" placeholder="Tuliskan komentar approval di sini..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success"><i class="bx bx-check me-1"></i>Approve</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal Reject --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger bg-opacity-10 border-bottom border-danger border-opacity-25">
        <h5 class="modal-title text-danger fw-bold"><i class="bx bx-x-circle me-2"></i>Reject Termin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('erp.payment-advice-details.reject', $paymentAdviceDetail) }}" method="POST">
        @csrf
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
            <textarea name="reason" class="form-control" rows="3" placeholder="Tuliskan alasan penolakan..." required></textarea>
          </div>
        </div>
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger"><i class="bx bx-x me-1"></i>Reject</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
BLADE;

file_put_contents($file, $content);
echo "Successfully rewrote payment_advice_details/show.blade.php\n";
