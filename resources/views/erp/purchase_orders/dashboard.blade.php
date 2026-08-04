@extends('layouts.home')

@section('title', 'Procurement Dashboard')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold py-3 mb-2">Procurement Dashboard</h4>
  <p class="text-muted">List of Approved Request Forms and Completed PRs ready for PO creation.</p>

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

  <div class="card">
    <div class="card-header border-bottom py-3">
      <h6 class="mb-0 fw-bold">Approved RFs & PRs</h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>RF No</th>
            <th>Date</th>
            <th>Requestor</th>
            <th>Remark</th>
            <th>PR No</th>
            <th style="min-width: 250px;">Purchase Orders</th>
          </tr>
        </thead>
        <tbody>
          @forelse($requestForms as $rf)
            @php
              $prs = $rf->purchaseRequests;
              $hasPo = $rf->purchaseOrders->count() > 0;
            @endphp
            <tr>
              <td>
                <a href="{{ route('erp.request-form.show', $rf) }}" class="fw-semibold text-primary">
                  {{ $rf->rf_no }}
                </a>
              </td>
              <td>{{ $rf->rf_date?->format('Y/m/d') ?: '-' }}</td>
              <td>{{ $rf->requestor }}</td>
              <td>{{ $rf->remark }}</td>
              <td>
                @if($prs->count() > 0)
                  <div class="d-flex flex-column gap-1">
                    @foreach($prs as $pr)
                      <a href="{{ route('erp.purchase-requests.show', $pr) }}" class="text-primary text-decoration-none">
                        {{ $pr->pr_no }}
                      </a>
                    @endforeach
                  </div>
                @else
                  -
                @endif
              </td>
              <td>
                @if($hasPo)
                  <div class="d-flex flex-column gap-2">
                    @foreach($rf->purchaseOrders as $po)
                      @php
                        $badgeClass = $po->status === 'Approved' ? 'success' : ($po->status === 'Submitted' ? 'warning' : ($po->status === 'Rejected' ? 'danger' : 'secondary'));
                      @endphp
                      <div class="d-flex align-items-center justify-content-between border rounded p-1 ps-2 bg-white shadow-sm">
                        <span class="badge bg-label-{{ $badgeClass }} fw-bold" style="font-size: 0.7rem;">
                          {{ $po->status ?: 'Draft' }}
                        </span>
                        <div class="d-flex align-items-center gap-1">
                          <a href="{{ route('erp.purchase-orders.show', $po) }}" class="btn btn-xs btn-outline-success">
                            {{ $po->po_no }}
                          </a>
                          @if(auth()->user()->hasRole('superadmin'))
                            <form action="{{ route('erp.purchase-orders.destroy', $po) }}" method="POST" class="d-inline" onsubmit="return confirm('PERINGATAN KERAS!\n\nMenghapus PO ini akan berakibat:\n1. Dokumen PO ({{ $po->po_no }}) Dihapus Permanen.\n2. Seluruh Item di dalam PO ini ikut terhapus.\n3. Histori Approval PO terhapus.\n4. Status Barang (PR Item) di RF akan dikembalikan menjadi \'Requested\'.\n\nApakah Anda sangat yakin ingin melanjutkan?');">
                              @csrf
                              @method('DELETE')
                              <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete PO">
                                <i class="bx bx-trash"></i>
                              </button>
                            </form>
                          @endif
                        </div>
                      </div>
                    @endforeach
                    <a href="{{ route('erp.purchase-orders.create', $rf) }}" class="btn btn-xs btn-primary w-100 mt-1">
                      + Create Another PO
                    </a>
                  </div>
                @else
                  <div class="d-flex flex-column align-items-center justify-content-center gap-2 p-2 border rounded bg-light border-dashed">
                    <span class="badge bg-label-warning">Pending PO</span>
                    <a href="{{ route('erp.purchase-orders.create', $rf) }}" class="btn btn-sm btn-primary w-100">
                      Create PO
                    </a>
                  </div>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No records to display</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
