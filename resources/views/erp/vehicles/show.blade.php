@extends('layouts.home')

@section('title', 'Kendaraan - '.$vehicle->plate_number)

@include('erp.hr.partials.style')

@section('content')
@php
  $v = $vehicle;
  $money = fn ($x) => 'Rp '.number_format((float) $x, 0, ',', '.');
  $date = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
  $user = auth()->user();
  $stnk = \App\Models\Erp\Vehicle::expiryState($v->stnk_expiry_date);
  $tax = \App\Models\Erp\Vehicle::expiryState($v->tax_expiry_date);
  $days = $rentEnd ? (int) today()->diffInDays($rentEnd, false) : null;
  $costTotal = $totals->sum();
  $cards = $v->isRent()
    ? [
        ['bx-transfer', 'info', 'Total Sewa', $money($totals['Sewa']), $money($v->rent_price).' / '.strtolower($v->rent_period ?? 'bulan')],
        ['bx-wrench', 'warning', 'Total Servis', $money($totals['Servis']), 'Biaya lain '.$money($totals['Lainnya'])],
        ['bx-time-five', $rentStatus === 'Expired' ? 'danger' : 'success', 'Masa Sewa', $rentStatus,
          $rentEnd ? 's/d '.$date($rentEnd).' · '.($days < 0 ? 'lewat '.abs($days).' hari' : 'sisa '.$days.' hari') : '-'],
      ]
    : [
        ['bx-key', 'primary', 'Harga Beli', $money($v->purchase_price), 'Dibeli '.$date($v->purchase_date)],
        ['bx-wrench', 'warning', 'Total Servis', $money($totals['Servis']), 'Biaya lain '.$money($totals['Lainnya'])],
        ['bx-calculator', 'info', 'Total Biaya', $money((float) $v->purchase_price + $costTotal), 'Harga beli + servis + lainnya'],
      ];
  $info = [
    'Identitas' => [
      'Perusahaan' => $v->company_name, 'Jenis' => $v->kind, 'Merk / Model' => trim($v->brand.' '.$v->model),
      'Tipe' => $v->vehicle_type, 'Tahun' => $v->production_year, 'Warna' => $v->color,
      'Kondisi' => $v->condition, 'Status Pemakaian' => $v->usage_status, 'Kilometer' => $v->mileage !== null ? number_format($v->mileage, 0, ',', '.') : null,
      'Plat yang Digantikan' => $v->replacement_plate,
    ],
    'Penempatan' => [
      'Region' => $v->region, 'Lokasi' => $v->location, 'Area Sekarang' => $v->current_area,
      'Project' => $v->subProject ? $v->subProject->sub_project_code.' — '.$v->subProject->name : null, 'PIC' => $v->pic,
    ],
    'Dokumen' => [
      'Nama pada STNK' => $v->stnk_name, 'Alamat pada STNK' => $v->stnk_address,
      'Nomor Rangka' => $v->chassis_number, 'Nomor Mesin' => $v->engine_number,
    ],
  ];
  if ($v->isRent()) {
    $info['Sewa'] = [
      'Vendor Rental' => $v->rentalSupplier?->name, 'Harga Sewa' => $money($v->rent_price).' / '.strtolower($v->rent_period ?? 'bulan'),
      'Mulai Sewa' => $date($v->rental_start_date), 'Akhir Sewa (diisi)' => $date($v->rental_end_date),
      'Akhir Sewa (berlaku)' => $date($rentEnd),
    ];
  }
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

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('erp.vehicles.index') }}">Kendaraan</a></li>
      <li class="breadcrumb-item active fw-semibold">{{ $v->plate_number }}</li>
    </ol>
  </nav>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-lg"><span class="avatar-initial rounded bg-label-{{ $v->isRent() ? 'info' : 'primary' }}"><i class="bx {{ $v->kind === 'Motor' ? 'bx-cycling' : 'bx-car' }} fs-3"></i></span></div>
        <div>
          <h4 class="fw-bold mb-1">{{ $v->plate_number }}</h4>
          <div class="d-flex flex-wrap gap-2 align-items-center small">
            <span class="badge bg-label-{{ $v->isRent() ? 'info' : 'primary' }}">{{ $v->isRent() ? 'Sewa (Rent)' : 'Milik Sendiri (Own)' }}</span>
            <span class="badge bg-label-{{ $v->is_active ? 'success' : 'secondary' }}">{{ $v->is_active ? 'Aktif' : 'Tidak aktif' }}</span>
            <span class="badge bg-label-{{ \App\Models\Erp\Vehicle::expiryColor($stnk) }}">STNK {{ $date($v->stnk_expiry_date) }}</span>
            <span class="badge bg-label-{{ \App\Models\Erp\Vehicle::expiryColor($tax) }}">Pajak {{ $date($v->tax_expiry_date) }}</span>
            <span class="text-muted">{{ trim($v->brand.' '.$v->model) }} · {{ $v->location ?: '-' }}</span>
          </div>
        </div>
      </div>
      <div class="d-flex gap-2">
        @if($user->hasPermission('vehicles.update'))
          <a href="{{ route('erp.vehicles.edit', $v) }}" class="btn btn-outline-primary"><i class="bx bx-edit me-1"></i>Edit</a>
        @endif
        @if($user->hasPermission('vehicles.delete'))
          <form method="POST" action="{{ route('erp.vehicles.destroy', $v) }}" onsubmit="return confirm('Hapus kendaraan {{ $v->plate_number }}?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger"><i class="bx bx-trash me-1"></i>Hapus</button>
          </form>
        @endif
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    @foreach($cards as [$icon, $color, $label, $value, $note])
      <div class="col-md-4">
        <div class="card hr-stat card-border-shadow-{{ $color }} shadow-sm h-100">
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
    <div class="col-lg-8">
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header pb-2 d-flex justify-content-between align-items-center">
          <h6 class="hr-section-title mb-0"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-receipt"></i></span></span>Riwayat Biaya</h6>
          <span class="small text-muted">dari PO yang sudah disetujui</span>
        </div>
        @if($lines->isEmpty())
          @include('erp.hr.partials.empty', ['icon' => 'bx-receipt', 'title' => 'Belum ada biaya', 'text' => 'Buat RF dengan memilih plat ini di item-nya. Biaya muncul di sini setelah PO-nya disetujui.'])
        @else
          <div class="table-responsive">
            <table class="table table-hover hr-table mb-0">
              <thead><tr><th>PO</th><th>Jenis</th><th>Item</th><th>Periode</th><th class="text-end">Total + Pajak</th></tr></thead>
              <tbody>
                @foreach($lines as $line)
                  @php $rfItem = $line->requestFormItem; $po = $line->purchaseOrder; @endphp
                  <tr>
                    <td class="text-nowrap">
                      <a href="{{ route('erp.purchase-orders.show', $po) }}" class="fw-semibold hr-code">{{ $po->po_no }}</a>
                      <div class="small text-muted">{{ $date($po->date) }} · {{ $po->supplier?->name ?? '-' }}</div>
                    </td>
                    <td><span class="badge bg-label-{{ ['Sewa' => 'info', 'Servis' => 'warning'][$rfItem?->vehicle_cost_type] ?? 'secondary' }}">{{ $rfItem?->vehicle_cost_type ?? '-' }}</span></td>
                    <td>{{ $rfItem?->product_name }}
                      <div class="small text-muted">@if($rfItem?->requestForm)<a href="{{ route('erp.request-form.show', $rfItem->requestForm) }}">{{ $rfItem->requestForm->rf_no }}</a> · @endif{{ number_format((float) $line->qty, 0, ',', '.') }} × {{ $money($line->unit_cost) }}</div></td>
                    <td class="small text-nowrap">{{ $rfItem?->rental_start_date ? $date($rfItem->rental_start_date).' – '.$date($rfItem->rental_end_date) : '-' }}</td>
                    <td class="text-end fw-semibold text-nowrap">{{ $money($line->total_cost) }}</td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot><tr><th colspan="4" class="text-end">Total</th><th class="text-end text-nowrap">{{ $money($costTotal) }}</th></tr></tfoot>
            </table>
          </div>
        @endif
      </div>

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header pb-2">
          <h6 class="hr-section-title mb-0"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-warning"><i class="bx bx-loader-circle"></i></span></span>Pengajuan Dalam Proses</h6>
        </div>
        @if($inProgress->isEmpty())
          <div class="card-body pt-0 small text-muted">Tidak ada RF untuk kendaraan ini yang masih menunggu.</div>
        @else
          <div class="table-responsive">
            <table class="table hr-table mb-0">
              <thead><tr><th>RF</th><th>Jenis</th><th>Item</th><th>Status RF</th><th class="text-end">Estimasi</th></tr></thead>
              <tbody>
                @foreach($inProgress as $item)
                  <tr>
                    <td class="text-nowrap"><a href="{{ route('erp.request-form.show', $item->requestForm) }}" class="fw-semibold hr-code">{{ $item->requestForm->rf_no }}</a>
                      <div class="small text-muted">{{ $date($item->requestForm->rf_date) }}</div></td>
                    <td><span class="badge bg-label-secondary">{{ $item->vehicle_cost_type }}</span></td>
                    <td>{{ $item->product_name }}@if($item->rental_start_date)<div class="small text-muted">{{ $date($item->rental_start_date) }} – {{ $date($item->rental_end_date) }}</div>@endif</td>
                    <td><span class="badge bg-label-info">{{ $item->requestForm->status }}</span></td>
                    <td class="text-end text-nowrap">{{ $item->currency }} {{ number_format((float) $item->original_total_cost, 0, ',', '.') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

    <div class="col-lg-4">
      @foreach($info as $title => $rows)
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header pb-2"><h6 class="mb-0 fw-semibold">{{ $title }}</h6></div>
          <div class="card-body pt-0">
            <dl class="row mb-0 small">
              @foreach($rows as $label => $value)
                <dt class="col-5 text-muted fw-normal">{{ $label }}</dt>
                <dd class="col-7 mb-2">{{ ($value === null || $value === '' || $value === '-') ? '-' : $value }}</dd>
              @endforeach
            </dl>
          </div>
        </div>
      @endforeach
      @if($v->description)
        <div class="card shadow-sm border-0 mb-4"><div class="card-body small"><div class="text-muted mb-1">Keterangan</div>{!! nl2br(e($v->description)) !!}</div></div>
      @endif
    </div>
  </div>
</div>
@endsection
