@extends('layouts.home')

@section('title', 'Kumpulan Purchase Orders')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0 fw-bold py-1">
        <span class="text-muted fw-light">Procurement ERP /</span> Purchase Orders List
      </h4>
      <div class="text-muted small">Daftar & Kumpulan Seluruh Purchase Order (Master PO)</div>
    </div>
    @if(auth()->user()->hasRole(['procurement', 'superadmin', 'admin']))
      <a href="{{ route('erp.procurement.dashboard') }}" class="btn btn-primary btn-sm">
        <i class="bx bx-plus me-1"></i>+ Create PO Baru (dari RF/PR)
      </a>
    @endif
  </div>

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

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold"><i class="bx bx-list-check me-2 text-primary"></i>Kumpulan Master Purchase Orders</h6>
      
      <!-- Filter Tabs -->
      <div class="btn-group btn-group-sm" role="group" id="po-filter-tabs">
        <button type="button" class="btn btn-outline-primary active" data-filter="">Semua PO</button>
        <button type="button" class="btn btn-outline-primary" data-filter="Draft">Draft</button>
        <button type="button" class="btn btn-outline-primary" data-filter="Approved">Approved</button>
        <button type="button" class="btn btn-outline-primary" data-filter="Completed">✓ Completed</button>
      </div>
    </div>

    <div class="card-body mt-3">
      <div class="table-responsive">
        <table class="table table-hover align-middle w-100" id="po-master-table">
          <thead class="table-light">
            <tr>
              <th width="5%">No</th>
              <th>No PO</th>
              <th>Tanggal</th>
              <th>Supplier</th>
              <th>Tujuan / Gudang</th>
              <th>Total Amount With Tax</th>
              <th>Status</th>
              <th>GR</th>
              <th>Payment Closed</th>
              <th width="10%">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($purchaseOrders as $index => $po)
              <tr data-status="{{ $po->status }}">
                <td>{{ $index + 1 }}</td>
                <td>
                  <a href="{{ route('erp.purchase-orders.show', $po) }}" class="fw-bold text-primary">
                    {{ $po->po_no }}
                  </a>
                </td>
                <td>{{ \Carbon\Carbon::parse($po->date)->format('Y/m/d') }}</td>
                <td class="fw-semibold">{{ $po->supplier?->name ?: '-' }}</td>
                <td>{{ $po->destination ?: '-' }}</td>
                <td class="fw-bold text-dark">IDR {{ number_format($po->total_po_amount_with_tax, 0, ',', '.') }}</td>
                <td>
                  @if($po->status === 'Draft')
                    <span class="badge bg-label-secondary">Draft</span>
                  @elseif($po->status === 'Submitted')
                    <span class="badge bg-label-warning">Submitted</span>
                  @elseif($po->status === 'Approved')
                    <span class="badge bg-label-success fw-bold">Approved</span>
                  @elseif($po->status === 'Completed')
                    <span class="badge bg-label-info fw-bold">✓ Completed</span>
                  @else
                    <span class="badge bg-label-danger">{{ $po->status }}</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($po->gr)
                    <span class="badge bg-label-success"><i class="bx bx-check me-1"></i>Received</span>
                  @else
                    <span class="badge bg-label-secondary">-</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($po->payment_closed)
                    <span class="badge bg-label-success"><i class="bx bx-check me-1"></i>Closed</span>
                  @else
                    <span class="badge bg-label-warning">Not Closed</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex align-items-center gap-1">
                    <a href="{{ route('erp.purchase-orders.show', $po) }}" class="btn btn-xs btn-primary me-1" title="Detail PO">
                      <i class="bx bx-show me-1"></i>View Detail
                    </a>
                    @if(auth()->user()->hasRole('superadmin'))
                      <form action="{{ route('erp.purchase-orders.destroy', $po) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus PO ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete PO">
                          <i class="bx bx-trash"></i>
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
  var table = $('#po-master-table').DataTable({
    order: [[1, 'desc']],
    pageLength: 15,
    language: {
      emptyTable: "Belum ada data Purchase Order.",
      zeroRecords: "Tidak ada Purchase Order yang cocok dengan pencarian."
    }
  });

  // Tab Filtering (Semua, Draft, Approved, Completed)
  $('#po-filter-tabs button').on('click', function() {
    $('#po-filter-tabs button').removeClass('active');
    $(this).addClass('active');

    var filterVal = $(this).data('filter');
    if (filterVal === '') {
      table.column(6).search('').draw();
    } else {
      table.column(6).search('^' + filterVal + '$', true, false).draw();
    }
  });
});
</script>
@endpush
