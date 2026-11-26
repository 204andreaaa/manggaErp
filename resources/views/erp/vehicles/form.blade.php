@extends('layouts.home')

@section('title', $vehicle->exists ? 'Edit Kendaraan - '.$vehicle->plate_number : 'Kendaraan Baru')

@include('erp.hr.partials.style')

@section('content')
@php
  $rent = $vehicle->ownership === 'Rent';
  $back = $vehicle->exists ? route('erp.vehicles.show', $vehicle) : route('erp.vehicles.index');
  $req = '<span class="text-danger">*</span>';
  $reqRent = $rent ? $req : '';
  $val = fn ($field) => old($field, $vehicle->$field);
  $dateVal = fn ($field) => old($field, $vehicle->$field?->format('Y-m-d'));
@endphp
<div class="container-xxl flex-grow-1 container-p-y">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('erp.vehicles.index') }}">Kendaraan</a></li>
      @if($vehicle->exists)<li class="breadcrumb-item"><a href="{{ $back }}">{{ $vehicle->plate_number }}</a></li>@endif
      <li class="breadcrumb-item active fw-semibold">{{ $vehicle->exists ? 'Edit' : 'Kendaraan Baru' }} · {{ $rent ? 'Sewa (Rent)' : 'Milik Sendiri (Own)' }}</li>
    </ol>
  </nav>

  @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="bx bx-check-circle me-1"></i>{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  @foreach($suggestions as $field => $values)
    <datalist id="dl-{{ $field }}">@foreach($values as $v)<option value="{{ $v }}">@endforeach</datalist>
  @endforeach

  <form method="POST" action="{{ $vehicle->exists ? route('erp.vehicles.update', $vehicle) : route('erp.vehicles.store') }}">
    @csrf
    @if($vehicle->exists) @method('PUT') @else <input type="hidden" name="ownership" value="{{ $vehicle->ownership }}"> @endif

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header pb-2">
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-car"></i></span></span>Identitas</h6>
          </div>
          <div class="card-body row g-3">
            <div class="col-md-4">
              <label class="form-label">Plat Nomor {!! $req !!}</label>
              <input type="text" name="plate_number" class="form-control text-uppercase" maxlength="20" required value="{{ $val('plate_number') }}" placeholder="mis. B 2601 PFR">
            </div>
            <div class="col-md-4">
              <label class="form-label">Jenis {!! $req !!}</label>
              <select name="kind" class="form-select" required>
                @foreach(\App\Models\Erp\Vehicle::KINDS as $k)<option value="{{ $k }}" @selected($val('kind') === $k)>{{ $k }}</option>@endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Merk {!! $reqRent !!}</label>
              <input type="text" name="brand" class="form-control" maxlength="60" list="dl-brand" @required($rent) value="{{ $val('brand') }}" placeholder="mis. Daihatsu">
            </div>
            <div class="col-md-4">
              <label class="form-label">Model</label>
              <input type="text" name="model" class="form-control" maxlength="60" list="dl-model" value="{{ $val('model') }}" placeholder="mis. Grand Max">
            </div>
            <div class="col-md-4">
              <label class="form-label">Tipe</label>
              <input type="text" name="vehicle_type" class="form-control" maxlength="60" list="dl-vehicle_type" value="{{ $val('vehicle_type') }}" placeholder="mis. Pick Up">
            </div>
            <div class="col-md-4">
              <label class="form-label">Tahun Produksi {!! $req !!}</label>
              <input type="number" name="production_year" class="form-control" min="1950" max="{{ now()->year + 1 }}" required value="{{ $val('production_year') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Warna</label>
              <input type="text" name="color" class="form-control" maxlength="40" list="dl-color" value="{{ $val('color') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Kilometer</label>
              <input type="number" name="mileage" class="form-control" min="0" value="{{ $val('mileage') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Kondisi {!! $reqRent !!}</label>
              <input type="text" name="condition" class="form-control" maxlength="40" list="dl-condition" @required($rent) value="{{ $val('condition') }}" placeholder="mis. Good">
            </div>
            <div class="col-md-4">
              <label class="form-label">Status Pemakaian</label>
              <input type="text" name="usage_status" class="form-control" maxlength="40" list="dl-usage_status" value="{{ $val('usage_status') }}" placeholder="mis. Stand By">
            </div>
            <div class="col-md-4">
              <label class="form-label">Plat yang Digantikan</label>
              <input type="text" name="replacement_plate" class="form-control text-uppercase" maxlength="20" value="{{ $val('replacement_plate') }}" placeholder="plat lama, bila ganti unit">
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header pb-2">
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-success"><i class="bx bx-map"></i></span></span>Penempatan</h6>
          </div>
          <div class="card-body row g-3">
            <div class="col-md-4">
              <label class="form-label">Region {!! $reqRent !!}</label>
              <input type="text" name="region" class="form-control" maxlength="80" list="dl-region" @required($rent) value="{{ $val('region') }}" placeholder="mis. Kalimantan Timur">
            </div>
            <div class="col-md-4">
              <label class="form-label">Lokasi {!! $reqRent !!}</label>
              <input type="text" name="location" class="form-control" maxlength="80" list="dl-location" @required($rent) value="{{ $val('location') }}" placeholder="mis. Balikpapan">
            </div>
            <div class="col-md-4">
              <label class="form-label">Area Sekarang {!! $reqRent !!}</label>
              <input type="text" name="current_area" class="form-control" maxlength="80" list="dl-current_area" @required($rent) value="{{ $val('current_area') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Project</label>
              <select name="sub_project_id" class="form-select">
                <option value="">-- Tidak untuk project --</option>
                @foreach($subProjects as $sp)
                  <option value="{{ $sp->id }}" @selected((string) $val('sub_project_id') === (string) $sp->id)>{{ $sp->sub_project_code }} — {{ $sp->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">PIC</label>
              <input type="text" name="pic" class="form-control" maxlength="120" value="{{ $val('pic') }}">
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header pb-2">
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-warning"><i class="bx bx-id-card"></i></span></span>Dokumen</h6>
          </div>
          <div class="card-body row g-3">
            <div class="col-md-6">
              <label class="form-label">Nama pada STNK</label>
              <input type="text" name="stnk_name" class="form-control" maxlength="150" value="{{ $val('stnk_name') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Alamat pada STNK</label>
              <input type="text" name="stnk_address" class="form-control" maxlength="500" value="{{ $val('stnk_address') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nomor Rangka</label>
              <input type="text" name="chassis_number" class="form-control" maxlength="60" value="{{ $val('chassis_number') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nomor Mesin</label>
              <input type="text" name="engine_number" class="form-control" maxlength="60" value="{{ $val('engine_number') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">STNK Habis {!! $reqRent !!}</label>
              <input type="date" name="stnk_expiry_date" class="form-control" @required($rent) value="{{ $dateVal('stnk_expiry_date') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Pajak Habis</label>
              <input type="date" name="tax_expiry_date" class="form-control" value="{{ $dateVal('tax_expiry_date') }}">
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header pb-2">
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-info"><i class="bx {{ $rent ? 'bx-transfer' : 'bx-key' }}"></i></span></span>{{ $rent ? 'Sewa' : 'Pembelian' }}</h6>
          </div>
          <div class="card-body row g-3">
            @if($rent)
              <div class="col-12">
                <label class="form-label">Vendor Rental {!! $req !!}</label>
                <select name="rental_supplier_id" class="form-select" required>
                  <option value="">-- Pilih supplier --</option>
                  @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" @selected((string) $val('rental_supplier_id') === (string) $s->id)>{{ $s->name }}</option>
                  @endforeach
                </select>
                <div class="form-text">Diambil dari master Supplier.</div>
              </div>
              <div class="col-12">
                <label class="form-label">Harga Sewa {!! $req !!}</label>
                <div class="input-group"><span class="input-group-text">Rp</span>
                  <input type="number" name="rent_price" class="form-control" min="0" step="1" required value="{{ $val('rent_price') !== null ? (float) $val('rent_price') : '' }}">
                  <span class="input-group-text">per</span>
                  <select name="rent_period" class="form-select" style="max-width:110px" required>
                    @foreach(\App\Models\Erp\Vehicle::RENT_PERIODS as $period)<option value="{{ $period }}" @selected($val('rent_period') === $period)>{{ $period }}</option>@endforeach
                  </select>
                </div>
                <div class="form-text">Harga di kontrak sewa. Biaya yang dihitung tetap dari PO sewa.</div>
              </div>
              <div class="col-6">
                <label class="form-label">Mulai Sewa {!! $req !!}</label>
                <input type="date" name="rental_start_date" class="form-control" required value="{{ $dateVal('rental_start_date') }}">
              </div>
              <div class="col-6">
                <label class="form-label">Akhir Sewa {!! $req !!}</label>
                <input type="date" name="rental_end_date" class="form-control" required value="{{ $dateVal('rental_end_date') }}">
              </div>
              <div class="col-12 form-text mt-1">Akhir sewa maju sendiri bila ada PO sewa yang disetujui untuk periode setelahnya.</div>
            @else
              <div class="col-12">
                <label class="form-label">Tanggal Beli</label>
                <input type="date" name="purchase_date" class="form-control" value="{{ $dateVal('purchase_date') }}">
              </div>
              <div class="col-12">
                <label class="form-label">Harga Beli {!! $req !!}</label>
                <div class="input-group"><span class="input-group-text">Rp</span>
                  <input type="number" name="purchase_price" class="form-control" min="0" step="1" required value="{{ $val('purchase_price') !== null ? (float) $val('purchase_price') : '' }}"></div>
              </div>
            @endif
            <div class="col-12">
              <label class="form-label">Keterangan</label>
              <textarea name="description" class="form-control" rows="3" maxlength="2000">{{ $val('description') }}</textarea>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="vehActive" @checked(old('is_active', $vehicle->is_active ?? true))>
                <label class="form-check-label" for="vehActive">Aktif</label>
              </div>
              <div class="form-text">Hilangkan centang bila mobil sewa sudah dikembalikan atau mobil sendiri sudah dijual.</div>
            </div>
          </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a href="{{ $back }}" class="btn btn-label-secondary flex-fill">Batal</a>
          @unless($vehicle->exists)
            <button class="btn btn-outline-primary flex-fill" name="save_new" value="1">Simpan & Tambah Lagi</button>
          @endunless
          <button class="btn btn-primary flex-fill"><i class="bx bx-save me-1"></i>Simpan</button>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection
