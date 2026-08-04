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

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1 fw-bold">Approval Configuration</h4>
      <div class="text-muted small">Manage Request Form Approval Workflows</div>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConfigModal">
      <i class="bx bx-plus me-1"></i> Add Step
    </button>
  </div>

  <div class="row g-4">
    <!-- Project Configs -->
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold text-primary"><i class="bx bx-buildings me-2"></i>Project RF Approval</h6>
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Level</th>
                <th>Name</th>
                <th>Assigned To</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($projectConfigs as $config)
                <tr>
                  <td><span class="badge bg-label-primary">Step {{ $config->level }}</span></td>
                  <td class="fw-semibold">{{ $config->name }}</td>
                  <td>
                    @if($config->role_id)
                      Role: <span class="fw-bold">{{ $config->role->name }}</span>
                    @elseif($config->user_id)
                      User: <span class="fw-bold">{{ $config->user->name }}</span>
                    @else
                      -
                    @endif
                  </td>
                  <td class="text-end">
                    <form action="{{ route('erp.approval-configs.destroy', $config) }}" method="POST" onsubmit="return confirm('Hapus konfigurasi ini?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No configuration found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Non-Project Configs -->
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold text-success"><i class="bx bx-shopping-bag me-2"></i>Non-Project RF Approval</h6>
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Level</th>
                <th>Name</th>
                <th>Assigned To</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($nonProjectConfigs as $config)
                <tr>
                  <td><span class="badge bg-label-success">Step {{ $config->level }}</span></td>
                  <td class="fw-semibold">{{ $config->name }}</td>
                  <td>
                    @if($config->role_id)
                      Role: <span class="fw-bold">{{ $config->role->name }}</span>
                    @elseif($config->user_id)
                      User: <span class="fw-bold">{{ $config->user->name }}</span>
                    @else
                      -
                    @endif
                  </td>
                  <td class="text-end">
                    <form action="{{ route('erp.approval-configs.destroy', $config) }}" method="POST" onsubmit="return confirm('Hapus konfigurasi ini?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No configuration found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mt-1">
    <!-- PO Low Configs (<= 1M) -->
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold text-info"><i class="bx bx-receipt me-2"></i>PO Approval (<= 1M)</h6>
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Level</th>
                <th>Name</th>
                <th>Assigned To</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($poLowConfigs as $config)
                <tr>
                  <td><span class="badge bg-label-info">Step {{ $config->level }}</span></td>
                  <td class="fw-semibold">{{ $config->name }}</td>
                  <td>
                    @if($config->role_id)
                      Role: <span class="fw-bold">{{ $config->role->name }}</span>
                    @elseif($config->user_id)
                      User: <span class="fw-bold">{{ $config->user->name }}</span>
                    @else
                      -
                    @endif
                  </td>
                  <td class="text-end">
                    <form action="{{ route('erp.approval-configs.destroy', $config) }}" method="POST" onsubmit="return confirm('Hapus konfigurasi ini?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No configuration found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- PO High Configs (> 1M) -->
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold text-danger"><i class="bx bx-badge-check me-2"></i>PO Approval (> 1M)</h6>
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Level</th>
                <th>Name</th>
                <th>Assigned To</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($poHighConfigs as $config)
                <tr>
                  <td><span class="badge bg-label-danger">Step {{ $config->level }}</span></td>
                  <td class="fw-semibold">{{ $config->name }}</td>
                  <td>
                    @if($config->role_id)
                      Role: <span class="fw-bold">{{ $config->role->name }}</span>
                    @elseif($config->user_id)
                      User: <span class="fw-bold">{{ $config->user->name }}</span>
                    @else
                      -
                    @endif
                  </td>
                  <td class="text-end">
                    <form action="{{ route('erp.approval-configs.destroy', $config) }}" method="POST" onsubmit="return confirm('Hapus konfigurasi ini?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No configuration found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Add Config -->
<div class="modal fade" id="addConfigModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('erp.approval-configs.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Add Approval Step</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Record Type <span class="text-danger">*</span></label>
            <select name="record_type" class="form-select" required>
              <option value="project">Project</option>
              <option value="non_project">Non-Project</option>
              <option value="purchase_order_low">PO Approval (<= 1M)</option>
              <option value="purchase_order_high">PO Approval (> 1M)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Level / Step (Number) <span class="text-danger">*</span></label>
            <input type="number" name="level" class="form-control" min="1" required placeholder="e.g. 1" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Manager Approval">
          </div>
          
          <div class="mb-3">
            <label class="form-label d-block">Assignment Type <span class="text-danger">*</span></label>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="assignment_type" id="assignRole" value="role" checked onchange="toggleAssignType()">
              <label class="form-check-label" for="assignRole">Assign to a Role</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="assignment_type" id="assignUser" value="user" onchange="toggleAssignType()">
              <label class="form-check-label" for="assignUser">Assign to a Specific User</label>
            </div>
          </div>

          <div class="mb-3" id="roleSelectWrapper">
            <label class="form-label">Select Role</label>
            <select name="role_id" class="form-select" id="roleSelect">
              <option value="">-- Choose Role --</option>
              @foreach($roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
              @endforeach
            </select>
            <small class="text-muted d-block mt-1">Siapapun yang memiliki peran ini berhak untuk menyetujui.</small>
          </div>

          <div class="mb-3 d-none" id="userSelectWrapper">
            <label class="form-label">Select User</label>
            <select name="user_id" class="form-select" id="userSelect">
              <option value="">-- Choose User --</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }} - {{ ucwords(str_replace('_', ' ', $user->project_role ?? '')) }}</option>
              @endforeach
            </select>
            <small class="text-muted d-block mt-1">Hanya orang spesifik ini yang berhak untuk menyetujui.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Step</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function toggleAssignType() {
  const isRole = document.getElementById('assignRole').checked;
  const roleSelectWrapper = document.getElementById('roleSelectWrapper');
  const userSelectWrapper = document.getElementById('userSelectWrapper');
  const roleSelect = document.getElementById('roleSelect');
  const userSelect = document.getElementById('userSelect');

  if (isRole) {
    roleSelectWrapper.classList.remove('d-none');
    userSelectWrapper.classList.add('d-none');
    userSelect.value = ''; // Reset user
  } else {
    userSelectWrapper.classList.remove('d-none');
    roleSelectWrapper.classList.add('d-none');
    roleSelect.value = ''; // Reset role
  }
}

// Auto-fill level based on record_type
document.addEventListener('DOMContentLoaded', function() {
    const projectCount = {{ $projectConfigs->count() }};
    const nonProjectCount = {{ $nonProjectConfigs->count() }};
    const poLowCount = {{ $poLowConfigs->count() }};
    const poHighCount = {{ $poHighConfigs->count() }};
    const recordTypeSelect = document.querySelector('[name="record_type"]');
    const levelInput = document.querySelector('[name="level"]');
 
    function updateLevel() {
        const val = recordTypeSelect.value;
        if (val === 'project') {
            levelInput.value = projectCount + 1;
        } else if (val === 'non_project') {
            levelInput.value = nonProjectCount + 1;
        } else if (val === 'purchase_order_low') {
            levelInput.value = poLowCount + 1;
        } else if (val === 'purchase_order_high') {
            levelInput.value = poHighCount + 1;
        }
    }

    recordTypeSelect.addEventListener('change', updateLevel);
    
    // Auto-fill when modal is opened
    const addConfigModal = document.getElementById('addConfigModal');
    if(addConfigModal) {
        addConfigModal.addEventListener('show.bs.modal', function () {
            updateLevel();
        });
    }
});
</script>
@endpush
