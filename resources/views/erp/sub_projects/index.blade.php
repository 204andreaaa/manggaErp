@extends('layouts.home')

@section('title', 'Sub Projects')

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
        </select>
        <span class="text-muted small ms-2">entries</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <div class="input-group input-group-sm">
          <span class="input-group-text"><i class="bx bx-search"></i></span>
          <input type="text" id="dtSearch" class="form-control form-control-sm" placeholder="Search...">
        </div>
        @if(auth()->user()->hasPermission('budgets.create'))
          <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalCreate">
            <i class="bx bx-plus me-1"></i> Add Sub Project
          </button>
        @endif
      </div>
    </div>
  </div>

  {{-- TABLE CARD --}}
  <div class="card">
    <div class="table-responsive">
      <table id="dtSubProjects" class="table table-hover align-middle mb-0 w-100">
        <thead class="table-light">
          <tr>
            <th style="width:8%;" class="text-uppercase fw-bold small">NO</th>
            <th class="text-uppercase fw-bold small">BUDGET PARENT</th>
            <th style="width:15%;" class="text-uppercase fw-bold small">SUB PROJECT CODE</th>
            <th class="text-uppercase fw-bold small">SUB PROJECT NAME</th>
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
    <form id="formCreate" class="modal-content" method="POST" action="{{ route('erp.sub-projects.store') }}">
      @csrf
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold"><i class="bx bx-plus-circle me-2 text-primary"></i>Add Sub Project</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Budget Parent <span class="text-danger">*</span></label>
          <select name="budget_parent_id" class="form-select" required>
            <option value="">-- Select Budget Parent --</option>
            @foreach($budgetParents as $bp)
              <option value="{{ $bp->id }}">{{ $bp->budget_code }} - {{ $bp->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Sub Project Code <span class="text-danger">*</span></label>
          <input name="sub_project_code" class="form-control" required placeholder="e.g. SP-01" maxlength="50">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Sub Project Name <span class="text-danger">*</span></label>
          <input name="name" class="form-control" required placeholder="e.g. Database Setup">
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
        <h5 class="modal-title fw-bold"><i class="bx bx-edit-alt me-2 text-warning"></i>Edit Sub Project</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Budget Parent <span class="text-danger">*</span></label>
          <select name="budget_parent_id" id="edit_budget_parent_id" class="form-select" required>
            @foreach($budgetParents as $bp)
              <option value="{{ $bp->id }}">{{ $bp->budget_code }} - {{ $bp->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Sub Project Code <span class="text-danger">*</span></label>
          <input name="sub_project_code" id="edit_sub_project_code" class="form-control" required maxlength="50">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Sub Project Name <span class="text-danger">*</span></label>
          <input name="name" id="edit_name" class="form-control" required>
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

    const DT = $('#dtSubProjects').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: "{{ route('erp.sub-projects.datatable') }}",
            type: "POST",
            headers: { 'X-CSRF-TOKEN': CSRF }
        },
        columns: [
            { data: 'rownum',             name: 'id',                   className: 'align-middle fw-semibold', orderable: false },
            { data: 'budget_parent',      name: 'budgetParent.name',    className: 'align-middle' },
            { data: 'sub_project_code',   name: 'sub_project_code',     className: 'align-middle fw-bold' },
            { data: 'name',               name: 'name',                 className: 'align-middle' },
            { data: 'actions',            orderable: false, searchable: false, className: 'text-center align-middle' },
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
            toast('Sub Project created successfully!');
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        } finally {
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save');
        }
    });

    window.openEdit = function (id, budget_parent_id, sub_project_code, name) {
        $('#edit_id').val(id);
        $('#edit_budget_parent_id').val(budget_parent_id);
        $('#edit_sub_project_code').val(sub_project_code);
        $('#edit_name').val(name);
        $('#formEdit').attr('action', "{{ route('erp.sub-projects.update', ':id') }}".replace(':id', id));
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
            toast('Sub Project updated successfully!');
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        } finally {
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Update');
        }
    });

    window.deleteItem = async function (id) {
        const ask = await Swal.fire({
            icon: 'warning', title: 'Delete Sub Project?',
            text: 'This action cannot be undone.',
            showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel'
        });
        if (!ask.isConfirmed) return;
        try {
            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('_method', 'DELETE');
            const res = await fetch("{{ route('erp.sub-projects.destroy', ':id') }}".replace(':id', id), { method: 'POST', body: fd });
            if (!res.ok) throw new Error('Delete failed');
            DT.ajax.reload(null, false);
            toast('Sub Project deleted', 'success');
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        }
    };

    // Initialize Select2
    $('select[name="budget_parent_id"]').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalCreate')
    });
    $('#edit_budget_parent_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalEdit')
    });
});
</script>
@endpush
@endsection
