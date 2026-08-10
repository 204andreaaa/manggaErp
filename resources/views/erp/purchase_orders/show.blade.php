@extends('layouts.home')

@section('title', 'PO Detail: ' . $purchaseOrder->po_no)

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
      <h4 class="mb-1 fw-bold text-dark"><i class="bx bx-cart text-primary me-2"></i>Purchase Order Detail</h4>
      <div class="text-muted small">
        PO No: <span class="fw-bold text-dark me-2">{{ $purchaseOrder->po_no }}</span>
        Reference RF: 
        <a href="{{ route('erp.request-form.show', $purchaseOrder->requestForm) }}" class="badge bg-label-primary fs-7 text-decoration-none">
          {{ $purchaseOrder->requestForm->rf_no }}
        </a>
      </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex align-items-center flex-wrap gap-2">
      <a href="{{ route('erp.procurement.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
        <i class="bx bx-arrow-back me-1"></i>Dashboard
      </a>

      <a href="{{ route('erp.purchase-orders.print', $purchaseOrder) }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">
        <i class="bx bx-printer me-1"></i>Print PO
      </a>

      {{-- Edit & Delete Buttons --}}
      @if($purchaseOrder->status === 'Draft' || $purchaseOrder->status === 'Rejected')
        <a href="{{ route('erp.purchase-orders.edit', $purchaseOrder) }}" class="btn btn-outline-warning btn-sm rounded-pill px-3">
          <i class="bx bx-edit me-1"></i>Edit
        </a>
      @endif

      @if(auth()->user()->hasRole('superadmin'))
        <form action="{{ route('erp.purchase-orders.destroy', $purchaseOrder) }}" method="POST" class="d-inline" onsubmit="return confirm('PERINGATAN KERAS!\n\nMenghapus PO ini akan berakibat:\n1. Dokumen PO ({{ $purchaseOrder->po_no }}) Dihapus Permanen.\n2. {{ $purchaseOrder->items->count() }} Item di dalam PO ini ikut terhapus.\n3. Histori Approval PO terhapus.\n4. Status Barang (PR Item) di RF akan dikembalikan menjadi \'Requested\'.\n\nApakah Anda sangat yakin ingin melanjutkan?');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
            <i class="bx bx-trash me-1"></i>Delete
          </button>
        </form>
      @endif

      {{-- Verification Button --}}
      @if(!$purchaseOrder->verified_by_id)
        @if(auth()->user()->hasRole('finance') || auth()->user()->hasRole('superadmin'))
          <form action="{{ route('erp.purchase-orders.verify', $purchaseOrder) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
              <i class="bx bx-check-shield me-1"></i>Verify PO
            </button>
          </form>
        @endif
      @else
        <span class="badge bg-success px-3 py-2 fs-7"><i class="bx bx-check-double me-1"></i>Verified</span>
      @endif

      {{-- Submit for Approval Button --}}
      @if($purchaseOrder->status === 'Draft')
        @if($purchaseOrder->verified_by_id)
          <form action="{{ route('erp.purchase-orders.submit', $purchaseOrder) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success btn-sm rounded-pill px-3">
              <i class="bx bx-paper-plane me-1"></i>Submit for Approval
            </button>
          </form>
        @else
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3 opacity-75" onclick="Swal.fire({icon: 'warning', title: 'Verification Required', text: 'PO must be verified by Finance first before it can be submitted for approval.', confirmButtonColor: '#4f46e5'})">
            <i class="bx bx-lock-alt me-1"></i>Submit for Approval
          </button>
        @endif
      @elseif($purchaseOrder->status === 'Submitted')
        @php
          $user = auth()->user();
          $canApprove = false;
          $activeApproval = $purchaseOrder->approvals()->where('status', 'Pending')->first();
          if ($activeApproval) {
              if ($user->hasRole('superadmin')) {
                  $canApprove = true;
              } elseif ($activeApproval->assigned_to_user_id && $user->id == $activeApproval->assigned_to_user_id) {
                  $canApprove = true;
              } elseif ($activeApproval->assigned_to_role_id) {
                  $hasRole = \Illuminate\Support\Facades\DB::connection('tenant')
                      ->table('role_user')
                      ->where('user_id', $user->id)
                      ->where('role_id', $activeApproval->assigned_to_role_id)
                      ->exists();
                  if ($hasRole) {
                      $canApprove = true;
                  }
              }
          } else {
              if ($purchaseOrder->total_po_amount_with_tax <= 1000000) {
                  if ($user->hasRole('procurement') || $user->hasRole('superadmin')) {
                      $canApprove = true;
                  }
              } else {
                  if ($user->hasRole('ceo') || $user->hasRole('superadmin')) {
                      $canApprove = true;
                  }
              }
          }
        @endphp
        @if($canApprove)
          <button type="button" class="btn btn-success btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#approvePoModal">
            <i class="bx bx-check me-1"></i>Approve PO
          </button>
          <button type="button" class="btn btn-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#rejectPoModal">
            <i class="bx bx-x me-1"></i>Reject PO
          </button>
        @endif
      @endif

      {{-- Goods Receipt Button --}}
      @if($purchaseOrder->status === 'Approved' && !$purchaseOrder->gr && !$purchaseOrder->goodsReceipts()->where('status', 'Received')->exists())
        <a href="{{ route('erp.goods-receipts.create', $purchaseOrder) }}" class="btn btn-success btn-sm rounded-pill px-3">
          <i class="bx bx-package me-1"></i>Create Goods Receipt
        </a>
      @elseif($purchaseOrder->gr || $purchaseOrder->status === 'Completed')
        <span class="badge bg-success px-3 py-2 fs-7"><i class="bx bx-check-circle me-1"></i>GR Completed</span>
      @endif
    </div>
  </div>

  {{-- Stats Summary Row --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-primary bg-opacity-10 h-100">
        <div class="text-muted small fw-semibold">TOTAL PO AMOUNT (WITH TAX)</div>
        <h5 class="mb-0 fw-extrabold text-primary">IDR {{ number_format($purchaseOrder->total_po_amount_with_tax, 0, ',', '.') }}</h5>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
        <div class="text-muted small fw-semibold">SUPPLIER</div>
        <h6 class="mb-0 fw-bold text-dark text-truncate">{{ $purchaseOrder->supplier?->name ?: '-' }}</h6>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100 border-start border-4 border-warning">
        <div class="text-muted small fw-semibold">STATUS</div>
        <div>
          @php
            $stBadge = match($purchaseOrder->status) {
              'Approved', 'Completed' => 'bg-success',
              'Submitted' => 'bg-warning',
              'Rejected' => 'bg-danger',
              default => 'bg-secondary',
            };
          @endphp
          <span class="badge {{ $stBadge }} px-3 py-1 fs-7 fw-bold">{{ $purchaseOrder->status }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
        <div class="text-muted small fw-semibold">BALANCE AMOUNT</div>
        <h6 class="mb-0 fw-bold text-dark">IDR {{ number_format($purchaseOrder->balance_amount, 0, ',', '.') }}</h6>
      </div>
    </div>
  </div>

  {{-- Main Container Card --}}
  <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
    
    {{-- Tab Navigation Bar --}}
    <div class="bg-white border-bottom">
      <ul class="nav nav-tabs nav-fill po-nav-tabs border-0" id="poShowTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active py-3" id="tab-overview-btn" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
            <i class="bx bx-buildings me-2 fs-5"></i>1. Overview & Supplier Info
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-items-btn" data-bs-toggle="tab" data-bs-target="#tab-items" type="button" role="tab">
            <i class="bx bx-package me-2 fs-5"></i>2. PO Line Items <span class="badge bg-primary rounded-pill ms-1">{{ $purchaseOrder->items->count() }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-approval-btn" data-bs-toggle="tab" data-bs-target="#tab-approval" type="button" role="tab">
            <i class="bx bx-shield-check me-2 fs-5"></i>3. Approval History & Workflow
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-do-btn" data-bs-toggle="tab" data-bs-target="#tab-do" type="button" role="tab">
            <i class="bx bx-truck me-2 fs-5"></i>4. Goods Receipts (DO)
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-payments-btn" data-bs-toggle="tab" data-bs-target="#tab-payments" type="button" role="tab">
            <i class="bx bx-credit-card me-2 fs-5"></i>5. Payments & Attachments
          </button>
        </li>
      </ul>
    </div>

    {{-- Tab Content Panes --}}
    <div class="card-body p-4 tab-content" id="poShowTabContent">

      {{-- Tab 1: Overview & Supplier Info --}}
      <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
        <div class="row g-4">
          
          {{-- Left Column: Supplier & Header Details --}}
          <div class="col-md-6 border-end">
            <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-buildings me-1"></i>Supplier & Delivery Details</h6>
            
            <table class="table table-borderless table-sm mb-0">
              <tbody>
                <tr>
                  <td class="text-muted fw-semibold" style="width: 35%;">Reference RF</td>
                  <td class="fw-bold">: 
                    <a href="{{ route('erp.request-form.show', $purchaseOrder->requestForm) }}" class="text-primary">
                      {{ $purchaseOrder->requestForm->rf_no }}
                    </a>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">PO Number</td>
                  <td class="fw-extrabold text-dark">: {{ $purchaseOrder->po_no }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Supplier Name</td>
                  <td>: 
                    @if($purchaseOrder->supplier)
                      <a href="{{ route('erp.suppliers.show', $purchaseOrder->supplier) }}" class="text-primary fw-bold">
                        {{ $purchaseOrder->supplier->name }}
                      </a>
                    @else
                      -
                    @endif
                  </td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Destination</td>
                  <td>: 
                    @if($purchaseOrder->warehouse)
                      <span class="fw-bold text-dark">{{ $purchaseOrder->warehouse->name }}</span> ({{ $purchaseOrder->warehouse->warehouse_code }})
                    @else
                      {{ $purchaseOrder->destination ?: '-' }}
                    @endif
                  </td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Supplier Address</td>
                  <td>: {{ $purchaseOrder->address ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Bank Account</td>
                  <td class="fw-semibold text-primary">: {{ $purchaseOrder->bank_account ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">PO Date</td>
                  <td>: {{ $purchaseOrder->date ? \Carbon\Carbon::parse($purchaseOrder->date)->format('Y/m/d') : '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">ETA</td>
                  <td>: {{ $purchaseOrder->eta ? \Carbon\Carbon::parse($purchaseOrder->eta)->format('Y/m/d') : '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Payment Method</td>
                  <td>: <span class="badge bg-label-info">{{ $purchaseOrder->payment_method ?: '-' }}</span></td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">PO Description</td>
                  <td>: {{ $purchaseOrder->description ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Elapsed Time</td>
                  <td>: <span class="badge bg-label-secondary">{{ $purchaseOrder->elapsed_time }}</span></td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Payment Closed</td>
                  <td>: 
                    <i class="bx {{ $purchaseOrder->payment_closed ? 'bx-check-circle text-success' : 'bx-x-circle text-muted' }} fs-5 align-middle me-1"></i>
                    {{ $purchaseOrder->payment_closed ? 'Yes' : 'No' }}
                  </td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Goods Receipt Status</td>
                  <td>: 
                    <i class="bx {{ $purchaseOrder->gr ? 'bx-check-circle text-success' : 'bx-time-five text-warning' }} fs-5 align-middle me-1"></i>
                    {{ $purchaseOrder->gr ? 'Completed' : 'Pending' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          {{-- Right Column: Print & Billing Specifications --}}
          <div class="col-md-6">
            <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-printer me-1"></i>Print & Billing Specifications</h6>
            
            <table class="table table-borderless table-sm mb-4">
              <tbody>
                <tr>
                  <td class="text-muted fw-semibold" style="width: 35%;">Project Code / Tag</td>
                  <td class="fw-bold text-dark">: {{ $purchaseOrder->project ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Invoice To</td>
                  <td>: {{ $purchaseOrder->invoice_to ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Attention To</td>
                  <td>: {{ $purchaseOrder->attention_to ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Transfer To Account</td>
                  <td class="fw-semibold text-primary">: {{ $purchaseOrder->transfer_to ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Payment Terms</td>
                  <td>: <span class="badge bg-label-primary">{{ $purchaseOrder->payment_terms ?: '-' }}</span></td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Authorized Signature</td>
                  <td class="fw-bold text-dark">: {{ $purchaseOrder->signature ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Other Instructions</td>
                  <td>: {{ $purchaseOrder->other_instructions ?: '-' }}</td>
                </tr>
              </tbody>
            </table>

            {{-- Biometric & Verification Info --}}
            <div class="p-3 bg-light rounded-3 border">
              <h6 class="fw-bold text-dark mb-2"><i class="bx bx-shield-quarter me-1 text-primary"></i>Finance Verification Status</h6>
              <div class="row g-2 small">
                <div class="col-6">
                  <span class="text-muted">Verified By:</span>
                  <div class="fw-bold text-dark">{{ $purchaseOrder->verifiedBy?->name ?: 'Not Verified Yet' }}</div>
                </div>
                <div class="col-6">
                  <span class="text-muted">Verification Timestamp:</span>
                  <div class="fw-bold text-dark">{{ $purchaseOrder->verified_at ? \Carbon\Carbon::parse($purchaseOrder->verified_at)->format('d M Y H:i') : '-' }}</div>
                </div>
              </div>
            </div>

          </div>

        </div>
      </div>

      {{-- Tab 2: PO Line Items --}}
      <div class="tab-pane fade" id="tab-items" role="tabpanel">
        <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-package me-1"></i>Purchase Order Items Table</h6>

        <div class="table-responsive rounded-3 border mb-4">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr class="text-uppercase small fw-bold text-muted">
                <th>PO Detail No</th>
                <th>Product Name</th>
                <th>Product Model</th>
                <th>Remarks / Description</th>
                <th class="text-end">Qty Req</th>
                <th class="text-end">Qty PO</th>
                <th class="text-end">Unit Cost</th>
                <th class="text-end">Tax</th>
                <th class="text-end">Total Cost</th>
              </tr>
            </thead>
            <tbody>
              @forelse($purchaseOrder->items as $item)
                <tr>
                  <td>
                    <span class="fw-bold text-primary">{{ $item->po_detail_no }}</span>
                  </td>
                  <td>
                    <div class="fw-bold text-dark">{{ $item->product_name }}</div>
                  </td>
                  <td>
                    <span class="badge bg-label-secondary">{{ $item->model ?: '-' }}</span>
                  </td>
                  <td>
                    <div class="small text-muted">{{ $item->remarks ?: ($item->product_description ?: '-') }}</div>
                  </td>
                  <td class="text-end fw-semibold">
                    {{ number_format($item->qty, 2, ',', '.') }}
                  </td>
                  <td class="text-end fw-bold text-dark">
                    {{ number_format($item->qty, 2, ',', '.') }}
                  </td>
                  <td class="text-end">
                    IDR {{ number_format($item->unit_cost, 0, ',', '.') }}
                  </td>
                  <td class="text-end">
                    IDR {{ number_format($item->tax ?: 0, 0, ',', '.') }}
                  </td>
                  <td class="text-end fw-bold text-primary">
                    IDR {{ number_format($item->total_cost, 0, ',', '.') }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center text-muted py-4">No items found in this Purchase Order.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- Financial Summary Card --}}
        <div class="row justify-content-end">
          <div class="col-md-5">
            <div class="card bg-light border-0 p-3 rounded-3">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted fw-semibold">Subtotal PO Amount</span>
                <span class="fw-bold text-dark">IDR {{ number_format($purchaseOrder->total_po_amount, 0, ',', '.') }}</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted fw-semibold">Total Tax Amount</span>
                <span class="fw-bold text-dark">IDR {{ number_format($purchaseOrder->tax, 0, ',', '.') }}</span>
              </div>
              <hr class="my-2">
              <div class="d-flex justify-content-between">
                <span class="fw-bold text-dark fs-6">Grand Total PO Amount</span>
                <span class="fw-extrabold text-primary fs-5">IDR {{ number_format($purchaseOrder->total_po_amount_with_tax, 0, ',', '.') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Tab 3: Approval History & Workflow --}}
      <div class="tab-pane fade" id="tab-approval" role="tabpanel">
        <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-shield-check me-1"></i>Approval Log & History Trail</h6>

        <div class="table-responsive rounded-3 border">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr class="text-uppercase small fw-bold text-muted">
                <th>Date / Time</th>
                <th>Status</th>
                <th>Assigned Approver / Role</th>
                <th>Actual Approver</th>
                <th>Comments / Notes</th>
              </tr>
            </thead>
            <tbody>
              @forelse($purchaseOrder->approvals()->orderBy('created_at', 'asc')->get() as $approval)
                <tr>
                  <td class="small fw-semibold text-muted">
                    {{ $approval->created_at ? $approval->created_at->format('d M Y H:i:s') : '-' }}
                  </td>
                  <td>
                    @php
                      $appBadge = match($approval->status) {
                        'Approved' => 'bg-success',
                        'Rejected' => 'bg-danger',
                        'Pending' => 'bg-warning',
                        default => 'bg-secondary',
                      };
                    @endphp
                    <span class="badge {{ $appBadge }} px-3 py-1 fw-bold">{{ $approval->status }}</span>
                  </td>
                  <td>
                    <span class="fw-bold text-dark">{{ $approval->assignedToUser?->name ?: ($approval->assignedToRole?->name ?: $approval->record_type) }}</span>
                  </td>
                  <td>
                    <span class="fw-semibold">{{ $approval->actualApprover?->name ?: '-' }}</span>
                  </td>
                  <td class="small">
                    {{ $approval->comments ?: '-' }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    <i class="bx bx-info-circle me-1"></i>PO is currently in Draft status. Submit for approval to initiate the approval workflow.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- Tab 4: Goods Receipts (DO) --}}
      <div class="tab-pane fade" id="tab-do" role="tabpanel">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h6 class="fw-bold mb-0 text-primary"><i class="bx bx-truck me-1"></i>Goods Receipt & Delivery Orders</h6>
          @if($purchaseOrder->status === 'Approved' && !$purchaseOrder->gr)
            <a href="{{ route('erp.goods-receipts.create', $purchaseOrder) }}" class="btn btn-primary btn-sm rounded-pill px-3">
              <i class="bx bx-plus me-1"></i>New Goods Receipt
            </a>
          @endif
        </div>

        <div class="table-responsive rounded-3 border">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr class="text-uppercase small fw-bold text-muted">
                <th>Action</th>
                <th>GR / DO No</th>
                <th>Record Type</th>
                <th>Date</th>
                <th class="text-end">Total Delivered Qty</th>
                <th class="text-end">Total Received Qty</th>
                <th>Status</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              @forelse($purchaseOrder->goodsReceipts as $gr)
                <tr>
                  <td>
                    <a href="{{ route('erp.goods-receipts.show', $gr) }}" class="btn btn-xs btn-label-primary">View</a>
                  </td>
                  <td class="fw-bold text-primary">{{ $gr->gr_no }}</td>
                  <td>{{ $gr->record_type ?: 'Standard' }}</td>
                  <td>{{ $gr->date ? \Carbon\Carbon::parse($gr->date)->format('Y/m/d') : '-' }}</td>
                  <td class="text-end fw-semibold">{{ number_format($gr->items->sum('qty_delivered'), 2, ',', '.') }}</td>
                  <td class="text-end fw-bold text-success">{{ number_format($gr->items->sum('qty_received'), 2, ',', '.') }}</td>
                  <td><span class="badge bg-label-success">{{ $gr->status }}</span></td>
                  <td>{{ $gr->notes ?: '-' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">No Goods Receipts (DO) found for this Purchase Order.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- Tab 5: Payments & Attachments --}}
      <div class="tab-pane fade" id="tab-payments" role="tabpanel">
        
        {{-- Attachments Section --}}
        <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-paperclip me-1"></i>Uploaded Attachments</h6>
        <div class="row g-3 mb-4">
          @php
            $attachments = is_array($purchaseOrder->attachments) ? $purchaseOrder->attachments : json_decode($purchaseOrder->attachments ?? '[]', true);
          @endphp
          @forelse($attachments as $att)
            @php
              $path = is_string($att) ? $att : ($att['path'] ?? '');
              $filename = is_string($att) ? basename($att) : ($att['name'] ?? basename($path));
              $url = asset('storage/' . $path);
            @endphp
            <div class="col-md-4 col-6">
              <div class="border rounded-3 p-3 bg-light d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center text-truncate me-2">
                  <i class="bx bx-file text-primary fs-3 me-2"></i>
                  <span class="small fw-semibold text-dark text-truncate">{{ $filename }}</span>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-label-primary rounded-circle" onclick="showAttachmentModal('{{ $url }}', '{{ $filename }}')">
                  <i class="bx bx-show"></i>
                </button>
              </div>
            </div>
          @empty
            <div class="col-12">
              <div class="text-muted small p-3 bg-light rounded-3 text-center border">
                No attachments uploaded for this Purchase Order.
              </div>
            </div>
          @endforelse
        </div>

        {{-- Payment Advice Section --}}
        <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-credit-card me-1"></i>Payment Advice Details</h6>
        <div class="table-responsive rounded-3 border mb-4">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr class="text-uppercase small fw-bold text-muted">
                <th>Supplier Detail No</th>
                <th>Approved Date</th>
                <th>Created Date</th>
                <th>Date Paid</th>
                <th>Invoice No</th>
                <th class="text-end">Payment Amount</th>
                <th class="text-end">Payment With Tax</th>
                <th>Remark</th>
              </tr>
            </thead>
            <tbody>
              @forelse($purchaseOrder->paymentAdvices as $pa)
                @foreach($pa->details as $pad)
                  <tr>
                    <td class="fw-bold text-primary">{{ $pad->supplier_detail_no }}</td>
                    <td>{{ $pad->approved_date ? \Carbon\Carbon::parse($pad->approved_date)->format('Y/m/d') : '-' }}</td>
                    <td>{{ $pad->created_at ? $pad->created_at->format('Y/m/d') : '-' }}</td>
                    <td>{{ $pad->date_paid ? \Carbon\Carbon::parse($pad->date_paid)->format('Y/m/d') : '-' }}</td>
                    <td>{{ $pad->invoice_no ?: '-' }}</td>
                    <td class="text-end">IDR {{ number_format($pad->payment_amount, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold text-success">IDR {{ number_format($pad->payment_amount_with_tax, 0, ',', '.') }}</td>
                    <td>{{ $pad->remark ?: '-' }}</td>
                  </tr>
                @endforeach
              @empty
                <tr><td colspan="8" class="text-center text-muted py-3">No payment details found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

      </div>

    </div>

  </div>

  {{-- Modal Approve PO --}}
  <div class="modal fade" id="approvePoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('erp.purchase-orders.approve', $purchaseOrder) }}" method="POST" class="modal-content shadow-lg border-0 rounded-4">
        @csrf
        <div class="modal-header border-bottom bg-success bg-opacity-10 py-3">
          <h5 class="modal-title fw-bold text-success"><i class="bx bx-check-circle me-1"></i>Approve PO: {{ $purchaseOrder->po_no }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Comments / Approval Notes</label>
            <textarea name="comments" class="form-control rounded-3" rows="3" placeholder="Enter optional approval note..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-top bg-light py-3">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success text-white px-4 shadow-sm">
            <i class="bx bx-check me-1"></i>Approve PO
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Modal Reject PO --}}
  <div class="modal fade" id="rejectPoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('erp.purchase-orders.reject', $purchaseOrder) }}" method="POST" class="modal-content shadow-lg border-0 rounded-4">
        @csrf
        <div class="modal-header border-bottom bg-danger bg-opacity-10 py-3">
          <h5 class="modal-title fw-bold text-danger"><i class="bx bx-x-circle me-1"></i>Reject PO: {{ $purchaseOrder->po_no }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold text-danger">Rejection Reason / Comments <span class="text-danger">*</span></label>
            <textarea name="comments" class="form-control rounded-3" rows="3" required placeholder="Describe why this PO is being rejected..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-top bg-light py-3">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger text-white px-4 shadow-sm">
            <i class="bx bx-x me-1"></i>Reject PO
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Modal Attachment Preview --}}
  <div class="modal fade" id="attachmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content rounded-4 overflow-hidden shadow-lg border-0">
        <div class="modal-header border-bottom bg-light py-3">
          <h5 class="modal-title fw-bold text-dark" id="attachmentModalTitle">Preview Attachment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0 text-center bg-light" style="min-height: 400px; max-height: 80vh; overflow: auto; display: flex; align-items: center; justify-content: center;">
          <div id="attachmentLoading" class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <iframe id="attachmentIframe" src="" style="width: 100%; height: 75vh; border: none; display: none;"></iframe>
          <img id="attachmentImage" src="" style="max-width: 100%; max-height: 75vh; display: none;" />
          <div id="attachmentDownload" class="p-4" style="display: none;">
            <i class="bx bx-file" style="font-size: 4rem; color: #4f46e5;"></i>
            <h6 class="mt-3 mb-1" id="attachmentUnsupportedName">Filename.ext</h6>
            <p class="text-muted small mb-3">Preview not available for this file type.</p>
            <a href="#" id="attachmentDownloadBtn" class="btn btn-primary btn-sm rounded-pill px-3" target="_blank" download>Download File</a>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

@push('scripts')
<script>
function showAttachmentModal(url, filename) {
    const modal = new bootstrap.Modal(document.getElementById('attachmentModal'));
    
    document.getElementById('attachmentModalTitle').innerText = filename;
    
    document.getElementById('attachmentIframe').style.display = 'none';
    document.getElementById('attachmentImage').style.display = 'none';
    document.getElementById('attachmentDownload').style.display = 'none';
    document.getElementById('attachmentLoading').style.display = 'block';
    
    modal.show();
    
    const ext = filename.split('.').pop().toLowerCase();
    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);
    const isPdf = ['pdf'].includes(ext);
    
    setTimeout(() => {
        document.getElementById('attachmentLoading').style.display = 'none';
        if (isImage) {
            const img = document.getElementById('attachmentImage');
            img.src = url;
            img.style.display = 'block';
        } else if (isPdf) {
            const iframe = document.getElementById('attachmentIframe');
            iframe.src = url;
            iframe.style.display = 'block';
        } else {
            document.getElementById('attachmentUnsupportedName').innerText = filename;
            const btn = document.getElementById('attachmentDownloadBtn');
            btn.href = url;
            document.getElementById('attachmentDownload').style.display = 'block';
        }
    }, 400);
}
</script>
@endpush
@endsection
