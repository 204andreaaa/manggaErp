@extends('layouts.home')

@section('title', 'ERP Warehouses (Destinations)')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .swal2-container{ z-index:20000 !important; }
    #tblWarehouses{
        table-layout: fixed;
        width: 100% !important;
    }
    #tblWarehouses thead th{
        font-size: .70rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    #tblWarehouses tbody td{
        font-size: .82rem;
        word-break: break-word;
        overflow-wrap: anywhere;
        vertical-align: middle;
    }
    #tblWarehouses td, #tblWarehouses th{
        padding: .55rem .75rem;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0 fw-bold">ERP Warehouses (Destinations)</h4>
        @if(auth()->user()->hasPermission('warehouse.create'))
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#glassWarehouse" id="btnShowAdd">
                <i class="bx bx-plus"></i> Add ERP Warehouse
            </button>
        @endif
    </div>

    {{-- Toolbar --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted small mb-0">Show</label>
                    <select id="pageLength" class="form-select form-select-sm" style="width:90px">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" id="dtSearch" class="form-control" placeholder="Search warehouses...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Add/Edit --}}
    <div class="modal fade" id="glassWarehouse" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 bg-white">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add ERP Warehouse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formWarehouse" class="modal-body" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="method" value="POST">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Warehouse Code <span class="text-danger">*</span></label>
                            <input name="warehouse_code" id="warehouse_code" class="form-control" required placeholder="WH001">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Warehouse Name <span class="text-danger">*</span></label>
                            <input name="name" id="name" class="form-control" required placeholder="e.g. Fatmawati Blok C16-17">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Type</label>
                            <input name="type" id="type" class="form-control" placeholder="e.g. Main, Sub">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input name="phone" id="phone" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fax</label>
                            <input name="fax" id="fax" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Last Stock Take Date</label>
                            <input type="date" name="last_stock_take_date" id="last_stock_take_date" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Work</label>
                            <input name="work" id="work" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Latitude</label>
                            <input name="latitude" id="latitude" class="form-control" placeholder="e.g. 6°10.5' LS">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Longitude</label>
                            <input name="longitude" id="longitude" class="form-control" placeholder="e.g. 106°49.7' BT">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Capacity</label>
                            <input type="number" name="capacity" id="capacity" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Total Value (IDR)</label>
                            <input type="number" step="0.01" name="total_value" id="total_value" class="form-control" value="0">
                        </div>

                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                                <label class="form-check-label fw-semibold" for="is_active">Active Warehouse</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Billing Address</label>
                            <textarea name="address" id="address" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Remark</label>
                            <textarea name="remark" id="remark" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary text-white" id="btnSubmit">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="table-responsive">
            <table id="tblWarehouses" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">NO</th>
                        <th style="width:130px">WAREHOUSE CODE</th>
                        <th style="width:250px">WAREHOUSE NAME</th>
                        <th>ADDRESS</th>
                        <th style="width:150px">PHONE</th>
                        <th style="width:100px">STATUS</th>
                        <th style="width:100px" class="text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    const baseUrl     = @json(url('erp/warehouses'));
    const nextCodeUrl = @json(route('erp.warehouses.next_code'));

    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    $.fn.dataTable.ext.errMode = 'none';

    const table = $('#tblWarehouses').DataTable({
        processing: true,
        serverSide: true,
        lengthChange: false,
        dom: 'rtip',
        ajax: { url: baseUrl + '/datatable', type: 'GET' },
        order: [[1, 'asc']],
        columns: [
            { data: 'rownum', orderable: false, searchable: false },
            { data: 'warehouse_code' },
            { data: 'name' },
            { data: 'address' },
            { data: 'phone' },
            { data: 'is_active', className: 'text-center' },
            { data: 'actions', orderable: false, searchable: false, className:'text-end' }
        ]
    });

    $('#tblWarehouses').on('error.dt', function(){
        Swal.fire({ icon:'error', title:'Server error', text:'Check system logs' });
    });

    $('#pageLength').on('change', function(){
        table.page.len(parseInt(this.value||10,10)).draw();
    });

    $('#dtSearch').on('keyup change', function(){
        table.search(this.value).draw();
    });

    $('#warehouse_code').on('input', function(){ this.value = this.value.toUpperCase(); });

    $('#btnShowAdd').on('click', function () {
        $('#modalTitle').text('Add ERP Warehouse');
        $('#formWarehouse').attr('action', baseUrl);
        $('#method').val('POST');
        $('#btnSubmit').text('Submit');
        $('#formWarehouse input:not([type="hidden"]), #formWarehouse textarea').val('');
        $('#is_active').prop('checked', true);

        $.get(nextCodeUrl, function(res){
            $('#warehouse_code').val(res?.next_code || '');
        });
    });

    // Submit (Add/Edit)
    $('#formWarehouse').on('submit', function (e) {
        e.preventDefault();
        const btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function (res) {
                $('#glassWarehouse').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Success', text: res.success, timer: 1500, showConfirmButton: false });
            },
            error: function (xhr) {
                let msg = 'Failed to save warehouse.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            },
            complete: function () {
                btn.prop('disabled', false).text($('#method').val() === 'POST' ? 'Submit' : 'Update');
            }
        });
    });

    // Edit Modal trigger
    $('#tblWarehouses').on('click', '.js-edit', function () {
        const d = $(this).data();
        $('#modalTitle').text('Edit ERP Warehouse: ' + d.name);
        $('#formWarehouse').attr('action', baseUrl + '/' + d.id);
        $('#method').val('PUT');
        $('#btnSubmit').text('Update');

        $('#warehouse_code').val(d.warehouse_code);
        $('#name').val(d.name);
        $('#type').val(d.type);
        $('#address').val(d.address);
        $('#phone').val(d.phone);
        $('#fax').val(d.fax);
        $('#last_stock_take_date').val(d.last_stock_take_date);
        $('#work').val(d.work);
        $('#is_active').prop('checked', d.is_active === 1);
        $('#latitude').val(d.latitude);
        $('#longitude').val(d.longitude);
        $('#capacity').val(d.capacity);
        $('#total_value').val(d.total_value);
        $('#remark').val(d.remark);

        $('#glassWarehouse').modal('show');
    });

    // Delete
    $('#tblWarehouses').on('click', '.js-del', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'You will delete this ERP Warehouse!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: baseUrl + '/' + id,
                    type: 'DELETE',
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire({ icon: 'success', title: 'Deleted', text: res.success, timer: 1500, showConfirmButton: false });
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete.' });
                    }
                });
            }
        });
    });
});
</script>
@endpush
