@extends('layouts.home')

@section('title', 'Units of Measure (UOM)')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- TOP BAR --}}
  <div class="card mb-3">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <label class="form-label mb-0 small text-uppercase fw-bold text-muted me-1">SHOW</label>
        <select id="pageLength" class="form-select form-select-sm" style="width:80px;">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span class="text-muted small ms-2">entries</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <div class="input-group input-group-sm">
          <span class="input-group-text"><i class="bx bx-search"></i></span>
          <input type="text" id="dtSearch" class="form-control form-control-sm" placeholder="Search UOM...">
        </div>
        @if(auth()->user()->hasPermission('products.create'))
          <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalCreate">
            <i class="bx bx-plus me-1"></i> Add UOM
          </button>
        @endif
      </div>
    </div>
  </div>

  {{-- TABLE CARD --}}
  <div class="card">
    <div class="table-responsive">
      <table id="dtUoms" class="table table-hover align-middle mb-0 w-100">
        <thead class="table-light">
          <tr>
            <th style="width:8%;" class="text-uppercase fw-bold small">NO</th>
            <th class="text-uppercase fw-bold small">UOM NAME</th>
            <th class="text-uppercase fw-bold small">DESCRIPTION</th>
            <th style="width:15%;" class="text-uppercase fw-bold small text-center">ACTIONS</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

{{-- CREATE MODAL --}}
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formCreate" class="modal-content" method="POST" action="{{ route('erp.uoms.store') }}">
      @csrf
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold"><i class="bx bx-plus-circle me-2 text-primary"></i>Add UOM</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">UOM Name <span class="text-danger">*</span></label>
          <input name="uom_name" class="form-control" required placeholder="e.g. BOX, PCS, METER, KG">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Description</label>
          <textarea name="description" rows="2" class="form-control" placeholder="Optional description..."></textarea>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-primary" type="submit" id="btnCreateUom"><i class="bx bx-save me-1"></i>Save</button>
      </div>
    </form>
  </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formEdit" class="modal-content" method="POST">
      @csrf
      <input type="hidden" name="_method" value="PUT">
      <input type="hidden" name="edit_id" id="edit_id">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold"><i class="bx bx-edit-alt me-2 text-warning"></i>Edit UOM</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">UOM Name <span class="text-danger">*</span></label>
          <input name="uom_name" id="edit_uom_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Description</label>
          <textarea name="description" id="edit_description" rows="2" class="form-control"></textarea>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-warning text-white" type="submit" id="btnEditUom"><i class="bx bx-save me-1"></i>Update</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
$(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    const DT = $('#dtUoms').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: "{{ route('erp.uoms.datatable') }}",
            type: "POST",
            headers: { 'X-CSRF-TOKEN': CSRF }
        },
        columns: [
            { data: 'rownum',    name: 'id',       className: 'align-middle fw-semibold', orderable: false },
            { data: 'uom_name', name: 'uom_name', className: 'align-middle fw-bold' },
            { data: 'description', name: 'description', className: 'align-middle text-muted' },
            { data: 'actions',  orderable: false,  searchable: false, className: 'text-center align-middle' },
        ],
        dom: 'tip',
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' }
    });

    // page length
    $('#pageLength').on('change', function () { DT.page.len(+this.value).draw(); });

    // search
    $('#dtSearch').on('keyup change', function () { DT.search(this.value).draw(); });

    const toast = (msg, icon='success') =>
        Swal.fire({ icon, title: msg, timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });

    // ---- CREATE ----
    $('#formCreate').on('submit', async function (e) {
        e.preventDefault();
        const btn = $('#btnCreateUom');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        try {
            const res = await fetch(this.action, { method: 'POST', body: new FormData(this), headers: { 'X-CSRF-TOKEN': CSRF } });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message || Object.values(json.errors||{}).flat().join('\n'));
            bootstrap.Modal.getInstance($('#modalCreate')[0]).hide();
            this.reset();
            DT.ajax.reload(null, false);
            toast('UOM created successfully!');
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        } finally {
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save');
        }
    });

    // ---- EDIT open ----
    window.openEdit = function (id, name, desc) {
        $('#edit_id').val(id);
        $('#edit_uom_name').val(name);
        $('#edit_description').val(desc || '');
        $('#formEdit').attr('action', "{{ route('erp.uoms.update', ':id') }}".replace(':id', id));
        new bootstrap.Modal($('#modalEdit')[0]).show();
    };

    // ---- EDIT submit ----
    $('#formEdit').on('submit', async function (e) {
        e.preventDefault();
        const btn = $('#btnEditUom');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        try {
            const res = await fetch(this.action, { method: 'POST', body: new FormData(this), headers: { 'X-CSRF-TOKEN': CSRF } });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message || Object.values(json.errors||{}).flat().join('\n'));
            bootstrap.Modal.getInstance($('#modalEdit')[0]).hide();
            DT.ajax.reload(null, false);
            toast('UOM updated successfully!');
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        } finally {
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Update');
        }
    });

    // ---- DELETE ----
    window.deleteItem = async function (id) {
        const ask = await Swal.fire({
            icon: 'warning', title: 'Delete UOM?',
            text: 'This action cannot be undone.',
            showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel'
        });
        if (!ask.isConfirmed) return;
        try {
            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('_method', 'DELETE');
            const res = await fetch("{{ route('erp.uoms.destroy', ':id') }}".replace(':id', id), { method: 'POST', body: fd });
            if (!res.ok) throw new Error('Delete failed');
            DT.ajax.reload(null, false);
            toast('UOM deleted', 'success');
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        }
    };
});
</script>
@endpush
@endsection
