@extends('layouts.home')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold py-1 mb-0">
      <span class="text-muted fw-light">Procurement ERP /</span> Payment Advice (Supplier Invoices)
    </h4>
    @if(auth()->user()->hasRole('superadmin'))
    <a href="{{ route('erp.payment-advices.create') }}" class="btn btn-outline-primary btn-sm">
      <i class="bx bx-plus me-1"></i>New PA (Manual Fallback)
    </a>
    @endif
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="mb-0 fw-bold"><i class="bx bx-credit-card-front me-2 text-primary"></i>Daftar Tagihan & Pembayaran Supplier (Payment Advice)</h6>
    </div>
    <div class="card-body mt-3">
      <div class="table-responsive">
        <table class="table table-hover align-middle w-100" id="payment-advice-table">
          <thead class="table-light">
            <tr>
              <th width="5%">No</th>
              <th>Supplier Invoice No</th>
              <th>PO No</th>
              <th>Supplier</th>
              <th>Invoice No</th>
              <th>Total Amount</th>
              <th>Outstanding</th>
              <th>Due Date</th>
              <th>Approval</th>
              <th>Status</th>
              <th width="10%">Action</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
  $('#payment-advice-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "{{ route('erp.payment-advices.datatable') }}",
      type: "POST",
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    },
    columns: [
      { data: 'rownum', name: 'rownum', orderable: false, searchable: false },
      { data: 'supplier_invoice_no', name: 'supplier_invoice_no' },
      { data: 'po_no', name: 'po_no' },
      { data: 'supplier_name', name: 'supplier_name' },
      { data: 'invoice_no', name: 'invoice_no' },
      { data: 'total_amount', name: 'total_amount' },
      { data: 'outstanding', name: 'outstanding' },
      { data: 'due_date', name: 'due_date' },
      { data: 'approval_status', name: 'approval_status' },
      { data: 'payment_closed', name: 'payment_closed' },
      { data: 'action', name: 'action', orderable: false, searchable: false }
    ],
    order: [[1, 'desc']]
  });
});
</script>
@endpush
