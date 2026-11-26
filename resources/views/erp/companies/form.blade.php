@extends('layouts.home')

@section('title', $company->exists ? 'Edit Perusahaan - '.$company->name : 'Perusahaan Baru')

@include('erp.hr.partials.style')

@section('content')
@php $val = fn ($field) => old($field, $company->$field); @endphp
<div class="container-xxl flex-grow-1 container-p-y">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('erp.companies.index') }}">Perusahaan</a></li>
      <li class="breadcrumb-item active fw-semibold">{{ $company->exists ? $company->name : 'Perusahaan Baru' }}</li>
    </ol>
  </nav>

  @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <form method="POST" action="{{ $company->exists ? route('erp.companies.update', $company) : route('erp.companies.store') }}">
    @csrf
    @if($company->exists) @method('PUT') @endif
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card shadow-sm border-0">
          <div class="card-header pb-2">
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-building"></i></span></span>Data Perusahaan</h6>
          </div>
          <div class="card-body row g-3">
            <div class="col-md-4">
              <label class="form-label">Kode <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control text-uppercase" maxlength="30" required value="{{ $val('code') }}" placeholder="mis. IMPRIMA">
            </div>
            <div class="col-md-4">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" maxlength="150" required value="{{ $val('name') }}" placeholder="mis. Imprima">
            </div>
            <div class="col-md-4">
              <label class="form-label">Nama Singkat</label>
              <input type="text" name="short_name" class="form-control" maxlength="50" value="{{ $val('short_name') }}" placeholder="yang tampil di pilihan">
            </div>
            <div class="col-12">
              <label class="form-label">Nama Legal</label>
              <input type="text" name="legal_name" class="form-control" maxlength="200" value="{{ $val('legal_name') }}" placeholder="mis. PT Imprima Prima Sejahtera">
            </div>
            <div class="col-12">
              <label class="form-label">Alamat</label>
              <textarea name="address" class="form-control" rows="2" maxlength="1000">{{ $val('address') }}</textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kota</label>
              <input type="text" name="city" class="form-control" maxlength="100" value="{{ $val('city') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Provinsi</label>
              <input type="text" name="province" class="form-control" maxlength="100" value="{{ $val('province') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Telepon</label>
              <input type="text" name="phone" class="form-control" maxlength="50" value="{{ $val('phone') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" maxlength="150" value="{{ $val('email') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Website</label>
              <input type="text" name="website" class="form-control" maxlength="200" value="{{ $val('website') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">NPWP</label>
              <input type="text" name="tax_number" class="form-control" maxlength="50" value="{{ $val('tax_number') }}">
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-body">
            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" id="coActive" @checked(old('is_active', $company->is_active))>
              <label class="form-check-label" for="coActive">Aktif</label>
            </div>
            <div class="form-text mb-3">Yang tidak aktif tidak muncul lagi di pilihan Invoice dan Kendaraan; data lama tetap.</div>
            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" name="is_default" value="1" id="coDefault" @checked(old('is_default', $company->is_default))>
              <label class="form-check-label" for="coDefault">Perusahaan default</label>
            </div>
            <div class="form-text">Muncul paling atas di pilihan. Hanya satu perusahaan yang bisa jadi default.</div>
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('erp.companies.index') }}" class="btn btn-label-secondary flex-fill">Batal</a>
          <button class="btn btn-primary flex-fill"><i class="bx bx-save me-1"></i>Simpan</button>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection
