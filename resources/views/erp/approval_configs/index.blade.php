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
      <div class="text-muted small">Manage ERP Approval Workflows</div>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConfigModal">
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
                <tr>
                  <td><span class="badge bg-label-primary">Step {{ $config->level }}</span></td>
                  <td class="fw-semibold">{{ $config->name }}</td>
                  <td>
                    User: <span class="fw-bold">{{ $config->user->name ?? "-" }}</span>
                  </td>
                  <td>
                    @if(!is_null($config->is_project))
                      <span class="badge bg-label-secondary me-1">
                        {{ $config->is_project ? 'Project Only' : 'Non-Project Only' }}
                      </span>
                    @else
                      <span class="badge bg-label-secondary me-1">All Types</span>
                    @endif
                    @if($config->min_amount || $config->max_amount)
                      <span class="text-muted small d-block mt-1">
                        Amount: 
                        {{ $config->min_amount ? number_format($config->min_amount, 0, ',', '.') : '0' }}
                        - 
                        {{ $config->max_amount ? number_format($config->max_amount, 0, ',', '.') : '∞' }}
                      </span>
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
                <tr>
                  <td><span class="badge bg-label-success">Step {{ $config->level }}</span></td>
                  <td class="fw-semibold">{{ $config->name }}</td>
                  <td>
                    User: <span class="fw-bold">{{ $config->user->name ?? "-" }}</span>
                  </td>
                  <td>
                    @if(!is_null($config->is_project))
                      <span class="badge bg-label-secondary me-1">
                        {{ $config->is_project ? 'Project Only' : 'Non-Project Only' }}
                      </span>
                    @else
                      <span class="badge bg-label-secondary me-1">All Types</span>
                    @endif
                    @if($config->min_amount || $config->max_amount)
                      <span class="text-muted small d-block mt-1">
                        Amount: 
                        {{ $config->min_amount ? number_format($config->min_amount, 0, ',', '.') : '0' }}
                        - 
                        {{ $config->max_amount ? number_format($config->max_amount, 0, ',', '.') : '∞' }}
                      </span>
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
                <tr>
                  <td><span class="badge bg-label-info">Step {{ $config->level }}</span></td>
                  <td class="fw-semibold">{{ $config->name }}</td>
                  <td>
                    User: <span class="fw-bold">{{ $config->user->name ?? "-" }}</span>
                  </td>
                  <td>
                    @if(!is_null($config->is_project))
                      <span class="badge bg-label-secondary me-1">
                        {{ $config->is_project ? 'Project Only' : 'Non-Project Only' }}
                      </span>
                    @else
                      <span class="badge bg-label-secondary me-1">All Types</span>
                    @endif
                    @if($config->min_amount || $config->max_amount)
                      <span class="text-muted small d-block mt-1">
                        Amount: 
                        {{ $config->min_amount ? number_format($config->min_amount, 0, ',', '.') : '0' }}
                        - 
                        {{ $config->max_amount ? number_format($config->max_amount, 0, ',', '.') : '∞' }}
                      </span>
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
                        <span class="fw-bold text-dark">{{ $config->user->name ?? '-' }}</span>
                        <div class="text-muted small">{{ $config->user->email ?? '' }}</div>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge bg-label-secondary">Draft Purchase Order (Before Approval)</span></td>
                  <td class="text-end">
                    <form action="{{ route('erp.approval-configs.destroy', $config) }}" method="POST" onsubmit="return confirm('Hapus konfigurasi verifikator PO ini?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-3">Default Verifier: Head of Procurement (Febri Saputra)</td></tr>
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
                        <span class="fw-bold text-dark">{{ $config->user->name ?? '-' }}</span>
                        <div class="text-muted small">{{ $config->user->email ?? '' }}</div>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge bg-label-secondary">Physical Goods Receipt & QC Gudang</span></td>
                  <td class="text-end">
                    <form action="{{ route('erp.approval-configs.destroy', $config) }}" method="POST" onsubmit="return confirm('Hapus konfigurasi verifikator GR ini?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-3">Default Verifier: Logistik & Warehouse (Nikmal Hadi)</td></tr>
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
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('erp.approval-configs.store') }}" method="POST">
        @csrf
        <div class="modal-header bg-light">
          <h5 class="modal-title fw-bold"><i class="bx bx-cog me-2"></i>Add Approval / Verification Configuration</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
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
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Level / Step (Number) <span class="text-danger">*</span></label>
              <input type="number" name="level" class="form-control" min="1" required placeholder="e.g. 1">
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Finance Approval">
          </div>

          <div class="mb-3">
            <label class="form-label">Assign to User <span class="text-danger">*</span></label>
            <select name="user_id" class="form-select" required>
              <option value="">-- Select User --</option>
              @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
              @endforeach
            </select>
          </div>
          
          <hr>
          <h6 class="fw-bold">Conditions (Optional)</h6>
          
          <div class="row">
            <div class="col-md-4 mb-3">
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
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Configuration</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const maxLevels = @json($maxLevels);
    
    
    const recordTypeSelect = document.querySelector('select[name="record_type"]');
    const levelInput = document.querySelector('input[name="level"]');
    
    const userSelect = document.querySelector('select[name="user_id"]');
    
    // Auto-calculate level on change
    recordTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        const currentMax = maxLevels[selectedType] || 0;
        levelInput.value = currentMax + 1;
    });

    // Initialize level on load
    recordTypeSelect.dispatchEvent(new Event('change'));

    });
});
</script>
@endsection