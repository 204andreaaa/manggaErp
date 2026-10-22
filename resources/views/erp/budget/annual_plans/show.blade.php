@extends('layouts.home')

@section('title', 'Annual Budget Plan - ' . $annualBudgetPlan->code)

@include('erp.budget.partials.sf-style')

@section('content')
@php
  $plan = $annualBudgetPlan;
  $money = fn ($v) => 'IDR '.number_format((float) $v, 0, ',', '.');
  $totalRevenue = (float) $plan->monthlyBudgetPlans->sum('total_revenue_plan');
  $expensePct = $totalRevenue > 0 ? round($plan->total_expense_plan / $totalRevenue * 100, 2) : null;
  $left = [
    ['Anual Budget ID', '<b>'.e($plan->code).'</b>'],
    ['Year', e($plan->year)],
    ['Account', e($plan->budgetParent->name ?? '-')],
    ['WID', $plan->workItem ? e($plan->workItem->wid_code.' - '.$plan->workItem->name) : '<span class="badge bg-label-warning">Belum ada WID</span>'],
    ['Area', e($plan->area)],
    ['Work Type', e($plan->work_type)],
    ['Work Subtype', e($plan->work_subtype ?? '')],
    ['Total Revenue Plan', e($money($totalRevenue))],
    ['Total Expense Plan', e($money($plan->total_expense_plan))],
    ['Expenses %', $expensePct === null ? '-' : '<span class="'.($expensePct > 100 ? 'text-danger fw-bold' : '').'">'.e(number_format($expensePct, 2, ',', '.')).'%</span>'],
    ['Total Actual Expense', e($money($plan->total_actual_expense))],
  ];
  $right = [
    ['Budget Amount (dari WID)', '<b>'.e($money($plan->budget_amount)).'</b>'],
    ['Sudah Dibagi ke Bulan', e($money((float) $plan->budget_amount - (float) $plan->remaining_budget))],
    ['Belum Dibagi ke Bulan', '<b class="'.((float) $plan->remaining_budget <= 0 ? 'text-danger' : 'text-success').'">'.e($money($plan->remaining_budget)).'</b>'],
    ['Owner (KAM)', e($plan->budgetParent?->kam?->name ?? '-')],
    ['Currency', e($plan->currency ?: 'IDR')],
    ['Created', e($plan->created_at?->format('Y/m/d H:i') ?? '-')],
    ['Last Modified', e($plan->updated_at?->format('Y/m/d H:i') ?? '-')],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #1798c1">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-calendar"></i></span>
      <div><div class="sf-object">Annual Budget Plan</div><div class="sf-name">{{ $plan->code }}</div></div>
    </div>
    <a href="{{ route('erp.annual-budget-plans.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to List</a>
  </div>

  <div class="sf-card">
    <div class="sf-card-head"><h6>Annual Budget Plan Detail</h6></div>
    @include('erp.budget.partials.sf-fields', ['left' => $left, 'right' => $right])
  </div>

  <div class="sf-card">
    <div class="sf-card-head">
      <h6>Monthly Budget Plan</h6>
      <div class="sf-actions"><button class="btn btn-primary btn-sm" onclick="openMonthModal('add')"><i class="bx bx-plus me-1"></i>New Monthly Budget Plan</button></div>
    </div>
    @if($plan->monthlyBudgetPlans->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr>
              <th>Action</th>
              <th>Monthly Budget Plan Name</th>
              <th class="text-end">Month</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Budget Amount</th>
              <th class="text-end">Belum Dibagi ke Detail</th>
              <th class="text-end">Revenue Plan</th>
              <th class="text-end">Total Revenue Plan</th>
              <th class="text-end">Subtotal Expense Plan</th>
              <th class="text-end">Total Expense Plan</th>
              <th class="text-end">Total Direct Expense Plan</th>
              <th class="text-end">Total Indirect Expense Plan</th>
              <th class="text-end">Total Actual Expense</th>
            </tr>
          </thead>
          <tbody>
            @foreach($plan->monthlyBudgetPlans as $m)
              <tr>
                <td><a href="#" class="sf-link" onclick='openMonthModal("edit", @json($m)); return false;'>Edit</a></td>
                <td><a class="sf-link" href="{{ route('erp.monthly-budget-plans.show', $m) }}">{{ $m->code }}</a></td>
                <td class="text-end">{{ $m->month }}</td>
                <td class="text-end">{{ $m->qty }} {{ $m->unit }}</td>
                <td class="text-end">{{ $money($m->budget_amount) }}</td>
                <td class="text-end {{ (float) $m->remaining_budget < 0 ? 'text-danger fw-bold' : '' }}">{{ $money($m->remaining_budget) }}</td>
                <td class="text-end">{{ $money($m->revenue_plan) }}</td>
                <td class="text-end">{{ $money($m->total_revenue_plan) }}</td>
                <td class="text-end">{{ $money($m->subtotal_expense_plan) }}</td>
                <td class="text-end">{{ $money($m->total_expense_plan) }}</td>
                <td class="text-end">{{ $money($m->total_direct_expense_plan) }}</td>
                <td class="text-end">{{ $money($m->total_indirect_expense_plan) }}</td>
                <td class="text-end">{{ $money($m->total_actual_expense) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
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
          <label class="form-label fw-semibold">Budget Amount <span class="text-danger">*</span></label>
          <input type="number" step="0.01" name="budget_amount" class="form-control" required min="0">
          <div class="form-text">Jatah bulan ini, diambil dari sisa Annual: <b>IDR {{ number_format($plan->remaining_budget, 0, ',', '.') }}</b>.</div>
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
            form.querySelector('[name="budget_amount"]').value = month.budget_amount;
            form.querySelector('[name="description"]').value = month.description || '';
        } else {
            form.action = storeUrl;
            methodField.value = 'POST';
            title.innerHTML = '<i class="bx bx-plus-circle me-2 text-primary"></i>Add Monthly Budget Plan';
            submitBtn.textContent = 'Save';
        }
        new bootstrap.Modal(document.getElementById('modalMonth')).show();
    };

    // Live refresh: this exact Annual Budget Plan (or one of its Monthly Budget
    // Plans, which also pushes here since this page lists them) changed elsewhere
    // — reload so totals / monthly rows stay current. No destroy route exists for
    // annual budget plans yet, so there's no "Deleted" redirect branch to handle.
    window.addEventListener('erp-live-update', function (e) {
        if (e.detail.record_type === 'annual_budget_plan' && e.detail.id == {{ $annualBudgetPlan->id }}) {
            location.reload();
        }
    });
})();
</script>
@endpush
@endsection
