@extends('layouts.home')

@php
  $a = $advance;
  $isNew = ! $a->exists;
  $title = $isNew ? 'New Advance-Expense Declaration Non Project' : 'Edit '.$a->code;
  $val = fn (string $field) => old($field, $a->$field instanceof \Carbon\CarbonInterface ? $a->$field->format('Y-m-d') : $a->$field);
@endphp

@section('title', $title)

@include('erp.budget.partials.sf-style')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #d27d5b">

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-wallet"></i></span>
      <div><div class="sf-object">Advance-Expense Declaration Non Project</div><div class="sf-name">{{ $isNew ? 'New' : $a->code }} &middot; {{ $employee->name }}</div></div>
    </div>
    <a href="{{ $isNew ? route('erp.hr.employees.show', $employee) : route('erp.hr.employee-advances.show', $a) }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Cancel</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  @endif

  <form method="POST" action="{{ $isNew ? route('erp.hr.employee-advances.store') : route('erp.hr.employee-advances.update', $a) }}">
    @csrf
    @unless($isNew) @method('PUT') @endunless
    @if($isNew)<input type="hidden" name="employee_id" value="{{ $employee->id }}">@endif

    <div class="sf-card">
      <div class="sf-card-head"><h6>Information</h6></div>
      <div class="row g-4 p-3">
        <div class="col-lg-6">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Request By</label>
            <input class="form-control bg-light" value="{{ $employee->nik }} - {{ $employee->name }}{{ $employee->department ? ' ('.$employee->department.')' : '' }}" readonly>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select" required>
                @foreach(\App\Models\Erp\EmployeeAdvance::STATUSES as $s)
                  <option value="{{ $s }}" @selected($val('status') === $s)>{{ $s }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Advance Request Date</label>
              <input type="date" name="advance_request_date" class="form-control" value="{{ $val('advance_request_date') }}">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Advance Amount</label>
            <input type="number" step="0.01" min="0" name="advance_amount" class="form-control" value="{{ $val('advance_amount') }}">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ $val('description') }}</textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Expense Type</label>
            <select name="expense_type" class="form-select">
              <option value="">--None--</option>
              @foreach(\App\Models\Erp\EmployeeAdvance::EXPENSE_TYPES as $t)
                <option value="{{ $t }}" @selected($val('expense_type') === $t)>{{ $t }}</option>
              @endforeach
            </select>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Payment Status</label>
              <select name="payment_status" class="form-select">
                <option value="">--None--</option>
                @foreach(\App\Models\Erp\EmployeeAdvance::PAYMENT_STATUSES as $p)
                  <option value="{{ $p }}" @selected($val('payment_status') === $p)>{{ $p }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Payment Date</label>
              <input type="date" name="payment_date" class="form-control" value="{{ $val('payment_date') }}">
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Currency <span class="text-danger">*</span></label>
            <select name="currency" class="form-select" required>
              @foreach(['IDR' => 'IDR - Indonesian Rupiah', 'USD' => 'USD - US Dollar'] as $code => $label)
                <option value="{{ $code }}" @selected($val('currency') === $code)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Declaration Date</label>
            <input type="date" name="declaration_date" class="form-control" value="{{ $val('declaration_date') }}">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Declaration Amount</label>
            <input type="number" step="0.01" min="0" name="declaration_amount" class="form-control" value="{{ $val('declaration_amount') }}">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Description of Expense Declaration</label>
            <textarea name="declaration_description" class="form-control" rows="3">{{ $val('declaration_description') }}</textarea>
          </div>
        </div>
      </div>
      <div class="sf-card-foot d-flex justify-content-center gap-2 p-3">
        <button type="submit" class="btn btn-primary btn-sm">Save</button>
        @if($isNew)<button type="submit" name="save_new" value="1" class="btn btn-outline-primary btn-sm">Save &amp; New</button>@endif
        <a href="{{ $isNew ? route('erp.hr.employees.show', $employee) : route('erp.hr.employee-advances.show', $a) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
      </div>
    </div>
  </form>
</div>
@endsection
