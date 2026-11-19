@extends('layouts.home')

@section('title', 'Tarif Pajak Default Invoice')

@include('erp.hr.partials.style')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('erp.invoices.index') }}">Invoice</a></li>
      <li class="breadcrumb-item active fw-semibold">Tarif Pajak Default</li>
    </ol>
  </nav>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible d-flex align-items-center border-0 shadow-sm" role="alert">
      <i class="bx bx-check-circle fs-4 me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <div class="row">
    <div class="col-lg-6">
      <form method="POST" action="{{ route('erp.invoices.tax-settings.update') }}" class="card shadow-sm border-0">
        @csrf @method('PUT')
        <div class="card-header pb-2">
          <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-info"><i class="bx bx-calculator"></i></span></span>Tarif Pajak Default Invoice Customer</h6>
        </div>
        <div class="card-body row g-3">
          <div class="col-md-6">
            <label class="form-label">PPN</label>
            <div class="input-group">
              <input type="number" name="ppn_rate" class="form-control" min="0" max="100" step="0.01" required value="{{ old('ppn_rate', (float) $ppnRate) }}">
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">PPh</label>
            <div class="input-group">
              <input type="number" name="pph_rate" class="form-control" min="0" max="100" step="0.01" required value="{{ old('pph_rate', (float) $pphRate) }}">
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="col-12">
            <div class="alert alert-info small border-0 mb-0">
              <i class="bx bx-info-circle me-1"></i>Tarif ini otomatis terisi saat membuat <b>invoice baru</b>, dan masih bisa diubah Finance per invoice. Invoice yang sudah ada tidak ikut berubah.
            </div>
          </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
          <a href="{{ route('erp.invoices.index') }}" class="btn btn-label-secondary">Kembali</a>
          <button class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
