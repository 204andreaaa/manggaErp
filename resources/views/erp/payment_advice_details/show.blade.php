@extends('layouts.home')

@section('title', 'Payment Advice Detail: ' . $paymentAdviceDetail->supplier_detail_no)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  
  {{-- Flash Alerts --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible shadow-sm border-0 mb-4" role="alert">
      <div class="d-flex align-items-center">
        <i class="bx bx-check-circle me-2 fs-4"></i>
        <div>{{ session('success') }}</div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible shadow-sm border-0 mb-4" role="alert">
      <div class="d-flex align-items-center">
        <i class="bx bx-error-circle me-2 fs-4"></i>
        <div>{{ session('error') }}</div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @php
    $unapprovedPrev = $paymentAdviceDetail->previousUnapprovedDetail();
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

  {{-- Page Title & Toolbar --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
      <h4 class="mb-1 fw-bold text-dark"><i class="bx bx-receipt text-primary me-2"></i>Rincian Termin Pembayaran</h4>
      <div class="text-muted small d-flex align-items-center flex-wrap gap-2">
        <span>SID No: <strong class="text-dark">{{ $paymentAdviceDetail->supplier_detail_no }}</strong></span>
        <span>•</span>
        <span>PA Induk: 
          <a href="{{ route('erp.payment-advices.show', $paymentAdviceDetail->paymentAdvice) }}" class="fw-bold text-primary text-decoration-none">
            {{ $paymentAdviceDetail->paymentAdvice->supplier_invoice_no }}
          </a>
        </span>
        @if($paymentAdviceDetail->purchaseOrder)
          <span>•</span>
          <span>PO: 
            <a href="{{ route('erp.purchase-orders.show', $paymentAdviceDetail->purchaseOrder) }}" class="badge bg-label-primary fs-7 text-decoration-none">
              {{ $paymentAdviceDetail->purchaseOrder->po_no }}
            </a>
          </span>
        @endif
      </div>
    </div>

    {{-- Action Toolbar --}}
    <div class="d-flex align-items-center flex-wrap gap-2">
      <a href="{{ route('erp.payment-advices.show', $paymentAdviceDetail->paymentAdvice) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
        <i class="bx bx-arrow-back me-1"></i>Back to PA Header
      </a>

      @if($paymentAdviceDetail->approval_status === 'Draft')
        @if($unapprovedPrev)
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" disabled data-bs-toggle="tooltip" title="Termin sebelumnya ({{ $unapprovedPrev->payment_type }}) belum di-approve.">
            <i class="bx bx-lock-alt me-1"></i>Terkunci (Antrean)
          </button>
        @else
          @if(auth()->user()->hasRole(['finance', 'superadmin']))
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#submitApprovalModal">
              <i class="bx bx-paper-plane me-1"></i>Submit for Approval
            </button>
          @else
            <span class="badge bg-label-secondary px-3 py-2 fs-7"><i class="bx bx-time me-1"></i>Draft (Menunggu Finance)</span>
          @endif
        @endif
      @elseif($paymentAdviceDetail->approval_status === 'Submitted')
        <span class="badge bg-label-warning px-3 py-2 fs-7">
          <i class="bx bx-time-five me-1"></i>Approval In Progress
        </span>
      @elseif($paymentAdviceDetail->approval_status === 'Approved' && !$paymentAdviceDetail->date_paid)
        @if(auth()->user()->hasRole('finance') || auth()->user()->hasRole('superadmin'))
          <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#markPaidModal">
            <i class="bx bx-check-double me-1"></i>Mark as Paid
          </button>
        @else
          <span class="badge bg-success px-3 py-2 fs-7"><i class="bx bx-check-circle me-1"></i>Approved (Unpaid)</span>
        @endif
      @elseif($paymentAdviceDetail->date_paid)
        <span class="badge bg-success px-3 py-2 fs-7"><i class="bx bx-check-double me-1"></i>Paid ({{ $paymentAdviceDetail->date_paid->format('d M Y') }})</span>
      @endif
    </div>
  </div>

  {{-- Sequential Warning Alert --}}
  @if($paymentAdviceDetail->approval_status === 'Draft' && $unapprovedPrev)
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4 rounded-3" role="alert">
      <i class="bx bx-lock-alt fs-3 me-3 text-warning"></i>
      <div>
        <h6 class="alert-heading fw-bold mb-1">Termin Ini Belum Dapat Diajukan (Terkunci)</h6>
        <p class="mb-0 small">
          Pengajuan termin harus dilakukan berurutan. Termin sebelumnya <strong>{{ $unapprovedPrev->payment_type }}</strong> ({{ $unapprovedPrev->supplier_detail_no }}) masih berstatus <span class="badge bg-label-warning fw-bold">{{ $unapprovedPrev->approval_status }}</span> dan harus <strong>Approved</strong> terlebih dahulu.
        </p>
      </div>
    </div>
  @endif

  {{-- Stats Summary Row --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-primary bg-opacity-10 h-100">
        <div class="text-muted small fw-semibold">NOMINAL WITH TAX</div>
        <h5 class="mb-0 fw-extrabold text-primary">IDR {{ number_format($paymentAdviceDetail->payment_amount_with_tax, 0, ',', '.') }}</h5>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
        <div class="text-muted small fw-semibold">JENIS / TERMIN</div>
        <h6 class="mb-0 fw-bold text-dark text-truncate">{{ $paymentAdviceDetail->payment_type }}</h6>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100 border-start border-4 border-warning">
        <div class="text-muted small fw-semibold">STATUS APPROVAL</div>
        <div>
          @php
            $stBadge = match($paymentAdviceDetail->approval_status) {
              'Approved' => 'bg-success',
              'Submitted' => 'bg-warning',
              'Rejected' => 'bg-danger',
              default => 'bg-secondary',
            };
          @endphp
          <span class="badge {{ $stBadge }} px-3 py-1 fs-7 fw-bold">
            @if($paymentAdviceDetail->approval_status === 'Approved')
              <i class="bx bx-check-circle me-1"></i>
            @elseif($paymentAdviceDetail->approval_status === 'Submitted')
              <i class="bx bx-time me-1"></i>
            @endif
            {{ $paymentAdviceDetail->approval_status }}
          </span>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
        <div class="text-muted small fw-semibold">SUPPLIER</div>
        <h6 class="mb-0 fw-bold text-dark text-truncate">{{ $paymentAdviceDetail->paymentAdvice->supplier?->name ?: '-' }}</h6>
      </div>
    </div>
  </div>

  {{-- Main Container Card --}}
  <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
    <div class="card-header border-bottom bg-white py-3">
      <h6 class="mb-0 fw-bold text-dark"><i class="bx bx-info-circle text-primary me-2"></i>Informasi Rincian Termin</h6>
    </div>
    <div class="card-body p-4 bg-light bg-opacity-50">
      <div class="row g-4">
        {{-- Left Column --}}
        <div class="col-lg-6">
          <div class="card shadow-none border rounded-3 h-100 bg-white">
            <div class="card-header border-bottom py-2.5 bg-light bg-opacity-50">
              <h6 class="mb-0 fw-semibold text-dark small"><i class="bx bx-receipt me-1.5 text-primary"></i>Detail Dokumen</h6>
            </div>
            <div class="card-body p-0">
              <table class="table table-borderless table-sm mb-0">
                <tbody>
                  <tr><td class="text-muted ps-4 py-2 w-40">Supplier Detail No</td><td class="fw-bold text-dark py-2">{{ $paymentAdviceDetail->supplier_detail_no }}</td></tr>
                  <tr class="border-top"><td class="text-muted ps-4 py-2">Supplier Invoice Induk</td><td class="py-2">
                    <a href="{{ route('erp.payment-advices.show', $paymentAdviceDetail->paymentAdvice) }}" class="fw-bold text-primary">{{ $paymentAdviceDetail->paymentAdvice->supplier_invoice_no }}</a>
                  </td></tr>
                  <tr class="border-top"><td class="text-muted ps-4 py-2">PO Reference</td><td class="py-2">
                    @if($paymentAdviceDetail->purchaseOrder)
                      <a href="{{ route('erp.purchase-orders.show', $paymentAdviceDetail->purchaseOrder) }}" class="fw-bold text-primary">{{ $paymentAdviceDetail->purchaseOrder->po_no }}</a>
                    @else
                      -
                    @endif
                  </td></tr>
                  <tr class="border-top"><td class="text-muted ps-4 py-2">Supplier</td><td class="fw-semibold text-dark py-2">{{ $paymentAdviceDetail->paymentAdvice->supplier?->name ?? '-' }}</td></tr>
                  <tr class="border-top">
                    <td class="text-muted ps-4 py-2">Invoice Fisik Vendor</td>
                    <td class="py-2">
                      <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="fw-bold text-dark">{{ $paymentAdviceDetail->invoice_no ?: '-' }}</span>
                        @if($paymentAdviceDetail->approval_status === 'Draft' && (auth()->user()->hasRole('finance') || auth()->user()->hasRole('superadmin')))
                          <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-0.5" data-bs-toggle="modal" data-bs-target="#editInvoiceModal">
                            <i class="bx bx-edit me-1"></i>Input / Edit
                          </button>
                        @endif
                      </div>
                    </td>
                  </tr>
                  <tr class="border-top">
                    <td class="text-muted ps-4 py-2">Lampiran Invoice Vendor</td>
                    <td class="py-2">
                      @if($paymentAdviceDetail->invoice_attachment)
                        <button type="button" class="btn btn-xs btn-label-primary rounded-pill px-3 py-1" data-bs-toggle="modal" data-bs-target="#viewInvoiceModal">
                          <i class="bx bx-show me-1"></i>Preview Invoice
                        </button>
                      @else
                        <span class="text-muted small">-</span>
                      @endif
                    </td>
                  </tr>
                  <tr class="border-top"><td class="text-muted ps-4 py-2">Goods Receipt (GR)</td><td class="py-2">
                    @php
                      $grs = $paymentAdviceDetail->goodsReceipt 
                          ? collect([$paymentAdviceDetail->goodsReceipt]) 
                          : ($paymentAdviceDetail->purchaseOrder?->goodsReceipts ?? collect());
                    @endphp
                    @if($grs->isNotEmpty())
                      <div class="d-flex flex-column gap-1">
                        @foreach($grs as $g)
                          <div class="d-flex align-items-center flex-wrap gap-1">
                            <a href="{{ route('erp.goods-receipts.show', $g) }}" class="fw-bold text-primary text-decoration-none">
                              <i class="bx bx-package me-1"></i>{{ $g->do_no }}
                            </a>
                            @if($g->status === 'Received')
                              <span class="badge bg-label-success" style="font-size: 0.72rem;"><i class="bx bx-check me-1"></i>Diterima Fisik</span>
                            @else
                              <span class="badge bg-label-warning" style="font-size: 0.72rem;"><i class="bx bx-time me-1"></i>{{ $g->status }}</span>
                            @endif
                          </div>
                        @endforeach
                      </div>
                    @else
                      <span class="text-muted small">Belum ada (DP / Sebelum Barang Diterima)</span>
                    @endif
                  </td></tr>
                  <tr class="border-top"><td class="text-muted ps-4 py-2">Payment Method</td><td class="py-2"><span class="badge bg-label-info fw-bold">{{ $paymentAdviceDetail->payment_method }}</span></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {{-- Right Column --}}
        <div class="col-lg-6">
          <div class="card shadow-none border rounded-3 h-100 bg-white">
            <div class="card-header border-bottom py-2.5 bg-light bg-opacity-50">
              <h6 class="mb-0 fw-semibold text-dark small"><i class="bx bx-calculator me-1.5 text-primary"></i>Finansial & Status</h6>
            </div>
            <div class="card-body p-0">
              <table class="table table-borderless table-sm mb-0">
                <tbody>
                  <tr><td class="text-muted ps-4 py-2 w-40">Nominal DPP</td><td class="fw-semibold text-dark py-2">IDR {{ number_format($paymentAdviceDetail->payment_amount, 0, ',', '.') }}</td></tr>
                  <tr class="border-top"><td class="text-muted ps-4 py-2">Nominal + PPN</td><td class="fw-bold text-primary py-2">IDR {{ number_format($paymentAdviceDetail->payment_amount_with_tax, 0, ',', '.') }}</td></tr>
                  <tr class="border-top"><td class="text-muted ps-4 py-2">Remark / Catatan</td><td class="py-2 text-dark">{{ $paymentAdviceDetail->remark ?? '-' }}</td></tr>
                  <tr class="border-top"><td class="text-muted ps-4 py-2">Approved Date</td><td class="py-2 fw-semibold text-dark">{{ $paymentAdviceDetail->approved_date?->format('d M Y') ?? '-' }}</td></tr>
                  <tr class="border-top"><td class="text-muted ps-4 py-2">Date Paid</td><td class="py-2">
                    @if($paymentAdviceDetail->date_paid)
                      <span class="badge bg-label-success fw-bold">{{ $paymentAdviceDetail->date_paid->format('d M Y') }}</span>
                    @else
                      <span class="badge bg-label-secondary">BELUM DIBAYAR</span>
                    @endif
                  </td></tr>
                  <tr class="border-top">
                    <td class="text-muted ps-4 py-2">Bukti Slip Transfer</td>
                    <td class="py-2">
                      @if($paymentAdviceDetail->payment_receipt)
                        <button type="button" class="btn btn-xs btn-label-success rounded-pill px-3 py-1" data-bs-toggle="modal" data-bs-target="#viewReceiptModal">
                          <i class="bx bx-show me-1"></i>Preview Slip Transfer
                        </button>
                      @else
                        <span class="text-muted small">-</span>
                      @endif
                    </td>
                  </tr>
                  <tr class="border-top"><td class="text-muted ps-4 py-2">Created By</td><td class="py-2 text-dark">{{ $paymentAdviceDetail->paymentAdvice->owner?->name ?? 'Admin' }}</td></tr>
                  <tr class="border-top"><td class="text-muted ps-4 py-2">Tgl Dibuat</td><td class="py-2 text-muted">{{ $paymentAdviceDetail->created_at->format('d M Y, H:i') }}</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Approval History Card (Matching RF & PO Design Exactly) --}}
  <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
    <div class="card-header border-bottom bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <div class="bg-primary bg-opacity-10 rounded p-1">
          <i class="bx bx-check-shield text-primary fs-5"></i>
        </div>
        <h6 class="mb-0 fw-bold text-primary">Approval History & Workflow</h6>
      </div>
      <div>
        @if($paymentAdviceDetail->approval_status === 'Approved')
          <span class="badge bg-label-success fw-bold px-3 py-2 fs-7"><i class="bx bx-check-double me-1"></i>Fully Approved</span>
        @elseif($paymentAdviceDetail->approval_status === 'Submitted')
          <span class="badge bg-label-warning fw-bold px-3 py-2 fs-7"><i class="bx bx-time-five me-1"></i>Approval In Progress</span>
        @elseif($paymentAdviceDetail->approval_status === 'Draft')
          <span class="badge bg-label-secondary fw-bold px-3 py-2 fs-7"><i class="bx bx-edit me-1"></i>Draft</span>
        @elseif($paymentAdviceDetail->approval_status === 'Rejected')
          <span class="badge bg-label-danger fw-bold px-3 py-2 fs-7"><i class="bx bx-x-circle me-1"></i>Rejected</span>
        @endif
      </div>
    </div>
    <div class="table-responsive border-0">
      <table class="table table-hover align-middle mb-0 text-nowrap">
        <thead class="table-light">
          <tr class="text-muted text-uppercase small">
            <th style="width: 120px;">ACTION</th>
            <th>DATE</th>
            <th>STATUS</th>
            <th>ASSIGNED TO</th>
            <th>ACTUAL APPROVER</th>
            <th>COMMENTS / NOTES</th>
            <th style="width: 140px;" class="text-center">OVERALL STATUS</th>
          </tr>
        </thead>
        <tbody>
          @if($paymentAdviceDetail->approvals->count() === 0)
            <tr>
              <td colspan="7" class="text-muted text-center py-4 bg-light">
                <i class="bx bx-info-circle me-1"></i> Approval flow belum di-submit. Klik tombol <strong>Submit for Approval</strong> di atas untuk memulai alur persetujuan.
              </td>
            </tr>
          @else
            {{-- Submission Step --}}
            <tr class="bg-light">
              <td colspan="6" class="fw-bold py-2 px-3 text-primary">
                <i class="bx bx-paper-plane me-2"></i>Termin Payment Request Submitted
              </td>
              <td class="bg-success text-white fw-bold text-center py-2">
                <i class="bx bx-check-circle me-1"></i> Submitted
              </td>
            </tr>
            <tr class="bg-white">
              <td>-</td>
              <td>{{ $paymentAdviceDetail->created_date_sid ? \Carbon\Carbon::parse($paymentAdviceDetail->created_date_sid)->format('Y-m-d H:i') : $paymentAdviceDetail->created_at->format('Y-m-d H:i') }}</td>
              <td><span class="badge bg-label-info">Submitted</span></td>
              <td>{{ $paymentAdviceDetail->paymentAdvice->owner?->name ?: 'Finance' }}</td>
              <td>{{ $paymentAdviceDetail->paymentAdvice->owner?->name ?: 'Finance' }}</td>
              <td>Termin Submitted for approval</td>
              <td class="text-center"></td>
            </tr>

            {{-- Approval Steps --}}
            @foreach($paymentAdviceDetail->approvals()->orderBy('level')->get() as $approval)
              @php
                $statusBg = 'bg-secondary';
                $statusIcon = 'bx-minus';
                if ($approval->status === 'Approved') {
                    $statusBg = 'bg-success';
                    $statusIcon = 'bx-check-circle';
                } elseif ($approval->status === 'Pending') {
                    $statusBg = 'bg-warning';
                    $statusIcon = 'bx-time-five';
                } elseif ($approval->status === 'Rejected') {
                    $statusBg = 'bg-danger';
                    $statusIcon = 'bx-x-circle';
                }
              @endphp
              <tr class="bg-light">
                <td colspan="6" class="fw-bold py-2 px-3 text-dark">
                  <i class="bx bx-badge-check me-2 text-primary"></i>Step {{ $approval->level }}: {{ $approval->assignedRole?->name ?: ($approval->assignedUser?->name ?: ($approval->name ?: 'Level '.$approval->level)) }}
                </td>
                <td class="{{ $statusBg }} text-white fw-bold text-center py-2">
                  <i class="bx {{ $statusIcon }} me-1"></i> {{ $approval->status }}
                </td>
              </tr>
              <tr class="bg-white">
                <td>
                  @php
                    $user = auth()->user();
                    $canApproveStep = false;
                    if ($approval->status === 'Pending') {
                        if ($user->hasRole('superadmin')) {
                            $canApproveStep = true;
                        } elseif ($approval->assigned_to_user_id && $user->id == $approval->assigned_to_user_id) {
                            $canApproveStep = true;
                        } elseif ($approval->assigned_to_role_id) {
                            $canApproveStep = \Illuminate\Support\Facades\DB::connection('master')
                                ->table('role_user')
                                ->where('user_id', $user->id)
                                ->where('role_id', $approval->assigned_to_role_id)
                                ->exists();
                        } elseif (!$approval->assigned_to_user_id && !$approval->assigned_to_role_id) {
                            if ($user->hasRole('finance') || $user->hasRole('ceo') || $user->hasRole('superadmin')) {
                                $canApproveStep = true;
                            }
                        }
                    }
                  @endphp
                  @if($canApproveStep)
                    <div class="d-flex gap-1">
                      <button type="button" class="btn btn-xs btn-success px-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="bx bx-check me-1"></i>Approve
                      </button>
                      <button type="button" class="btn btn-xs btn-outline-danger px-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bx bx-x me-1"></i>Reject
                      </button>
                    </div>
                  @else
                    -
                  @endif
                </td>
                <td>{{ $approval->approved_at ? \Carbon\Carbon::parse($approval->approved_at)->format('Y-m-d H:i') : '-' }}</td>
                <td>
                  <span class="badge bg-label-{{ $statusBg === 'bg-success' ? 'success' : ($statusBg === 'bg-warning' ? 'warning' : ($statusBg === 'bg-danger' ? 'danger' : 'secondary')) }}">
                    {{ $approval->status }}
                  </span>
                </td>
                <td>{{ $approval->assignedUser?->name ?? $approval->assignedRole?->name ?? '-' }}</td>
                <td>{{ $approval->actualApprover?->name ?? '-' }}</td>
                <td>{{ $approval->comments ?: '-' }}</td>
                <td class="text-center"></td>
              </tr>
            @endforeach
          @endif
        </tbody>
      </table>
    </div>
  </div>

</div>

{{-- Modal Input / Edit Invoice Fisik Vendor --}}
<div class="modal fade" id="editInvoiceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25">
        <h5 class="modal-title text-primary fw-bold"><i class="bx bx-receipt me-2"></i>Input / Edit Invoice Vendor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('erp.payment-advice-details.update-invoice', $paymentAdviceDetail) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-bold">Nomor Invoice Resmi Vendor <span class="text-danger">*</span></label>
            <input type="text" name="invoice_no" class="form-control" required placeholder="Contoh: INV-2026/08/BU-002" value="{{ $paymentAdviceDetail->invoice_no }}">
            <small class="text-muted">Nomor surat tagihan atau faktur dari vendor/supplier.</small>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Upload Berkas Invoice / Faktur (PDF, JPG, PNG)</label>
            <input type="file" name="invoice_attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            <small class="text-muted">Maksimal ukuran file 10MB. Lampiran dapat diakses oleh Approver (CEO/Manager).</small>
          </div>
          @if($paymentAdviceDetail->invoice_attachment)
            <div class="alert alert-info py-2 px-3 small">
              <i class="bx bx-file me-1"></i>Berkas saat ini: <a href="{{ asset($paymentAdviceDetail->invoice_attachment) }}" target="_blank" class="fw-bold text-primary">Download/Lihat Berkas</a>
            </div>
          @endif
        </div>
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4"><i class="bx bx-save me-1"></i>Simpan Invoice</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal Submit Approval --}}
<div class="modal fade" id="submitApprovalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25">
        <h5 class="modal-title text-primary fw-bold"><i class="bx bx-paper-plane me-2"></i>Konfirmasi Pengajuan Approval</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('erp.payment-advice-details.submit', $paymentAdviceDetail) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body p-4">
          @if(!empty($paymentAdviceDetail->invoice_no) && $paymentAdviceDetail->invoice_no !== '-')
            <p class="text-muted mb-3">Apakah Anda yakin ingin mengajukan permohonan persetujuan (approval) untuk termin pembayaran berikut?</p>
            
            <div class="card bg-light border-0 p-3 mb-3 rounded-3">
              <div class="d-flex justify-content-between mb-1.5 small">
                <span class="text-muted">Jenis Termin:</span>
                <span class="fw-bold text-dark">{{ $paymentAdviceDetail->payment_type }}</span>
              </div>
              <div class="d-flex justify-content-between mb-1.5 small">
                <span class="text-muted">Nominal Pembayaran:</span>
                <span class="fw-bold text-primary fs-6">IDR {{ number_format($paymentAdviceDetail->payment_amount_with_tax, 0, ',', '.') }}</span>
              </div>
              <div class="d-flex justify-content-between mb-1.5 small">
                <span class="text-muted">Invoice Resmi Vendor:</span>
                <span class="badge bg-label-info fw-bold">{{ $paymentAdviceDetail->invoice_no }}</span>
              </div>
              <div class="d-flex justify-content-between small">
                <span class="text-muted">Lampiran Dokumen:</span>
                @if($paymentAdviceDetail->invoice_attachment)
                  <span class="text-success fw-semibold"><i class="bx bx-check-circle me-1"></i>Sudah Terlampir</span>
                @else
                  <span class="text-muted"><i class="bx bx-minus me-1"></i>Tidak ada lampiran</span>
                @endif
              </div>
            </div>

            <input type="hidden" name="invoice_no" value="{{ $paymentAdviceDetail->invoice_no }}">
          @else
            <div class="alert alert-warning mb-3 small">
              <i class="bx bx-info-circle me-1"></i>Nomor invoice vendor belum diisi. Anda dapat mengisinya di bawah ini:
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Nomor Invoice Supplier Fisik <span class="text-danger">*</span></label>
              <input type="text" name="invoice_no" class="form-control" required placeholder="Misal: INV/2026/08/999" value="{{ $paymentAdviceDetail->invoice_no }}">
              <small class="text-muted">Nomor invoice resmi dari vendor/supplier untuk termin ini.</small>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Upload Berkas Invoice Vendor (Opsional)</label>
              <input type="file" name="invoice_attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
              <small class="text-muted">Lampirkan file PDF / scan invoice vendor agar memudahkan verifikasi Approver.</small>
            </div>
          @endif

          @if($paymentAdviceDetail->purchaseOrder && $paymentAdviceDetail->purchaseOrder->goodsReceipts->count() > 1)
            <div class="mb-3">
              <label class="form-label fw-bold">Referensi Dokumen GR / Surat Jalan (Opsional)</label>
              <select name="erp_goods_receipt_id" class="form-select">
                <option value="">-- Hubungkan ke Semua Dokumen GR (Pengiriman Bertahap) --</option>
                @foreach($paymentAdviceDetail->purchaseOrder->goodsReceipts as $g)
                  <option value="{{ $g->id }}" {{ $paymentAdviceDetail->erp_goods_receipt_id == $g->id ? 'selected' : '' }}>
                    {{ $g->do_no }} (Status: {{ $g->status }})
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Pilih surat jalan GR tertentu jika pembayaran ini khusus untuk pengiriman termin bertahap.</small>
            </div>
          @endif
        </div>
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4"><i class="bx bx-paper-plane me-1"></i>Ya, Ajukan Approval</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal Mark as Paid with Slip Upload --}}
<div class="modal fade" id="markPaidModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success bg-opacity-10 border-bottom border-success border-opacity-25">
        <h5 class="modal-title text-success fw-bold"><i class="bx bx-check-double me-2"></i>Konfirmasi Pencairan / Pembayaran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('erp.payment-advice-details.mark-paid', $paymentAdviceDetail) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body p-4">
          <p class="mb-3 text-muted">Konfirmasi bahwa termin <strong>{{ $paymentAdviceDetail->payment_type }}</strong> sebesar <strong>IDR {{ number_format($paymentAdviceDetail->payment_amount_with_tax, 0, ',', '.') }}</strong> telah dibayarkan/ditransfer ke rekening vendor.</p>
          <div class="mb-3">
            <label class="form-label fw-bold">Upload Bukti Slip Transfer Bank (Opsional)</label>
            <input type="file" name="payment_receipt" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            <small class="text-muted">Lampirkan resi transfer bank / bukti kas keluar sebagai arsip audit.</small>
          </div>
        </div>
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success btn-sm rounded-pill px-4"><i class="bx bx-check me-1"></i>Konfirmasi Lunas (Mark as Paid)</button>
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
          <p class="text-muted mb-3">Setujui pengajuan termin <strong>{{ $paymentAdviceDetail->payment_type }}</strong> ({{ $paymentAdviceDetail->supplier_detail_no }}).</p>
          <div class="mb-3">
            <label class="form-label fw-bold">Comments (Opsional)</label>
            <textarea name="comments" class="form-control" rows="3" placeholder="Tuliskan komentar approval di sini..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success btn-sm rounded-pill px-4"><i class="bx bx-check me-1"></i>Approve</button>
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
          <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4"><i class="bx bx-x me-1"></i>Reject</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if($paymentAdviceDetail->invoice_attachment)
  {{-- Modal Preview Invoice Vendor --}}
  <div class="modal fade" id="viewInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-header bg-light py-3 border-bottom d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="bx bx-receipt text-primary fs-4"></i>
            <h5 class="modal-title fw-bold text-dark mb-0">Preview Invoice: {{ $paymentAdviceDetail->invoice_no ?: $paymentAdviceDetail->supplier_detail_no }}</h5>
          </div>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ asset($paymentAdviceDetail->invoice_attachment) }}" download class="btn btn-sm btn-outline-primary rounded-pill px-3">
              <i class="bx bx-download me-1"></i>Download
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
        </div>
        <div class="modal-body p-3 bg-dark bg-opacity-10 text-center">
          @php
            $invExt = strtolower(pathinfo($paymentAdviceDetail->invoice_attachment, PATHINFO_EXTENSION));
          @endphp
          @if(in_array($invExt, ['jpg', 'jpeg', 'png', 'webp', 'gif']))
            <div class="p-2 bg-white rounded-3 shadow-sm d-inline-block">
              <img src="{{ asset($paymentAdviceDetail->invoice_attachment) }}" class="img-fluid rounded" style="max-height: 78vh; object-fit: contain;" alt="Invoice Vendor">
            </div>
          @else
            <iframe src="{{ asset($paymentAdviceDetail->invoice_attachment) }}" class="w-100 rounded-3 shadow-sm" style="height: 78vh; border: none;"></iframe>
          @endif
        </div>
      </div>
    </div>
  </div>
@endif

@if($paymentAdviceDetail->payment_receipt)
  {{-- Modal Preview Bukti Slip Transfer --}}
  <div class="modal fade" id="viewReceiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-header bg-light py-3 border-bottom d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="bx bx-check-double text-success fs-4"></i>
            <h5 class="modal-title fw-bold text-dark mb-0">Preview Slip Bukti Transfer: {{ $paymentAdviceDetail->supplier_detail_no }}</h5>
          </div>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ asset($paymentAdviceDetail->payment_receipt) }}" download class="btn btn-sm btn-outline-success rounded-pill px-3">
              <i class="bx bx-download me-1"></i>Download
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
        </div>
        <div class="modal-body p-3 bg-dark bg-opacity-10 text-center">
          @php
            $rcpExt = strtolower(pathinfo($paymentAdviceDetail->payment_receipt, PATHINFO_EXTENSION));
          @endphp
          @if(in_array($rcpExt, ['jpg', 'jpeg', 'png', 'webp', 'gif']))
            <div class="p-2 bg-white rounded-3 shadow-sm d-inline-block">
              <img src="{{ asset($paymentAdviceDetail->payment_receipt) }}" class="img-fluid rounded" style="max-height: 78vh; object-fit: contain;" alt="Slip Transfer">
            </div>
          @else
            <iframe src="{{ asset($paymentAdviceDetail->payment_receipt) }}" class="w-100 rounded-3 shadow-sm" style="height: 78vh; border: none;"></iframe>
          @endif
        </div>
      </div>
    </div>
  </div>
@endif

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });
  });
</script>
@endsection