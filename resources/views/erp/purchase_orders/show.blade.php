@extends('layouts.home')

@section('title', 'PO Detail: ' . $purchaseOrder->po_no)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Top Navigation & Title -->
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <div class="text-muted small">PO</div>
      <h4 class="mb-0 fw-bold">{{ $purchaseOrder->po_no }}</h4>
    </div>
    <a href="{{ route('erp.procurement.dashboard') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bx bx-arrow-back me-1"></i>Back to Dashboard
    </a>
  </div>

  <!-- Jump Links -->
  <div class="mb-3 small">
    <a href="#po-detail" class="text-primary me-2">PO Detail [{{ $purchaseOrder->items->count() }}]</a> | 
    <a href="#approval-history" class="text-primary me-2">Approval History</a> | 
    <a href="#" class="text-primary me-2">DO [0]</a> | 
    <a href="#" class="text-primary me-2">Payment Advice Detail [0]</a> | 
    <a href="#" class="text-primary me-2">Payment Advice [0]</a> | 
    <a href="#" class="text-primary me-2">PO History [0]</a> | 
    <a href="#" class="text-primary">Open Activities [0]</a>
  </div>

  <style>
    .cursor-pointer { cursor: pointer; }
    .collapse-header:hover { background-color: #f8f9fa; }
    /* Salesforce Style Section Top Accent Line */
    .card {
      border-top: 3px solid #696cff !important;
      border-radius: 6px;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
    }
  </style>

  <!-- 1. PO Detail -->
  <div class="card mb-4" id="po-detail">
    <div class="card-header border-bottom p-0 d-flex justify-content-between align-items-center">
      <div class="flex-grow-1 py-2 px-4 cursor-pointer collapse-header" data-bs-toggle="collapse" data-bs-target="#collapsePoDetail">
        <h6 class="mb-0 fw-bold"><i class="bx bx-chevron-down me-2"></i>PO Detail</h6>
      </div>
      <div class="d-flex gap-1 align-items-center pe-4">
        <!-- Edit & Delete -->
        @if($purchaseOrder->status === 'Draft' || $purchaseOrder->status === 'Rejected')
          <a href="{{ route('erp.purchase-orders.edit', $purchaseOrder) }}" class="btn btn-xs btn-outline-primary">Edit</a>
        @endif
        @if(auth()->user()->hasRole('superadmin'))
          <form action="{{ route('erp.purchase-orders.destroy', $purchaseOrder) }}" method="POST" class="d-inline" onsubmit="return confirm('PERINGATAN KERAS!\n\nMenghapus PO ini akan berakibat:\n1. Dokumen PO ({{ $purchaseOrder->po_no }}) Dihapus Permanen.\n2. {{ $purchaseOrder->items->count() }} Item di dalam PO ini ikut terhapus.\n3. Histori Approval PO terhapus.\n4. Status Barang (PR Item) di RF akan dikembalikan menjadi \'Requested\' sehingga akan muncul kembali di form Pembuatan PO.\n\nApakah Anda sangat yakin ingin melanjutkan?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-xs btn-outline-danger">Delete</button>
          </form>
        @endif

        <!-- Submit for Approval -->
        @if($purchaseOrder->status === 'Draft')
          @if($purchaseOrder->verified_by_id)
            <form action="{{ route('erp.purchase-orders.submit', $purchaseOrder) }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-xs btn-primary">Submit for Approval</button>
            </form>
          @else
            <button type="button" class="btn btn-xs btn-primary opacity-50" onclick="Swal.fire({icon: 'warning', title: 'Verification Required', text: 'PO must be verified by Finance first before it can be submitted for approval.', confirmButtonColor: '#696cff'})">Submit for Approval</button>
          @endif
        @elseif($purchaseOrder->status === 'Submitted')
          <!-- Approve / Reject Modals -->
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
                // Fallback rules
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
            <button type="button" class="btn btn-xs btn-success" data-bs-toggle="modal" data-bs-target="#approvePoModal">Approve PO</button>
            <button type="button" class="btn btn-xs btn-danger text-white" data-bs-toggle="modal" data-bs-target="#rejectPoModal">Reject PO</button>
          @endif
        @endif

        <a href="{{ route('erp.purchase-orders.print', $purchaseOrder) }}" target="_blank" class="btn btn-xs btn-outline-secondary">Print PO</a>
        
        @if($purchaseOrder->status === 'Approved' && !$purchaseOrder->gr && !$purchaseOrder->goodsReceipts()->where('status', 'Received')->exists())
          <a href="{{ route('erp.goods-receipts.create', $purchaseOrder) }}" class="btn btn-xs btn-primary">Create Goods Received</a>
        @elseif($purchaseOrder->gr || $purchaseOrder->status === 'Completed')
          <button type="button" class="btn btn-xs btn-outline-success" disabled><i class="bx bx-check-circle me-1"></i>GR Completed</button>
        @endif
        
        @if(!$purchaseOrder->verified_by_id)
          @if(auth()->user()->hasRole('finance') || auth()->user()->hasRole('superadmin'))
            <form action="{{ route('erp.purchase-orders.verify', $purchaseOrder) }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-xs btn-primary">Verification</button>
            </form>
          @endif
        @else
          <button type="button" class="btn btn-xs btn-success" disabled>Verified</button>
        @endif
      </div>
    </div>
    
    <div class="collapse show" id="collapsePoDetail">
      <div class="card-body mt-3">
        <!-- Grid Fields -->
        <div class="row small">
          <!-- Left Column -->
          <div class="col-md-6 border-end">
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">RF</div>
              <div class="col-8">
                <a href="{{ route('erp.request-form.show', $purchaseOrder->requestForm) }}" class="text-primary text-decoration-none">
                  {{ $purchaseOrder->requestForm->rf_no }}
                </a>
              </div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">PO No</div>
              <div class="col-8 fw-bold">{{ $purchaseOrder->po_no }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Supplier Name</div>
              <div class="col-8">
                @if($purchaseOrder->supplier)
                  <a href="{{ route('erp.suppliers.show', $purchaseOrder->supplier) }}" class="text-primary text-decoration-none fw-bold">
                    {{ $purchaseOrder->supplier->name }}
                  </a>
                @else
                  -
                @endif
              </div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Destination</div>
              <div class="col-8">
                @if($purchaseOrder->warehouse)
                  <a href="{{ route('erp.warehouses.show', $purchaseOrder->warehouse) }}" class="text-primary text-decoration-none fw-bold">
                    {{ $purchaseOrder->warehouse->name }}
                  </a>
                @else
                  {{ $purchaseOrder->destination ?: '-' }}
                @endif
              </div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Bank Account</div>
              <div class="col-8">{{ $purchaseOrder->bank_account ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Total PO Amount</div>
              <div class="col-8 fw-bold">IDR {{ number_format($purchaseOrder->total_po_amount, 0, ',', '.') }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Tax</div>
              <div class="col-8">IDR {{ number_format($purchaseOrder->tax, 0, ',', '.') }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Total PO Amount With Tax</div>
              <div class="col-8 fw-bold text-success">IDR {{ number_format($purchaseOrder->total_po_amount_with_tax, 0, ',', '.') }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Balance Amount</div>
              <div class="col-8">IDR {{ number_format($purchaseOrder->balance_amount, 0, ',', '.') }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Payment Method</div>
              <div class="col-8">{{ $purchaseOrder->payment_method ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">ETA</div>
              <div class="col-8">{{ $purchaseOrder->eta?->format('Y/m/d') ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Description</div>
              <div class="col-8">{{ $purchaseOrder->description ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Elapsed Time</div>
              <div class="col-8">{{ $purchaseOrder->elapsed_time }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Payment Closed</div>
              <div class="col-8">
                <i class="bx {{ $purchaseOrder->payment_closed ? 'bx-check-square text-success' : 'bx-square text-muted' }}"></i>
              </div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">GR</div>
              <div class="col-8">
                <i class="bx {{ $purchaseOrder->gr ? 'bx-check-square text-success' : 'bx-square text-muted' }}"></i>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="col-md-6">
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Owner</div>
              <div class="col-8">
                <a href="#" class="text-primary text-decoration-none">
                  {{ $purchaseOrder->owner?->name ?: '-' }}
                </a>
              </div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Contact Person</div>
              <div class="col-8">{{ $purchaseOrder->contact_person ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Address</div>
              <div class="col-8">{{ $purchaseOrder->address ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Date</div>
              <div class="col-8">{{ $purchaseOrder->date?->format('Y/m/d') ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Status</div>
              <div class="col-8 font-weight-bold">
                @if($purchaseOrder->status === 'Approved')
                  <span class="text-success fw-bold">{{ $purchaseOrder->status }}</span>
                @elseif($purchaseOrder->status === 'Submitted')
                  <span class="text-warning fw-bold">{{ $purchaseOrder->status }}</span>
                @elseif($purchaseOrder->status === 'Rejected')
                  <span class="text-danger fw-bold">{{ $purchaseOrder->status }}</span>
                @else
                  <span class="text-muted fw-bold">{{ $purchaseOrder->status }}</span>
                @endif
              </div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Amount Paid</div>
              <div class="col-8">IDR {{ number_format($purchaseOrder->amount_paid, 0, ',', '.') }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Submitted Date</div>
              <div class="col-8">{{ $purchaseOrder->submitted_date?->format('Y/m/d H:i') ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Approved Date</div>
              <div class="col-8">{{ $purchaseOrder->approved_date?->format('Y/m/d H:i') ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Rejected Date</div>
              <div class="col-8">{{ $purchaseOrder->rejected_date?->format('Y/m/d H:i') ?: '-' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Expense Type -->
  <div class="card mb-4">
    <div class="card-header border-bottom py-2 cursor-pointer collapse-header" data-bs-toggle="collapse" data-bs-target="#collapseExpenseType">
      <h6 class="mb-0 fw-bold"><i class="bx bx-chevron-down me-2"></i>Expense Type</h6>
    </div>
    <div class="collapse show" id="collapseExpenseType">
      <div class="card-body mt-3">
        <div class="row small ps-3">
          <div class="col-md-6">
            <div class="mb-2">
              <i class="bx {{ $purchaseOrder->expense_material_equipment ? 'bx-check-square text-success' : 'bx-square text-muted' }} me-2"></i>
              <span class="text-muted">Material-Equipment</span>
            </div>
            <div class="mb-2">
              <i class="bx {{ $purchaseOrder->expense_material_subcon ? 'bx-check-square text-success' : 'bx-square text-muted' }} me-2"></i>
              <span class="text-muted">Material-Subcon</span>
            </div>
            <div class="mb-2">
              <i class="bx {{ $purchaseOrder->expense_personnel ? 'bx-check-square text-success' : 'bx-square text-muted' }} me-2"></i>
              <span class="text-muted">Personnel</span>
            </div>
            <div class="mb-2">
              <i class="bx {{ $purchaseOrder->expense_transportation ? 'bx-check-square text-success' : 'bx-square text-muted' }} me-2"></i>
              <span class="text-muted">Transportation & Telecommunication</span>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-2">
              <i class="bx {{ $purchaseOrder->expense_utilities ? 'bx-check-square text-success' : 'bx-square text-muted' }} me-2"></i>
              <span class="text-muted">Utilities</span>
            </div>
            <div class="mb-2">
              <i class="bx {{ $purchaseOrder->expense_office ? 'bx-check-square text-success' : 'bx-square text-muted' }} me-2"></i>
              <span class="text-muted">Office</span>
            </div>
            <div class="mb-2">
              <i class="bx {{ $purchaseOrder->expense_other ? 'bx-check-square text-success' : 'bx-square text-muted' }} me-2"></i>
              <span class="text-muted">Other Expense</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Print Related Section -->
  <div class="card mb-4">
    <div class="card-header border-bottom py-2 cursor-pointer collapse-header" data-bs-toggle="collapse" data-bs-target="#collapsePrintRelated">
      <h6 class="mb-0 fw-bold"><i class="bx bx-chevron-down me-2"></i>Print Related</h6>
    </div>
    <div class="collapse show" id="collapsePrintRelated">
      <div class="card-body mt-3">
        <div class="row small ps-3">
          <div class="col-md-6">
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Project</div>
              <div class="col-8">{{ $purchaseOrder->project ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Invoice To</div>
              <div class="col-8">{{ $purchaseOrder->invoice_to ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Attention To</div>
              <div class="col-8">{{ $purchaseOrder->attention_to ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Transfer To</div>
              <div class="col-8">{{ $purchaseOrder->transfer_to ?: '-' }}</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Other Instructions</div>
              <div class="col-8">{{ $purchaseOrder->other_instructions ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Payment Terms</div>
              <div class="col-8">{{ $purchaseOrder->payment_terms ?: '-' }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Signature</div>
              <div class="col-8">{{ $purchaseOrder->signature ?: '-' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. Biometric Section -->
  <div class="card mb-4">
    <div class="card-header border-bottom py-2 cursor-pointer collapse-header" data-bs-toggle="collapse" data-bs-target="#collapseBiometric">
      <h6 class="mb-0 fw-bold"><i class="bx bx-chevron-down me-2"></i>Biometric</h6>
    </div>
    <div class="collapse show" id="collapseBiometric">
      <div class="card-body mt-3">
        <div class="row small ps-3">
          <div class="col-md-6">
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Verified By</div>
              <div class="col-8">{{ $purchaseOrder->verifiedBy?->name ?: '-' }}</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Verification Timestamp</div>
              <div class="col-8">{{ $purchaseOrder->verification_timestamp?->format('Y/m/d H:i') ?: '-' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 5. PO Items -->
  <div class="card mb-4">
    <div class="card-header border-bottom py-2 cursor-pointer collapse-header" data-bs-toggle="collapse" data-bs-target="#collapsePoItems">
      <h6 class="mb-0 fw-bold"><i class="bx bx-chevron-down me-2"></i>PO Items</h6>
    </div>
    <div class="collapse show" id="collapsePoItems">
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Action</th>
              <th>PO Detail No</th>
              <th>WID</th>
              <th>Product</th>
              <th>Model</th>
              <th>Product Description</th>
              <th>[Print Related] Remark</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Unit Cost</th>
              <th class="text-end">Tax</th>
              <th class="text-end">Total Cost</th>
            </tr>
          </thead>
          <tbody>
            @forelse($purchaseOrder->items as $item)
              <tr>
                <td>
                  <span class="text-muted small">Edit | Del</span>
                </td>
                <td>
                  <span class="fw-semibold">{{ $item->po_detail_no }}</span>
                </td>
                <td>{{ $item->requestFormItem?->wid ?: '-' }}</td>
                <td>
                  @if($item->requestFormItem)
                    <a href="{{ route('erp.request-form-items.show', $item->requestFormItem) }}" class="text-primary text-decoration-none">
                      {{ $item->requestFormItem->product_name }}
                    </a>
                  @else
                    -
                  @endif
                </td>
                <td>{{ $item->requestFormItem?->erpProduct?->productModel?->model_name ?: '-' }}</td>
                <td>{{ $item->requestFormItem?->product_description ?: '-' }}</td>
                <td>[{{ $purchaseOrder->remark ?? 'Biaya Penghemat Telepon Periode Juni 2026' }}]</td>
                <td class="text-end">{{ number_format((float)$item->qty, 2, ',', '.') }}</td>
                <td class="text-end">IDR {{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                <td class="text-end">IDR {{ number_format($item->tax, 0, ',', '.') }}</td>
                <td class="text-end fw-semibold">IDR {{ number_format($item->total_cost, 0, ',', '.') }}</td>
              </tr>
            @empty
              <tr><td colspan="11" class="text-center text-muted py-3">No records to display</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 6. Attachments Section -->
  <div class="card mb-4">
    <div class="card-header border-bottom py-2 cursor-pointer collapse-header" data-bs-toggle="collapse" data-bs-target="#collapseAttachments">
      <h6 class="mb-0 fw-bold"><i class="bx bx-chevron-down me-2"></i>Attachments</h6>
    </div>
    <div class="collapse show" id="collapseAttachments">
      <div class="card-body mt-3">
        @if($purchaseOrder->notesAttachments->count() > 0)
        <div class="row small ps-3">
          <div class="col-12">
            <ul class="list-unstyled mb-0">
              @foreach($purchaseOrder->notesAttachments as $attachment)
                <li class="mb-1">
                  <a href="javascript:void(0);" onclick="showAttachmentModal('{{ Storage::url($attachment->file_path) }}', '{{ $attachment->file_name }}')" class="text-primary text-decoration-none d-flex align-items-center">
                    <i class="bx bx-file me-2"></i> {{ $attachment->file_name }}
                  </a>
                </li>
              @endforeach
            </ul>
          </div>
        </div>
        @else
        <div class="text-muted small">No attachments found</div>
        @endif
      </div>
    </div>
  </div>

  <!-- 7. DO (Goods Receipts) -->
  <div class="card mb-4 border-top border-2 border-warning" id="dos">
    <div class="card-header border-bottom p-0 d-flex justify-content-between align-items-center">
      <div class="flex-grow-1 py-2 px-4 cursor-pointer collapse-header" data-bs-toggle="collapse" data-bs-target="#collapseDos">
        <h6 class="mb-0 fw-bold d-flex align-items-center text-warning">
          <i class="bx bx-chevron-down me-2"></i><i class="bx bxs-flag-alt me-2"></i>DO
        </h6>
      </div>
      <div class="d-flex gap-1 align-items-center pe-4">
        @if($purchaseOrder->status === 'Approved')
          <a href="{{ route('erp.goods-receipts.create', $purchaseOrder) }}" class="btn btn-xs btn-outline-secondary">New DO</a>
        @else
          <button type="button" class="btn btn-xs btn-outline-secondary" disabled>New DO</button>
        @endif
      </div>
    </div>
    <div class="collapse show" id="collapseDos">
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Action</th>
              <th>DO No</th>
              <th>Record Type</th>
              <th>Date</th>
              <th class="text-end">Total Delivered Qty</th>
              <th class="text-end">Total Received Qty</th>
              <th>Status</th>
              <th>Owner First Name</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            @forelse($purchaseOrder->goodsReceipts ?? [] as $gr)
              <tr>
                <td><span class="text-muted small">Edit | Del</span></td>
                <td class="fw-semibold">
                  <a href="{{ route('erp.goods-receipts.show', $gr) }}" class="text-primary">{{ $gr->do_no }}</a>
                </td>
                <td>{{ $gr->record_type }}</td>
                <td>{{ $gr->date?->format('Y/m/d') ?: '-' }}</td>
                <td class="text-end">{{ number_format($gr->total_delivered_qty, 2, ',', '.') }}</td>
                <td class="text-end">{{ number_format($gr->total_received_qty, 2, ',', '.') }}</td>
                <td>{{ $gr->status }}</td>
                <td>{{ $gr->owner?->name ?: 'Administrator' }}</td>
                <td>{{ $gr->remarks ?: '-' }}</td>
              </tr>
            @empty
              <tr><td colspan="9" class="text-center text-muted py-3">No DOs found</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 8. Approval History -->
  <div class="card mb-4" id="approval-history">
    <div class="card-header border-bottom py-2 px-3 d-flex justify-content-between align-items-center bg-white">
      <div class="d-flex align-items-center gap-2 cursor-pointer collapse-header" data-bs-toggle="collapse" data-bs-target="#collapseApproval">
        <h6 class="mb-0 fw-bold"><i class="bx bx-chevron-down me-2"></i>Approval History</h6>
        @if($purchaseOrder->status === 'Draft' && $purchaseOrder->verified_by_id)
          <form action="{{ route('erp.purchase-orders.submit', $purchaseOrder) }}" method="POST" class="d-inline ms-2">
            @csrf
            <button type="submit" class="btn btn-xs btn-outline-primary fw-semibold"><i class="bx bx-paper-plane me-1"></i>Submit for Approval</button>
          </form>
        @endif
      </div>
      <div>
        <a href="#" class="text-muted small">Approval History Help <i class='bx bx-help-circle ms-1'></i></a>
      </div>
    </div>
    <div class="collapse show" id="collapseApproval">
      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle mb-0 small text-nowrap" style="border-color: #e2e8f0;">
          <thead class="table-light">
            <tr class="text-muted text-uppercase" style="font-size: 0.725rem; letter-spacing: 0.05em;">
              <th style="width: 100px;">ACTION</th>
              <th>DATE</th>
              <th>STATUS</th>
              <th>ASSIGNED TO</th>
              <th>ACTUAL APPROVER</th>
              <th>COMMENTS</th>
              <th style="width: 140px; text-align: center;">OVERALL STATUS</th>
            </tr>
          </thead>
          <tbody>
            @if($purchaseOrder->status === 'Draft' && $purchaseOrder->approvals()->count() === 0)
              <tr>
                <td colspan="7" class="text-muted text-center py-4 bg-light">
                  <i class="bx bx-info-circle me-1"></i> PO is currently in <strong>Draft</strong> status. Submit for approval to initiate the approval workflow.
                </td>
              </tr>
            @elseif($purchaseOrder->approvals()->count() > 0)
              @foreach($purchaseOrder->approvals->sortBy('level') as $app)
                @php
                  // Dynamic status badge styling (Image 2 style)
                  $statusBg = 'bg-secondary';
                  $statusIcon = 'bx-minus';
                  if ($app->status === 'Approved' || ($app->level == 0 && $app->status === 'Approved')) {
                      $statusBg = 'bg-success';
                      $statusIcon = 'bx-check-circle';
                  } elseif ($app->status === 'Pending') {
                      $statusBg = 'bg-warning';
                      $statusIcon = 'bx-time-five';
                  } elseif ($app->status === 'Rejected') {
                      $statusBg = 'bg-danger';
                      $statusIcon = 'bx-x-circle';
                  } elseif ($app->status === 'Waiting') {
                      $statusBg = 'bg-secondary';
                      $statusIcon = 'bx-pause-circle';
                  }
                  
                  $stepTitle = $app->level == 0 
                      ? 'Approval Request Submitted' 
                      : 'Step: ' . ($app->assignedUser?->name ?: ($app->assignedRole?->name ?: 'Level ' . $app->level));
                @endphp
                <!-- Banner Header Row (Image 2 style) -->
                <tr>
                  <td colspan="6" class="bg-primary text-white fw-bold py-2 px-3" style="background-color: #696cff !important;">
                    <i class="bx {{ $app->level == 0 ? 'bx-paper-plane' : 'bx-badge-check' }} me-2"></i>{{ $stepTitle }}
                  </td>
                  <td class="{{ $statusBg }} text-white fw-bold text-center py-2">
                    <i class="bx {{ $statusIcon }} me-1"></i>{{ $app->level == 0 ? 'Approved' : $app->status }}
                  </td>
                </tr>
                <!-- Step Detail Row -->
                <tr class="bg-white">
                  <td>
                    @php
                      $user = auth()->user();
                      $canApproveThisStep = false;
                      if ($app->status === 'Pending' && $purchaseOrder->status === 'Submitted') {
                          if ($user->hasRole('superadmin')) {
                              $canApproveThisStep = true;
                          } elseif ($app->assigned_to_user_id && $user->id == $app->assigned_to_user_id) {
                              $canApproveThisStep = true;
                          } elseif ($app->assigned_to_role_id) {
                              $canApproveThisStep = \Illuminate\Support\Facades\DB::connection('tenant')
                                  ->table('role_user')
                                  ->where('user_id', $user->id)
                                  ->where('role_id', $app->assigned_to_role_id)
                                  ->exists();
                          }
                      }
                    @endphp
                    @if($canApproveThisStep)
                      <div class="d-flex gap-1">
                        <button type="button" class="btn btn-xs btn-success text-white" data-bs-toggle="modal" data-bs-target="#approvePoModal">Approve</button>
                        <button type="button" class="btn btn-xs btn-danger text-white" data-bs-toggle="modal" data-bs-target="#rejectPoModal">Reject</button>
                      </div>
                    @else
                      -
                    @endif
                  </td>
                  <td>{{ $app->approved_at?->format('Y/m/d H:i') ?: ($app->created_at?->format('Y/m/d H:i') ?: '-') }}</td>
                  <td>{{ $app->level == 0 ? 'Submitted' : $app->status }}</td>
                  <td>
                    @if($app->assigned_to_user_id)
                      {{ $app->assignedUser?->name }}
                    @elseif($app->assigned_to_role_id)
                      Role: {{ $app->assignedRole?->name }}
                    @else
                      {{ $app->level == 0 ? ($purchaseOrder->owner?->name ?: '-') : '-' }}
                    @endif
                  </td>
                  <td>{{ $app->actualApprover?->name ?: ($app->level == 0 ? ($purchaseOrder->owner?->name ?: '-') : '-') }}</td>
                  <td>{{ $app->comments ?: '-' }}</td>
                  <td class="text-center"></td>
                </tr>
              @endforeach
            @else
              <!-- Fallback Static Approval History (Image 2 style) -->
              <tr>
                <td colspan="6" class="bg-primary text-white fw-bold py-2 px-3" style="background-color: #696cff !important;">
                  <i class="bx bx-paper-plane me-2"></i>Approval Request Submitted
                </td>
                <td class="bg-success text-white fw-bold text-center py-2">
                  <i class="bx bx-check-circle me-1"></i>Approved
                </td>
              </tr>
              <tr class="bg-white">
                <td>-</td>
                <td>{{ $purchaseOrder->submitted_date?->format('Y/m/d H:i') ?: ($purchaseOrder->created_at?->format('Y/m/d H:i') ?: '-') }}</td>
                <td>Submitted</td>
                <td>{{ $purchaseOrder->owner?->name ?: 'System' }}</td>
                <td>{{ $purchaseOrder->owner?->name ?: 'System' }}</td>
                <td>PO Submitted for approval</td>
                <td></td>
              </tr>
              @if($purchaseOrder->status === 'Approved')
                <tr>
                  <td colspan="6" class="bg-primary text-white fw-bold py-2 px-3" style="background-color: #696cff !important;">
                    <i class="bx bx-badge-check me-2"></i>Step: {{ $purchaseOrder->verifiedBy?->name ?: 'Manager Approval' }}
                  </td>
                  <td class="bg-success text-white fw-bold text-center py-2">
                    <i class="bx bx-check-circle me-1"></i>Approved
                  </td>
                </tr>
                <tr class="bg-white">
                  <td>-</td>
                  <td>{{ $purchaseOrder->approved_date?->format('Y/m/d H:i') ?: '-' }}</td>
                  <td>Approved</td>
                  <td>
                    @if($purchaseOrder->total_po_amount_with_tax <= 1000000)
                      Role: Procurement Manager
                    @else
                      Role: CEO
                    @endif
                  </td>
                  <td>{{ $purchaseOrder->verifiedBy?->name ?: 'Authorized Approver' }}</td>
                  <td>Approved</td>
                  <td></td>
                </tr>
              @endif
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Related List: Payment Advice Detail -->
  @php
    $poPaDetails = \App\Models\Erp\ErpPaymentAdviceDetail::where('erp_purchase_order_id', $purchaseOrder->id)->get();
    $poPAdvices = \App\Models\Erp\ErpPaymentAdvice::where('erp_purchase_order_id', $purchaseOrder->id)->get();
  @endphp

  <div class="card mb-4 border-top border-2 border-primary" id="payment-advice-detail">
    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light border-bottom">
      <h6 class="mb-0 fw-bold"><i class="bx bx-receipt me-1 text-primary"></i>Payment Advice Detail</h6>
      <a href="{{ route('erp.payment-advices.create', ['po_id' => $purchaseOrder->id]) }}" class="btn btn-xs btn-outline-primary">
        <i class="bx bx-plus me-1"></i>New Payment Advice Detail
      </a>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th width="8%">Action</th>
            <th>Supplier Detail No</th>
            <th>Approved Date</th>
            <th>Created Date SID</th>
            <th>Date Paid</th>
            <th>Invoice No</th>
            <th>Payment Amount</th>
            <th>Payment Amount With Tax</th>
            <th>Remark</th>
            <th>Days Invoice Overdue</th>
            <th>Days over due</th>
          </tr>
        </thead>
        <tbody>
          @forelse($poPaDetails as $pad)
            <tr>
              <td>
                <a href="{{ route('erp.payment-advice-details.show', $pad) }}" class="text-primary fw-semibold">View</a>
              </td>
              <td>
                <a href="{{ route('erp.payment-advice-details.show', $pad) }}" class="fw-bold text-primary">{{ $pad->supplier_detail_no }}</a>
              </td>
              <td>{{ $pad->approved_date?->format('Y/m/d') ?? '-' }}</td>
              <td>{{ $pad->created_date_sid?->format('Y/m/d') ?? '-' }}</td>
              <td>{{ $pad->date_paid?->format('Y/m/d') ?? '-' }}</td>
              <td>{{ $pad->paymentAdvice->invoice_no ?? '-' }}</td>
              <td>IDR {{ number_format($pad->payment_amount, 0, ',', '.') }}</td>
              <td class="fw-bold">IDR {{ number_format($pad->payment_amount_with_tax, 0, ',', '.') }}</td>
              <td>{{ $pad->remark ?? '-' }}</td>
              <td>{{ $pad->days_invoice_overdue }}</td>
              <td>{{ $pad->days_overdue }}</td>
            </tr>
          @empty
            <tr><td colspan="11" class="text-center text-muted py-3">No records to display</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Related List: Payment Advice -->
  <div class="card mb-4 border-top border-2 border-primary" id="payment-advice">
    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light border-bottom">
      <h6 class="mb-0 fw-bold"><i class="bx bx-credit-card-front me-1 text-primary"></i>Payment Advice</h6>
      <a href="{{ route('erp.payment-advices.create', ['po_id' => $purchaseOrder->id]) }}" class="btn btn-xs btn-outline-primary">
        <i class="bx bx-plus me-1"></i>New Payment Advice
      </a>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th width="8%">Action</th>
            <th>Supplier Invoice No</th>
            <th>Supplier Name</th>
            <th>Contact Person</th>
            <th>Sum of Payment Amount With Tax</th>
            <th>Outstanding</th>
            <th>Due Date</th>
            <th>Status</th>
            <th>Approval Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($poPAdvices as $pa)
            <tr>
              <td>
                <a href="{{ route('erp.payment-advices.show', $pa) }}" class="text-primary fw-semibold">View</a>
              </td>
              <td>
                <a href="{{ route('erp.payment-advices.show', $pa) }}" class="fw-bold text-primary">{{ $pa->supplier_invoice_no }}</a>
              </td>
              <td>{{ $pa->supplier?->name ?? '-' }}</td>
              <td>{{ $pa->contact_person ?? '-' }}</td>
              <td class="fw-bold">IDR {{ number_format($pa->sum_payment_amount_with_tax, 0, ',', '.') }}</td>
              <td class="text-danger fw-bold">IDR {{ number_format($pa->outstanding, 0, ',', '.') }}</td>
              <td>{{ $pa->due_date?->format('Y/m/d') ?? '-' }}</td>
              <td>{{ $pa->status }}</td>
              <td>
                @if($pa->approval_status === 'Approved')
                  <span class="badge bg-label-success fw-bold">Approved</span>
                @else
                  <span class="badge bg-label-warning fw-bold">{{ $pa->approval_status }}</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-3">No records to display</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Approve PO -->
  <div class="modal fade" id="approvePoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('erp.purchase-orders.approve', $purchaseOrder) }}" method="POST" class="modal-content">
        @csrf
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold">Approve PO: {{ $purchaseOrder->po_no }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Comments</label>
            <textarea name="comments" class="form-control" rows="3" placeholder="Enter optional comments..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm text-white">Approve PO</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Reject PO -->
  <div class="modal fade" id="rejectPoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('erp.purchase-orders.reject', $purchaseOrder) }}" method="POST" class="modal-content">
        @csrf
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold text-danger">Reject PO: {{ $purchaseOrder->po_no }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold text-danger">Rejection Reason / Comments <span class="text-danger">*</span></label>
            <textarea name="comments" class="form-control" rows="3" required placeholder="Describe why this PO is being rejected..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm text-white">Reject PO</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Attachment Preview -->
  <div class="modal fade" id="attachmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold" id="attachmentModalTitle">Preview Attachment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0 text-center bg-light" style="min-height: 400px; max-height: 80vh; overflow: auto; display: flex; align-items: center; justify-content: center;">
          <div id="attachmentLoading" class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <iframe id="attachmentIframe" src="" style="width: 100%; height: 75vh; border: none; display: none;"></iframe>
          <img id="attachmentImage" src="" style="max-width: 100%; max-height: 75vh; display: none;" />
          <div id="attachmentDownload" class="p-4" style="display: none;">
            <i class="bx bx-file" style="font-size: 4rem; color: #696cff;"></i>
            <h6 class="mt-3 mb-1" id="attachmentUnsupportedName">Filename.ext</h6>
            <p class="text-muted small mb-3">Preview tidak tersedia untuk format file ini.</p>
            <a href="#" id="attachmentDownloadBtn" class="btn btn-primary btn-sm" target="_blank" download>Download File</a>
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
    
    // Hide all contents initially
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
    }, 400); // Delay to let modal open smoothly
}

// Toggle chevron icon on collapse
document.addEventListener('DOMContentLoaded', function () {
    var collapsibles = document.querySelectorAll('.collapse');
    collapsibles.forEach(function (collapsible) {
        collapsible.addEventListener('show.bs.collapse', function () {
            var toggleBtn = document.querySelector('[data-bs-target="#' + collapsible.id + '"] .bx-chevron-down');
            if (toggleBtn) {
                toggleBtn.style.transform = 'rotate(0deg)';
                toggleBtn.style.transition = 'transform 0.3s ease';
            }
        });
        collapsible.addEventListener('hide.bs.collapse', function () {
            var toggleBtn = document.querySelector('[data-bs-target="#' + collapsible.id + '"] .bx-chevron-down');
            if (toggleBtn) {
                toggleBtn.style.transform = 'rotate(-90deg)';
                toggleBtn.style.transition = 'transform 0.3s ease';
            }
        });
    });
});
</script>
@endpush
@endsection
