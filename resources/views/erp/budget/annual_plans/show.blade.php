@extends('layouts.home')

@section('title', 'Annual Budget Plan - ' . $annualBudgetPlan->code)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bx bx-calendar me-2 text-primary"></i>{{ $annualBudgetPlan->code }}</h4>
      <div class="text-muted small">
        {{ $annualBudgetPlan->budgetParent->name ?? '-' }} &middot; {{ $annualBudgetPlan->year }} &middot;
        {{ $annualBudgetPlan->area }} &middot; {{ $annualBudgetPlan->work_type }}
        @if($annualBudgetPlan->work_subtype) - {{ $annualBudgetPlan->work_subtype }} @endif
      </div>
    </div>
    <a href="{{ route('erp.annual-budget-plans.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card h-100"><div class="card-body">
        <div class="text-muted small text-uppercase fw-bold">Total Expense Plan (setahun)</div>
        <div class="fs-4 fw-bold text-primary">IDR {{ number_format($annualBudgetPlan->total_expense_plan, 0, ',', '.') }}</div>
      </div></div>
    </div>
    <div class="col-md-6">
      <div class="card h-100"><div class="card-body">
        <div class="text-muted small text-uppercase fw-bold">Total Actual Expense (setahun)</div>
        <div class="fs-4 fw-bold text-success">IDR {{ number_format($annualBudgetPlan->total_actual_expense, 0, ',', '.') }}</div>
      </div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold">Monthly Budget Plans</h6>
      <button class="btn btn-primary btn-sm" onclick="openMonthModal('add')"><i class="bx bx-plus me-1"></i>Add Month</button>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-uppercase fw-bold small">Code</th>
            <th class="text-uppercase fw-bold small text-center">Month</th>
            <th class="text-uppercase fw-bold small text-end">Qty</th>
            <th class="text-uppercase fw-bold small text-end">Revenue Plan</th>
            <th class="text-uppercase fw-bold small text-end">Expense Plan</th>
            <th class="text-uppercase fw-bold small text-end">Actual Expense</th>
            <th class="text-uppercase fw-bold small text-center">Status</th>
            <th class="text-uppercase fw-bold small text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($annualBudgetPlan->monthlyBudgetPlans as $m)
            <tr>
              <td class="fw-bold">{{ $m->code }}</td>
              <td class="text-center">{{ \Carbon\Carbon::create()->month($m->month)->format('F') }}</td>
              <td class="text-end">{{ $m->qty }} {{ $m->unit }}</td>
              <td class="text-end">{{ number_format($m->total_revenue_plan, 0, ',', '.') }}</td>
              <td class="text-end">{{ number_format($m->total_expense_plan, 0, ',', '.') }}</td>
              <td class="text-end">{{ number_format($m->total_actual_expense, 0, ',', '.') }}</td>
              <td class="text-center"><span class="badge bg-label-secondary">{{ $m->approval_status }}</span></td>
              <td class="text-center">
                <a href="{{ route('erp.monthly-budget-plans.show', $m) }}" class="btn btn-sm btn-primary me-1"><i class="bx bx-show"></i></a>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick='openMonthModal("edit", @json($m))'><i class="bx bx-edit-alt"></i></button>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada Monthly Budget Plan</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalMonth" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="monthForm" class="modal-content" method="POST" action="{{ route('erp.annual-budget-plans.monthly-plans.store', $annualBudgetPlan) }}">
      @csrf
      <input type="hidden" name="_method" id="monthFormMethod" value="POST">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="monthModalTitle"><i class="bx bx-plus-circle me-2 text-primary"></i>Add Monthly Budget Plan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
          <select name="month" class="form-select" required>
            @for($i = 1; $i <= 12; $i++)
              <option value="{{ $i }}">{{ \Carbon\Carbon::create()->month($i)->format('F') }}</option>
            @endfor
          </select>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Unit</label>
            <input name="unit" class="form-control" value="Site" placeholder="e.g. Site">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Qty <span class="text-danger">*</span></label>
            <input type="number" name="qty" class="form-control" required min="0" value="1">
          </div>
        </div>
        <div class="mb-3 mt-3">
          <label class="form-label fw-semibold">Revenue Plan (per unit) <span class="text-danger">*</span></label>
          <input type="number" step="0.01" name="revenue_plan" class="form-control" required min="0" value="0">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Description</label>
          <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-primary" type="submit" id="monthSubmitBtn"><i class="bx bx-save me-1"></i>Save</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
(function () {
    const storeUrl = @json(route('erp.annual-budget-plans.monthly-plans.store', $annualBudgetPlan));
    const updateUrlTemplate = @json(route('erp.monthly-budget-plans.update', ['monthlyBudgetPlan' => '__ID__']));
    const form = document.getElementById('monthForm');
    const methodField = document.getElementById('monthFormMethod');
    const title = document.getElementById('monthModalTitle');
    const submitBtn = document.getElementById('monthSubmitBtn');

    window.openMonthModal = function (mode, month) {
        form.reset();
        if (mode === 'edit' && month) {
            form.action = updateUrlTemplate.replace('__ID__', month.id);
            methodField.value = 'PUT';
            title.innerHTML = '<i class="bx bx-edit-alt me-2 text-warning"></i>Edit Monthly Budget Plan';
            submitBtn.textContent = 'Update';
            form.querySelector('[name="month"]').value = month.month;
            form.querySelector('[name="unit"]').value = month.unit || '';
            form.querySelector('[name="qty"]').value = month.qty;
            form.querySelector('[name="revenue_plan"]').value = month.revenue_plan;
            form.querySelector('[name="description"]').value = month.description || '';
        } else {
            form.action = storeUrl;
            methodField.value = 'POST';
            title.innerHTML = '<i class="bx bx-plus-circle me-2 text-primary"></i>Add Monthly Budget Plan';
            submitBtn.textContent = 'Save';
        }
        new bootstrap.Modal(document.getElementById('modalMonth')).show();
    };
})();
</script>
@endpush
@endsection
