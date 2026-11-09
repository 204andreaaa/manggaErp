@extends('layouts.home')

@section('title', 'Karyawan - ' . $employee->name)

@include('erp.hr.partials.style')

@section('content')
@php
  $e = $employee;
  $money = fn ($v) => 'IDR '.number_format((float) $v, 0, ',', '.');
  $canEdit = auth()->user()->hasPermission('employees.update');
  $years = $e->join_date ? number_format($e->join_date->floatDiffInYears(now()), 1, ',', '.').' tahun' : '-';
  $bank = collect([$e->bank_name, $e->bank_account_number])->filter()->implode(' · ');
  $info = [
    ['bx-envelope', 'primary', 'Email', $e->email ?: '-'],
    ['bx-phone', 'success', 'Telepon', $e->phone ?: '-'],
    ['bx-calendar-check', 'info', 'Tanggal Bergabung', $e->join_date?->format('d M Y') ?? '-'],
    ['bx-time-five', 'warning', 'Masa Kerja', $years],
    ['bx-calendar-x', 'danger', 'Akhir Kontrak / Probation', $e->end_contract_date?->format('d M Y') ?? '-'],
    ['bx-credit-card', 'secondary', 'Rekening Bank', $bank ? $bank.($e->bank_account_holder ? ' a.n '.$e->bank_account_holder : '') : '-'],
    ['bx-key', 'primary', 'Akun Login', $e->user?->username ?? 'Belum terhubung'],
  ];
  $active = $advances->where('status', '!=', 'Cancelled');
  $stats = [
    ['bx-wallet', 'primary', 'Total Advance', $money($active->sum('advance_amount'))],
    ['bx-receipt', 'success', 'Total Dideklarasikan', $money($active->sum('declaration_amount'))],
    ['bx-hourglass', 'warning', 'Belum Dideklarasikan', $active->whereNull('declaration_amount')->count().' record'],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible d-flex align-items-center border-0 shadow-sm" role="alert">
      <i class="bx bx-check-circle fs-4 me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('erp.hr.employees.index') }}">Data Karyawan</a></li>
        <li class="breadcrumb-item active fw-semibold">{{ $e->name }}</li>
      </ol>
    </nav>
    <a href="{{ route('erp.hr.employees.index') }}" class="btn btn-sm btn-label-secondary"><i class="bx bx-arrow-back me-1"></i>Kembali</a>
  </div>

  {{-- Profile header --}}
  <div class="card hr-hero shadow-sm mb-4">
    <div class="hr-hero-banner"></div>
    <div class="hr-hero-body d-flex flex-wrap align-items-start gap-3">
      <div class="hr-avatar">{{ strtoupper(mb_substr($e->name, 0, 1)) }}</div>
      <div class="flex-grow-1">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
          <h4 class="fw-bold mb-0">{{ $e->name }}</h4>
          <span class="badge bg-label-{{ $e->status === 'active' ? 'success' : 'secondary' }} rounded-pill">{{ $e->status === 'active' ? 'Aktif' : ucfirst($e->status) }}</span>
          <span class="badge bg-label-primary rounded-pill">{{ ucfirst($e->employment_status ?? '-') }}</span>
        </div>
        <div class="hr-meta">
          <span><i class="bx bx-id-card"></i>{{ $e->nik ?? '-' }}</span>
          <span><i class="bx bx-briefcase"></i>{{ $e->position ?: 'Staff' }}</span>
          <span><i class="bx bx-buildings"></i>{{ $e->department ?: 'Departemen belum diisi' }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    {{-- Employee info --}}
    <div class="col-xl-4 col-lg-5">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header pb-2">
          <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-user"></i></span></span>Informasi Karyawan</h6>
        </div>
        <div class="card-body pt-0">
          <ul class="hr-info-list">
            @foreach($info as [$icon, $color, $label, $value])
              <li>
                <span class="hr-info-icon bg-label-{{ $color }}"><i class="bx {{ $icon }}"></i></span>
                <div class="min-w-0">
                  <div class="hr-info-label">{{ $label }}</div>
                  <div class="hr-info-value">{{ $value }}</div>
                </div>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>

    <div class="col-xl-8 col-lg-7">
      {{-- Totals --}}
      <div class="row g-3 mb-4">
        @foreach($stats as [$icon, $color, $label, $value])
          <div class="col-md-4">
            <div class="card hr-stat card-border-shadow-{{ $color }} shadow-sm">
              <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar flex-shrink-0"><span class="avatar-initial rounded bg-label-{{ $color }}"><i class="bx {{ $icon }} fs-4"></i></span></div>
                <div class="min-w-0">
                  <p class="hr-stat-label">{{ $label }}</p>
                  <p class="hr-stat-value text-{{ $color }}">{{ $value }}</p>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      {{-- ADVEXNPJ --}}
      <div class="card shadow-sm border-0" id="advexnpj">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-warning"><i class="bx bx-wallet"></i></span></span>Advance-Expense Declaration Non Project</h6>
            <small class="text-muted">Pinjaman, tunjangan, klaim &amp; pengeluaran lain untuk karyawan ini</small>
          </div>
          @if($canEdit)
            <a href="{{ route('erp.hr.employee-advances.create', ['employee_id' => $e->id]) }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Buat Baru</a>
          @endif
        </div>

        @if($advances->isEmpty())
          <div class="card-body text-center py-5">
            <div class="avatar avatar-xl mx-auto mb-3"><span class="avatar-initial rounded-circle bg-label-secondary"><i class="bx bx-receipt fs-2"></i></span></div>
            <h6 class="mb-1">Belum ada record</h6>
            <p class="text-muted small mb-0">No records to display</p>
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-hover hr-table mb-0">
              <thead class="table-light">
                <tr>
                  <th>ID &amp; Jenis</th>
                  <th>Keterangan</th>
                  <th class="text-end">Advance / Deklarasi</th>
                  <th>Status</th>
                  @if($canEdit)<th class="text-end"></th>@endif
                </tr>
              </thead>
              <tbody>
                @foreach($advances as $a)
                  @php [$typeColor, $typeIcon] = $a->typeStyle(); @endphp
                  <tr>
                    <td class="text-nowrap">
                      <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-sm flex-shrink-0"><span class="avatar-initial rounded bg-label-{{ $typeColor }}"><i class="bx {{ $typeIcon }}"></i></span></div>
                        <div>
                          <a class="hr-code" href="{{ route('erp.hr.employee-advances.show', $a) }}">{{ $a->code }}</a>
                          <div class="small text-muted">{{ $a->expense_type ?: 'Tanpa jenis' }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="hr-clamp small" title="{{ $a->description }}">{{ $a->description ?: '-' }}</div>
                      @if($a->advance_request_date)<div class="small text-muted mt-1"><i class="bx bx-calendar me-1"></i>{{ $a->advance_request_date->format('d M Y') }}</div>@endif
                    </td>
                    <td class="text-end text-nowrap">
                      <div class="fw-semibold">{{ $a->advance_amount !== null ? $money($a->advance_amount) : '-' }}</div>
                      @if($a->declaration_amount !== null)
                        <div class="small text-success fw-semibold"><i class="bx bx-check-circle me-1"></i>{{ $money($a->declaration_amount) }}</div>
                      @else
                        <div class="small text-muted">Belum dideklarasikan</div>
                      @endif
                    </td>
                    <td class="text-nowrap">
                      <span class="badge bg-label-{{ $a->statusColor() }}">{{ $a->status }}</span>
                      @if($a->payment_status)<div class="mt-1"><span class="badge bg-label-{{ $a->paymentColor() }} rounded-pill" style="font-size:.68rem">{{ $a->payment_status }}</span></div>@endif
                    </td>
                    @if($canEdit)
                      <td class="text-end text-nowrap">
                        <a href="{{ route('erp.hr.employee-advances.edit', $a) }}" class="btn btn-icon btn-sm btn-label-primary" title="Edit"><i class="bx bx-edit-alt"></i></a>
                        <form method="POST" action="{{ route('erp.hr.employee-advances.destroy', $a) }}" class="d-inline" onsubmit="return confirm('Hapus {{ $a->code }}?')">
                          @csrf @method('DELETE')
                          <button type="submit" class="btn btn-icon btn-sm btn-label-danger" title="Del"><i class="bx bx-trash"></i></button>
                        </form>
                      </td>
                    @endif
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
