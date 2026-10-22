@extends('layouts.home')

@section('title', 'Monthly Budget Plan - ' . $monthlyBudgetPlan->code)

@include('erp.budget.partials.sf-style')

@section('content')
@php
  $mp = $monthlyBudgetPlan;
  $annual = $mp->annualBudgetPlan;
  $money = fn ($v) => 'IDR '.number_format((float) $v, 0, ',', '.');
  $overBudget = $mp->total_actual_expense > $mp->total_expense_plan;
  $left = [
    ['Monthly Budget Plan Name', '<b>'.e($mp->code).'</b>'],
    ['Anual Budget Plan', '<a class="sf-link" href="'.e(route('erp.annual-budget-plans.show', $annual)).'">'.e($annual->code).'</a>'],
    ['Year', e($mp->year)],
    ['Month', e($mp->month)],
    ['Account Name', e($annual->budgetParent->name ?? '-')],
    ['Area', e($annual->area)],
    ['Type', e($annual->work_type)],
    ['Work Subtype', e($annual->work_subtype ?? '')],
    ['Revenue Plan', e($money($mp->revenue_plan))],
    ['Qty', e($mp->qty)],
    ['Unit', e($mp->unit ?? '')],
    ['Description', nl2br(e($mp->description ?? ''))],
  ];
  $right = [
    ['Total Actual Expense', e($money($mp->total_actual_expense))],
    ['Total Direct Expense Plan', e($money($mp->total_direct_expense_plan))],
    ['Total Indirect Expense Plan', e($money($mp->total_indirect_expense_plan))],
    ['Subtotal Expense Plan', e($money($mp->subtotal_expense_plan))],
    ['Total Expense Plan', '<b>'.e($money($mp->total_expense_plan)).'</b>'],
    ['Total Revenue Plan', '<b>'.e($money($mp->total_revenue_plan)).'</b>'],
    ['Status Anggaran', '<span class="fw-bold '.($overBudget ? 'text-danger' : 'text-success').'">'.($overBudget ? 'Over Budget' : 'Dalam Batas').'</span>'],
    ['Created', e($mp->created_at?->format('Y/m/d H:i') ?? '-')],
  ];
@endphp
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
      <span class="sf-icon"><i class="bx bx-calendar-event"></i></span>
      <div><div class="sf-object">Monthly Budget Plan</div><div class="sf-name">{{ $mp->code }}</div></div>
    </div>
    <a href="{{ route('erp.annual-budget-plans.show', $annual) }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to {{ $annual->code }}</a>
  </div>

  <div class="sf-card">
    <div class="sf-card-head"><h6>Monthly Budget Plan Detail</h6></div>
    @include('erp.budget.partials.sf-fields', ['left' => $left, 'right' => $right])
  </div>

  <div class="sf-card">
    <div class="sf-card-head">
      <h6>Budget Plan Detail</h6>
      <div class="sf-actions"><button class="btn btn-primary btn-sm" onclick="openDetailModal('add')"><i class="bx bx-plus me-1"></i>New Budget Plan Detail</button></div>
    </div>
    @if($mp->budgetPlanDetails->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr>
              <th>Action</th>
              <th>Budget Plan Detail</th>
              <th>Expense Type</th>
              <th class="text-center">Indirect</th>
              <th class="text-end">Actual Advance Request</th>
              <th class="text-end">Actual Expense Amount</th>
              <th class="text-end">Amount</th>
              <th class="text-end">Total Expense Plan</th>
              <th class="text-end">Remaining Budget</th>
              <th class="text-end">Month</th>
              <th class="text-end">Qty</th>
            </tr>
          </thead>
          <tbody>
            @foreach($mp->budgetPlanDetails as $d)
              @php $detailOver = $d->actual_expense_amount > $d->total_expense_plan; @endphp
              <tr>
                <td><a href="#" class="sf-link" onclick='openDetailModal("edit", @json($d)); return false;'>Edit</a></td>
                <td class="fw-semibold">{{ $d->code }}</td>
                <td>{{ $d->expense_type }}</td>
                <td class="text-center">@if($d->is_indirect)<i class="bx bx-check"></i>@endif</td>
                <td class="text-end">{{ $money($d->actual_advance_request) }}</td>
                <td class="text-end {{ $detailOver ? 'text-danger fw-bold' : '' }}">{{ $money($d->actual_expense_amount) }}@if($detailOver) <i class="bx bx-error-circle" title="Over Budget"></i>@endif</td>
                <td class="text-end">{{ $money($d->amount) }}</td>
                <td class="text-end">{{ $money($d->total_expense_plan) }}</td>
                <td class="text-end fw-semibold {{ $d->remaining_budget <= 0 ? 'text-danger' : 'text-success' }}">{{ $money($d->remaining_budget) }}</td>
                <td class="text-end">{{ $mp->month }}</td>
                <td class="text-end">{{ $mp->qty }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
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
            <option value="Materials">Materials</option>
            <option value="Materials-Subcon">Materials-Subcon</option>
            <option value="Transportation & Telecommunication">Transportation & Telecommunication</option>
            <option value="Office">Office</option>
            <option value="Utilities">Utilities</option>
            <option value="Other Expense">Other Expense</option>
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

    // Live refresh: this exact Monthly Budget Plan (or one of its Budget Plan
    // Details, which also pushes here since this page lists them) changed
    // elsewhere — reload so totals / detail rows stay current.
    window.addEventListener('erp-live-update', function (e) {
        if (e.detail.record_type === 'monthly_budget_plan' && e.detail.id == {{ $monthlyBudgetPlan->id }}) {
            location.reload();
        }
    });
})();
</script>
@endpush
@endsection
