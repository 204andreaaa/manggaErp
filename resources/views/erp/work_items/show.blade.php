@extends('layouts.home')

@section('title', 'Work (WID) - ' . $workItem->wid_code)

@include('erp.budget.partials.sf-style')

@section('content')
@php
  $w = $workItem;
  $money = fn ($v) => 'IDR '.number_format((float) $v, 0, ',', '.');
  $sp = $w->subProject;
  $bp = $sp?->budgetParent;
  $left = [
    ['Work Id', '<b>'.e($w->wid_code).'</b>'],
    ['Work Name', e($w->name)],
    ['Project', $sp ? '<a class="sf-link" href="'.e(route('erp.sub-projects.show', $sp)).'">'.e($sp->sub_project_code.' - '.$sp->name).'</a>' : '-'],
    ['Account (Budget Parent)', $bp ? '<a class="sf-link" href="'.e(route('erp.budget-parents.show', $bp)).'">'.e($bp->name).'</a>' : '-'],
  ];
  $right = [
    ['Allocated Budget', '<b>'.e($money($w->allocated_budget)).'</b>'],
    ['Terpakai', e($money((float) $w->allocated_budget - (float) $w->remaining_budget))],
    ['Remaining Budget', '<b class="'.((float) $w->remaining_budget <= 0 ? 'text-danger' : 'text-success').'">'.e($money($w->remaining_budget)).'</b>'],
    ['Last Modified', e($w->updated_at?->format('Y/m/d H:i') ?? '-')],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #4bc076">

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-task"></i></span>
      <div><div class="sf-object">Work (WID)</div><div class="sf-name">{{ $w->wid_code }} &middot; {{ $w->name }}</div></div>
    </div>
    <a href="{{ route('erp.work-items.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to List</a>
  </div>

  @include('erp.budget.partials.jump-bar', ['sections' => \App\Services\WorkItemUsage::sections($usage)])

  <div class="sf-card">
    <div class="sf-card-head"><h6>Work Detail</h6></div>
    @include('erp.budget.partials.sf-fields', ['left' => $left, 'right' => $right])
  </div>

  @include('erp.budget.partials.wid-related', ['usage' => $usage, 'money' => $money, 'showWid' => false])
</div>

@push('scripts')
<script>
  // Another user changing anything listed here refreshes the page.
  window.addEventListener('erp-live-update', function (e) {
    if (['work_item', 'annual_budget_plan', 'request_form', 'purchase_order', 'advance_request', 'expense_declaration'].includes(e.detail.record_type)) {
      location.reload();
    }
  });
</script>
@endpush
@endsection
