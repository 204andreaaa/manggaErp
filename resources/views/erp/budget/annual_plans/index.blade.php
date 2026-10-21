@extends('layouts.home')

@section('title', 'Annual Budget Plans')

@include('erp.budget.partials.sf-style')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #1798c1">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-calendar"></i></span>
      <div><div class="sf-object">Budget Plans</div><div class="sf-name">Annual Budget Plans</div></div>
    </div>
  </div>

  <div class="sf-card">
    <div class="sf-card-head">
      <h6>Annual Budget Plans</h6>
      <div class="sf-actions">
        <button class="btn btn-primary btn-sm" onclick="openPlanModal('add')"><i class="bx bx-plus me-1"></i>New Annual Budget Plan</button>
      </div>
    </div>
    @if($plans->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr>
              <th>Action</th>
              <th>Anual Budget ID</th>
              <th>Account</th>
              <th>Year</th>
              <th>Area</th>
              <th>Work Type</th>
              <th>Work Subtype</th>
              <th>Currency</th>
              <th class="text-end">Total Expense Plan</th>
              <th class="text-end">Total Actual Expense</th>
            </tr>
          </thead>
          <tbody>
            @foreach($plans as $plan)
              <tr>
                <td><a href="#" class="sf-link" onclick='openPlanModal("edit", @json($plan)); return false;'>Edit</a></td>
                <td><a class="sf-link" href="{{ route('erp.annual-budget-plans.show', $plan) }}">{{ $plan->code }}</a></td>
                <td>{{ $plan->budgetParent->name ?? '-' }}</td>
                <td>{{ $plan->year }}</td>
                <td>{{ $plan->area }}</td>
                <td>{{ $plan->work_type }}</td>
                <td>{{ $plan->work_subtype }}</td>
                <td>{{ $plan->currency ?: 'IDR' }}</td>
                <td class="text-end">IDR {{ number_format($plan->total_expense_plan, 0, ',', '.') }}</td>
                <td class="text-end">IDR {{ number_format($plan->total_actual_expense, 0, ',', '.') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
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

    // Live refresh: this table is rendered server-side (no ajax DataTable),
    // so another user's annual budget plan create/update just reloads the page.
    window.addEventListener('erp-live-update', function (e) {
        if (e.detail.record_type === 'annual_budget_plan') {
            location.reload();
        }
    });
})();
</script>
@endpush
@endsection
