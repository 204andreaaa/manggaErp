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
  $sections = [
    'projects' => ['Projects', $bp->subProjects->count()],
    'works' => ['Works (WID)', $workItems->count()],
  ] + \App\Services\WorkItemUsage::sections($usage);
@endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #e0a63a">

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-buildings"></i></span>
      <div><div class="sf-object">Account (Budget Parent)</div><div class="sf-name">{{ $bp->name }}</div></div>
    </div>
    <a href="{{ route('erp.budget-parents.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to List</a>
  </div>

  @include('erp.budget.partials.jump-bar', ['sections' => $sections])

  <div class="sf-card">
    <div class="sf-card-head"><h6>Account Detail</h6></div>
    @include('erp.budget.partials.sf-fields', ['left' => $left, 'right' => $right])
  </div>

  {{-- Projects (Sub Projects) --}}
  <div class="sf-card" id="rel-projects" style="--sf-accent: #7f8de1">
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
                <td><a class="sf-link fw-semibold" href="{{ route('erp.sub-projects.show', $sp) }}">{{ $sp->sub_project_code }}</a></td>
                <td>{{ $sp->name }}</td>
                <td class="text-end">{{ $sp->work_items_count }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  @include('erp.budget.partials.wid-list', ['workItems' => $workItems, 'money' => $money])

  @include('erp.budget.partials.wid-related', ['usage' => $usage, 'money' => $money])
</div>

@push('scripts')
<script>
  // Another user changing anything listed here refreshes the page.
  window.addEventListener('erp-live-update', function (e) {
    if (['budget_parent', 'annual_budget_plan', 'sub_project', 'work_item', 'request_form', 'purchase_order', 'advance_request', 'expense_declaration'].includes(e.detail.record_type)) {
      location.reload();
    }
  });
</script>
@endpush
@endsection
