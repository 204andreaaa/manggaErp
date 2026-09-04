@extends('layouts.home')

@section('title', $moduleTitle ?? 'HRIS Module')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bx {{ $moduleIcon ?? 'bx-briefcase' }} text-primary me-2"></i>{{ $moduleTitle ?? 'HRIS Module' }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item text-muted">HRIS</li>
                    <li class="breadcrumb-item active fw-semibold">{{ $moduleTitle ?? 'Module' }}</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Coming Soon Card --}}
    <div class="card shadow-sm border-0 text-center py-5">
        <div class="card-body p-5">
            <div class="mb-4">
                <span class="avatar avatar-xl bg-label-primary rounded-circle p-3 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bx {{ $moduleIcon ?? 'bx-time' }} fs-1 text-primary"></i>
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-2">{{ $moduleTitle ?? 'Modul Sedang Dikembangkan' }}</h3>
            <p class="text-muted mx-auto mb-4" style="max-width: 600px;">
                {{ $moduleDesc ?? 'Modul ini sedang dalam tahap persiapan dan akan segera tersedia pada pembaruan sistem berikutnya.' }}
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="{{ route('erp.hr.employees.index') }}" class="btn btn-primary shadow-sm">
                    <i class="bx bx-id-card me-1"></i> Buka Direktori Karyawan
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-home me-1"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
