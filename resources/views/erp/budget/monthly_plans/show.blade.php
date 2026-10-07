@extends('layouts.home')

@section('title', 'Monthly Budget Plan - ' . $monthlyBudgetPlan->code)

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

  @php $annual = $monthlyBudgetPlan->annualBudgetPlan; @endphp

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bx bx-calendar-event me-2 text-primary"></i>{{ $monthlyBudgetPlan->code }}</h4>
      <div class="text-muted small">
        {{ $annual->budgetParent->name ?? '-' }} &middot; {{ \Carbon\Carbon::create()->month($monthlyBudgetPlan->month)->format('F') }} {{ $monthlyBudgetPlan->year }}
        &middot; {{ $annual->area }} &middot; {{ $annual->work_type }}
      </div>
    </div>
    <a href="{{ route('erp.annual-budget-plans.show', $annual) }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to {{ $annual->code }}</a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card h-100"><div class="card-body">
        <div class="text-muted small text-uppercase fw-bold">Revenue Plan</div>
        <div class="fs-5 fw-bold">{{ number_format($monthlyBudgetPlan->total_revenue_plan, 0, ',', '.') }}</div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card h-100"><div class="card-body">
        <div class="text-muted small text-uppercase fw-bold">Expense Plan</div>
        <div class="fs-5 fw-bold text-primary">{{ number_format($monthlyBudgetPlan->total_expense_plan, 0, ',', '.') }}</div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card h-100"><div class="card-body">
        <div class="text-muted small text-uppercase fw-bold">Actual Expense</div>
        <div class="fs-5 fw-bold text-success">{{ number_format($monthlyBudgetPlan->total_actual_expense, 0, ',', '.') }}</div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card h-100"><div class="card-body">
        @php $overBudget = $monthlyBudgetPlan->total_actual_expense > $monthlyBudgetPlan->total_expense_plan; @endphp
        <div class="text-muted small text-uppercase fw-bold">Status Anggaran</div>
        <div class="fs-5 fw-bold {{ $overBudget ? 'text-danger' : 'text-success' }}">
          {{ $overBudget ? 'Over Budget' : 'Dalam Batas' }}
        </div>
      </div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold">Budget Plan Details (per Expense Type)</h6>
      <button class="btn btn-primary btn-sm" onclick="openDetailModal('add')"><i class="bx bx-plus me-1"></i>Add Expense Type</button>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-uppercase fw-bold small">Code</th>
            <th class="text-uppercase fw-bold small">Expense Type</th>
            <th class="text-uppercase fw-bold small text-center">Direct/Indirect</th>
            <th class="text-uppercase fw-bold small text-end">Amount/unit</th>
            <th class="text-uppercase fw-bold small text-end">Total Plan</th>
            <th class="text-uppercase fw-bold small text-end">Actual Kasbon</th>
            <th class="text-uppercase fw-bold small text-end">Actual Expense</th>
            <th class="text-uppercase fw-bold small text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($monthlyBudgetPlan->budgetPlanDetails as $d)
            @php $detailOver = $d->actual_expense_amount > $d->total_expense_plan; @endphp
            <tr>
              <td class="fw-bold">{{ $d->code }}</td>
              <td>{{ $d->expense_type }}</td>
              <td class="text-center"><span class="badge bg-label-secondary">{{ $d->is_indirect ? 'Indirect' : 'Direct' }}</span></td>
              <td class="text-end">{{ number_format($d->amount, 0, ',', '.') }}</td>
              <td class="text-end">{{ number_format($d->total_expense_plan, 0, ',', '.') }}</td>
              <td class="text-end">{{ number_format($d->actual_advance_request, 0, ',', '.') }}</td>
              <td class="text-end {{ $detailOver ? 'text-danger fw-bold' : '' }}">
                {{ number_format($d->actual_expense_amount, 0, ',', '.') }}
                @if($detailOver) <i class="bx bx-error-circle" title="Over Budget"></i> @endif
              </td>
              <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick='openDetailModal("edit", @json($d))'><i class="bx bx-edit-alt"></i></button>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada Budget Plan Detail</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="detailForm" class="modal-content" method="POST" action="{{ route('erp.monthly-budget-plans.budget-plan-details.store', $monthlyBudgetPlan) }}">
      @csrf
      <input type="hidden" name="_method" id="detailFormMethod" value="POST">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="detailModalTitle"><i class="bx bx-plus-circle me-2 text-primary"></i>Add Budget Plan Detail</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Expense Type <span class="text-danger">*</span></label>
          <select name="expense_type" class="form-select" required>
            <option value="Personnel">Personnel</option>
            <option value="Materials-Subcon">Materials-Subcon</option>
            <option value="Transportation & Telecommunication">Transportation & Telecommunication</option>
            <option value="Office">Office</option>
            <option value="Other Expense">Other Expense</option>
            <option value="Utilities">Utilities</option>
          </select>
        </div>
        <div class="mb-3 form-check">
          <input type="checkbox" name="is_indirect" value="1" class="form-check-input" id="isIndirect">
          <label class="form-check-label" for="isIndirect">Biaya Tidak Langsung (Indirect)</label>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Amount per unit <span class="text-danger">*</span></label>
          <input type="number" step="0.01" name="amount" class="form-control" required min="0" value="0">
          <div class="form-text text-muted">Dikalikan Qty ({{ $monthlyBudgetPlan->qty }} {{ $monthlyBudgetPlan->unit }}) untuk dapat Total Plan.</div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Description</label>
          <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-primary" type="submit" id="detailSubmitBtn"><i class="bx bx-save me-1"></i>Save</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
(function () {
    const storeUrl = @json(route('erp.monthly-budget-plans.budget-plan-details.store', $monthlyBudgetPlan));
    const updateUrlTemplate = @json(route('erp.budget-plan-details.update', ['budgetPlanDetail' => '__ID__']));
    const form = document.getElementById('detailForm');
    const methodField = document.getElementById('detailFormMethod');
    const title = document.getElementById('detailModalTitle');
    const submitBtn = document.getElementById('detailSubmitBtn');

    window.openDetailModal = function (mode, detail) {
        form.reset();
        if (mode === 'edit' && detail) {
            form.action = updateUrlTemplate.replace('__ID__', detail.id);
            methodField.value = 'PUT';
            title.innerHTML = '<i class="bx bx-edit-alt me-2 text-warning"></i>Edit Budget Plan Detail';
            submitBtn.textContent = 'Update';
            form.querySelector('[name="expense_type"]').value = detail.expense_type;
            form.querySelector('[name="is_indirect"]').checked = !!detail.is_indirect;
            form.querySelector('[name="amount"]').value = detail.amount;
            form.querySelector('[name="description"]').value = detail.description || '';
        } else {
            form.action = storeUrl;
            methodField.value = 'POST';
            title.innerHTML = '<i class="bx bx-plus-circle me-2 text-primary"></i>Add Budget Plan Detail';
            submitBtn.textContent = 'Save';
        }
        new bootstrap.Modal(document.getElementById('modalDetail')).show();
    };
})();
</script>
@endpush
@endsection
