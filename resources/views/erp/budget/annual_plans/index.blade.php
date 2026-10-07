@extends('layouts.home')

@section('title', 'Annual Budget Plans')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <div class="card mb-3">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Annual Budget Plans</h5>
        <div class="text-muted small">Rencana anggaran tahunan per Budget Parent + Area + Work Type</div>
      </div>
      <button class="btn btn-primary btn-sm px-3" onclick="openPlanModal('add')">
        <i class="bx bx-plus me-1"></i> Add Annual Budget Plan
      </button>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-uppercase fw-bold small">Code</th>
            <th class="text-uppercase fw-bold small">Budget Parent</th>
            <th class="text-uppercase fw-bold small">Year</th>
            <th class="text-uppercase fw-bold small">Area</th>
            <th class="text-uppercase fw-bold small">Work Type / Subtype</th>
            <th class="text-uppercase fw-bold small text-center">Months</th>
            <th class="text-uppercase fw-bold small text-end">Total Expense Plan</th>
            <th class="text-uppercase fw-bold small text-end">Actual Expense</th>
            <th class="text-uppercase fw-bold small text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($plans as $plan)
            <tr>
              <td class="fw-bold">{{ $plan->code }}</td>
              <td>{{ $plan->budgetParent->name ?? '-' }}</td>
              <td>{{ $plan->year }}</td>
              <td>{{ $plan->area }}</td>
              <td>{{ $plan->work_type }}@if($plan->work_subtype) <span class="text-muted small d-block">{{ $plan->work_subtype }}</span>@endif</td>
              <td class="text-center"><span class="badge bg-label-primary">{{ $plan->monthly_budget_plans_count }}/12</span></td>
              <td class="text-end">{{ number_format($plan->total_expense_plan, 0, ',', '.') }}</td>
              <td class="text-end">{{ number_format($plan->total_actual_expense, 0, ',', '.') }}</td>
              <td class="text-center">
                <a href="{{ route('erp.annual-budget-plans.show', $plan) }}" class="btn btn-sm btn-primary me-1"><i class="bx bx-show"></i></a>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick='openPlanModal("edit", @json($plan))'><i class="bx bx-edit-alt"></i></button>
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-4">Belum ada Annual Budget Plan</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalPlan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="planForm" class="modal-content" method="POST" action="{{ route('erp.annual-budget-plans.store') }}">
      @csrf
      <input type="hidden" name="_method" id="planFormMethod" value="POST">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="planModalTitle"><i class="bx bx-plus-circle me-2 text-primary"></i>Add Annual Budget Plan</h5>
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
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
            <input type="number" name="year" class="form-control" required value="{{ now()->year }}" min="2000" max="2100">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Currency</label>
            <input name="currency" class="form-control" value="IDR">
          </div>
        </div>
        <div class="mb-3 mt-3">
          <label class="form-label fw-semibold">Area <span class="text-danger">*</span></label>
          <input name="area" class="form-control" required placeholder="e.g. Sumatera Tengah - IMP">
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Work Type <span class="text-danger">*</span></label>
            <input name="work_type" class="form-control" required placeholder="e.g. ITC">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Work Subtype</label>
            <input name="work_subtype" class="form-control" placeholder="e.g. Loss Survey and Installation">
          </div>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-primary" type="submit" id="planSubmitBtn"><i class="bx bx-save me-1"></i>Save</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
(function () {
    const storeUrl = @json(route('erp.annual-budget-plans.store'));
    const updateUrlTemplate = @json(route('erp.annual-budget-plans.update', ['annual_budget_plan' => '__ID__']));
    const form = document.getElementById('planForm');
    const methodField = document.getElementById('planFormMethod');
    const title = document.getElementById('planModalTitle');
    const submitBtn = document.getElementById('planSubmitBtn');

    window.openPlanModal = function (mode, plan) {
        form.reset();
        if (mode === 'edit' && plan) {
            form.action = updateUrlTemplate.replace('__ID__', plan.id);
            methodField.value = 'PUT';
            title.innerHTML = '<i class="bx bx-edit-alt me-2 text-warning"></i>Edit Annual Budget Plan';
            submitBtn.textContent = 'Update';
            form.querySelector('[name="budget_parent_id"]').value = plan.budget_parent_id;
            form.querySelector('[name="year"]').value = plan.year;
            form.querySelector('[name="currency"]').value = plan.currency || 'IDR';
            form.querySelector('[name="area"]').value = plan.area;
            form.querySelector('[name="work_type"]').value = plan.work_type;
            form.querySelector('[name="work_subtype"]').value = plan.work_subtype || '';
        } else {
            form.action = storeUrl;
            methodField.value = 'POST';
            title.innerHTML = '<i class="bx bx-plus-circle me-2 text-primary"></i>Add Annual Budget Plan';
            submitBtn.textContent = 'Save';
            form.querySelector('[name="year"]').value = new Date().getFullYear();
            form.querySelector('[name="currency"]').value = 'IDR';
        }
        new bootstrap.Modal(document.getElementById('modalPlan')).show();
    };
})();
</script>
@endpush
@endsection
