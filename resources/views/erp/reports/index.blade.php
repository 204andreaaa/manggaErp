@extends('layouts.home')

@section('title', 'Custom Report Builder')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bx bx-bar-chart-alt-2 text-primary me-2"></i>Custom Report Builder</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item text-muted">Reporting</li>
                    <li class="breadcrumb-item active fw-semibold">Report Hub</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('erp.reports.builder') }}" class="btn btn-primary shadow-sm">
                <i class="bx bx-plus me-1"></i> Buat Laporan Baru
            </a>
        </div>
    </div>

    {{-- Report Types Catalog --}}
    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-3"><i class="bx bx-grid-alt me-1 text-primary"></i>Pilih Sumber Data Laporan (Report Types)</h5>
        <div class="row g-3">
            @foreach($reportTypes as $typeKey => $type)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-none hover-shadow transition-all bg-white">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="avatar avatar-md bg-label-primary rounded-3 d-flex align-items-center justify-content-center">
                                        <i class="bx {{ $type['icon'] }} fs-4 text-primary"></i>
                                    </div>
                                    <span class="badge bg-label-secondary font-monospace">{{ $type['badge'] }}</span>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">{{ $type['name'] }}</h5>
                                <p class="text-muted small mb-3">{{ $type['description'] }}</p>
                            </div>
                            <div class="pt-2 border-top">
                                <a href="{{ route('erp.reports.builder', ['type' => $typeKey]) }}" class="btn btn-sm btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                    <span>Buka Report Builder</span>
                                    <i class="bx bx-right-arrow-alt ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Saved Report Templates --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold"><i class="bx bx-bookmark me-2 text-primary"></i>Template Laporan Tersimpan</h5>
            <span class="badge bg-label-primary px-3 py-2 rounded-pill">{{ count($savedReports) }} Template</span>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px" class="text-center">No</th>
                        <th>Judul Laporan</th>
                        <th>Tipe Data</th>
                        <th>Kolom Terpilih</th>
                        <th>Dibuat Oleh</th>
                        <th>Tanggal Simpan</th>
                        <th style="width:140px" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($savedReports as $idx => $r)
                        @php
                            $colCount = is_array($r->selected_columns) ? count($r->selected_columns) : 0;
                            $typeMeta = $reportTypes[$r->report_type] ?? ['name' => ucfirst($r->report_type), 'icon' => 'bx-file'];
                        @endphp
                        <tr>
                            <td class="text-center text-muted fw-semibold">{{ $idx + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $r->title }}</div>
                                @if($r->description)
                                    <small class="text-muted">{{ Str::limit($r->description, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-label-primary">
                                    <i class="bx {{ $typeMeta['icon'] }} me-1"></i>{{ $typeMeta['name'] }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-label-secondary font-monospace">{{ $colCount }} Kolom</span>
                            </td>
                            <td>
                                <small class="text-dark fw-semibold">{{ $r->user->name ?? 'System' }}</small>
                            </td>
                            <td>
                                <small class="text-muted">{{ $r->created_at ? $r->created_at->format('d M Y H:i') : '-' }}</small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('erp.reports.builder', ['report_id' => $r->id]) }}" class="btn btn-primary" title="Buka & Jalankan Report">
                                        <i class="bx bx-play me-1"></i> Buka
                                    </a>
                                    <button type="button" class="btn btn-outline-danger js-delete-report" data-id="{{ $r->id }}" data-title="{{ $r->title }}" title="Hapus Template">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bx bx-file-blank fs-1 d-block mb-2 text-secondary"></i>
                                Belum ada template laporan tersimpan. Silakan pilih sumber data di atas untuk mulai membuat laporan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.js-delete-report').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const title = this.getAttribute('data-title');
        
        Swal.fire({
            title: 'Hapus Template?',
            text: `Yakin ingin menghapus template laporan "${title}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/erp/reports/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Terhapus!', data.message, 'success').then(() => window.location.reload());
                    }
                });
            }
        });
    });
});
</script>
@endpush
@endsection
