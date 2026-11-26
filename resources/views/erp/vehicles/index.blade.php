@extends('layouts.home')

@section('title', 'Kendaraan')

@include('erp.hr.partials.style')

@section('content')
@php
  $date = fn ($d) => $d?->format('d M Y') ?? '-';
  $cards = [
    ['bx-car', 'primary', 'Kendaraan Aktif', $stats['active'], $stats['own'].' milik sendiri · '.$stats['rent'].' sewa', null],
    ['bx-time-five', 'danger', 'Sewa Habis', $stats['expired'], 'Kendaraan sewa aktif yang masa sewanya lewat', 'expired'],
    ['bx-id-card', 'warning', 'STNK / Pajak', $stats['documents'], 'Sudah lewat atau habis dalam '.\App\Models\Erp\Vehicle::EXPIRY_WARNING_DAYS.' hari', 'documents'],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y">

  @foreach(['success' => 'bx-check-circle', 'error' => 'bx-error-circle'] as $key => $icon)
    @if(session($key))
      <div class="alert alert-{{ $key === 'error' ? 'danger' : 'success' }} alert-dismissible d-flex align-items-center border-0 shadow-sm" role="alert">
        <i class="bx {{ $icon }} fs-4 me-2"></i>{{ session($key) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
  @endforeach

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <div class="avatar avatar-lg"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-car fs-3"></i></span></div>
      <div>
        <h4 class="fw-bold mb-0">Kendaraan</h4>
        <small class="text-muted">Mobil & motor milik sendiri (Own) dan sewa (Rent)</small>
      </div>
    </div>
    @if(auth()->user()->hasPermission('vehicles.create'))
      <a href="{{ route('erp.vehicles.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Kendaraan Baru</a>
    @endif
  </div>

  <div class="row g-3 mb-4">
    @foreach($cards as [$icon, $color, $label, $value, $note, $alert])
      <div class="col-md-4">
        <a href="{{ $alert ? request()->fullUrlWithQuery(['alert' => request('alert') === $alert ? null : $alert]) : route('erp.vehicles.index') }}" class="text-reset">
          <div class="card hr-stat card-border-shadow-{{ $color }} shadow-sm {{ $alert && request('alert') === $alert ? 'border border-'.$color : '' }}">
            <div class="card-body">
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-{{ $color }}"><i class="bx {{ $icon }}"></i></span></div>
                <span class="hr-stat-label">{{ $label }}</span>
              </div>
              <p class="hr-stat-value text-{{ $color }}">{{ $value }}</p>
              <small class="text-muted">{{ $note }}</small>
            </div>
          </div>
        </a>
      </div>
    @endforeach
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-header">
      <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
        <div class="input-group input-group-sm" style="max-width:300px">
          <span class="input-group-text"><i class="bx bx-search"></i></span>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari plat, merk, lokasi, PIC...">
        </div>
        @if(request('alert'))<input type="hidden" name="alert" value="{{ request('alert') }}">@endif
        <div class="btn-group btn-group-sm" role="group">
          @foreach(['' => 'Semua', 'Own' => 'Own', 'Rent' => 'Rent'] as $value => $label)
            <a href="{{ request()->fullUrlWithQuery(['ownership' => $value ?: null]) }}"
               class="btn {{ (string) request('ownership') === $value ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
          @endforeach
        </div>
        <select name="active" class="form-select form-select-sm" style="max-width:160px" onchange="this.form.submit()">
          <option value="1" @selected(request('active', '1') === '1')>Aktif</option>
          <option value="0" @selected(request('active') === '0')>Tidak aktif</option>
          <option value="all" @selected(request('active') === 'all')>Semua</option>
        </select>
        @if(request()->hasAny(['q', 'ownership', 'active', 'alert']))
          <a href="{{ route('erp.vehicles.index') }}" class="btn btn-sm btn-text-secondary"><i class="bx bx-x me-1"></i>Reset</a>
        @endif
      </form>
    </div>

    @if($vehicles->isEmpty())
      @include('erp.hr.partials.empty', ['icon' => 'bx-car', 'title' => 'Belum ada kendaraan', 'text' => 'Kendaraan yang didaftarkan akan muncul di sini.'])
    @else
      <div class="table-responsive">
        <table class="table table-hover hr-table mb-0">
          <thead>
            <tr><th>Plat</th><th>Jenis</th><th>Kendaraan</th><th>Lokasi</th><th>Sewa</th><th>STNK</th><th>Pajak</th></tr>
          </thead>
          <tbody>
            @foreach($vehicles as $v)
              @php
                $stnk = \App\Models\Erp\Vehicle::expiryState($v->stnk_expiry_date);
                $tax = \App\Models\Erp\Vehicle::expiryState($v->tax_expiry_date);
              @endphp
              <tr style="cursor:pointer" onclick="window.location='{{ route('erp.vehicles.show', $v) }}'">
                <td>
                  <a href="{{ route('erp.vehicles.show', $v) }}" class="fw-semibold hr-code text-nowrap">{{ $v->plate_number }}</a>
                  @unless($v->is_active)<div class="small text-danger">tidak aktif</div>@endunless
                </td>
                <td><span class="badge bg-label-{{ $v->isRent() ? 'info' : 'primary' }}">{{ $v->ownership }}</span><div class="small text-muted">{{ $v->kind }}</div></td>
                <td>{{ trim($v->brand.' '.$v->model) ?: '-' }}<div class="small text-muted">{{ $v->production_year }}@if($v->subProject) · {{ $v->subProject->sub_project_code }}@endif</div></td>
                <td>{{ $v->location ?: '-' }}<div class="small text-muted">{{ $v->pic }}</div></td>
                <td class="text-nowrap">
                  @if($v->isRent())
                    <span class="badge bg-label-{{ $v->rent_status === 'Expired' ? 'danger' : 'success' }}">{{ $v->rent_status }}</span>
                    <div class="small text-muted">s/d {{ $date($v->rent_end) }}</div>
                  @else <span class="text-muted">-</span> @endif
                </td>
                <td class="text-nowrap"><span class="badge bg-label-{{ \App\Models\Erp\Vehicle::expiryColor($stnk) }}">{{ $date($v->stnk_expiry_date) }}</span></td>
                <td class="text-nowrap"><span class="badge bg-label-{{ \App\Models\Erp\Vehicle::expiryColor($tax) }}">{{ $date($v->tax_expiry_date) }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-footer py-2 small text-muted">{{ $vehicles->count() }} kendaraan</div>
    @endif
  </div>
</div>
@endsection
