@extends('layouts.home')

@section('title', 'Perusahaan')

@include('erp.hr.partials.style')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible d-flex align-items-center border-0 shadow-sm" role="alert">
      <i class="bx bx-check-circle fs-4 me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <div class="avatar avatar-lg"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-building fs-3"></i></span></div>
      <div>
        <h4 class="fw-bold mb-0">Perusahaan</h4>
        <small class="text-muted">Perusahaan kita sendiri, dipilih di Invoice dan Kendaraan. Berlaku untuk semua project.</small>
      </div>
    </div>
    <a href="{{ route('erp.companies.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Perusahaan Baru</a>
  </div>

  <div class="card shadow-sm border-0">
    @if($companies->isEmpty())
      @include('erp.hr.partials.empty', ['icon' => 'bx-building', 'title' => 'Belum ada perusahaan', 'text' => 'Tambahkan perusahaan pertama.'])
    @else
      <div class="table-responsive">
        <table class="table table-hover hr-table mb-0">
          <thead><tr><th>Kode</th><th>Nama</th><th>Nama Legal</th><th>Kota</th><th>NPWP</th><th>Status</th><th></th></tr></thead>
          <tbody>
            @foreach($companies as $c)
              <tr class="{{ $c->is_active ? '' : 'text-muted' }}">
                <td class="fw-semibold hr-code">{{ $c->code }}</td>
                <td>{{ $c->name }}@if($c->short_name && $c->short_name !== $c->name)<div class="small text-muted">tampil sebagai {{ $c->short_name }}</div>@endif</td>
                <td>{{ $c->legal_name ?: '-' }}</td>
                <td>{{ $c->city ?: '-' }}</td>
                <td>{{ $c->tax_number ?: '-' }}</td>
                <td>
                  <span class="badge bg-label-{{ $c->is_active ? 'success' : 'secondary' }}">{{ $c->is_active ? 'Aktif' : 'Tidak aktif' }}</span>
                  @if($c->is_default)<span class="badge bg-label-primary">Default</span>@endif
                </td>
                <td class="text-end"><a href="{{ route('erp.companies.edit', $c) }}" class="btn btn-sm btn-icon btn-text-primary" title="Edit"><i class="bx bx-edit"></i></a></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
  <div class="small text-muted mt-2"><i class="bx bx-info-circle me-1"></i>Perusahaan tidak dihapus karena data invoice dan kendaraan menunjuk ke sana. Yang sudah tidak dipakai cukup dinonaktifkan, supaya tidak muncul lagi di pilihan.</div>
</div>
@endsection
