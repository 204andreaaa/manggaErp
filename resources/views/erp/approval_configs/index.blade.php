@extends('layouts.home')

@section('title', 'Approval Configuration')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
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

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(!empty($coverageGaps))
    <div class="alert alert-warning" role="alert">
      <div class="fw-bold mb-1"><i class="bx bx-error-circle me-1"></i>Rentang Nominal Belum Ter-cover</div>
      <div class="small mb-2">Dokumen dengan kombinasi berikut tidak akan menemukan approval flow saat disubmit (submit akan ditolak dengan error "Approval flow is not configured"):</div>
      <ul class="mb-0 small">
        @foreach($coverageGaps as $gap)
          <li>
            <span class="fw-semibold">{{ ['request_form' => 'Request Form', 'purchase_order' => 'Purchase Order', 'payment_advice' => 'Payment Advice'][$gap['record_type']] }}</span>
            @if(!is_null($gap['is_project']))
              ({{ $gap['is_project'] ? 'Project' : 'Non-Project' }})
            @endif
            — nominal {{ number_format($gap['from'], 0, ',', '.') }}
            {{ $gap['to'] !== null ? ('s/d ' . number_format($gap['to'], 0, ',', '.')) : 'ke atas' }}
          </li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1 fw-bold">Approval Configuration</h4>
      <div class="text-muted small">Manage ERP Approval Workflows</div>
    </div>
    <button class="btn btn-primary" onclick="openConfigModal('add')">
      <i class="bx bx-plus me-1"></i> Add Step
    </button>
  </div>

  <div class="row g-4">

    <!-- Request Form Configs -->
    <div class="col-12">
      <div class="card h-100">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold text-primary"><i class="bx bx-file me-2"></i>Request Form Approval Workflow</h6>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Level / Step</th>
                <th>Name</th>
                <th>Assigned To</th>
                <th>Conditions</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($rfConfigs as $config)
                @include('erp.approval_configs._row', ['config' => $config, 'badgeClass' => 'bg-label-primary'])
              @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No configuration found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Purchase Order Configs -->
    <div class="col-12">
      <div class="card h-100">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold text-success"><i class="bx bx-cart me-2"></i>Purchase Order Approval Workflow</h6>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Level / Step</th>
                <th>Name</th>
                <th>Assigned To</th>
                <th>Conditions</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($poConfigs as $config)
                @include('erp.approval_configs._row', ['config' => $config, 'badgeClass' => 'bg-label-success'])
              @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No configuration found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Payment Advice Configs -->
    <div class="col-12">
      <div class="card h-100">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold text-info"><i class="bx bx-receipt me-2"></i>Payment Advice Approval Workflow</h6>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Level / Step</th>
                <th>Name</th>
                <th>Assigned To</th>
                <th>Conditions</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($paConfigs as $config)
                @include('erp.approval_configs._row', ['config' => $config, 'badgeClass' => 'bg-label-info'])
              @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No configuration found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Document Verification Configs (PO & GR) -->
    <div class="col-12">
      <div class="card h-100 border border-primary border-opacity-25 shadow-sm">
        <div class="card-header border-bottom py-3 bg-primary bg-opacity-10 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold text-primary"><i class="bx bx-shield-quarter me-2"></i>Document Verification Workflows (PO & Goods Receipt)</h6>
          <span class="badge bg-primary">Operational Verifiers</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Workflow Module</th>
                <th>Verification Name</th>
                <th>Assigned Verifier</th>
                <th>Target Document</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              {{-- PO Verification --}}
              @forelse($poVerifConfigs as $config)
                <tr>
                  <td><span class="badge bg-label-warning"><i class="bx bx-cart me-1"></i>Procurement PO</span></td>
                  <td class="fw-bold text-dark">{{ $config->name }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-xs me-2">
                        <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-user"></i></span>
                      </div>
                      <div>
                        <span class="fw-bold text-dark">{{ $config->user->name ?? ($config->role->name ?? '-') }}</span>
                        <div class="text-muted small">{{ $config->user->email ?? ($config->role ? 'Role: '.$config->role->name : '') }}</div>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge bg-label-secondary">Draft Purchase Order (Before Approval)</span></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick='openConfigModal("edit", @json($config))'><i class="bx bx-edit-alt"></i></button>
                    <form action="{{ route('erp.approval-configs.destroy', $config) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus konfigurasi verifikator PO ini?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-3"><span class="badge bg-label-secondary me-2">Bypass Verification (Non-Aktif)</span> PO dapat langsung diajukan (Submit for Approval) tanpa verifikasi khusus. Tambahkan step di modal jika ingin mengaktifkan verifikasi PO.</td></tr>
              @endforelse

              {{-- GR Verification --}}
              @forelse($grVerifConfigs as $config)
                <tr>
                  <td><span class="badge bg-label-info"><i class="bx bx-package me-1"></i>Warehouse GR / DO</span></td>
                  <td class="fw-bold text-dark">{{ $config->name }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-xs me-2">
                        <span class="avatar-initial rounded-circle bg-label-info"><i class="bx bx-user"></i></span>
                      </div>
                      <div>
                        <span class="fw-bold text-dark">{{ $config->user->name ?? ($config->role->name ?? '-') }}</span>
                        <div class="text-muted small">{{ $config->user->email ?? ($config->role ? 'Role: '.$config->role->name : '') }}</div>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge bg-label-secondary">Physical Goods Receipt & QC Gudang</span></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick='openConfigModal("edit", @json($config))'><i class="bx bx-edit-alt"></i></button>
                    <form action="{{ route('erp.approval-configs.destroy', $config) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus konfigurasi verifikator GR ini?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-3"><span class="badge bg-label-secondary me-2">Standar Gudang / Logistik</span> Penerimaan barang fisik dapat diverifikasi oleh staf Gudang / Logistik / GA / Superadmin. Tambahkan step di modal jika ingin menunjuk verifikator khusus.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Modal Add/Edit Config -->
<div class="modal fade" id="addConfigModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="configForm" action="{{ route('erp.approval-configs.store') }}" method="POST">
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">
        <div class="modal-header bg-light">
          <h5 class="modal-title fw-bold" id="configModalTitle"><i class="bx bx-cog me-2"></i>Add Approval / Verification Configuration</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3" id="workflowTypeCol">
              <label class="form-label fw-bold">Workflow Type <span class="text-danger">*</span></label>
              <select name="record_type" class="form-select" required>
                <optgroup label="Multi-Level Approvals">
                  <option value="request_form">Request Form (RF)</option>
                  <option value="purchase_order">Purchase Order (PO)</option>
                  <option value="payment_advice">Payment Advice (PA)</option>
                </optgroup>
                <optgroup label="Document Verifications">
                  <option value="po_verification">🛒 PO Procurement Verifier (Verifikasi PO)</option>
                  <option value="gr_verification">📦 GR Physical QC Verifier (Verifikasi Fisik Gudang)</option>
                </optgroup>
              </select>
            </div>
            <div class="col-md-6 mb-3" id="levelStepCol">
              <label class="form-label fw-bold">Level / Step (Number) <span class="text-danger">*</span></label>
              <input type="number" name="level" class="form-control" min="1" required placeholder="e.g. 1">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Finance Approval">
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold">Assign To <span class="text-danger">*</span></label>
              <select name="assign_type" id="assignTypeSelect" class="form-select">
                <option value="user">Specific User</option>
                <option value="role">Role (anyone with this role)</option>
              </select>
            </div>
            <div class="col-md-8 mb-3" id="assignUserCol">
              <label class="form-label">User</label>
              <select name="user_id" id="userIdSelect" class="form-select">
                <option value="">-- Select User --</option>
                @foreach($users as $u)
                  <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-8 mb-3 d-none" id="assignRoleCol">
              <label class="form-label">Role</label>
              <select name="role_id" id="roleIdSelect" class="form-select">
                <option value="">-- Select Role --</option>
                @foreach($roles as $r)
                  <option value="{{ $r->id }}">{{ $r->name }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div id="conditionsSection">
            <hr>
            <h6 class="fw-bold">Conditions (Optional)</h6>

            <div class="row">
              <div class="col-md-4 mb-3" id="isProjectCol">
                <label class="form-label">Project Type</label>
                <select name="is_project" class="form-select">
                  <option value="">All (Both)</option>
                  <option value="1">Project Only</option>
                  <option value="0">Non-Project Only</option>
                </select>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Min Amount</label>
                <input type="number" step="0.01" name="min_amount" class="form-control" placeholder="0">
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Max Amount</label>
                <input type="number" step="0.01" name="max_amount" class="form-control" placeholder="No limit">
              </div>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="configSubmitBtn">Save Configuration</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const maxLevels = @json($maxLevels);
    const storeUrl = @json(route('erp.approval-configs.store'));
    const updateUrlTemplate = @json(route('erp.approval-configs.update', ['approval_config' => '__ID__']));

    const form = document.getElementById('configForm');
    const formMethod = document.getElementById('formMethod');
    const modalTitle = document.getElementById('configModalTitle');
    const submitBtn = document.getElementById('configSubmitBtn');

    const recordTypeSelect = document.querySelector('select[name="record_type"]');
    const levelInput = document.querySelector('input[name="level"]');
    const nameInput = document.querySelector('input[name="name"]');
    const workflowTypeCol = document.getElementById('workflowTypeCol');
    const levelStepCol = document.getElementById('levelStepCol');
    const conditionsSection = document.getElementById('conditionsSection');
    const isProjectCol = document.getElementById('isProjectCol');
    const addConfigModal = document.getElementById('addConfigModal');

    const assignTypeSelect = document.getElementById('assignTypeSelect');
    const assignUserCol = document.getElementById('assignUserCol');
    const assignRoleCol = document.getElementById('assignRoleCol');
    const userIdSelect = document.getElementById('userIdSelect');
    const roleIdSelect = document.getElementById('roleIdSelect');

    function updateAssignFields() {
        const isRole = assignTypeSelect.value === 'role';
        assignUserCol.classList.toggle('d-none', isRole);
        assignRoleCol.classList.toggle('d-none', !isRole);
    }

    function updateModalFields() {
        if (!recordTypeSelect) return;
        const selectedType = recordTypeSelect.value;
        const isVerification = (selectedType === 'po_verification' || selectedType === 'gr_verification');

        if (isVerification) {
            if (levelInput) levelInput.value = 1;
            if (levelStepCol) levelStepCol.style.display = 'none';
            if (workflowTypeCol) workflowTypeCol.className = 'col-md-12 mb-3';
            if (conditionsSection) conditionsSection.style.display = 'none';

            if (nameInput && (!nameInput.value || nameInput.value.includes('Approval') || nameInput.value.includes('Verifier'))) {
                if (selectedType === 'po_verification') {
                    nameInput.value = 'Verifikasi Procurement PO';
                } else if (selectedType === 'gr_verification') {
                    nameInput.value = 'Verifikasi Fisik Gudang (QC)';
                }
            }
        } else {
            if (levelStepCol) levelStepCol.style.display = 'block';
            if (workflowTypeCol) workflowTypeCol.className = 'col-md-6 mb-3';
            if (conditionsSection) conditionsSection.style.display = 'block';
            // Payment Advice's approval flow never filters by project type at runtime.
            if (isProjectCol) isProjectCol.style.display = (selectedType === 'payment_advice') ? 'none' : 'block';

            if (!form.dataset.editing) {
                const currentMax = maxLevels[selectedType] || 0;
                if (levelInput) levelInput.value = currentMax + 1;
            }

            if (nameInput && nameInput.value.includes('Verifikasi')) {
                nameInput.value = '';
            }
        }
    }

    function resetForm() {
        form.reset();
        delete form.dataset.editing;
        form.action = storeUrl;
        formMethod.value = 'POST';
        modalTitle.innerHTML = '<i class="bx bx-cog me-2"></i>Add Approval / Verification Configuration';
        submitBtn.textContent = 'Save Configuration';
        assignTypeSelect.value = 'user';
        updateAssignFields();
        updateModalFields();
    }

    window.openConfigModal = function(mode, config) {
        resetForm();

        if (mode === 'edit' && config) {
            form.dataset.editing = '1';
            form.action = updateUrlTemplate.replace('__ID__', config.id);
            formMethod.value = 'PUT';
            modalTitle.innerHTML = '<i class="bx bx-edit-alt me-2"></i>Edit Approval / Verification Configuration';
            submitBtn.textContent = 'Update Configuration';

            recordTypeSelect.value = config.record_type;
            levelInput.value = config.level;
            nameInput.value = config.name;

            if (config.role_id) {
                assignTypeSelect.value = 'role';
                roleIdSelect.value = config.role_id;
            } else {
                assignTypeSelect.value = 'user';
                userIdSelect.value = config.user_id;
            }
            updateAssignFields();

            document.querySelector('select[name="is_project"]').value = (config.is_project === null || config.is_project === undefined) ? '' : (config.is_project ? '1' : '0');
            document.querySelector('input[name="min_amount"]').value = config.min_amount ?? '';
            document.querySelector('input[name="max_amount"]').value = config.max_amount ?? '';

            updateModalFields();
        }

        const modal = bootstrap.Modal.getOrCreateInstance(addConfigModal);
        modal.show();
    };

    if (recordTypeSelect) {
        recordTypeSelect.addEventListener('change', updateModalFields);
    }
    if (assignTypeSelect) {
        assignTypeSelect.addEventListener('change', updateAssignFields);
    }
    if (addConfigModal) {
        addConfigModal.addEventListener('hidden.bs.modal', resetForm);
    }

    resetForm();
});
</script>
@endpush
