@extends('layouts.home')

@section('title', 'HRIS - Data Karyawan')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bx bx-user-pin text-primary me-2"></i>Human Resource Information System (HRIS)</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item text-muted">HRIS</li>
                    <li class="breadcrumb-item active fw-semibold">Data Karyawan</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-label-warning px-3 py-2 rounded-pill d-flex align-items-center">
                <i class="bx bx-time-five me-1"></i> Modul HRIS (Under Development)
            </span>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-border-shadow-primary h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-group"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ $totalEmployees }}</h4>
                    </div>
                    <p class="mb-0 text-muted small fw-semibold">Total Karyawan Terdaftar</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-border-shadow-success h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="bx bx-user-check"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ $activeEmployees }}</h4>
                    </div>
                    <p class="mb-0 text-muted small fw-semibold">Karyawan Aktif</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-border-shadow-info h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="bx bx-key"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ $linkedUsers }}</h4>
                    </div>
                    <p class="mb-0 text-muted small fw-semibold">Terhubung Akun Login</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-border-shadow-warning h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-buildings"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">5</h4>
                    </div>
                    <p class="mb-0 text-muted small fw-semibold">Divisi / Departemen</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Banner Notice --}}
    <div class="alert alert-primary d-flex align-items-center border-0 shadow-sm mb-4" role="alert">
        <span class="badge badge-center rounded-pill bg-primary p-3 me-3 text-white"><i class="bx bx-info-circle fs-5"></i></span>
        <div class="d-flex flex-column">
            <span class="fw-bold fs-6">Pemisahan Tabel Data Karyawan & Akun Login Berhasil Diaktifkan!</span>
            <span class="small text-muted">Struktur database telah dipisahkan ke tabel <code>employees</code> (HRIS) dan terhubung dengan tabel <code>users</code> (Akun Login). Modul absensi, cuti, dan payroll akan aktif di fase rilis HRIS berikutnya.</span>
        </div>
    </div>

    {{-- Employees Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold"><i class="bx bx-id-card me-2 text-primary"></i>Direktori Karyawan (Master Employees)</h5>
            <span class="badge bg-label-primary px-3 py-2 rounded-pill">{{ count($employees) }} Karyawan</span>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px" class="text-center">No</th>
                        <th>NIK</th>
                        <th>Nama Karyawan</th>
                        <th>Departemen & Jabatan</th>
                        <th>Kontak</th>
                        <th class="text-center">Akun Login</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $index => $emp)
                        <tr>
                            <td class="text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                            <td>
                                <span class="badge bg-label-secondary font-monospace px-2 py-1">{{ $emp->nik ?? '-' }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded-circle bg-label-primary fw-bold">
                                            {{ strtoupper(substr($emp->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $emp->name }}</div>
                                        <small class="text-muted">{{ $emp->email ?? '-' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $emp->position ?? 'Staff' }}</div>
                                <span class="badge bg-label-info font-monospace" style="font-size: 0.72rem;">{{ $emp->department ?? 'Umum' }}</span>
                            </td>
                            <td>
                                <small class="text-muted"><i class="bx bx-phone me-1"></i>{{ $emp->phone ?? '-' }}</small>
                            </td>
                            <td class="text-center">
                                @if($emp->user)
                                    <span class="badge bg-label-success px-2 py-1" title="Username: {{ $emp->user->username }}">
                                        <i class="bx bx-check-circle me-1"></i>{{ $emp->user->username }}
                                    </span>
                                @else
                                    <span class="badge bg-label-secondary px-2 py-1">
                                        <i class="bx bx-x-circle me-1"></i>Non-Login
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($emp->status === 'active')
                                    <span class="badge bg-label-success">Aktif</span>
                                @else
                                    <span class="badge bg-label-secondary">{{ ucfirst($emp->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada data karyawan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
