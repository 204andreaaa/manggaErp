@extends('layouts.home')

@section('title', 'Budget Parents')

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
          <input type="text" id="dtSearch" class="form-control form-control-sm" placeholder="Search Budget...">
        </div>
        @if(auth()->user()->hasPermission('budgets.create'))
          <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalCreate">
            <i class="bx bx-plus me-1"></i> Add Budget Parent
          </button>
        @endif
      </div>
    </div>
  </div>

  {{-- TABLE CARD --}}
  <div class="card">
    <div class="table-responsive">
      <table id="dtBudgets" class="table table-hover align-middle mb-0 w-100">
        <thead class="table-light">
          <tr>
            <th style="width:8%;" class="text-uppercase fw-bold small">NO</th>
            <th style="width:12%;" class="text-uppercase fw-bold small">CODE</th>
            <th class="text-uppercase fw-bold small">BUDGET NAME</th>
            <th class="text-uppercase fw-bold small text-end">TOTAL BUDGET</th>
            <th class="text-uppercase fw-bold small text-end">REMAINING BUDGET</th>
            <th style="width:10%;" class="text-uppercase fw-bold small text-center">STATUS</th>
            <th style="width:12%;" class="text-uppercase fw-bold small text-center">ACTIONS</th>
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
    <form id="formCreate" class="modal-content" method="POST" action="{{ route('erp.budget-parents.store') }}">
      @csrf
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold"><i class="bx bx-plus-circle me-2 text-primary"></i>Add Budget Parent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Budget Code <span class="text-danger">*</span></label>
          <input name="budget_code" class="form-control" required placeholder="e.g. BDG-2026-01" maxlength="50">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Budget Name <span class="text-danger">*</span></label>
          <input name="name" class="form-control" required placeholder="e.g. Budget IT 2026">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Total Budget <span class="text-danger">*</span></label>
          <input type="number" name="total_budget" class="form-control" required min="0" step="0.01">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
          <select name="status" class="form-select" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-primary" type="submit" id="btnCreate"><i class="bx bx-save me-1"></i>Save</button>
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
        <h5 class="modal-title fw-bold"><i class="bx bx-edit-alt me-2 text-warning"></i>Edit Budget Parent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Budget Code <span class="text-danger">*</span></label>
          <input name="budget_code" id="edit_budget_code" class="form-control" required maxlength="50">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Budget Name <span class="text-danger">*</span></label>
          <input name="name" id="edit_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Total Budget <span class="text-danger">*</span></label>
          <input type="number" name="total_budget" id="edit_total_budget" class="form-control" required min="0" step="0.01">
          <div class="form-text text-warning">Changing this will adjust the remaining budget accordingly.</div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
          <select name="status" id="edit_status" class="form-select" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-warning text-white" type="submit" id="btnEdit"><i class="bx bx-save me-1"></i>Update</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
$(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    const DT = $('#dtBudgets').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: "{{ route('erp.budget-parents.datatable') }}",
            type: "POST",
            headers: { 'X-CSRF-TOKEN': CSRF }
        },
        columns: [
            { data: 'rownum',           name: 'id',               className: 'align-middle fw-semibold', orderable: false },
            { data: 'budget_code',      name: 'budget_code',      className: 'align-middle fw-bold' },
            { data: 'name',             name: 'name',             className: 'align-middle' },
            { data: 'total_budget',     name: 'total_budget',     className: 'align-middle text-end text-primary fw-semibold' },
            { data: 'remaining_budget', name: 'remaining_budget', className: 'align-middle text-end text-success fw-semibold' },
            { data: 'status',           name: 'status',           className: 'align-middle text-center' },
            { data: 'actions',          orderable: false, searchable: false, className: 'text-center align-middle' },
        ],
        dom: 'tip',
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' }
    });

    $('#pageLength').on('change', function () { DT.page.len(+this.value).draw(); });
    $('#dtSearch').on('keyup change', function () { DT.search(this.value).draw(); });

    const toast = (msg, icon='success') =>
        Swal.fire({ icon, title: msg, timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });

    $('#formCreate').on('submit', async function (e) {
        e.preventDefault();
        const btn = $('#btnCreate');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        try {
            const res = await fetch(this.action, { method: 'POST', body: new FormData(this), headers: { 'X-CSRF-TOKEN': CSRF } });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message || Object.values(json.errors||{}).flat().join('\n'));
            bootstrap.Modal.getInstance($('#modalCreate')[0]).hide();
            this.reset();
            DT.ajax.reload(null, false);
            toast('Budget Parent created successfully!');
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        } finally {
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save');
        }
    });

    window.openEdit = function (id, code, name, total_budget, status) {
        $('#edit_id').val(id);
        $('#edit_budget_code').val(code);
        $('#edit_name').val(name);
        $('#edit_total_budget').val(total_budget);
        $('#edit_status').val(status);
        $('#formEdit').attr('action', "{{ route('erp.budget-parents.update', ':id') }}".replace(':id', id));
        new bootstrap.Modal($('#modalEdit')[0]).show();
    };

    $('#formEdit').on('submit', async function (e) {
        e.preventDefault();
        const btn = $('#btnEdit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        try {
            const res = await fetch(this.action, { method: 'POST', body: new FormData(this), headers: { 'X-CSRF-TOKEN': CSRF } });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message || Object.values(json.errors||{}).flat().join('\n'));
            bootstrap.Modal.getInstance($('#modalEdit')[0]).hide();
            DT.ajax.reload(null, false);
            toast('Budget Parent updated successfully!');
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        } finally {
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Update');
        }
    });

    window.deleteItem = async function (id) {
        const ask = await Swal.fire({
            icon: 'warning', title: 'Delete Budget?',
            text: 'This action cannot be undone.',
            showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel'
        });
        if (!ask.isConfirmed) return;
        try {
            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('_method', 'DELETE');
            const res = await fetch("{{ route('erp.budget-parents.destroy', ':id') }}".replace(':id', id), { method: 'POST', body: fd });
            if (!res.ok) throw new Error('Delete failed');
            DT.ajax.reload(null, false);
            toast('Budget Parent deleted', 'success');
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        }
    };
});
</script>
@endpush
@endsection
