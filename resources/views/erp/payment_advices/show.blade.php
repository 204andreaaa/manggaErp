@extends('layouts.home')

@section('title', 'Payment Advice Detail: ' . $paymentAdvice->supplier_invoice_no)

@section('content')
<style>
  .po-nav-tabs .nav-link {
    color: #64748b;
    border: none;
    border-bottom: 3px solid transparent;
    border-radius: 0;
    font-weight: 500;
    transition: all 0.2s ease;
  }
  .po-nav-tabs .nav-link:hover {
    color: #4f46e5;
    background: #f8fafc;
  }
  .po-nav-tabs .nav-link.active {
    color: #4f46e5 !important;
    background: #ffffff !important;
    border-bottom-color: #4f46e5 !important;
    font-weight: 700 !important;
  }
</style>

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

  {{-- Page Title & Toolbar --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
      <h4 class="mb-1 fw-bold text-dark"><i class="bx bx-money text-primary me-2"></i>Payment Advice Detail</h4>
      <div class="text-muted small">
        PA No: <span class="fw-bold text-dark me-2">{{ $paymentAdvice->supplier_invoice_no }}</span>
        @if($paymentAdvice->purchaseOrder)
        Reference PO: 
        <a href="{{ route('erp.purchase-orders.show', $paymentAdvice->purchaseOrder) }}" class="badge bg-label-primary fs-7 text-decoration-none">
          {{ $paymentAdvice->purchaseOrder->po_no }}
        </a>
        @endif
      </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex align-items-center flex-wrap gap-2">
      <a href="{{ route('erp.payment-advices.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
        <i class="bx bx-arrow-back me-1"></i>Back to List
      </a>

      {{-- Edit & Delete Buttons --}}
      @if(auth()->user()->hasRole('superadmin'))
        <form action="{{ route('erp.payment-advices.destroy', $paymentAdvice) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Payment Advice ini secara permanen?');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
            <i class="bx bx-trash me-1"></i>Delete
          </button>
        </form>
      @endif

    </div>
  </div>

  {{-- Stats Summary Row --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-primary bg-opacity-10 h-100">
        <div class="text-muted small fw-semibold">TOTAL INVOICE (WITH TAX)</div>
        <h5 class="mb-0 fw-extrabold text-primary">IDR {{ number_format($paymentAdvice->total_invoice_amount_with_tax, 0, ',', '.') }}</h5>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
        <div class="text-muted small fw-semibold">SUPPLIER</div>
        <h6 class="mb-0 fw-bold text-dark text-truncate">{{ $paymentAdvice->supplier?->name ?: '-' }}</h6>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100 border-start border-4 border-warning">
        <div class="text-muted small fw-semibold">STATUS</div>
        <div>
          @php
            $stBadge = match($paymentAdvice->status) {
              'Approved', 'Completed' => 'bg-success',
              'Submitted' => 'bg-warning',
              'Rejected' => 'bg-danger',
              default => 'bg-secondary',
            };
          @endphp
          <span class="badge {{ $stBadge }} px-3 py-1 fs-7 fw-bold">
            @if($paymentAdvice->status === 'Completed')
              <i class="bx bx-check-circle me-1"></i>
            @endif
            {{ $paymentAdvice->status }}
          </span>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100 border-start border-4 border-danger">
        <div class="text-muted small fw-semibold">OUTSTANDING BALANCE</div>
        <h6 class="mb-0 fw-bold text-danger">IDR {{ number_format($paymentAdvice->outstanding, 0, ',', '.') }}</h6>
      </div>
    </div>
  </div>

  {{-- Main Container Card --}}
  <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
    
    {{-- Tab Navigation Bar --}}
    <div class="bg-white border-bottom">
      <ul class="nav nav-tabs nav-fill po-nav-tabs border-0" id="paShowTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active py-3" id="tab-overview-btn" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
            <i class="bx bx-buildings me-2 fs-5"></i>1. Overview Info
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-items-btn" data-bs-toggle="tab" data-bs-target="#tab-items" type="button" role="tab">
            <i class="bx bx-receipt me-2 fs-5"></i>2. Termin Pembayaran <span class="badge bg-primary rounded-pill ms-1">{{ $paymentAdvice->details->count() }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-approval-btn" data-bs-toggle="tab" data-bs-target="#tab-approval" type="button" role="tab">
            <i class="bx bx-shield-check me-2 fs-5"></i>3. Approval History
          </button>
        </li>
      </ul>
    </div>

    <div class="card-body p-4 bg-light bg-opacity-50">
      <div class="tab-content p-0 border-0 shadow-none bg-transparent" id="paShowTabContent">
        
        {{-- TAB 1: Overview --}}
        <div class="tab-pane fade show active" id="tab-overview" role="tabpanel" tabindex="0">
          <div class="row g-4">
            {{-- Left Column --}}
            <div class="col-lg-6">
              <div class="card shadow-none border rounded-3 h-100">
                <div class="card-header border-bottom bg-white py-3">
                  <h6 class="mb-0 fw-bold text-dark"><i class="bx bx-info-circle text-primary me-2"></i>General Information</h6>
                </div>
                <div class="card-body p-0">
                  <table class="table table-borderless table-sm mb-0">
                    <tbody>
                      <tr><td class="text-muted ps-4 py-2 w-40">Supplier Invoice No</td><td class="fw-bold text-dark py-2">{{ $paymentAdvice->supplier_invoice_no }}</td></tr>
                      <tr class="border-top"><td class="text-muted ps-4 py-2">PO No</td><td class="py-2">
                        @if($paymentAdvice->purchaseOrder)
                          <a href="{{ route('erp.purchase-orders.show', $paymentAdvice->purchaseOrder) }}" class="fw-bold text-primary">{{ $paymentAdvice->purchaseOrder->po_no }}</a>
                        @else
                          -
                        @endif
                      </td></tr>
                      <tr class="border-top"><td class="text-muted ps-4 py-2">Supplier Name</td><td class="fw-semibold text-dark py-2">{{ $paymentAdvice->supplier?->name ?? '-' }}</td></tr>
                      <tr class="border-top"><td class="text-muted ps-4 py-2">Invoice No</td><td class="py-2">{{ $paymentAdvice->invoice_no }}</td></tr>
                      <tr class="border-top"><td class="text-muted ps-4 py-2">Contact Person</td><td class="py-2">{{ $paymentAdvice->contact_person ?? '-' }}</td></tr>
                      <tr class="border-top"><td class="text-muted ps-4 py-2">Payment Terms (TOP)</td><td class="py-2"><span class="badge bg-label-primary fw-bold">{{ $paymentAdvice->purchaseOrder?->payment_terms ?: ($paymentAdvice->details->pluck('payment_type')->implode(', ') ?: 'Standard') }}</span></td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            {{-- Right Column --}}
            <div class="col-lg-6">
              <div class="card shadow-none border rounded-3 mb-4">
                <div class="card-header border-bottom bg-white py-3">
                  <h6 class="mb-0 fw-bold text-dark"><i class="bx bx-time-five text-primary me-2"></i>Status & Dates</h6>
                </div>
                <div class="card-body p-0">
                  <table class="table table-borderless table-sm mb-0">
                    <tbody>
                      <tr><td class="text-muted ps-4 py-2 w-40">Owner</td><td class="fw-semibold text-dark py-2">{{ $paymentAdvice->owner?->name ?? '-' }}</td></tr>
                      <tr class="border-top"><td class="text-muted ps-4 py-2">Due Date (Jatuh Tempo)</td><td class="py-2">
                        @if($paymentAdvice->due_date)
                          <div class="d-flex align-items-center flex-wrap gap-2">
                            <span class="fw-bold text-dark">{{ $paymentAdvice->due_date->format('d M Y') }}</span>
                            @php
                              $diffDays = (int) now()->startOfDay()->diffInDays($paymentAdvice->due_date->startOfDay(), false);
                            @endphp
                            @if($paymentAdvice->payment_closed)
                              <span class="badge bg-label-success"><i class="bx bx-check-double me-1"></i>Lunas</span>
                            @elseif($diffDays < 0)
                              <span class="badge bg-danger"><i class="bx bx-error me-1"></i>Terlambat {{ abs($diffDays) }} Hari</span>
                            @elseif($diffDays === 0)
                              <span class="badge bg-warning"><i class="bx bx-time me-1"></i>Jatuh Tempo Hari Ini</span>
                            @else
                              <span class="badge bg-label-info"><i class="bx bx-time-five me-1"></i>Sisa {{ $diffDays }} Hari Lagi</span>
                            @endif
                          </div>
                        @else
                          <span class="text-muted">-</span>
                        @endif
                      </td></tr>
                      <tr class="border-top"><td class="text-muted ps-4 py-2">Payment Closed</td><td class="py-2">
                        @if($paymentAdvice->payment_closed)
                          <span class="badge bg-success"><i class="bx bx-check-double me-1"></i>Yes (Lunas)</span>
                        @else
                          <span class="badge bg-label-secondary"><i class="bx bx-time me-1"></i>No (Berjalan)</span>
                        @endif
                      </td></tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Payment Summary Box -->
              <div class="card shadow-none border rounded-3 bg-primary bg-opacity-10 border-primary border-opacity-25">
                <div class="card-body p-3">
                  <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-calculator me-1"></i>Payment Summary</h6>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Total Invoice Amount:</span>
                    <span class="fw-bold">IDR {{ number_format($paymentAdvice->total_invoice_amount, 0, ',', '.') }}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Total Invoice (With Tax):</span>
                    <span class="fw-bold text-primary">IDR {{ number_format($paymentAdvice->total_invoice_amount_with_tax, 0, ',', '.') }}</span>
                  </div>
                  <hr class="my-2 border-primary border-opacity-25">
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Sum of Payment Amount:</span>
                    <span class="fw-bold">IDR {{ number_format($paymentAdvice->sum_payment_amount, 0, ',', '.') }}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Sum of Payment (With Tax):</span>
                    <span class="fw-bold text-success">IDR {{ number_format($paymentAdvice->sum_payment_amount_with_tax, 0, ',', '.') }}</span>
                  </div>
                  <hr class="my-2 border-primary border-opacity-25">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="text-danger small fw-bold">Outstanding Balance:</span>
                    <span class="fw-extrabold text-danger fs-5">IDR {{ number_format($paymentAdvice->outstanding, 0, ',', '.') }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- TAB 2: Termin Pembayaran --}}
        <div class="tab-pane fade" id="tab-items" role="tabpanel" tabindex="0">
          <div class="card shadow-none border rounded-3">
            <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
              <h6 class="mb-0 fw-bold text-dark"><i class="bx bx-receipt me-1 text-primary"></i>Payment Advice Detail (Termin Pembayaran)</h6>
              @php
                $unallocatedHeader = max(0, $paymentAdvice->total_invoice_amount_with_tax - $paymentAdvice->details->sum('payment_amount_with_tax'));
              @endphp
              @if(!$paymentAdvice->payment_closed && (auth()->user()->hasRole('finance') || auth()->user()->hasRole('superadmin')))
                @if($unallocatedHeader > 0)
                  <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addTerminModal">
                    <i class="bx bx-plus me-1"></i>+ Add / Custom Termin
                  </button>
                @else
                  <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3 shadow-sm" disabled data-bs-toggle="tooltip" title="Alokasi termin sudah mencapai 100% dari total tagihan PO (Sisa Rp 0)">
                    <i class="bx bx-check-double me-1"></i>Termin 100% Lengkap
                  </button>
                @endif
              @elseif($paymentAdvice->payment_closed)
                <span class="badge bg-label-success rounded-pill px-3"><i class="bx bx-check-shield me-1"></i>Lunas (Closed)</span>
              @endif
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="ps-4">No.</th>
                    <th>Supplier Detail No</th>
                    <th>Jenis / Termin</th>
                    <th class="text-end">Nominal Bayar</th>
                    <th class="text-end">With Tax</th>
                    <th class="text-center">Status</th>
                    <th>Approved Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody class="border-top-0">
                  @forelse($paymentAdvice->details as $idx => $pad)
                    <tr>
                      <td class="ps-4 text-muted">{{ $idx + 1 }}</td>
                      <td>
                        <a href="{{ route('erp.payment-advice-details.show', $pad) }}" class="fw-bold text-primary">{{ $pad->supplier_detail_no }}</a>
                      </td>
                      <td><span class="badge bg-label-info">{{ $pad->payment_type }}</span></td>
                      <td class="text-end text-muted">IDR {{ number_format($pad->payment_amount, 0, ',', '.') }}</td>
                      <td class="text-end fw-bold text-dark">IDR {{ number_format($pad->payment_amount_with_tax, 0, ',', '.') }}</td>
                      <td class="text-center">
                        @if($pad->approval_status === 'Approved')
                          <span class="badge bg-label-success fw-bold"><i class="bx bx-check-circle me-1"></i>Approved</span>
                        @elseif($pad->approval_status === 'Submitted')
                          <span class="badge bg-label-warning fw-bold"><i class="bx bx-time me-1"></i>Submitted</span>
                        @elseif($pad->approval_status === 'Rejected')
                          <span class="badge bg-label-danger fw-bold"><i class="bx bx-x-circle me-1"></i>Rejected</span>
                        @else
                          @php $prevUnapp = $pad->previousUnapprovedDetail(); @endphp
                          @if($prevUnapp)
                            <span class="badge bg-label-secondary fw-semibold" data-bs-toggle="tooltip" title="Terkunci: Menunggu termin sebelumnya ({{ $prevUnapp->payment_type }}) di-approve">
                              <i class="bx bx-lock-alt me-1 text-warning"></i>Draft
                            </span>
                          @else
                            <span class="badge bg-label-secondary fw-semibold">Draft</span>
                          @endif
                        @endif
                      </td>
                      <td>{{ $pad->approved_date?->format('d M Y') ?? '-' }}</td>
                      <td>
                        <div class="d-flex align-items-center gap-1">
                          <a href="{{ route('erp.payment-advice-details.show', $pad) }}" class="btn btn-sm btn-icon btn-label-primary rounded-circle" data-bs-toggle="tooltip" title="Lihat Detail Termin"><i class="bx bx-show"></i></a>
                          @if((auth()->user()->hasRole('finance') || auth()->user()->hasRole('superadmin')) && ($pad->approval_status === 'Draft' || empty($pad->approval_status)))
                            <form action="{{ route('erp.payment-advice-details.destroy', $pad) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus termin ini untuk menyesuaikan jadwal custom?');">
                              @csrf
                              @method('DELETE')
                              <button type="submit" class="btn btn-sm btn-icon btn-outline-danger rounded-circle" data-bs-toggle="tooltip" title="Hapus Termin Draft"><i class="bx bx-trash"></i></button>
                            </form>
                          @endif
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="8" class="text-center text-muted py-5">
                        <div class="mb-3"><i class="bx bx-receipt fs-1 text-light"></i></div>
                        Belum ada rincian termin pembayaran.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {{-- TAB 3: Approval History (Rekap Seluruh Termin) --}}
        <div class="tab-pane fade" id="tab-approval" role="tabpanel" tabindex="0">
          @php
            $hasAnyApprovals = $paymentAdvice->details->some(fn($d) => $d->approvals->isNotEmpty()) || $paymentAdvice->approvals->isNotEmpty();
          @endphp

          @if(!$hasAnyApprovals && $paymentAdvice->details->isEmpty())
            <div class="card shadow-none border rounded-3 text-center py-5">
              <div class="card-body">
                <div class="mb-3"><i class="bx bx-shield-x fs-1 text-muted"></i></div>
                <h6 class="fw-bold text-dark">Belum Ada Riwayat Approval</h6>
                <p class="text-muted small mb-0">Rincian termin pembayaran belum memiliki riwayat approval.</p>
              </div>
            </div>
          @else
            <div class="d-flex flex-column gap-4">
              @foreach($paymentAdvice->details as $idx => $detail)
                @php
                  $detailBadgeColor = match($detail->approval_status) {
                    'Approved' => 'success',
                    'Submitted' => 'warning',
                    'Rejected' => 'danger',
                    default => 'secondary'
                  };
                @endphp
                <div class="card shadow-none border rounded-3 overflow-hidden">
                  <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-primary rounded-pill px-2.5 py-1">Termin #{{ $idx + 1 }}</span>
                      <h6 class="mb-0 fw-bold text-dark">{{ $detail->payment_type }}</h6>
                      <span class="text-muted small">({{ $detail->supplier_detail_no }})</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                      <div class="text-end">
                        <span class="text-muted small">Nominal:</span>
                        <span class="fw-bold text-primary ms-1">IDR {{ number_format($detail->payment_amount_with_tax, 0, ',', '.') }}</span>
                      </div>
                      <span class="badge bg-label-{{ $detailBadgeColor }} fw-bold px-3 py-1.5 fs-7">
                        @if($detail->approval_status === 'Approved')
                          <i class="bx bx-check-circle me-1"></i>
                        @elseif($detail->approval_status === 'Submitted')
                          <i class="bx bx-time me-1"></i>
                        @endif
                        {{ $detail->approval_status }}
                      </span>
                      <a href="{{ route('erp.payment-advice-details.show', $detail) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1">
                        <i class="bx bx-show me-1"></i>Buka Termin
                      </a>
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
                        @if($detail->approvals->isEmpty())
                          <tr>
                            <td colspan="7" class="text-center py-4 text-muted small bg-light">
                              @if($detail->approval_status === 'Draft')
                                <i class="bx bx-info-circle me-1 text-secondary"></i>Termin ini masih berstatus <strong>Draft</strong> (belum diajukan untuk approval).
                              @else
                                <i class="bx bx-info-circle me-1 text-secondary"></i>Belum ada data workflow bertingkat.
                              @endif
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
                            <td>{{ $detail->created_date_sid ? \Carbon\Carbon::parse($detail->created_date_sid)->format('Y-m-d H:i') : $detail->created_at->format('Y-m-d H:i') }}</td>
                            <td><span class="badge bg-label-info">Submitted</span></td>
                            <td>{{ $paymentAdvice->owner?->name ?: 'Finance' }}</td>
                            <td>{{ $paymentAdvice->owner?->name ?: 'Finance' }}</td>
                            <td>Termin Submitted for approval</td>
                            <td class="text-center"></td>
                          </tr>

                          {{-- Approval Steps --}}
                          @foreach($detail->approvals->sortBy('level') as $approval)
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
                                @if($approval->status === 'Pending')
                                  <a href="{{ route('erp.payment-advice-details.show', $detail) }}" class="btn btn-xs btn-primary px-2">
                                    <i class="bx bx-check-shield me-1"></i>Proses
                                  </a>
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
              @endforeach

              {{-- Sisa Approval Header Legacy jika ada --}}
              @if($paymentAdvice->approvals->isNotEmpty())
                <div class="card shadow-none border rounded-3 overflow-hidden mt-2">
                  <div class="card-header bg-light py-2 border-bottom">
                    <h6 class="mb-0 fw-bold text-muted small"><i class="bx bx-archive me-1"></i>Riwayat Approval Header (Legacy)</h6>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 small">
                      <thead class="table-light">
                        <tr>
                          <th class="ps-4">Step</th>
                          <th>Status</th>
                          <th>Assigned</th>
                          <th>Action By</th>
                          <th>Date</th>
                          <th>Comments</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($paymentAdvice->approvals->sortBy('level') as $hAppr)
                          <tr>
                            <td class="ps-4 fw-semibold">Level {{ $hAppr->level }}</td>
                            <td><span class="badge bg-label-{{ $hAppr->status === 'Approved' ? 'success' : ($hAppr->status === 'Pending' ? 'warning' : 'secondary') }}">{{ $hAppr->status }}</span></td>
                            <td>{{ $hAppr->assignedUser?->name ?? '-' }}</td>
                            <td>{{ $hAppr->actualApprover?->name ?? '-' }}</td>
                            <td>{{ $hAppr->approved_at ? \Carbon\Carbon::parse($hAppr->approved_at)->format('d M Y, H:i') : '-' }}</td>
                            <td>{{ $hAppr->comments ?? '-' }}</td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              @endif

            </div>
          @endif
        </div>

      </div>
    </div>
  </div>

</div>

<!-- Modal Tambah Termin Payment Advice Detail -->
<div class="modal fade" id="addTerminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="{{ route('erp.payment-advice-details.store', $paymentAdvice) }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold text-primary"><i class="bx bx-plus-circle me-1"></i>Tambah Termin Pembayaran Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        @php
          $unallocatedInModal = max(0, $paymentAdvice->total_invoice_amount_with_tax - $paymentAdvice->details->sum('payment_amount_with_tax'));
        @endphp
        <div class="alert alert-info py-2 px-3 small mb-3">
          <i class="bx bx-info-circle me-1"></i>Sisa Kuota Termin yang Belum Terjadwal: <strong>IDR {{ number_format($unallocatedInModal, 0, ',', '.') }}</strong>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Jenis Pembayaran (Payment Type) <span class="text-danger">*</span></label>
          <select name="payment_type" class="form-select" required>
            <option value="Final Payment (Pelunasan 100%)">Final Payment (Pelunasan 100%)</option>
            <option value="Partial Payment / DP (Cicilan / Bertahap)">Partial Payment / DP (Cicilan / Bertahap)</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Nominal Pembayaran Termin (IDR) <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text">IDR</span>
            <input type="number" step="0.01" min="1" max="{{ $unallocatedInModal }}" name="payment_amount" class="form-control" value="{{ $unallocatedInModal }}" placeholder="Nominal termin" required>
          </div>
          <small class="text-muted">Maksimal: IDR {{ number_format($unallocatedInModal, 0, ',', '.') }} (Tidak boleh melebihi sisa total tagihan).</small>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Metode Pembayaran <span class="text-danger">*</span></label>
          <select name="payment_method" class="form-select" required>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cash">Cash</option>
            <option value="Cheque">Cheque</option>
            <option value="Credit Card">Credit Card</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Keterangan / Remark</label>
          <textarea name="remark" class="form-control" rows="2" placeholder="Catatan termin pembayaran..."></textarea>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-save me-1"></i>Simpan Termin</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });
  });
</script>
@endsection