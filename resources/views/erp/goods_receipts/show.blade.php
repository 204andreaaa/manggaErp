@extends('layouts.home')

@section('title', 'DO Detail: ' . $goodsReceipt->do_no)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Title Section -->
  <div class="d-flex align-items-center mb-2">
    <div>
      <div class="text-muted small">DO</div>
      <h4 class="mb-0 fw-bold d-flex align-items-center">
        <i class="bx bxs-flag-alt text-warning me-2 fs-3"></i> {{ $goodsReceipt->do_no }}
      </h4>
    </div>
    <div class="ms-auto">
      <a href="{{ route('erp.purchase-orders.show', $goodsReceipt->purchaseOrder) }}" class="small">« Back to List: Custom Object Definitions</a>
    </div>
  </div>

  <div class="mb-3 small d-flex gap-2">
    <a href="#do-detail" class="text-primary">DO Details [{{ $goodsReceipt->items->count() }}]</a> | 
    <a href="#notes-attachments" class="text-primary">Notes & Attachments [0]</a> | 
    <a href="#payment-advice" class="text-primary">Payment Advice Detail [0]</a>
  </div>

  <!-- DO Detail Card -->
  <div class="card mb-4" id="do-detail">
    <div class="card-header border-bottom py-2 d-flex justify-content-between align-items-center bg-light">
      <h6 class="mb-0 fw-bold">DO Detail</h6>
      <div class="d-flex gap-1 align-items-center">
        <button class="btn btn-xs btn-outline-secondary" disabled>Edit</button>
        <button class="btn btn-xs btn-outline-secondary" disabled>1. Bypass Verification GR</button>
        <button class="btn btn-xs btn-outline-secondary" disabled>2. Create Inventory Usage</button>
        <button class="btn btn-xs btn-outline-secondary" disabled>3. Change Record Type</button>
        
        @if($goodsReceipt->status !== 'Received')
          <button type="button" class="btn btn-xs btn-primary" data-bs-toggle="modal" data-bs-target="#receiveModal">Receive Verification</button>
        @else
          <button class="btn btn-xs btn-outline-secondary" disabled>Receive Verification</button>
        @endif
        
        <a href="{{ route('erp.goods-receipts.print', $goodsReceipt) }}" target="_blank" class="btn btn-xs btn-outline-secondary me-1">Print GR</a>

        @if(auth()->user()->hasRole('superadmin'))
          <form action="{{ route('erp.goods-receipts.destroy', $goodsReceipt) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Goods Receipt (DO) ini? Stok fisik yang pernah diterima akan dikurangi kembali dan PO akan dikembalikan ke status Approved.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-xs btn-outline-danger">
              <i class="bx bx-trash me-1"></i>Delete GR
            </button>
          </form>
        @endif
      </div>
    </div>
    
    <div class="card-body mt-3">
      <div class="row small">
        <!-- Left Column -->
        <div class="col-md-6 border-end">
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">DO No</div>
            <div class="col-8 fw-bold">{{ $goodsReceipt->do_no }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">PO No</div>
            <div class="col-8">
              <a href="{{ route('erp.purchase-orders.show', $goodsReceipt->purchaseOrder) }}" class="text-primary">
                {{ $goodsReceipt->purchaseOrder?->po_no }}
              </a>
            </div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Supplier Name</div>
            <div class="col-8">{{ $goodsReceipt->supplier?->name ?: '-' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Supplier Address</div>
            <div class="col-8">{{ $goodsReceipt->purchaseOrder?->address ?: '-' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Sending Contact</div>
            <div class="col-8">
              <a href="#" class="text-primary">{{ $goodsReceipt->sending_contact ?: '1203250003' }}</a>
            </div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Status</div>
            <div class="col-8 fw-bold">{{ $goodsReceipt->status }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Total Delivered Qty</div>
            <div class="col-8">{{ number_format($goodsReceipt->total_delivered_qty, 2, ',', '.') }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Error</div>
            <div class="col-8">No</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Receiving Contact</div>
            <div class="col-8">
              <a href="#" class="text-primary">{{ $goodsReceipt->receiving_contact ?: '8617701' }}</a>
            </div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Remarks Long</div>
            <div class="col-8">{{ $goodsReceipt->remarks ?: 'Biaya Penghemat Telepon Periode Juni 2026' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Document Complete</div>
            <div class="col-8">{{ $goodsReceipt->document_complete_date?->format('Y/m/d') ?: '-' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Status Receive Date</div>
            <div class="col-8">{{ $goodsReceipt->status_receive_date?->format('Y/m/d') ?: '-' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Count Received GR</div>
            <div class="col-8">0</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">KPI Received GR</div>
            <div class="col-8">Achieved</div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6 ps-4">
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Owner</div>
            <div class="col-8">
              <i class="bx bx-user me-1 text-muted"></i>
              <a href="#" class="text-primary">{{ $goodsReceipt->owner?->name ?: 'Administrator' }}</a>
              <a href="#" class="ms-1 text-primary">[Change]</a>
            </div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Date</div>
            <div class="col-8">{{ $goodsReceipt->date?->format('Y/m/d') ?: '-' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Supplier DO No</div>
            <div class="col-8">-</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Warehouse To</div>
            <div class="col-8">{{ $goodsReceipt->warehouse?->code ?: 'WH001' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Receiving Location Address</div>
            <div class="col-8"></div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Total Received Qty</div>
            <div class="col-8">{{ number_format($goodsReceipt->total_received_qty, 2, ',', '.') }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Record Type</div>
            <div class="col-8">{{ $goodsReceipt->record_type }} <a href="#" class="ms-1 text-primary">[Change]</a></div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Remarks</div>
            <div class="col-8">{{ $goodsReceipt->remarks ?: 'Biaya Penghemat Telepon Periode Juni 2026' }}</div>
          </div>
          <div class="row mb-1">
            <div class="col-4 text-end fw-semibold text-muted">Bypass Verification</div>
            <div class="col-8">
              <i class="bx {{ $goodsReceipt->bypass_verification ? 'bx-check-square text-success' : 'bx-square text-muted' }}"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Biometric Section -->
      <div class="border-top border-2 border-primary pt-3 mt-4">
        <h6 class="fw-bold mb-3 d-flex align-items-center">
          <i class="bx bx-caret-down me-1"></i> Biometric
        </h6>
        <div class="row small">
          <div class="col-md-6">
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Receive Verified By</div>
              <div class="col-8">{{ $goodsReceipt->verifiedBy?->name ?: '-' }}</div>
            </div>
            <div class="row mb-1 mt-2">
              <div class="col-4 text-end fw-semibold text-muted">Custom Links</div>
              <div class="col-8"><a href="{{ route('erp.goods-receipts.print', $goodsReceipt) }}" target="_blank" class="text-primary text-decoration-underline"><i class="bx bx-printer me-1"></i>Surat Jalan</a></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row mb-1">
              <div class="col-4 text-end fw-semibold text-muted">Receive Verification Timestamp</div>
              <div class="col-8">{{ $goodsReceipt->verification_timestamp?->format('Y/m/d H:i') ?: '-' }}</div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Action Buttons Footer -->
      <div class="text-center mt-4">
        <button class="btn btn-xs btn-outline-secondary" disabled>Edit</button>
        <button class="btn btn-xs btn-outline-secondary" disabled>1. Bypass Verification GR</button>
        <button class="btn btn-xs btn-outline-secondary" disabled>2. Create Inventory Usage</button>
        <button class="btn btn-xs btn-outline-secondary" disabled>3. Change Record Type</button>
        
        @if($goodsReceipt->status !== 'Received')
          <button type="button" class="btn btn-xs btn-primary" data-bs-toggle="modal" data-bs-target="#receiveModal">Receive Verification</button>
        @else
          <button class="btn btn-xs btn-outline-secondary" disabled>Receive Verification</button>
        @endif
        
        <a href="{{ route('erp.goods-receipts.print', $goodsReceipt) }}" target="_blank" class="btn btn-xs btn-outline-secondary me-1">Print GR</a>

        @if(auth()->user()->hasRole('superadmin'))
          <form action="{{ route('erp.goods-receipts.destroy', $goodsReceipt) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Goods Receipt (DO) ini? Stok fisik yang pernah diterima akan dikurangi kembali dan PO akan dikembalikan ke status Approved.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-xs btn-outline-danger">
              <i class="bx bx-trash me-1"></i>Delete GR
            </button>
          </form>
        @endif
      </div>
    </div>
  </div>

  <!-- Related List: DO Details -->
  <div class="card mb-4 border-top border-2 border-primary" id="do-details">
    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light border-bottom">
      <h6 class="mb-0 fw-bold">DO Details</h6>
      <button class="btn btn-xs btn-outline-secondary" disabled>New DO Detail</button>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th>Action</th>
            <th>DO Detail Name</th>
            <th>PO Detail</th>
            <th>RF</th>
            <th>Product</th>
            <th>Model</th>
            <th>Asset Id</th>
            <th class="text-end">Delivered Qty</th>
            <th class="text-end">Received Qty</th>
            <th>Updated</th>
            <th>Remark</th>
          </tr>
        </thead>
        <tbody>
          @forelse($goodsReceipt->items as $item)
            <tr>
              <td><span class="text-muted small">Edit | Del</span></td>
              <td class="fw-semibold">
                <a href="#" class="text-primary">{{ $item->do_detail_no }}</a>
              </td>
              <td>
                <a href="#" class="text-primary">{{ $item->purchaseOrderItem?->po_detail_no }}</a>
              </td>
              <td>
                <a href="#" class="text-primary">{{ $item->requestFormItem?->requestForm?->rf_no }}</a>
              </td>
              <td>
                <a href="#" class="text-primary">{{ $item->requestFormItem?->product_name }}</a>
              </td>
              <td>{{ $item->requestFormItem?->erpProduct?->productModel?->model_name ?: '-' }}</td>
              <td>-</td>
              <td class="text-end">{{ number_format((float)$item->delivered_qty, 2, ',', '.') }}</td>
              <td class="text-end">{{ number_format((float)$item->received_qty, 2, ',', '.') }}</td>
              <td></td>
              <td>{{ $item->remark ?: '-' }}</td>
            </tr>
          @empty
            <tr><td colspan="11" class="text-center text-muted py-3">No records to display</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Related List: Payment Advice Detail -->
  @php
    $paDetails = \App\Models\Erp\ErpPaymentAdviceDetail::where('erp_goods_receipt_id', $goodsReceipt->id)->orWhere('erp_purchase_order_id', $goodsReceipt->erp_purchase_order_id)->get();
    $pAdvices = \App\Models\Erp\ErpPaymentAdvice::where('erp_purchase_order_id', $goodsReceipt->erp_purchase_order_id)->get();
  @endphp

  <div class="card mb-4 border-top border-2 border-primary" id="payment-advice-detail">
    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light border-bottom">
      <h6 class="mb-0 fw-bold"><i class="bx bx-receipt me-1 text-primary"></i>Payment Advice Detail</h6>
      <a href="{{ route('erp.payment-advices.create', ['gr_id' => $goodsReceipt->id, 'po_id' => $goodsReceipt->erp_purchase_order_id]) }}" class="btn btn-xs btn-outline-primary">
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
          @forelse($paDetails as $pad)
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
      <a href="{{ route('erp.payment-advices.create', ['po_id' => $goodsReceipt->erp_purchase_order_id]) }}" class="btn btn-xs btn-outline-primary">
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
          @forelse($pAdvices as $pa)
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

  <!-- Related List: Notes & Attachments -->
  <div class="card mb-4 border-top border-2 border-primary" id="notes-attachments">
    <div class="card-header py-2 d-flex align-items-center gap-2 bg-light border-bottom">
      <h6 class="mb-0 fw-bold">Notes & Attachments</h6>
      <button class="btn btn-xs btn-outline-secondary" disabled>New Note</button>
      <button class="btn btn-xs btn-outline-secondary" disabled>Attach File</button>
    </div>
    <div class="card-body py-3">
      <div class="text-muted small">No records to display</div>
    </div>
  </div>
</div>

<!-- Receive Verification Modal -->
<div class="modal fade" id="receiveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form action="{{ route('erp.goods-receipts.receive', $goodsReceipt) }}" method="POST">
        @csrf
        <div class="modal-header border-bottom pb-3">
          <h5 class="modal-title fw-bold">Receive Verification</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Receive Verified By</label>
            <select name="verified_by_id" class="form-select form-select-sm" required>
              <option value="">-- Select User --</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}" {{ auth()->id() == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer border-top pt-3">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-primary">Submit Verification</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection
