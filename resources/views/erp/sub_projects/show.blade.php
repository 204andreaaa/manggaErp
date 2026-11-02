@extends('layouts.home')

@section('title', 'Project - ' . $subProject->name)

@include('erp.budget.partials.sf-style')

@section('content')
@php
  $sp = $subProject;
  $bp = $sp->budgetParent;
  $money = fn ($v) => 'IDR '.number_format((float) $v, 0, ',', '.');
  $allocated = (float) $workItems->sum('allocated_budget');
  $remaining = (float) $workItems->sum('remaining_budget');
  $left = [
    ['Project Code', '<b>'.e($sp->sub_project_code).'</b>'],
    ['Project Name', e($sp->name)],
    ['Account (Budget Parent)', $bp ? '<a class="sf-link" href="'.e(route('erp.budget-parents.show', $bp)).'">'.e($bp->name).'</a>' : '-'],
    ['Last Modified', e($sp->updated_at?->format('Y/m/d H:i') ?? '-')],
  ];
  $right = [
    ['Allocated ke WID', '<b>'.e($money($allocated)).'</b>'],
    ['Terpakai', e($money($allocated - $remaining))],
    ['Remaining (semua WID)', '<b class="'.($remaining <= 0 ? 'text-danger' : 'text-success').'">'.e($money($remaining)).'</b>'],
    ['Jumlah WID', e($workItems->count())],
  ];
  $sections = ['works' => ['Works (WID)', $workItems->count()]] + \App\Services\WorkItemUsage::sections($usage);
@endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #7f8de1">

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-git-branch"></i></span>
      <div><div class="sf-object">Project (Sub Project)</div><div class="sf-name">{{ $sp->name }}</div></div>
    </div>
    <a href="{{ route('erp.sub-projects.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to List</a>
  </div>

  @include('erp.budget.partials.jump-bar', ['sections' => $sections])

  <div class="sf-card">
    <div class="sf-card-head"><h6>Project Detail</h6></div>
    @include('erp.budget.partials.sf-fields', ['left' => $left, 'right' => $right])
  </div>

  @include('erp.budget.partials.wid-list', ['workItems' => $workItems, 'money' => $money, 'showProject' => false])

  @include('erp.budget.partials.wid-related', ['usage' => $usage, 'money' => $money])
</div>

@push('scripts')
<script>
  // Another user changing anything listed here refreshes the page.
  window.addEventListener('erp-live-update', function (e) {
    if (['sub_project', 'work_item', 'annual_budget_plan', 'request_form', 'purchase_order', 'advance_request', 'expense_declaration'].includes(e.detail.record_type)) {
      location.reload();
    }
  });
</script>
@endpush
@endsection
