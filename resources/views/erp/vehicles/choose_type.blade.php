@extends('layouts.home')

@section('title', 'Kendaraan Baru')

@include('erp.hr.partials.style')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('erp.vehicles.index') }}">Kendaraan</a></li>
      <li class="breadcrumb-item active fw-semibold">Kendaraan Baru</li>
    </ol>
  </nav>

  <h5 class="fw-bold mb-1">Kendaraan ini milik sendiri atau sewa?</h5>
  <p class="text-muted mb-4">Jenisnya tidak bisa diubah setelah disimpan.</p>

  <div class="row g-4" style="max-width:820px">
    @foreach([
      ['Own', 'bx-key', 'primary', 'Milik Sendiri (Own)', 'Dibeli perusahaan. Yang dicatat harga beli; biaya berikutnya dari servis.'],
      ['Rent', 'bx-transfer', 'info', 'Sewa (Rent)', 'Disewa dari vendor rental. Yang dicatat vendor, harga sewa (per bulan atau per tahun) dan masa sewa.'],
    ] as [$type, $icon, $color, $title, $text])
      <div class="col-md-6">
        <a href="{{ route('erp.vehicles.create', ['ownership' => $type]) }}" class="text-reset">
          <div class="card shadow-sm border-0 h-100 card-border-shadow-{{ $color }}">
            <div class="card-body">
              <div class="avatar avatar-md mb-3"><span class="avatar-initial rounded bg-label-{{ $color }}"><i class="bx {{ $icon }} fs-4"></i></span></div>
              <h6 class="fw-bold mb-1">{{ $title }}</h6>
              <p class="text-muted small mb-0">{{ $text }}</p>
            </div>
          </div>
        </a>
      </div>
    @endforeach
  </div>
</div>
@endsection
