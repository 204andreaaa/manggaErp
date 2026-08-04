@extends('layouts.home')

@section('title', 'ERP Suppliers')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .swal2-container{ z-index:20000 !important; }
    #tblSuppliers{
        table-layout: fixed;
        width: 100% !important;
    }
    #tblSuppliers thead th{
        font-size: .70rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    #tblSuppliers tbody td{
        font-size: .82rem;
        word-break: break-word;
        overflow-wrap: anywhere;
        vertical-align: middle;
    }
    #tblSuppliers td, #tblSuppliers th{
        padding: .55rem .75rem;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0 fw-bold">ERP Suppliers</h4>
        @if(auth()->user()->hasPermission('supplier.create'))
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#glassSupplier" id="btnShowAdd">
                <i class="bx bx-plus"></i> Add ERP Supplier
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
                        <input type="text" id="dtSearch" class="form-control" placeholder="Search suppliers...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Add/Edit --}}
    <div class="modal fade" id="glassSupplier" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 bg-white">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add ERP Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formSupplier" class="modal-body" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="method" value="POST">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier Code <span class="text-danger">*</span></label>
                            <input name="supplier_code" id="supplier_code" class="form-control" required placeholder="SUP-001">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                            <input name="name" id="name" class="form-control" required placeholder="Enter company name">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Parent Account</label>
                            <input name="parent_account" id="parent_account" class="form-control" placeholder="Enter parent account">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Classification</label>
                            <select name="classification" id="classification" class="form-select">
                                <option value="Unclassified">Unclassified</option>
                                <option value="VIP">VIP</option>
                                <option value="Regular">Regular</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Industry</label>
                            <input name="industry" id="industry" class="form-control" placeholder="e.g. Telecommunications">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <input name="category" id="category" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Products Provided</label>
                            <input name="products_provided" id="products_provided" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Services Provided</label>
                            <input name="services_provided" id="services_provided" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Products</label>
                            <input name="products" id="products" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Terms</label>
                            <select name="payment_terms_id" id="payment_terms_id" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($paymentTerms as $term)
                                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bank Name</label>
                            <input name="bank_name" id="bank_name" class="form-control" placeholder="e.g. BCA">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bank Account</label>
                            <input name="bank_account" id="bank_account" class="form-control" placeholder="Account No & Owner">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Billing Address</label>
                            <input name="address" id="address" class="form-control" placeholder="Enter address">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input name="phone" id="phone" class="form-control" placeholder="Enter phone">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fax</label>
                            <input name="fax" id="fax" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Website</label>
                            <input name="website" id="website" class="form-control" placeholder="https://example.com">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Note / Remark</label>
                            <textarea name="note" id="note" class="form-control" rows="2"></textarea>
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
            <table id="tblSuppliers" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">NO</th>
                        <th style="width:130px">SUPPLIER CODE</th>
                        <th style="width:200px">COMPANY NAME</th>
                        <th>ADDRESS</th>
                        <th style="width:130px">PHONE</th>
                        <th>NOTE</th>
                        <th style="width:120px">BANK NAME</th>
                        <th style="width:150px">BANK ACCOUNT</th>
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
    const baseUrl     = @json(url('erp/suppliers'));
    const nextCodeUrl = @json(route('erp.suppliers.next_code'));

    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    $.fn.dataTable.ext.errMode = 'none';

    const table = $('#tblSuppliers').DataTable({
        processing: true,
        serverSide: true,
        lengthChange: false,
        dom: 'rtip',
        ajax: { url: baseUrl + '/datatable', type: 'GET' },
        order: [[1, 'asc']],
        columns: [
            { data: 'rownum', orderable: false, searchable: false },
            { data: 'supplier_code' },
            { data: 'name' },
            { data: 'address' },
            { data: 'phone' },
            { data: 'note' },
            { data: 'bank_name' },
            { data: 'bank_account' },
            { data: 'actions', orderable: false, searchable: false, className:'text-end' }
        ]
    });

    $('#tblSuppliers').on('error.dt', function(){
        Swal.fire({ icon:'error', title:'Server error', text:'Check system logs' });
    });

    $('#pageLength').on('change', function(){
        table.page.len(parseInt(this.value||10,10)).draw();
    });

    $('#dtSearch').on('keyup change', function(){
        table.search(this.value).draw();
    });

    $('#supplier_code').on('input', function(){ this.value = this.value.toUpperCase(); });

    $('#btnShowAdd').on('click', function () {
        $('#modalTitle').text('Add ERP Supplier');
        $('#formSupplier').attr('action', baseUrl);
        $('#method').val('POST');
        $('#btnSubmit').text('Submit');
        $('#formSupplier input:not([type="hidden"]), #formSupplier select, #formSupplier textarea').val('');
        $('#classification').val('Unclassified');

        $.get(nextCodeUrl, function(res){
            $('#supplier_code').val(res?.next_code || '');
        });
    });

    // Submit (Add/Edit)
    $('#formSupplier').on('submit', function (e) {
        e.preventDefault();
        const btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function (res) {
                $('#glassSupplier').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Success', text: res.success, timer: 1500, showConfirmButton: false });
            },
            error: function (xhr) {
                let msg = 'Failed to save supplier.';
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
    $('#tblSuppliers').on('click', '.js-edit', function () {
        const d = $(this).data();
        $('#modalTitle').text('Edit ERP Supplier: ' + d.name);
        $('#formSupplier').attr('action', baseUrl + '/' + d.id);
        $('#method').val('PUT');
        $('#btnSubmit').text('Update');

        $('#supplier_code').val(d.supplier_code);
        $('#name').val(d.name);
        $('#parent_account').val(d.parent_account);
        $('#classification').val(d.classification);
        $('#industry').val(d.industry);
        $('#products_provided').val(d.products_provided);
        $('#services_provided').val(d.services_provided);
        $('#category').val(d.category);
        $('#products').val(d.products);
        $('#payment_terms_id').val(d.payment_terms_id);
        $('#address').val(d.address);
        $('#phone').val(d.phone);
        $('#fax').val(d.fax);
        $('#website').val(d.website);
        $('#note').val(d.note);
        $('#bank_name').val(d.bank_name);
        $('#bank_account').val(d.bank_account);

        $('#glassSupplier').modal('show');
    });

    // Delete
    $('#tblSuppliers').on('click', '.js-del', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'You will delete this ERP Supplier!',
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
