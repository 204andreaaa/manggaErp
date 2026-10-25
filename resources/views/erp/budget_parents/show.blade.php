@extends('layouts.home')

@section('title', 'Budget Parent - ' . $budgetParent->name)

@include('erp.budget.partials.sf-style')

@section('content')
@php
  $bp = $budgetParent;
  $money = fn ($v) => 'IDR '.number_format((float) $v, 0, ',', '.');
  $allocated = (float) $workItems->sum('allocated_budget');
  $left = [
    ['Account Name', '<b>'.e($bp->name).'</b>'],
    ['Budget Code', e($bp->budget_code)],
    ['Account Owner (KAM)', e($bp->kam->name ?? '-')],
    ['Status', e($bp->status)],
  ];
  $right = [
    ['Total Budget', '<b>'.e($money($bp->total_budget)).'</b>'],
    ['Dialokasikan ke WID', e($money($allocated))],
    ['Remaining Budget', '<b class="'.((float) $bp->remaining_budget <= 0 ? 'text-danger' : 'text-success').'">'.e($money($bp->remaining_budget)).'</b>'],
    ['Last Modified', e($bp->updated_at?->format('Y/m/d H:i') ?? '-')],
  ];
  // Jump links at the top, like Salesforce's related-list bar.
  $sections = [
    'annual' => ['Annual Budget Plans', $bp->annualBudgetPlans->count()],
    'projects' => ['Projects', $bp->subProjects->count()],
    'works' => ['Works (WID)', $workItems->count()],
    'advance' => ['Advance Request', $advanceRequests->count()],
    'declaration' => ['Expense Declaration', $declarations->count()],
  ];
  $statusBadge = fn ($s) => match ($s) {
      'Approved' => 'bg-label-success', 'Rejected' => 'bg-label-danger',
      'Submitted' => 'bg-label-warning', default => 'bg-label-secondary',
  };
@endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #e0a63a">

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-buildings"></i></span>
      <div><div class="sf-object">Account (Budget Parent)</div><div class="sf-name">{{ $bp->name }}</div></div>
    </div>
    <a href="{{ route('erp.budget-parents.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to List</a>
  </div>

  <div class="text-center small mb-3 bp-jump">
    @foreach($sections as $id => [$label, $count])
      <a href="#bp-{{ $id }}" class="sf-link">{{ $label }} [{{ $count }}]</a>@if(!$loop->last) <span class="text-muted mx-1">|</span> @endif
    @endforeach
  </div>

  <div class="sf-card">
    <div class="sf-card-head"><h6>Account Detail</h6></div>
    @include('erp.budget.partials.sf-fields', ['left' => $left, 'right' => $right])
  </div>

  {{-- Annual Budget Plans --}}
  <div class="sf-card" id="bp-annual" style="--sf-accent: #1798c1">
    <div class="sf-card-head">
      <h6><i class="bx bx-calendar me-1"></i>Annual Budget Plans</h6>
      @if(auth()->user()->hasPermission('annual_budget_plans.view'))
        <div class="sf-actions"><a href="{{ route('erp.annual-budget-plans.index') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Annual Budget Plan</a></div>
      @endif
    </div>
    @if($bp->annualBudgetPlans->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr>
              <th>Anual Budget ID</th><th>Year</th><th>WID</th><th>Area</th><th>Work Type</th><th>Work Subtype</th><th>Currency</th>
              <th class="text-end">Budget Amount</th><th class="text-end">Total Expense Plan</th><th class="text-end">Total Actual Expense</th>
            </tr>
          </thead>
          <tbody>
            @foreach($bp->annualBudgetPlans as $plan)
              <tr>
                <td><a class="sf-link" href="{{ route('erp.annual-budget-plans.show', $plan) }}">{{ $plan->code }}</a></td>
                <td>{{ $plan->year }}</td>
                <td>{{ $plan->workItem->wid_code ?? '-' }}</td>
                <td>{{ $plan->area }}</td>
                <td>{{ $plan->work_type }}</td>
                <td>{{ $plan->work_subtype }}</td>
                <td>{{ $plan->currency ?: 'IDR' }}</td>
                <td class="text-end">{{ $money($plan->budget_amount) }}</td>
                <td class="text-end">{{ $money($plan->total_expense_plan) }}</td>
                <td class="text-end">{{ $money($plan->total_actual_expense) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Projects (Sub Projects) --}}
  <div class="sf-card" id="bp-projects" style="--sf-accent: #7f8de1">
    <div class="sf-card-head">
      <h6><i class="bx bx-git-branch me-1"></i>Projects</h6>
      @if(auth()->user()->hasPermission('sub_projects.view') || auth()->user()->hasPermission('budgets.view'))
        <div class="sf-actions"><a href="{{ route('erp.sub-projects.index') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Project</a></div>
      @endif
    </div>
    @if($bp->subProjects->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead><tr><th>Project Code</th><th>Project Name</th><th class="text-end">Works (WID)</th></tr></thead>
          <tbody>
            @foreach($bp->subProjects as $sp)
              <tr>
                <td class="fw-semibold">{{ $sp->sub_project_code }}</td>
                <td>{{ $sp->name }}</td>
                <td class="text-end">{{ $sp->work_items_count }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Works (WID) --}}
  <div class="sf-card" id="bp-works" style="--sf-accent: #4bc076">
    <div class="sf-card-head">
      <h6><i class="bx bx-task me-1"></i>Works (WID)</h6>
      @if(auth()->user()->hasPermission('work_items.view') || auth()->user()->hasPermission('budgets.view'))
        <div class="sf-actions"><a href="{{ route('erp.work-items.index') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Work</a></div>
      @endif
    </div>
    @if($workItems->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead><tr><th>Work Id</th><th>Work Name</th><th>Project</th><th class="text-end">Allocated</th><th class="text-end">Remaining</th></tr></thead>
          <tbody>
            @foreach($workItems as $w)
              <tr>
                <td class="fw-semibold">{{ $w->wid_code }}</td>
                <td>{{ $w->name }}</td>
                <td>{{ $w->subProject->name ?? '-' }}</td>
                <td class="text-end">{{ $money($w->allocated_budget) }}</td>
                <td class="text-end {{ (float) $w->remaining_budget <= 0 ? 'text-danger' : 'text-success' }} fw-semibold">{{ $money($w->remaining_budget) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Advance Request --}}
  <div class="sf-card" id="bp-advance" style="--sf-accent: #d27d5b">
    <div class="sf-card-head">
      <h6><i class="bx bx-wallet me-1"></i>Advance Request</h6>
      @if(auth()->user()->hasPermission('advance_requests.create'))
        <div class="sf-actions"><a href="{{ route('erp.advance-requests.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Advance Request</a></div>
      @endif
    </div>
    @if($advanceRequests->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr><th>Advance Request Id</th><th>Date</th><th>Expense Type</th><th>Work Id</th><th class="text-end">Amount</th><th class="text-end">Outstanding</th><th>Status</th><th>Payment</th></tr>
          </thead>
          <tbody>
            @foreach($advanceRequests as $a)
              <tr>
                <td><a class="sf-link" href="{{ route('erp.advance-requests.show', $a) }}">{{ $a->code }}</a></td>
                <td>{{ $a->advance_request_date?->format('Y/m/d') }}</td>
                <td>{{ $a->budgetPlanDetail->expense_type ?? '-' }}</td>
                <td>{{ $a->work->wid_code ?? '-' }}</td>
                <td class="text-end">{{ $money($a->total_amount_advance) }}</td>
                <td class="text-end">{{ $money($a->advance_outstanding) }}</td>
                <td><span class="badge {{ $statusBadge($a->status) }}">{{ $a->status }}</span></td>
                <td>{{ $a->payment_status }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Expense Declaration --}}
  <div class="sf-card" id="bp-declaration" style="--sf-accent: #6fb1ad">
    <div class="sf-card-head">
      <h6><i class="bx bx-receipt me-1"></i>Expense Declaration</h6>
      @if(auth()->user()->hasPermission('expense_declarations.create'))
        <div class="sf-actions"><a href="{{ route('erp.expense-declarations.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Expense Declaration</a></div>
      @endif
    </div>
    @if($declarations->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr><th>Expense Declaration Id</th><th>Record Type</th><th>Advance Request</th><th>Expense Type</th><th>Work Id</th><th class="text-end">Amount</th><th>Status</th></tr>
          </thead>
          <tbody>
            @foreach($declarations as $d)
              <tr>
                <td><a class="sf-link" href="{{ route('erp.expense-declarations.show', $d) }}">{{ $d->code }}</a></td>
                <td>{{ str_replace('_', ' ', $d->record_type) }}</td>
                <td>{{ $d->advanceRequest->code ?? '-' }}</td>
                <td>{{ $d->expense_type }}</td>
                <td>{{ $d->work->wid_code ?? '-' }}</td>
                <td class="text-end">{{ $money($d->converted_amount_idr) }}</td>
                <td><span class="badge {{ $statusBadge($d->status) }}">{{ $d->status }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>

@push('styles')
<style>
  .bp-jump a { font-size: .8rem; }
  .sf-card[id] { scroll-margin-top: 90px; }
</style>
@endpush

@push('scripts')
<script>
  // Another user changing anything listed here refreshes the page.
  window.addEventListener('erp-live-update', function (e) {
    if (['budget_parent', 'annual_budget_plan', 'sub_project', 'work_item', 'advance_request', 'expense_declaration'].includes(e.detail.record_type)) {
      location.reload();
    }
  });
</script>
@endpush
@endsection
