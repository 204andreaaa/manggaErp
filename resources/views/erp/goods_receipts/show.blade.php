@extends('layouts.home')

@section('title', 'DO Detail: ' . $goodsReceipt->do_no)

@section('content')
<style>
  .gr-nav-tabs .nav-link {
    color: #64748b;
    border: none;
    border-bottom: 3px solid transparent;
    border-radius: 0;
    font-weight: 500;
    transition: all 0.2s ease;
  }
  .gr-nav-tabs .nav-link:hover {
    color: #4f46e5;
    background: #f8fafc;
  }
  .gr-nav-tabs .nav-link.active {
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
      <h4 class="mb-1 fw-bold text-dark"><i class="bx bx-truck text-primary me-2"></i>Goods Receipt / Delivery Order</h4>
      <div class="text-muted small">
        DO No: <span class="fw-bold text-dark me-2">{{ $goodsReceipt->do_no }}</span>
        Reference PO: 
        <a href="{{ route('erp.purchase-orders.show', $goodsReceipt->purchaseOrder) }}" class="badge bg-label-primary fs-7 text-decoration-none">
          {{ $goodsReceipt->purchaseOrder?->po_no }}
        </a>
      </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex align-items-center flex-wrap gap-2">
      <a href="{{ route('erp.purchase-orders.show', $goodsReceipt->purchaseOrder) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
        <i class="bx bx-arrow-back me-1"></i>Back to PO
      </a>

      <a href="{{ route('erp.goods-receipts.print', $goodsReceipt) }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">
        <i class="bx bx-printer me-1"></i>Print GR
      </a>

      @if($goodsReceipt->status !== 'Received')
        <button type="button" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#receiveModal">
          <i class="bx bx-check-double me-1"></i>Receive Verification
        </button>
      @else
        <span class="badge bg-success px-3 py-2 fs-7"><i class="bx bx-check-circle me-1"></i>Verification Completed</span>
      @endif

      @if(auth()->user()->hasRole('superadmin'))
        <form action="{{ route('erp.goods-receipts.destroy', $goodsReceipt) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Goods Receipt (DO) ini? Stok fisik yang pernah diterima akan dikurangi kembali dan PO akan dikembalikan ke status Approved.');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
            <i class="bx bx-trash me-1"></i>Delete GR
          </button>
        </form>
      @endif
    </div>
  </div>

  @php
    $calcDelivered = $goodsReceipt->items->sum(function($i) {
      return $i->delivered_qty > 0 ? $i->delivered_qty : ($i->purchaseOrderItem?->qty ?: 1);
    });

    $calcReceived = $goodsReceipt->items->sum(function($i) {
      return $i->received_qty > 0 ? $i->received_qty : ($i->delivered_qty > 0 ? $i->delivered_qty : ($i->purchaseOrderItem?->qty ?: 1));
    });
  @endphp

  {{-- Stats Summary Row --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-primary bg-opacity-10 h-100">
        <div class="text-muted small fw-semibold">DO NUMBER</div>
        <h5 class="mb-0 fw-extrabold text-primary">{{ $goodsReceipt->do_no }}</h5>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
        <div class="text-muted small fw-semibold">SUPPLIER</div>
        <h6 class="mb-0 fw-bold text-dark text-truncate">{{ $goodsReceipt->supplier?->name ?: '-' }}</h6>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100 border-start border-4 border-warning">
        <div class="text-muted small fw-semibold">STATUS</div>
        <div>
          <span class="badge {{ $goodsReceipt->status === 'Received' ? 'bg-success' : 'bg-warning' }} px-3 py-1 fs-7 fw-bold">{{ $goodsReceipt->status }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card shadow-sm border-0 rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
        <div class="text-muted small fw-semibold">TOTAL RECEIVED QTY</div>
        <h6 class="mb-0 fw-extrabold text-success">{{ number_format($calcReceived, 2, ',', '.') }}</h6>
      </div>
    </div>
  </div>

  {{-- Main Container Card --}}
  <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
    
    {{-- Tab Navigation Bar --}}
    <div class="bg-white border-bottom">
      <ul class="nav nav-tabs nav-fill gr-nav-tabs border-0" id="grShowTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active py-3" id="tab-overview-btn" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
            <i class="bx bx-buildings me-2 fs-5"></i>1. Overview & Warehouse Info
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-items-btn" data-bs-toggle="tab" data-bs-target="#tab-items" type="button" role="tab">
            <i class="bx bx-package me-2 fs-5"></i>2. Received Items (DO Line Items) <span class="badge bg-primary rounded-pill ms-1">{{ $goodsReceipt->items->count() }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-notes-btn" data-bs-toggle="tab" data-bs-target="#tab-notes" type="button" role="tab">
            <i class="bx bx-paperclip me-2 fs-5"></i>3. Verification & Biometrics
          </button>
        </li>
      </ul>
    </div>

    {{-- Tab Content Panes --}}
    <div class="card-body p-4 tab-content" id="grShowTabContent">

      {{-- Tab 1: Overview & Warehouse Info --}}
      <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
        <div class="row g-4">
          
          {{-- Left Column: DO Header & Supplier --}}
          <div class="col-md-6 border-end">
            <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-detail me-1"></i>DO Header & Supplier Details</h6>
            
            <table class="table table-borderless table-sm mb-0">
              <tbody>
                <tr>
                  <td class="text-muted fw-semibold" style="width: 38%;">DO Number</td>
                  <td class="fw-bold text-dark">: {{ $goodsReceipt->do_no }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Reference PO No</td>
                  <td>: 
                    <a href="{{ route('erp.purchase-orders.show', $goodsReceipt->purchaseOrder) }}" class="text-primary fw-bold">
                      {{ $goodsReceipt->purchaseOrder?->po_no }}
                    </a>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Supplier Name</td>
                  <td class="fw-bold text-dark">: {{ $goodsReceipt->supplier?->name ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Supplier Address</td>
                  <td>: {{ $goodsReceipt->purchaseOrder?->address ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Sending Contact</td>
                  <td class="text-primary fw-semibold">: {{ $goodsReceipt->sending_contact ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Receiving Contact</td>
                  <td class="text-primary fw-semibold">: {{ $goodsReceipt->receiving_contact ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Status</td>
                  <td>: <span class="badge {{ $goodsReceipt->status === 'Received' ? 'bg-success' : 'bg-warning' }}">{{ $goodsReceipt->status }}</span></td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Total Delivered Qty</td>
                  <td class="fw-bold">: {{ number_format($calcDelivered, 2, ',', '.') }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Remarks / Note</td>
                  <td>: {{ $goodsReceipt->remarks ?: '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          {{-- Right Column: Destination Warehouse & Receiver --}}
          <div class="col-md-6">
            <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-buildings me-1"></i>Destination & Receiver Information</h6>
            
            <table class="table table-borderless table-sm mb-4">
              <tbody>
                <tr>
                  <td class="text-muted fw-semibold" style="width: 38%;">Owner / P.I.C.</td>
                  <td class="fw-bold text-dark">: {{ $goodsReceipt->owner?->name ?: 'Administrator' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">DO Date</td>
                  <td>: {{ $goodsReceipt->date ? \Carbon\Carbon::parse($goodsReceipt->date)->format('Y-m-d') : '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Supplier DO No</td>
                  <td>: {{ $goodsReceipt->supplier_do_no ?: '-' }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Destination Warehouse</td>
                  <td class="fw-bold text-primary">: {{ $goodsReceipt->purchaseOrder?->warehouse?->name ?: ($goodsReceipt->warehouse?->name ?: 'Gudang Utama HQ') }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Record Type</td>
                  <td>: <span class="badge bg-label-info">{{ $goodsReceipt->record_type ?: 'EXTERNAL' }}</span></td>
                </tr>
                <tr>
                  <td class="text-muted fw-semibold">Total Received Qty</td>
                  <td class="fw-bold text-success">: {{ number_format($calcReceived, 2, ',', '.') }}</td>
                </tr>
              </tbody>
            </table>

            {{-- Biometric Verification Box --}}
            <div class="p-3 bg-light rounded-3 border">
              <h6 class="fw-bold text-dark mb-2"><i class="bx bx-shield-quarter me-1 text-primary"></i>Receive Verification Record</h6>
              <div class="row g-2 small">
                <div class="col-6">
                  <span class="text-muted d-block">Receive Verified By:</span>
                  <span class="fw-bold text-dark">{{ $goodsReceipt->verifiedBy?->name ?: ($goodsReceipt->receive_verified_by_id ? \App\Models\User::find($goodsReceipt->receive_verified_by_id)?->name : 'Not Verified Yet') }}</span>
                </div>
                <div class="col-6">
                  <span class="text-muted d-block">Verification Timestamp:</span>
                  <span class="fw-bold text-dark">{{ $goodsReceipt->verification_timestamp ? \Carbon\Carbon::parse($goodsReceipt->verification_timestamp)->format('d M Y H:i') : '-' }}</span>
                </div>
              </div>
            </div>

          </div>

        </div>
      </div>

      {{-- Tab 2: Received Items Table --}}
      <div class="tab-pane fade" id="tab-items" role="tabpanel">
        <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-package me-1"></i>Delivered & Received Line Items</h6>

        <div class="table-responsive rounded-3 border">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr class="text-uppercase small fw-bold text-muted">
                <th>DO Detail Name</th>
                <th>PO Detail No</th>
                <th>Product Name</th>
                <th>Product Model</th>
                <th class="text-end">Delivered Qty</th>
                <th class="text-end">Received Qty</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              @forelse($goodsReceipt->items as $item)
                @php
                  $poItem = $item->purchaseOrderItem;
                  $rfItem = $item->requestFormItem;
                  $delQty = $item->delivered_qty > 0 ? $item->delivered_qty : ($poItem?->qty ?: 1);
                  $recQty = $item->received_qty > 0 ? $item->received_qty : $delQty;
                  $prodName = $poItem?->product_name ?: ($rfItem?->product_name ?: 'Product Item');
                  $prodModel = $poItem?->model ?: ($rfItem?->erpProduct?->productModel?->name ?: '-');
                @endphp
                <tr>
                  <td>
                    <span class="fw-bold text-primary">{{ $item->do_detail_no ?: 'DOIN-'.$item->id }}</span>
                  </td>
                  <td>
                    <span class="fw-semibold text-dark">{{ $poItem?->po_detail_no ?: '-' }}</span>
                  </td>
                  <td>
                    <div class="fw-bold text-dark">{{ $prodName }}</div>
                  </td>
                  <td>
                    <span class="badge bg-label-secondary">{{ $prodModel }}</span>
                  </td>
                  <td class="text-end fw-bold text-dark">
                    {{ number_format($delQty, 2, ',', '.') }}
                  </td>
                  <td class="text-end fw-bold text-success">
                    {{ number_format($recQty, 2, ',', '.') }}
                  </td>
                  <td class="small text-muted">
                    {{ $item->remark ?: '-' }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">No items recorded in this Delivery Order.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- Tab 3: Verification & Biometrics --}}
      <div class="tab-pane fade" id="tab-notes" role="tabpanel">
        <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-shield-check me-1"></i>Verification & Delivery Documents</h6>
        
        <div class="row g-4">
          <div class="col-md-6">
            <div class="p-4 bg-light rounded-4 border">
              <h6 class="fw-bold text-dark mb-3"><i class="bx bx-file text-primary me-2 fs-5"></i>Surat Jalan / Delivery Note</h6>
              <p class="text-muted small mb-3">Dokumen bukti serah terima barang dari supplier ke lokasi gudang.</p>
              <a href="{{ route('erp.goods-receipts.print', $goodsReceipt) }}" target="_blank" class="btn btn-outline-primary rounded-pill px-4">
                <i class="bx bx-printer me-1"></i>Cetak Surat Jalan / GR
              </a>
            </div>
          </div>

          <div class="col-md-6">
            <div class="p-4 bg-light rounded-4 border">
              <h6 class="fw-bold text-dark mb-3"><i class="bx bx-check-shield text-success me-2 fs-5"></i>Status Verifikasi Penerimaan</h6>
              @if($goodsReceipt->status === 'Received')
                <div class="alert alert-success border-0 mb-0">
                  <i class="bx bx-check-circle me-1 fs-5 align-middle"></i> Barang telah berhasil diverifikasi dan masuk ke dalam Stok Fisik Gudang.
                </div>
              @else
                <div class="alert alert-warning border-0 mb-3">
                  <i class="bx bx-time-five me-1 fs-5 align-middle"></i> Status penerimaan masih pending verification.
                </div>
                <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#receiveModal">
                  <i class="bx bx-check-double me-1"></i>Verifikasi Penerimaan Barang
                </button>
              @endif
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>

  {{-- Receive Verification Modal --}}
  <div class="modal fade" id="receiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('erp.goods-receipts.receive', $goodsReceipt) }}" method="POST" class="modal-content shadow-lg border-0 rounded-4">
        @csrf
        <div class="modal-header border-bottom bg-success bg-opacity-10 py-3">
          <h5 class="modal-title fw-bold text-success"><i class="bx bx-check-circle me-1"></i>Verifikasi Penerimaan Barang (GR)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <p class="text-muted small">Dengan memverifikasi penerimaan barang ini, jumlah fisik barang akan secara otomatis ditambahkan ke dalam **Stok Fisik Gudang ERP**.</p>
          <div class="mb-3">
            <label class="form-label fw-semibold">Catatan Penerimaan (Opsional)</label>
            <textarea name="remarks" class="form-control rounded-3" rows="3" placeholder="Tuliskan catatan kondisi fisik barang..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-top bg-light py-3">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success text-white px-4 shadow-sm">
            <i class="bx bx-check me-1"></i>Proses & Tambah Stok
          </button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
