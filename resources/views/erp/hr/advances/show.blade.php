@extends('layouts.home')

@section('title', $advance->code)

@include('erp.hr.partials.style')

@section('content')
@php
  $a = $advance;
  $emp = $a->employee;
  $money = fn ($v) => $v === null ? '-' : $a->currency.' '.number_format((float) $v, 0, ',', '.');
  $date = fn ($d) => $d?->format('d M Y') ?? '-';
  [$typeColor, $typeIcon] = $a->typeStyle();
  $diff = $a->difference();
  $diffText = $diff === null ? 'Belum dideklarasikan' : ($diff == 0 ? 'Pas, tidak ada selisih' : ($diff > 0 ? 'Sisa yang belum terpakai' : 'Kelebihan pemakaian'));
  $cards = [
    ['bx-wallet', 'primary', 'Advance', $money($a->advance_amount), $a->advance_request_date ? 'Diajukan '.$date($a->advance_request_date) : 'Tanggal belum diisi'],
    ['bx-receipt', 'success', 'Deklarasi', $money($a->declaration_amount), $a->declaration_date ? 'Dideklarasikan '.$date($a->declaration_date) : 'Belum ada deklarasi'],
    ['bx-transfer-alt', $diff === null ? 'secondary' : ($diff == 0 ? 'info' : ($diff > 0 ? 'warning' : 'danger')), 'Selisih', $diff === null ? '-' : $money(abs($diff)), $diffText],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible d-flex align-items-center border-0 shadow-sm" role="alert">
      <i class="bx bx-check-circle fs-4 me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('erp.hr.employees.index') }}">Data Karyawan</a></li>
      @if($emp)<li class="breadcrumb-item"><a href="{{ route('erp.hr.employees.show', $emp) }}">{{ $emp->name }}</a></li>@endif
      <li class="breadcrumb-item active fw-semibold">{{ $a->code }}</li>
    </ol>
  </nav>

  {{-- Header --}}
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
      <div class="avatar avatar-lg flex-shrink-0"><span class="avatar-initial rounded bg-label-{{ $typeColor }}"><i class="bx {{ $typeIcon }} fs-3"></i></span></div>
      <div class="flex-grow-1">
        <small class="text-muted text-uppercase fw-semibold" style="letter-spacing:.05em">Advance-Expense Declaration Non Project</small>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <h4 class="fw-bold mb-0 hr-code" style="font-size:1.4rem">{{ $a->code }}</h4>
          <span class="badge bg-label-{{ $a->statusColor() }}">{{ $a->status }}</span>
          @if($a->payment_status)<span class="badge bg-label-{{ $a->paymentColor() }} rounded-pill"><i class="bx bx-credit-card me-1"></i>{{ $a->payment_status }}</span>@endif
          @if($a->expense_type)<span class="badge bg-label-{{ $typeColor }} rounded-pill">{{ $a->expense_type }}</span>@endif
        </div>
      </div>
      <div class="d-flex gap-2">
        @if(auth()->user()->hasPermission('employees.update'))
          <a href="{{ route('erp.hr.employee-advances.edit', $a) }}" class="btn btn-primary btn-sm"><i class="bx bx-edit-alt me-1"></i>Edit</a>
          <form method="POST" action="{{ route('erp.hr.employee-advances.destroy', $a) }}" onsubmit="return confirm('Hapus {{ $a->code }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-label-danger btn-sm"><i class="bx bx-trash me-1"></i>Hapus</button>
          </form>
        @endif
      </div>
    </div>
  </div>

  {{-- Amounts --}}
  <div class="row g-3 mb-4">
    @foreach($cards as [$icon, $color, $label, $value, $note])
      <div class="col-md-4">
        <div class="card hr-stat card-border-shadow-{{ $color }} shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
              <div class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-{{ $color }}"><i class="bx {{ $icon }}"></i></span></div>
              <span class="hr-stat-label">{{ $label }}</span>
            </div>
            <p class="hr-stat-value text-{{ $color }}">{{ $value }}</p>
            <small class="text-muted">{{ $note }}</small>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="row g-4">
    {{-- Advance --}}
    <div class="col-lg-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header pb-2">
          <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-wallet"></i></span></span>Pengajuan (Advance)</h6>
        </div>
        <div class="card-body pt-0">
          <ul class="hr-info-list">
            <li>
              <span class="hr-info-icon bg-label-primary"><i class="bx bx-user"></i></span>
              <div><div class="hr-info-label">Request By</div>
                <div class="hr-info-value">@if($emp)<a href="{{ route('erp.hr.employees.show', $emp) }}">{{ $emp->nik }} · {{ $emp->name }}</a>@else - @endif</div>
                <small class="text-muted">{{ $a->department_name ?: 'Tanpa departemen' }}</small></div>
            </li>
            <li><span class="hr-info-icon bg-label-info"><i class="bx bx-calendar"></i></span><div><div class="hr-info-label">Advance Request Date</div><div class="hr-info-value">{{ $date($a->advance_request_date) }}</div></div></li>
            <li><span class="hr-info-icon bg-label-{{ $typeColor }}"><i class="bx {{ $typeIcon }}"></i></span><div><div class="hr-info-label">Expense Type</div><div class="hr-info-value">{{ $a->expense_type ?: '-' }}</div></div></li>
            <li><span class="hr-info-icon bg-label-{{ $a->paymentColor() }}"><i class="bx bx-credit-card"></i></span><div><div class="hr-info-label">Payment</div><div class="hr-info-value">{{ $a->payment_status ?: '-' }}@if($a->payment_date) · {{ $date($a->payment_date) }}@endif</div></div></li>
            <li><span class="hr-info-icon bg-label-secondary"><i class="bx bx-detail"></i></span><div class="min-w-0"><div class="hr-info-label">Description</div><div class="hr-info-value fw-normal">{!! nl2br(e($a->description ?: '-')) !!}</div></div></li>
          </ul>
        </div>
      </div>
    </div>

    {{-- Declaration --}}
    <div class="col-lg-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header pb-2">
          <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-success"><i class="bx bx-receipt"></i></span></span>Deklarasi (Expense Declaration)</h6>
        </div>
        <div class="card-body pt-0">
          <ul class="hr-info-list">
            <li><span class="hr-info-icon bg-label-success"><i class="bx bx-calendar-check"></i></span><div><div class="hr-info-label">Declaration Date</div><div class="hr-info-value">{{ $date($a->declaration_date) }}</div></div></li>
            <li><span class="hr-info-icon bg-label-success"><i class="bx bx-money"></i></span><div><div class="hr-info-label">Declaration Amount</div><div class="hr-info-value">{{ $money($a->declaration_amount) }}</div></div></li>
            <li><span class="hr-info-icon bg-label-secondary"><i class="bx bx-detail"></i></span><div class="min-w-0"><div class="hr-info-label">Description of Expense Declaration</div><div class="hr-info-value fw-normal">{!! nl2br(e($a->declaration_description ?: '-')) !!}</div></div></li>
            <li><span class="hr-info-icon bg-label-info"><i class="bx bx-dollar-circle"></i></span><div><div class="hr-info-label">Currency</div><div class="hr-info-value">{{ $a->currency }}</div></div></li>
            <li><span class="hr-info-icon bg-label-warning"><i class="bx bx-history"></i></span><div><div class="hr-info-label">Last Modified By</div><div class="hr-info-value">{{ $a->updatedBy?->name ?? '-' }}</div><small class="text-muted">{{ $a->updated_at?->format('d M Y H:i') }}</small></div></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
