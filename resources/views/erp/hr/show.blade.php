@extends('layouts.home')

@section('title', 'Karyawan - ' . $employee->name)

@include('erp.hr.partials.style')

@section('content')
@php
  $e = $employee;
  $money = fn ($v) => $v === null ? '-' : 'IDR '.number_format((float) $v, 0, ',', '.');
  $canEdit = auth()->user()->hasPermission('employees.update');
  $badge = fn ($s) => match ($s) {
      'Approved', 'Completed', 'Paid', 'Closed' => 'success',
      'Rejected', 'Cancelled' => 'danger',
      'Submitted', 'Pending', 'Waiting Approval', 'Unpaid' => 'warning',
      default => 'secondary',
  };
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
  $declarationCount = $kasbons->sum(fn ($k) => $k->expenseDeclarations->count()) + $standaloneDeclarations->count();
  // [tab key, label, icon, colour, count]; only tabs the viewer may see
  $tabList = collect([
    ['summary', 'Ringkasan', 'bx-grid-alt', 'primary', null],
    ['rf', 'Request Form', 'bx-file', 'info', $requestForms->count()],
    ['kasbon', 'Kasbon Project', 'bx-wallet', 'warning', $kasbons->count() + $standaloneDeclarations->count()],
    ['advexnpj', 'ADVEXNPJ Non Project', 'bx-money', 'success', $advances->count()],
    ['leave', 'Cuti', 'bx-calendar-event', 'secondary', null],
  ])->filter(fn ($t) => $t[0] === 'summary' || $tabs[$t[0]]);
  $kinds = [
    'rf' => ['Request Form', 'bx-file', 'info'],
    'kasbon' => ['Advance Request', 'bx-wallet', 'warning'],
    'declaration' => ['Expense Declaration', 'bx-receipt', 'primary'],
    'advexnpj' => ['ADVEXNPJ', 'bx-money', 'success'],
  ];
  $year = now()->year;
  $cards = collect([
    $tabs['rf'] ? ['bx-file', 'info', 'Request Form '.$year, $requestForms->filter(fn ($r) => $r->rf_date?->year === $year)->count().' RF', $money($requestForms->filter(fn ($r) => $r->rf_date?->year === $year)->sum('total_amount'))] : null,
    $tabs['kasbon'] ? ['bx-wallet', 'warning', 'Kasbon Project', $kasbons->count().' kasbon', $declarationCount.' deklarasi'] : null,
    $tabs['advexnpj'] ? ['bx-money', 'success', 'ADVEXNPJ', $money($advances->where('status', '!=', 'Cancelled')->sum('advance_amount')), $advances->count().' record'] : null,
    ['bx-calendar-event', 'secondary', 'Sisa Cuti', 'Soon', 'Leave Request segera hadir'],
  ])->filter();
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
        @if(auth()->user()->hasPermission('employees.view'))<li class="breadcrumb-item"><a href="{{ route('erp.hr.employees.index') }}">Data Karyawan</a></li>@endif
        <li class="breadcrumb-item active fw-semibold">{{ $e->name }}</li>
      </ol>
    </nav>
    @if(auth()->user()->hasPermission('employees.view'))
      <a href="{{ route('erp.hr.employees.index') }}" class="btn btn-sm btn-label-secondary"><i class="bx bx-arrow-back me-1"></i>Kembali</a>
    @endif
  </div>

  {{-- Profile header + tabs --}}
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
    <div class="hr-tabs px-3">
      <ul class="nav nav-tabs border-0" role="tablist">
        @foreach($tabList as [$key, $label, $icon, $color, $count])
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="tab-{{ $key }}-btn" data-bs-toggle="tab" data-bs-target="#tab-{{ $key }}" data-hash="{{ $key }}" type="button" role="tab">
              <i class="bx {{ $icon }} me-1"></i>{{ $label }}
              @if($count !== null)<span class="badge rounded-pill bg-label-{{ $color }} ms-1">{{ $count }}</span>@endif
              @if($key === 'leave')<span class="badge rounded-pill bg-label-secondary ms-1">Soon</span>@endif
            </button>
          </li>
        @endforeach
      </ul>
    </div>
  </div>

  <div class="tab-content p-0 bg-transparent shadow-none">

    {{-- ===== Ringkasan ===== --}}
    <div class="tab-pane fade show active" id="tab-summary" role="tabpanel">
      <div class="row g-4">
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
                    <div class="min-w-0"><div class="hr-info-label">{{ $label }}</div><div class="hr-info-value">{{ $value }}</div></div>
                  </li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>

        <div class="col-xl-8 col-lg-7">
          <div class="row g-3 mb-4">
            @foreach($cards as [$icon, $color, $label, $value, $note])
              <div class="col-sm-6 col-xxl-3">
                <div class="card hr-stat card-border-shadow-{{ $color }} shadow-sm">
                  <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <div class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-{{ $color }}"><i class="bx {{ $icon }}"></i></span></div>
                      <span class="hr-stat-label">{{ $label }}</span>
                    </div>
                    <p class="hr-stat-value text-{{ $color === 'secondary' ? 'muted' : $color }}" style="font-size:1.1rem">{{ $value }}</p>
                    <small class="text-muted">{{ $note }}</small>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          <div class="card shadow-sm border-0">
            <div class="card-header pb-2">
              <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-info"><i class="bx bx-pulse"></i></span></span>Aktivitas Terbaru</h6>
            </div>
            <div class="card-body pt-2">
              @forelse($activity as [$kind, $date, $code, $desc, $amount, $status, $url])
                @php [$kindLabel, $kindIcon, $kindColor] = $kinds[$kind]; @endphp
                <a href="{{ $url }}" class="hr-activity d-flex align-items-center gap-3 text-reset">
                  <div class="avatar avatar-sm flex-shrink-0"><span class="avatar-initial rounded-circle bg-label-{{ $kindColor }}"><i class="bx {{ $kindIcon }}"></i></span></div>
                  <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                      <span class="hr-code text-{{ $kindColor }}">{{ $code }}</span>
                      <small class="text-muted">{{ $kindLabel }}</small>
                    </div>
                    <div class="small text-truncate text-body">{{ $desc ?: '-' }}</div>
                  </div>
                  <div class="text-end flex-shrink-0">
                    <div class="small fw-semibold">{{ $money($amount) }}</div>
                    <span class="badge bg-label-{{ $badge($status) }}" style="font-size:.66rem">{{ $status }}</span>
                  </div>
                  <small class="text-muted flex-shrink-0 d-none d-md-block" style="width:72px;text-align:right">{{ $date?->format('d M y') }}</small>
                </a>
              @empty
                <div class="text-center text-muted py-4"><i class="bx bx-ghost fs-2 d-block mb-1"></i>Belum ada aktivitas</div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- ===== Request Form ===== --}}
    @if($tabs['rf'])
      <div class="tab-pane fade" id="tab-rf" role="tabpanel">
        <div class="card shadow-sm border-0">
          <div class="card-header">
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-info"><i class="bx bx-file"></i></span></span>Request Form</h6>
            <small class="text-muted">RF yang dibuat oleh {{ $e->name }}</small>
          </div>
          @if(! $e->user_id)
            @include('erp.hr.partials.empty', ['icon' => 'bx-key', 'title' => 'Belum punya akun login', 'text' => 'RF hanya bisa dibuat lewat akun login.'])
          @elseif($requestForms->isEmpty())
            @include('erp.hr.partials.empty', ['icon' => 'bx-file', 'title' => 'Belum ada Request Form', 'text' => 'No records to display'])
          @else
            <div class="table-responsive">
              <table class="table table-hover hr-table mb-0">
                <thead class="table-light"><tr><th>RF No</th><th>Tanggal</th><th>Tipe</th><th>Keterangan</th><th class="text-end">Total</th><th>Status</th></tr></thead>
                <tbody>
                  @foreach($requestForms as $rf)
                    <tr>
                      <td class="text-nowrap"><a class="hr-code" href="{{ route('erp.request-form.show', $rf) }}">{{ $rf->rf_no }}</a></td>
                      <td class="text-nowrap">{{ $rf->rf_date?->format('d M Y') ?? '-' }}</td>
                      <td class="text-nowrap"><span class="badge bg-label-{{ $rf->record_type === 'project' ? 'primary' : 'info' }}">{{ $rf->record_type_label }}</span>
                        @if($rf->project_code || $rf->department_name)<div class="small text-muted mt-1">{{ $rf->project_code ?: $rf->department_name }}</div>@endif</td>
                      <td><div class="hr-clamp small" title="{{ $rf->remark }}">{{ $rf->remark ?: '-' }}</div></td>
                      <td class="text-end text-nowrap fw-semibold">{{ $money($rf->total_amount) }}</td>
                      <td><span class="badge bg-label-{{ $badge($rf->status) }}">{{ $rf->status }}</span></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    @endif

    {{-- ===== Kasbon Project: advance requests with their declarations underneath ===== --}}
    @if($tabs['kasbon'])
      <div class="tab-pane fade" id="tab-kasbon" role="tabpanel">
        <div class="card shadow-sm border-0">
          <div class="card-header">
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-warning"><i class="bx bx-wallet"></i></span></span>Kasbon Project</h6>
            <small class="text-muted">Advance Request &amp; Expense Declaration yang diajukan oleh {{ $e->name }}</small>
          </div>
          @if(! $e->user_id)
            @include('erp.hr.partials.empty', ['icon' => 'bx-key', 'title' => 'Belum punya akun login', 'text' => 'Kasbon project hanya bisa diajukan lewat akun login.'])
          @elseif($kasbons->isEmpty() && $standaloneDeclarations->isEmpty())
            @include('erp.hr.partials.empty', ['icon' => 'bx-wallet', 'title' => 'Belum ada kasbon project', 'text' => 'No records to display'])
          @else
            <div class="table-responsive">
              <table class="table table-hover hr-table mb-0">
                <thead class="table-light"><tr><th>Kode</th><th>Tanggal</th><th>Keterangan</th><th class="text-end">Nominal</th><th>Status</th></tr></thead>
                <tbody>
                  @foreach($kasbons as $k)
                    <tr>
                      <td class="text-nowrap">
                        <div class="d-flex align-items-center gap-2">
                          <div class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-warning"><i class="bx bx-wallet"></i></span></div>
                          <div><a class="hr-code" href="{{ route('erp.advance-requests.show', $k) }}">{{ $k->code }}</a><div class="small text-muted">Advance Request</div></div>
                        </div>
                      </td>
                      <td class="text-nowrap">{{ $k->advance_request_date?->format('d M Y') ?? '-' }}</td>
                      <td><div class="hr-clamp small" title="{{ $k->description }}">{{ $k->description ?: '-' }}</div></td>
                      <td class="text-end text-nowrap">
                        <div class="fw-semibold">{{ $money($k->total_amount_advance) }}</div>
                        {{-- the declarations themselves are listed on the advance request's page --}}
                        <div class="small text-muted">{{ $k->expenseDeclarations->count() ? $k->expenseDeclarations->count().' deklarasi' : 'Belum ada deklarasi' }}</div>
                      </td>
                      <td class="text-nowrap"><span class="badge bg-label-{{ $badge($k->status) }}">{{ $k->status }}</span>
                        @if($k->payment_status)<div class="mt-1"><span class="badge rounded-pill bg-label-{{ $badge($k->payment_status) }}" style="font-size:.66rem">{{ $k->payment_status }}</span>
                          @if($k->settled_at)<span class="badge rounded-pill bg-label-dark" style="font-size:.66rem">Closed</span>@endif</div>@endif</td>
                    </tr>
                  @endforeach
                  @foreach($standaloneDeclarations as $d)
                    <tr>
                      <td class="text-nowrap">
                        <div class="d-flex align-items-center gap-2">
                          <div class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-receipt"></i></span></div>
                          <div><a class="hr-code" href="{{ route('erp.expense-declarations.show', $d) }}">{{ $d->code }}</a><div class="small text-muted">Expense Declaration · tanpa kasbon</div></div>
                        </div>
                      </td>
                      <td class="text-nowrap">{{ $d->request_date?->format('d M Y') ?? '-' }}</td>
                      <td><div class="hr-clamp small" title="{{ $d->description }}">{{ $d->description ?: '-' }}</div></td>
                      <td class="text-end text-nowrap fw-semibold">{{ $money($d->amount_declaration) }}</td>
                      <td><span class="badge bg-label-{{ $badge($d->status) }}">{{ $d->status }}</span></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    @endif

    {{-- ===== ADVEXNPJ ===== --}}
    @if($tabs['advexnpj'])
      <div class="tab-pane fade" id="tab-advexnpj" role="tabpanel">
        <div class="card shadow-sm border-0" id="advexnpj">
          <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-success"><i class="bx bx-money"></i></span></span>Advance-Expense Declaration Non Project</h6>
              <small class="text-muted">Pinjaman, tunjangan, klaim &amp; pengeluaran lain untuk karyawan ini</small>
            </div>
            @if($canEdit)
              <a href="{{ route('erp.hr.employee-advances.create', ['employee_id' => $e->id]) }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Buat Baru</a>
            @endif
          </div>
          @if($advances->isEmpty())
            @include('erp.hr.partials.empty', ['icon' => 'bx-receipt', 'title' => 'Belum ada record', 'text' => 'No records to display'])
          @else
            <div class="table-responsive">
              <table class="table table-hover hr-table mb-0">
                <thead class="table-light">
                  <tr><th>ID &amp; Jenis</th><th>Keterangan</th><th class="text-end">Advance / Deklarasi</th><th>Status</th>@if($canEdit)<th class="text-end"></th>@endif</tr>
                </thead>
                <tbody>
                  @foreach($advances as $a)
                    @php [$typeColor, $typeIcon] = $a->typeStyle(); @endphp
                    <tr>
                      <td class="text-nowrap">
                        <div class="d-flex align-items-center gap-2">
                          <div class="avatar avatar-sm flex-shrink-0"><span class="avatar-initial rounded bg-label-{{ $typeColor }}"><i class="bx {{ $typeIcon }}"></i></span></div>
                          <div><a class="hr-code" href="{{ route('erp.hr.employee-advances.show', $a) }}">{{ $a->code }}</a><div class="small text-muted">{{ $a->expense_type ?: 'Tanpa jenis' }}</div></div>
                        </div>
                      </td>
                      <td>
                        <div class="hr-clamp small" title="{{ $a->description }}">{{ $a->description ?: '-' }}</div>
                        @if($a->advance_request_date)<div class="small text-muted mt-1"><i class="bx bx-calendar me-1"></i>{{ $a->advance_request_date->format('d M Y') }}</div>@endif
                      </td>
                      <td class="text-end text-nowrap">
                        <div class="fw-semibold">{{ $money($a->advance_amount) }}</div>
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
    @endif

    {{-- ===== Cuti (coming soon) ===== --}}
    <div class="tab-pane fade" id="tab-leave" role="tabpanel">
      <div class="card shadow-sm border-0">
        <div class="card-body text-center py-5">
          <div class="avatar avatar-xl mx-auto mb-3"><span class="avatar-initial rounded-circle bg-label-secondary"><i class="bx bx-calendar-event fs-2"></i></span></div>
          <h5 class="mb-1">Leave Request segera hadir</h5>
          <p class="text-muted mb-0">Pengajuan cuti, sisa cuti, dan riwayatnya akan tampil di tab ini.</p>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  // Keep the open tab in the address (#rf, #kasbon, ...) so refresh and Back return to it.
  (function () {
    const buttons = document.querySelectorAll('[data-hash]');
    const open = (hash) => {
      const btn = document.querySelector('[data-hash="' + hash + '"]');
      if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
    };
    buttons.forEach((btn) => btn.addEventListener('shown.bs.tab', () => history.replaceState(null, '', '#' + btn.dataset.hash)));
    if (location.hash) open(location.hash.slice(1));
  })();
</script>
@endpush
@endsection
