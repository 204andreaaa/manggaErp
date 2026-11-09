@extends('layouts.home')

@section('title', 'Karyawan - ' . $employee->name)

@include('erp.budget.partials.sf-style')

@section('content')
@php
  $e = $employee;
  $money = fn ($v) => $v === null ? '' : 'IDR '.number_format((float) $v, 0, ',', '.');
  $date = fn ($d) => $d?->format('Y/m/d') ?? '';
  $canEdit = auth()->user()->hasPermission('employees.update');
  $left = [
    ['Employee Id', '<b>'.e($e->nik ?? '-').'</b>'],
    ['Full Name', e($e->name)],
    ['Gender', e(['male' => 'Male', 'female' => 'Female'][$e->gender] ?? '')],
    ['Date of Join', e($date($e->join_date))],
    ['Years of Services', e($e->join_date ? number_format($e->join_date->floatDiffInYears(now()), 2, ',', '.') : '')],
    ['Position', e($e->position ?? '')],
    ['Department Name', e($e->department ?? '')],
    ['Bank Account', e(collect([$e->bank_name, $e->bank_account_number, $e->bank_account_holder ? 'a.n '.$e->bank_account_holder : null])->filter()->implode(' '))],
  ];
  $right = [
    ['Employee Status', e(ucfirst($e->employment_status ?? ''))],
    ['End of Probation/ Contract', e($date($e->end_contract_date))],
    ['Email', $e->email ? '<a class="sf-link" href="mailto:'.e($e->email).'">'.e($e->email).'</a>' : ''],
    ['Phone', e($e->phone ?? '')],
    ['Akun Login', e($e->user?->username ?? '-')],
    ['IsActive', $e->status === 'active' ? '<i class="bx bx-check fs-5"></i>' : e(ucfirst($e->status))],
    ['Last Modified', e($e->updated_at?->format('Y/m/d H:i') ?? '')],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #1798c1">

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-user-pin"></i></span>
      <div><div class="sf-object">Manpower Detail</div><div class="sf-name">{{ $e->name }}</div></div>
    </div>
    <a href="{{ route('erp.hr.employees.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to List</a>
  </div>

  @include('erp.budget.partials.jump-bar', ['sections' => ['advexnpj' => ['Advance-Expense Declaration Non Project', $advances->count()]]])

  <div class="sf-card">
    <div class="sf-card-head"><h6>Employee Detail</h6></div>
    @include('erp.budget.partials.sf-fields', ['left' => $left, 'right' => $right])
  </div>

  <div class="sf-card" id="rel-advexnpj" style="--sf-accent: #d27d5b">
    <div class="sf-card-head">
      <h6><i class="bx bx-wallet me-1"></i>Advance-Expense Declaration Non Project</h6>
      @if($canEdit)
        <div class="sf-actions"><a href="{{ route('erp.hr.employee-advances.create', ['employee_id' => $e->id]) }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Advance-Expense Declaration Non Project</a></div>
      @endif
    </div>
    @if($advances->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr>
              @if($canEdit)<th>Action</th>@endif
              <th>Advance Expense Declaration NPJ ID</th><th>Expense Type</th><th>Description</th><th>Description of Expense Declaration</th>
              <th class="text-end">Advance Amount</th><th class="text-end">Declaration Amount</th><th>Payment</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($advances as $a)
              <tr>
                @if($canEdit)
                  <td class="text-nowrap">
                    <a class="sf-link" href="{{ route('erp.hr.employee-advances.edit', $a) }}">Edit</a> |
                    <form method="POST" action="{{ route('erp.hr.employee-advances.destroy', $a) }}" class="d-inline" onsubmit="return confirm('Hapus {{ $a->code }}?')">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-link p-0 sf-link align-baseline">Del</button>
                    </form>
                  </td>
                @endif
                <td class="text-nowrap"><a class="sf-link" href="{{ route('erp.hr.employee-advances.show', $a) }}">{{ $a->code }}</a></td>
                <td>{{ $a->expense_type ?: '-' }}</td>
                <td style="min-width: 260px; white-space: normal">{{ $a->description }}</td>
                <td style="min-width: 260px; white-space: normal">{{ $a->declaration_description }}</td>
                <td class="text-end text-nowrap">{{ $money($a->advance_amount) }}</td>
                <td class="text-end text-nowrap">{{ $money($a->declaration_amount) }}</td>
                <td>{{ $a->payment_status ?: '-' }}@if($a->payment_date)<div class="small text-muted">{{ $a->payment_date->format('Y/m/d') }}</div>@endif</td>
                <td>{{ $a->status }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection
