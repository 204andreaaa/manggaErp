@extends('layouts.home')

@section('title', 'Request Form')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card mb-3">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Request Form</h5>
        <div class="text-muted small">List Request Form ERP</div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <div class="input-group input-group-sm">
          <span class="input-group-text"><i class="bx bx-search"></i></span>
          <input type="text" id="dtSearch" class="form-control form-control-sm" placeholder="Search RF...">
        </div>
        @if(auth()->user()->hasPermission('products.create'))
          <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalChooseType">
            <i class="bx bx-plus me-1"></i>Create RF
          </button>
        @endif
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body py-3 d-flex align-items-center gap-2">
      <label class="form-label mb-0 small text-uppercase fw-bold text-muted">Show</label>
      <select id="pageLength" class="form-select form-select-sm" style="width:80px;">
        <option value="10" selected>10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <span class="text-muted small">entries</span>
    </div>
    <div class="table-responsive">
      <table id="dtRequestForms" class="table table-hover align-middle mb-0 w-100">
        <thead class="table-light">
          <tr>
            <th class="text-uppercase fw-bold small" style="width:5%;">No</th>
            <th class="text-uppercase fw-bold small">RF No</th>
            <th class="text-uppercase fw-bold small">Record Type</th>
            <th class="text-uppercase fw-bold small">Project Code</th>
            <th class="text-uppercase fw-bold small">Requestor</th>
            <th class="text-uppercase fw-bold small">Date</th>
            <th class="text-uppercase fw-bold small text-center">Status</th>
            <th class="text-uppercase fw-bold small text-end">Total</th>
            <th class="text-uppercase fw-bold small text-center">Items</th>
            <th class="text-uppercase fw-bold small text-center" style="width:8%;">Action</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalChooseType" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">
          <i class="bx bx-file me-2 text-primary"></i>Pilih Type Request Form
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <a href="{{ route('erp.request-form.create', ['type' => 'project']) }}" class="btn btn-outline-primary w-100 text-start p-3">
              <div class="d-flex align-items-center gap-3">
                <i class="bx bx-briefcase fs-3"></i>
                <div>
                  <div class="fw-bold">Project Based</div>
                  <small class="text-muted">RF wajib memakai project code.</small>
                </div>
              </div>
            </a>
          </div>
          <div class="col-12">
            <a href="{{ route('erp.request-form.create', ['type' => 'non_project']) }}" class="btn btn-outline-secondary w-100 text-start p-3">
              <div class="d-flex align-items-center gap-3">
                <i class="bx bx-buildings fs-3"></i>
                <div>
                  <div class="fw-bold">Non Project Based</div>
                  <small class="text-muted">RF operasional tanpa project code.</small>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
$(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    const table = $('#dtRequestForms').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: "{{ route('erp.request-form.datatable') }}",
            type: "POST",
            headers: { 'X-CSRF-TOKEN': CSRF }
        },
        columns: [
            { data: 'rownum', orderable: false, className: 'align-middle fw-semibold' },
            { data: 'rf_no', name: 'rf_no', className: 'align-middle' },
            { data: 'record_type', name: 'record_type', className: 'align-middle' },
            { data: 'project_code', name: 'project_code', className: 'align-middle' },
            { data: 'requestor', name: 'requestor', className: 'align-middle' },
            { data: 'rf_date', name: 'rf_date', className: 'align-middle' },
            { data: 'status', name: 'status', className: 'align-middle text-center' },
            { data: 'total_amount', name: 'total_amount', className: 'align-middle text-end' },
            { data: 'items_count', orderable: false, className: 'align-middle text-center' },
            { data: 'actions', orderable: false, searchable: false, className: 'align-middle text-center' },
        ],
        dom: 'tip',
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' },
        order: [[1, 'desc']]
    });

    $('#pageLength').on('change', function () { table.page.len(+this.value).draw(); });
    $('#dtSearch').on('keyup change', function () { table.search(this.value).draw(); });
});
</script>
@endpush
@endsection
