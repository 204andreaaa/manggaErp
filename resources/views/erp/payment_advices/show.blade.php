@extends('layouts.home')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Top Navigation & Back Link -->
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <span class="text-muted small">Payment Advice</span>
      <h4 class="fw-bold mb-0 text-primary">{{ $paymentAdvice->supplier_invoice_no }}</h4>
    </div>
    <div>
      <a href="{{ route('erp.payment-advices.index') }}" class="btn btn-xs btn-outline-secondary">« Back to List: Payment Advice</a>
    </div>
  </div>

  <!-- Header Section with Buttons -->
  <div class="card mb-3 border-top border-3 border-primary">
    <div class="card-header border-bottom py-2 d-flex justify-content-between align-items-center bg-light">
      <h6 class="mb-0 fw-bold">Payment Advice Detail</h6>
      <div class="d-flex gap-1 align-items-center">
        @if($paymentAdvice->approval_status === 'Draft')
          <form action="{{ route('erp.payment-advices.submit', $paymentAdvice) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-xs btn-primary">Submit for Approval</button>
          </form>        @elseif($paymentAdvice->approval_status === 'Submitted')
          @if(auth()->user()->hasRole('finance') || auth()->user()->hasRole('ceo') || auth()->user()->hasRole('superadmin'))
            <form action="{{ route('erp.payment-advices.approve', $paymentAdvice) }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-xs btn-success me-1">Approve Payment</button>
            </form>
            <form action="{{ route('erp.payment-advices.reject', $paymentAdvice) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menolak (Reject) Payment Advice ini?');">
              @csrf
              <button type="submit" class="btn btn-xs btn-danger me-1">Reject</button>
            </form>
          @endif
        @endif

        @if($paymentAdvice->approval_status === 'Approved' && !$paymentAdvice->payment_closed)
          @if(auth()->user()->hasRole('finance') || auth()->user()->hasRole('superadmin'))
            <form action="{{ route('erp.payment-advices.mark-paid', $paymentAdvice) }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-xs btn-success"><i class="bx bx-check-double me-1"></i>Mark Paid & Close Payment</button>
            </form>
          @endif
        @endif

        @if(auth()->user()->hasRole('superadmin'))
          <form action="{{ route('erp.payment-advices.destroy', $paymentAdvice) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Payment Advice ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-xs btn-outline-danger me-1"><i class="bx bx-trash me-1"></i>Delete</button>
          </form>
        @endif
      </div>
    </div>

    <div class="card-body mt-3">
      <div class="row small">
        <!-- Left Column -->
        <div class="col-md-6 border-end">
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Supplier Invoice No</div>
            <div class="col-sm-7 fw-bold text-dark">{{ $paymentAdvice->supplier_invoice_no }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">PO No</div>
            <div class="col-sm-7">
              @if($paymentAdvice->purchaseOrder)
                <a href="{{ route('erp.purchase-orders.show', $paymentAdvice->purchaseOrder) }}" class="fw-bold text-primary">{{ $paymentAdvice->purchaseOrder->po_no }}</a>
              @else
                -
              @endif
            </div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Supplier Name</div>
            <div class="col-sm-7 fw-semibold">{{ $paymentAdvice->supplier?->name ?? '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Invoice No</div>
            <div class="col-sm-7">{{ $paymentAdvice->invoice_no }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Contact Person</div>
            <div class="col-sm-7">{{ $paymentAdvice->contact_person ?? '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Payment Type</div>
            <div class="col-sm-7"><span class="badge bg-label-info fw-bold">{{ $paymentAdvice->payment_type ?? 'Full Payment (Pelunasan 100%)' }}</span></div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6">
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Owner</div>
            <div class="col-sm-7">{{ $paymentAdvice->owner?->name ?? '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Due Date</div>
            <div class="col-sm-7">{{ $paymentAdvice->due_date?->format('Y/m/d') ?? '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Status</div>
            <div class="col-sm-7">
              @if($paymentAdvice->status === 'Draft')
                <span class="badge bg-label-secondary">Draft</span>
              @elseif($paymentAdvice->status === 'Submitted')
                <span class="badge bg-label-warning">Submitted</span>
              @elseif($paymentAdvice->status === 'Approved')
                <span class="badge bg-label-success fw-bold">Approved</span>
              @elseif($paymentAdvice->status === 'Completed')
                <span class="badge bg-label-info fw-bold">✓ Completed</span>
              @else
                <span class="badge bg-label-danger">{{ $paymentAdvice->status }}</span>
              @endif
            </div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Approval Status</div>
            <div class="col-sm-7">
              @if($paymentAdvice->approval_status === 'Approved')
                <span class="badge bg-label-success fw-bold">Approved</span>
              @elseif($paymentAdvice->approval_status === 'Submitted')
                <span class="badge bg-label-warning fw-bold">Submitted</span>
              @elseif($paymentAdvice->approval_status === 'Rejected')
                <span class="badge bg-label-danger fw-bold">Rejected</span>
              @else
                <span class="badge bg-label-secondary">Draft</span>
              @endif
            </div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-5 text-secondary fw-semibold text-end">Payment Closed</div>
            <div class="col-sm-7">
              <i class="bx {{ $paymentAdvice->payment_closed ? 'bx-check-square text-success' : 'bx-square text-muted' }}"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Summary Table -->
      <div class="border rounded p-3 bg-light mt-3">
        <h6 class="fw-bold mb-2 text-dark"><i class="bx bx-calculator me-1"></i>Payment Summary</h6>
        <div class="row small">
          <div class="col-md-6 border-end">
            <div class="row mb-1">
              <div class="col-sm-6 text-secondary text-end">Total Invoice Amount</div>
              <div class="col-sm-6 fw-bold">IDR {{ number_format($paymentAdvice->total_invoice_amount, 0, ',', '.') }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-sm-6 text-secondary text-end">Total Invoice Amount With Tax</div>
              <div class="col-sm-6 fw-bold text-primary">IDR {{ number_format($paymentAdvice->total_invoice_amount_with_tax, 0, ',', '.') }}</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row mb-1">
              <div class="col-sm-6 text-secondary text-end">Sum of Payment Amount</div>
              <div class="col-sm-6 fw-bold">IDR {{ number_format($paymentAdvice->sum_payment_amount, 0, ',', '.') }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-sm-6 text-secondary text-end">Sum of Payment Amount With Tax</div>
              <div class="col-sm-6 fw-bold text-success">IDR {{ number_format($paymentAdvice->sum_payment_amount_with_tax, 0, ',', '.') }}</div>
            </div>
            <div class="row mb-1">
              <div class="col-sm-6 text-secondary text-end">Outstanding Balance</div>
              <div class="col-sm-6 fw-bold text-danger">IDR {{ number_format($paymentAdvice->outstanding, 0, ',', '.') }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Related List: Payment Advice Detail -->
  <div class="card mb-4 border-top border-2 border-primary">
    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light border-bottom">
      <h6 class="mb-0 fw-bold"><i class="bx bx-receipt me-1 text-primary"></i>Payment Advice Detail (Termin Pembayaran)</h6>
      @if($paymentAdvice->payment_type === 'Final Payment (Pelunasan 100%)' || $paymentAdvice->payment_closed || $paymentAdvice->outstanding <= 0)
        <span class="badge bg-label-success"><i class="bx bx-check-circle me-1"></i>Full Payment (Locked)</span>
      @else
        <button type="button" class="btn btn-xs btn-primary" data-bs-toggle="modal" data-bs-target="#addTerminModal">
          <i class="bx bx-plus me-1"></i>+ New Payment Advice Detail (Tambah Termin)
        </button>
      @endif
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th width="10%">Action</th>
            <th>Supplier Detail No</th>
            <th>PO No</th>
            <th>Jenis / Termin</th>
            <th>Nominal Bayar</th>
            <th>Payment Amount With Tax</th>
            <th>Approval Status</th>
            <th>Approved Date</th>
            <th>Remark</th>
          </tr>
        </thead>
        <tbody>
          @forelse($paymentAdvice->details as $pad)
            <tr>
              <td>
                <div class="d-flex align-items-center gap-1">
                  <a href="{{ route('erp.payment-advice-details.show', $pad) }}" class="btn btn-xs btn-label-primary">View</a>
                  @if(auth()->user()->hasRole('superadmin'))
                    <form action="{{ route('erp.payment-advice-details.destroy', $pad) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus termin ini?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete Termin"><i class="bx bx-trash"></i></button>
                    </form>
                  @endif
                </div>
              </td>
              <td>
                <a href="{{ route('erp.payment-advice-details.show', $pad) }}" class="fw-bold text-primary">{{ $pad->supplier_detail_no }}</a>
              </td>
              <td>{{ $paymentAdvice->purchaseOrder?->po_no ?? '-' }}</td>
              <td><span class="badge bg-label-info">{{ $pad->payment_type }}</span></td>
              <td>IDR {{ number_format($pad->payment_amount, 0, ',', '.') }}</td>
              <td class="fw-bold">IDR {{ number_format($pad->payment_amount_with_tax, 0, ',', '.') }}</td>
              <td>
                @if($pad->approval_status === 'Approved')
                  <span class="badge bg-label-success fw-bold">Approved</span>
                @else
                  <span class="badge bg-label-warning fw-bold">{{ $pad->approval_status }}</span>
                @endif
              </td>
              <td>{{ $pad->approved_date?->format('Y/m/d') ?? '-' }}</td>
              <td class="text-truncate" style="max-width: 200px;">{{ $pad->remark ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted py-3">Belum ada rincian Payment Advice Detail.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Approval History (Salesforce Style Banner Rows) -->
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
          @if($paymentAdvice->approval_status === 'Approved')
            <!-- Banner Header Step 3 -->
            <tr style="background-color: #696cff; color: white;">
              <td colspan="7" class="fw-bold py-1 px-3" style="font-size: 0.82rem; letter-spacing: 0.5px;">Step: Step 3</td>
            </tr>
            <tr>
              <td>-</td>
              <td>{{ now()->format('Y/m/d H:i') }}</td>
              <td><span class="badge bg-label-success">Approved</span></td>
              <td>{{ auth()->user()->name }}</td>
              <td>{{ auth()->user()->name }}</td>
              <td>Approved Payment Advice Invoice</td>
              <td><span class="badge bg-success text-white"><i class="bx bx-check-circle me-1"></i>Approved</span></td>
            </tr>

            <!-- Banner Header Step 2 -->
            <tr style="background-color: #696cff; color: white;">
              <td colspan="7" class="fw-bold py-1 px-3" style="font-size: 0.82rem; letter-spacing: 0.5px;">Step: Step 2</td>
            </tr>
            <tr>
              <td>-</td>
              <td>{{ $paymentAdvice->created_at->format('Y/m/d H:i') }}</td>
              <td><span class="badge bg-label-success">Approved</span></td>
              <td>Finance Manager</td>
              <td>Finance Manager</td>
              <td>Verified & Approved</td>
              <td><span class="badge bg-success text-white"><i class="bx bx-check-circle me-1"></i>Approved</span></td>
            </tr>
          @elseif($paymentAdvice->approval_status === 'Submitted')
            <tr style="background-color: #696cff; color: white;">
              <td colspan="7" class="fw-bold py-1 px-3" style="font-size: 0.82rem; letter-spacing: 0.5px;">Step: Step 1</td>
            </tr>
            <tr>
              <td>-</td>
              <td>{{ $paymentAdvice->created_at->format('Y/m/d H:i') }}</td>
              <td><span class="badge bg-label-warning">Pending</span></td>
              <td>Finance / CEO</td>
              <td>-</td>
              <td>Awaiting Finance Approval</td>
              <td><span class="badge bg-warning text-white"><i class="bx bx-time me-1"></i>Pending</span></td>
            </tr>
          @else
            <tr>
              <td colspan="7" class="text-center text-muted py-3">Belum ada riwayat approval. Silakan klik tombol <b>Submit for Approval</b>.</td>
            </tr>
          @endif
        </tbody>
      </table>
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
        <div class="alert alert-info py-2 px-3 small mb-3">
          <i class="bx bx-info-circle me-1"></i>Sisa Tagihan Outstanding: <strong>IDR {{ number_format($paymentAdvice->outstanding, 0, ',', '.') }}</strong>
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
            <input type="number" step="0.01" name="payment_amount" class="form-control" value="{{ $paymentAdvice->outstanding }}" placeholder="Nominal termin" required>
          </div>
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
@endsection
