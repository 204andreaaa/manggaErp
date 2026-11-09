@extends('layouts.home')

@section('title', $advance->code)

@include('erp.budget.partials.sf-style')

@section('content')
@php
  $a = $advance;
  $emp = $a->employee;
  $money = fn ($v) => $v === null ? '' : e($a->currency).' '.number_format((float) $v, 0, ',', '.');
  $date = fn ($d) => $d?->format('Y/m/d') ?? '';
  $left = [
    ['Advance Expense Declaration NPJ ID', '<b>'.e($a->code).'</b>'],
    ['Request By', $emp ? '<a class="sf-link" href="'.e(route('erp.hr.employees.show', $emp)).'">'.e($emp->nik.' - '.$emp->name).'</a>' : '-'],
    ['Department', e($a->department_name ?? '')],
    ['Status', e($a->status)],
    ['Advance Request Date', e($date($a->advance_request_date))],
    ['Advance Amount', e($money($a->advance_amount))],
    ['Description', nl2br(e($a->description ?? ''))],
    ['Expense Type', e($a->expense_type ?? '')],
    ['Payment Status', e($a->payment_status ?? '')],
    ['Payment Date', e($date($a->payment_date))],
  ];
  $right = [
    ['Currency', e($a->currency)],
    ['Declaration Date', e($date($a->declaration_date))],
    ['Declaration Amount', e($money($a->declaration_amount))],
    ['Description of Expense Declaration', nl2br(e($a->declaration_description ?? ''))],
    ['Last Modified By', e(($a->updatedBy?->name ?? '-').', '.$a->updated_at?->format('Y/m/d H:i'))],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #d27d5b">

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-wallet"></i></span>
      <div><div class="sf-object">Advance-Expense Declaration Non Project</div><div class="sf-name">{{ $a->code }}</div></div>
    </div>
    <div class="d-flex gap-2">
      @if(auth()->user()->hasPermission('employees.update'))
        <a href="{{ route('erp.hr.employee-advances.edit', $a) }}" class="btn btn-primary btn-sm"><i class="bx bx-edit-alt me-1"></i>Edit</a>
        <form method="POST" action="{{ route('erp.hr.employee-advances.destroy', $a) }}" onsubmit="return confirm('Hapus {{ $a->code }}?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bx bx-trash me-1"></i>Delete</button>
        </form>
      @endif
      @if($emp)
        <a href="{{ route('erp.hr.employees.show', $emp) }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to Employee</a>
      @endif
    </div>
  </div>

  <div class="sf-card">
    <div class="sf-card-head"><h6>Information</h6></div>
    @include('erp.budget.partials.sf-fields', ['left' => $left, 'right' => $right])
  </div>
</div>
@endsection
