@extends('layouts.home')

@section('title', 'ERP Inventory Stocks')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <div class="text-muted small">Master Data ERP</div>
      <h4 class="mb-0 fw-bold d-flex align-items-center">
        <i class="bx bx-package text-primary me-2 fs-3"></i> Inventory Stock Levels
      </h4>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('erp.products.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bx bx-list-ul me-1"></i>Master Products
      </a>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold small text-uppercase text-muted">Filter Warehouse</label>
          <select id="filterWarehouse" class="form-select">
            <option value="">All Warehouses</option>
            @foreach($warehouses as $wh)
              <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold small text-uppercase text-muted">Item Category Type</label>
          <select id="filterItemType" class="form-select">
            <option value="">All Types</option>
            <option value="physical" selected>📦 Physical Products Only</option>
            <option value="non_physical">🔧 Non-Physical / Services Only</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold small text-uppercase text-muted">Search Product / Code</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" id="dtSearch" class="form-control" placeholder="Search product name, code...">
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between">
      <h6 class="mb-0 fw-bold"><i class="bx bx-list-check me-2"></i>Stock Balance Overview</h6>
      <div class="d-flex align-items-center gap-2">
        <label class="small text-muted me-1 mb-0">Show</label>
        <select id="pageLength" class="form-select form-select-sm w-auto">
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
    </div>

    <div class="table-responsive">
      <table id="dtStocks" class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;">#</th>
            <th class="text-uppercase fw-bold small">CODE</th>
            <th class="text-uppercase fw-bold small">PRODUCT NAME</th>
            <th class="text-uppercase fw-bold small">PART NO.</th>
            <th class="text-uppercase fw-bold small">TYPE</th>
            <th class="text-uppercase fw-bold small">WAREHOUSE</th>
            <th class="text-uppercase fw-bold small">QTY ON HAND</th>
            <th class="text-uppercase fw-bold small">STATUS</th>
            <th class="text-uppercase fw-bold small">LAST UPDATED</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

@push('scripts')
<script>
$(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    const DT = $('#dtStocks').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: "{{ route('erp.stocks.datatable') }}",
            type: "POST",
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: function (d) {
                d.warehouse_id = $('#filterWarehouse').val();
                d.item_type = $('#filterItemType').val();
                d.search = { value: $('#dtSearch').val() };
            }
        },
        columns: [
            { data: 'rownum',       className: 'align-middle fw-semibold', orderable: false },
            { data: 'product_code', className: 'align-middle fw-bold text-primary' },
            { data: 'product_name', className: 'align-middle fw-semibold' },
            { data: 'part_number',  className: 'align-middle text-muted small' },
            { data: 'item_type',    className: 'align-middle' },
            { data: 'warehouse',    className: 'align-middle fw-semibold' },
            { data: 'qty_on_hand',  className: 'align-middle fw-bold text-success' },
            { data: 'stock_status', className: 'align-middle' },
            { data: 'updated_at',   className: 'align-middle text-muted small' },
        ],
        dom: 'tip',
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' },
        order: [[6, 'desc']]
    });

    $('#pageLength').on('change', function () { DT.page.len(+this.value).draw(); });
    $('#filterWarehouse, #filterItemType').on('change', function () { DT.draw(); });
    $('#dtSearch').on('keyup change', function () { DT.draw(); });
});
</script>
@endpush
@endsection
