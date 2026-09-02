@extends('layouts.home')

@section('title', 'Work Items')

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
          <input type="text" id="dtSearch" class="form-control form-control-sm" placeholder="Search WID...">
        </div>
        @if(auth()->user()->hasPermission('budgets.create'))
          <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalCreate">
            <i class="bx bx-plus me-1"></i> Add Work Item (WID)
          </button>
        @endif
      </div>
    </div>
  </div>

  {{-- TABLE CARD --}}
  <div class="card">
    <div class="table-responsive">
      <table id="dtWorkItems" class="table table-hover align-middle mb-0 w-100">
        <thead class="table-light">
          <tr>
            <th style="width:8%;" class="text-uppercase fw-bold small">NO</th>
            <th class="text-uppercase fw-bold small">SUB PROJECT</th>
            <th style="width:12%;" class="text-uppercase fw-bold small">WID CODE</th>
            <th class="text-uppercase fw-bold small">WID NAME</th>
            <th class="text-uppercase fw-bold small text-end">ALLOCATED</th>
            <th class="text-uppercase fw-bold small text-end">REMAINING</th>
            <th style="width:10%;" class="text-uppercase fw-bold small text-center">ACTIONS</th>
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
    <form id="formCreate" class="modal-content" method="POST" action="{{ route('erp.work-items.store') }}">
      @csrf
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold"><i class="bx bx-plus-circle me-2 text-primary"></i>Add Work Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Sub Project <span class="text-danger">*</span></label>
          <select name="sub_project_id" id="create_sub_project_id" class="form-select" required>
            <option value="">-- Select Sub Project --</option>
            @foreach($subProjects as $sp)
              <option value="{{ $sp->id }}" data-available="{{ $sp->available_budget }}" data-parent="{{ $sp->budgetParent->name ?? '' }} ({{ $sp->budgetParent->budget_code ?? '' }})">
                {{ $sp->budgetParent->budget_code ?? '' }} | {{ $sp->sub_project_code }} - {{ $sp->name }}
              </option>
            @endforeach
          </select>
          <div id="create_budget_hint" class="alert alert-info py-2 px-3 mt-2 d-none small">
            <i class="bx bx-info-circle me-1"></i> <span id="create_budget_hint_text"></span>
          </div>
        </div>
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label fw-semibold mb-0">WID Code <span class="text-danger">*</span></label>
            <span class="badge bg-label-primary px-2 py-0" style="font-size: 11px;"><i class="bx bx-lock-alt me-1"></i>Otomatis (Terkunci)</span>
          </div>
          <input name="wid_code" id="create_wid_code" class="form-control bg-light fw-bold text-primary" required placeholder="Pilih Sub Project terlebih dahulu..." maxlength="50" readonly style="cursor: not-allowed;">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Work Item Name <span class="text-danger">*</span></label>
          <input name="name" class="form-control" required placeholder="e.g. Instalasi Kabel & Perangkat">
        </div>
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label fw-semibold mb-0">Allocated Budget (IDR) <span class="text-danger">*</span></label>
            <button type="button" class="btn btn-xs btn-outline-info py-0 px-2 rounded-pill d-none" id="btnMaxBudgetCreate" style="font-size: 11px;">
              <i class="bx bx-bolt-circle me-1"></i>Gunakan Sisa Maksimal
            </button>
          </div>
          <input type="number" name="allocated_budget" id="create_allocated_budget" class="form-control" required min="0" step="0.01" placeholder="e.g. 50000000">
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
        <h5 class="modal-title fw-bold"><i class="bx bx-edit-alt me-2 text-warning"></i>Edit Work Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Sub Project <span class="text-danger">*</span></label>
          <select name="sub_project_id" id="edit_sub_project_id" class="form-select" required>
            @foreach($subProjects as $sp)
              <option value="{{ $sp->id }}" data-available="{{ $sp->available_budget }}" data-parent="{{ $sp->budgetParent->name ?? '' }} ({{ $sp->budgetParent->budget_code ?? '' }})">
                {{ $sp->budgetParent->budget_code ?? '' }} | {{ $sp->sub_project_code }} - {{ $sp->name }}
              </option>
            @endforeach
          </select>
          <div id="edit_budget_hint" class="alert alert-info py-2 px-3 mt-2 d-none small">
            <i class="bx bx-info-circle me-1"></i> <span id="edit_budget_hint_text"></span>
          </div>
        </div>
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label fw-semibold mb-0">WID Code <span class="text-danger">*</span></label>
            <span class="badge bg-label-secondary px-2 py-0" style="font-size: 11px;"><i class="bx bx-lock-alt me-1"></i>Terkunci</span>
          </div>
          <input name="wid_code" id="edit_wid_code" class="form-control bg-light fw-bold text-primary" required maxlength="50" readonly style="cursor: not-allowed;">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Work Item Name <span class="text-danger">*</span></label>
          <input name="name" id="edit_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label fw-semibold mb-0">Allocated Budget (IDR) <span class="text-danger">*</span></label>
            <button type="button" class="btn btn-xs btn-outline-info py-0 px-2 rounded-pill d-none" id="btnMaxBudgetEdit" style="font-size: 11px;">
              <i class="bx bx-bolt-circle me-1"></i>Gunakan Sisa Maksimal
            </button>
          </div>
          <input type="number" name="allocated_budget" id="edit_allocated_budget" class="form-control" required min="0" step="0.01">
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

    const DT = $('#dtWorkItems').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: "{{ route('erp.work-items.datatable') }}",
            type: "POST",
            headers: { 'X-CSRF-TOKEN': CSRF }
        },
        columns: [
            { data: 'rownum',             name: 'id',                 className: 'align-middle fw-semibold', orderable: false },
            { data: 'sub_project',        name: 'subProject.name',    className: 'align-middle' },
            { data: 'wid_code',           name: 'wid_code',           className: 'align-middle fw-bold' },
            { data: 'name',               name: 'name',               className: 'align-middle' },
            { data: 'allocated_budget',   name: 'allocated_budget',   className: 'align-middle text-end text-primary fw-semibold' },
            { data: 'remaining_budget',   name: 'remaining_budget',   className: 'align-middle text-end text-success fw-semibold' },
            { data: 'actions',            orderable: false, searchable: false, className: 'text-center align-middle' },
        ],
        dom: 'tip',
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' }
    });

    $('#pageLength').on('change', function () { DT.page.len(+this.value).draw(); });
    $('#dtSearch').on('keyup change', function () { DT.search(this.value).draw(); });

    const toast = (msg, icon='success') =>
        Swal.fire({ icon, title: msg, timer: 2000, showConfirmButton: false, toast: true, position: 'top-end' });

    function updateBudgetHint(selectEl, hintContainer, hintTextEl, maxBtn) {
        const opt = $(selectEl).find('option:selected');
        const available = parseFloat(opt.data('available') || 0);
        const parentName = opt.data('parent') || '';

        if (opt.val() && parentName) {
            const formatted = 'Rp ' + new Intl.NumberFormat('id-ID').format(available);
            hintTextEl.html(`Pagu Induk: <strong>${parentName}</strong><br>Sisa Anggaran Tersedia: <strong class="text-success">${formatted}</strong>`);
            hintContainer.removeClass('d-none');
            if (maxBtn) {
                maxBtn.removeClass('d-none').data('available', available);
            }
        } else {
            hintContainer.addClass('d-none');
            if (maxBtn) {
                maxBtn.addClass('d-none');
            }
        }
    }

    $('#create_sub_project_id').on('change', function() {
        const spId = $(this).val();
        updateBudgetHint(this, $('#create_budget_hint'), $('#create_budget_hint_text'), $('#btnMaxBudgetCreate'));
        
        if (spId) {
            fetch("{{ route('erp.work-items.next-code') }}?sub_project_id=" + spId)
                .then(r => r.json())
                .then(data => {
                    if (data.wid_code) {
                        $('#create_wid_code').val(data.wid_code);
                    }
                });
        } else {
            $('#create_wid_code').val('');
        }
    });

    $('#edit_sub_project_id').on('change', function() {
        updateBudgetHint(this, $('#edit_budget_hint'), $('#edit_budget_hint_text'), $('#btnMaxBudgetEdit'));
    });

    // Auto-Cap Logic if input exceeds available budget
    function handleBudgetAutoCap(inputEl, selectEl) {
        const opt = $(selectEl).find('option:selected');
        const available = parseFloat(opt.data('available') || 0);
        if (!opt.val() || available <= 0) return;

        const currentVal = parseFloat($(inputEl).val() || 0);
        if (currentVal > available) {
            $(inputEl).val(available);
            const formatted = 'Rp ' + new Intl.NumberFormat('id-ID').format(available);
            toast('Nominal disesuaikan otomatis ke batas maksimal sisa pagu (' + formatted + ')', 'warning');
        }
    }

    $('#create_allocated_budget').on('input change blur', function() {
        handleBudgetAutoCap(this, $('#create_sub_project_id'));
    });

    $('#edit_allocated_budget').on('input change blur', function() {
        handleBudgetAutoCap(this, $('#edit_sub_project_id'));
    });

    // Max Budget Shortcut Buttons
    $('#btnMaxBudgetCreate').on('click', function() {
        const available = $(this).data('available') || 0;
        $('#create_allocated_budget').val(available).trigger('change');
    });

    $('#btnMaxBudgetEdit').on('click', function() {
        const available = $(this).data('available') || 0;
        $('#edit_allocated_budget').val(available).trigger('change');
    });

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
            $('#create_budget_hint').addClass('d-none');
            DT.ajax.reload(null, false);
            toast('Work Item created successfully!');
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan WID',
                html: `<div class="text-start" style="white-space: pre-line; font-size: 14px;">${err.message}</div>`,
                confirmButtonColor: '#696cff'
            });
        } finally {
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save');
        }
    });

    window.openEdit = function (id, sub_project_id, wid_code, name, allocated_budget) {
        $('#edit_id').val(id);
        $('#edit_sub_project_id').val(sub_project_id).trigger('change');
        $('#edit_wid_code').val(wid_code);
        $('#edit_name').val(name);
        $('#edit_allocated_budget').val(allocated_budget);
        $('#formEdit').attr('action', "{{ route('erp.work-items.update', ':id') }}".replace(':id', id));
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
            toast('Work Item updated successfully!');
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memperbarui WID',
                html: `<div class="text-start" style="white-space: pre-line; font-size: 14px;">${err.message}</div>`,
                confirmButtonColor: '#696cff'
            });
        } finally {
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Update');
        }
    });

    window.deleteItem = async function (id) {
        const ask = await Swal.fire({
            icon: 'warning', title: 'Delete Work Item?',
            text: 'This action cannot be undone.',
            showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel'
        });
        if (!ask.isConfirmed) return;
        try {
            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('_method', 'DELETE');
            const res = await fetch("{{ route('erp.work-items.destroy', ':id') }}".replace(':id', id), { method: 'POST', body: fd });
            if (!res.ok) throw new Error('Delete failed');
            DT.ajax.reload(null, false);
            toast('Work Item deleted', 'success');
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message });
        }
    };

    // Initialize Select2
    $('#create_sub_project_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalCreate')
    });
    $('#edit_sub_project_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalEdit')
    });
});
</script>
@endpush
@endsection
