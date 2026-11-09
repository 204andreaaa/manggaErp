@extends('layouts.home')

@php
  $a = $advance;
  $isNew = ! $a->exists;
  $title = $isNew ? 'New Advance-Expense Declaration Non Project' : 'Edit '.$a->code;
  $val = fn (string $field) => old($field, $a->$field instanceof \Carbon\CarbonInterface ? $a->$field->format('Y-m-d') : $a->$field);
  $cancelUrl = $isNew ? route('erp.hr.employees.show', $employee) : route('erp.hr.employee-advances.show', $a);
@endphp

@section('title', $title)

@include('erp.hr.partials.style')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('erp.hr.employees.index') }}">Data Karyawan</a></li>
      <li class="breadcrumb-item"><a href="{{ route('erp.hr.employees.show', $employee) }}">{{ $employee->name }}</a></li>
      <li class="breadcrumb-item active fw-semibold">{{ $isNew ? 'Buat Baru' : 'Edit '.$a->code }}</li>
    </ol>
  </nav>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible d-flex align-items-center border-0 shadow-sm" role="alert">
      <i class="bx bx-check-circle fs-4 me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  @endif

  <form method="POST" action="{{ $isNew ? route('erp.hr.employee-advances.store') : route('erp.hr.employee-advances.update', $a) }}">
    @csrf
    @unless($isNew) @method('PUT') @endunless
    @if($isNew)<input type="hidden" name="employee_id" value="{{ $employee->id }}">@endif

    {{-- Header: who it's for --}}
    <div class="card hr-hero shadow-sm mb-4">
      <div class="hr-hero-banner" style="height:64px"></div>
      <div class="hr-hero-body d-flex flex-wrap align-items-start gap-3">
        <div class="hr-avatar" style="width:64px;height:64px;font-size:1.6rem;border-radius:16px;margin-top:-44px">{{ strtoupper(mb_substr($employee->name, 0, 1)) }}</div>
        <div class="flex-grow-1">
          <small class="text-muted text-uppercase fw-semibold" style="letter-spacing:.05em">{{ $isNew ? 'New' : 'Edit' }} Advance-Expense Declaration Non Project</small>
          <h5 class="fw-bold mb-1">{{ $isNew ? 'Request By: '.$employee->name : $a->code.' · '.$employee->name }}</h5>
          <div class="hr-meta">
            <span><i class="bx bx-id-card"></i>{{ $employee->nik ?? '-' }}</span>
            <span><i class="bx bx-buildings"></i>{{ $employee->department ?: 'Departemen belum diisi' }}</span>
          </div>
        </div>
        <div style="min-width:200px">
          <label class="form-label small fw-semibold mb-1">Status <span class="text-danger">*</span></label>
          <select name="status" class="form-select" required>
            @foreach(\App\Models\Erp\EmployeeAdvance::STATUSES as $s)
              <option value="{{ $s }}" @selected($val('status') === $s)>{{ $s }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    <div class="row g-4">
      {{-- Advance --}}
      <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100 card-border-shadow-primary">
          <div class="card-header pb-2">
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-wallet"></i></span></span>Pengajuan (Advance)</h6>
            <small class="text-muted">Uang yang diminta / dibayarkan ke karyawan</small>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Advance Request Date</label>
                <input type="date" name="advance_request_date" class="form-control" value="{{ $val('advance_request_date') }}">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Advance Amount</label>
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <input type="number" step="0.01" min="0" name="advance_amount" class="form-control" value="{{ $val('advance_amount') }}" placeholder="0">
                </div>
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Expense Type</label>
                <select name="expense_type" class="form-select">
                  <option value="">--None--</option>
                  @foreach(\App\Models\Erp\EmployeeAdvance::EXPENSE_TYPES as $t)
                    <option value="{{ $t }}" @selected($val('expense_type') === $t)>{{ $t }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Contoh: Pinjaman karyawan Rp 3.000.000 untuk biaya berobat, cicilan potong gaji 5 bulan">{{ $val('description') }}</textarea>
              </div>
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
        </div>
      </div>

      {{-- Declaration --}}
      <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100 card-border-shadow-success">
          <div class="card-header pb-2">
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-success"><i class="bx bx-receipt"></i></span></span>Deklarasi (Expense Declaration)</h6>
            <small class="text-muted">Pertanggungjawaban pemakaian uangnya, boleh diisi belakangan</small>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Currency <span class="text-danger">*</span></label>
                <select name="currency" class="form-select" required>
                  @foreach(['IDR' => 'IDR - Indonesian Rupiah', 'USD' => 'USD - US Dollar'] as $code => $label)
                    <option value="{{ $code }}" @selected($val('currency') === $code)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Declaration Date</label>
                <input type="date" name="declaration_date" class="form-control" value="{{ $val('declaration_date') }}">
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Declaration Amount</label>
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <input type="number" step="0.01" min="0" name="declaration_amount" class="form-control" value="{{ $val('declaration_amount') }}" placeholder="0">
                </div>
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Description of Expense Declaration</label>
                <textarea name="declaration_description" class="form-control" rows="4" placeholder="Contoh: Kuitansi rumah sakit terlampir, total pemakaian Rp 2.750.000">{{ $val('declaration_description') }}</textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
      <div class="card-body d-flex flex-wrap justify-content-end gap-2 py-3">
        <a href="{{ $cancelUrl }}" class="btn btn-label-secondary">Batal</a>
        @if($isNew)<button type="submit" name="save_new" value="1" class="btn btn-label-primary"><i class="bx bx-plus me-1"></i>Save &amp; New</button>@endif
        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Save</button>
      </div>
    </div>
  </form>
</div>
@endsection
