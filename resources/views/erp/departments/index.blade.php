@extends('layouts.home')

@section('title', 'Master Departemen')

@push('styles')
<style>
    .dept-card-stats {
        border-radius: 0.75rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .dept-card-stats:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }
    .btn-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
</style>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Breadcrumb & Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bx bx-sitemap text-primary me-2"></i>Master Departemen & Divisi</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item text-muted">Human Resource (HRIS)</li>
                    <li class="breadcrumb-item active fw-semibold">Master Departemen</li>
                </ol>
            </nav>
        </div>
        <div>
            @if (auth()->user()->hasPermission('departments.create'))
                <button class="btn btn-primary d-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#mdlAddDept">
                    <i class="bx bx-plus me-1"></i> Tambah Departemen
                </button>
            @endif
        </div>
    </div>

    {{-- Quick Stats Cards --}}
    @php
        $totalDept = $departments->count();
        $activeDept = $departments->where('status', 'active')->count();
        $inactiveDept = $departments->where('status', 'inactive')->count();
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-4">
            <div class="card dept-card-stats border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md bg-label-primary rounded p-2 d-flex align-items-center justify-content-center">
                        <i class="bx bx-sitemap fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Departemen</div>
                        <h4 class="mb-0 fw-bold">{{ $totalDept }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="card dept-card-stats border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md bg-label-success rounded p-2 d-flex align-items-center justify-content-center">
                        <i class="bx bx-check-circle fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Departemen Aktif</div>
                        <h4 class="mb-0 fw-bold text-success">{{ $activeDept }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="card dept-card-stats border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md bg-label-secondary rounded p-2 d-flex align-items-center justify-content-center">
                        <i class="bx bx-pause-circle fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Nonaktif / Terkunci</div>
                        <h4 class="mb-0 fw-bold text-secondary">{{ $inactiveDept }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <div class="fw-semibold mb-1"><i class="bx bx-error me-1"></i> Terjadi kesalahan:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Departments Table Card --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0 fw-bold"><i class="bx bx-list-ul me-2 text-primary"></i>Daftar Departemen Organisasi</h5>
            <div class="d-flex align-items-center gap-2">
                <select id="f_status" class="form-select form-select-sm" style="width: 140px;">
                    <option value="">Semua Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div class="table-responsive text-nowrap">
            <table id="tblDepartments" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">#</th>
                        <th style="width: 120px;">Kode</th>
                        <th>Nama Departemen</th>
                        <th>Deskripsi / Divisi</th>
                        <th class="text-center" style="width: 120px;">Karyawan</th>
                        <th class="text-center" style="width: 100px;">Status</th>
                        <th style="width: 110px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $idx => $d)
                        <tr>
                            <td class="text-center text-muted fw-semibold">{{ $idx + 1 }}</td>
                            <td>
                                <span class="badge bg-label-primary px-2 py-1 font-monospace fw-bold">{{ $d->code }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $d->name }}</div>
                            </td>
                            <td>
                                <span class="text-muted small text-wrap" style="max-width: 320px; display: inline-block;">
                                    {{ $d->description ?: '-' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-label-info px-2 py-1">
                                    <i class="bx bx-user me-1"></i>{{ $d->employees_count ?? 0 }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($d->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    @if(auth()->user()->hasPermission('departments.update'))
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary js-edit"
                                            title="Edit Departemen"
                                            data-id="{{ $d->id }}"
                                            data-code="{{ $d->code }}"
                                            data-name="{{ $d->name }}"
                                            data-description="{{ $d->description }}"
                                            data-status="{{ $d->status }}">
                                            <i class="bx bx-edit-alt"></i>
                                        </button>
                                    @endif

                                    @if(auth()->user()->hasPermission('departments.delete'))
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger js-del"
                                            title="Hapus Departemen"
                                            data-id="{{ $d->id }}"
                                            data-name="{{ $d->name }}"
                                            data-employees="{{ $d->employees_count ?? 0 }}">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada data departemen.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH DEPARTEMEN --}}
<div class="modal fade" id="mdlAddDept" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold"><i class="bx bx-plus-circle text-primary me-2"></i>Tambah Departemen Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('erp.departments.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Kode Divisi <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control text-uppercase font-monospace" placeholder="e.g. PROC, FIN, IT" required value="{{ old('code') }}">
                            <small class="text-muted">Kode unik divisi (singkat)</small>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Nama Departemen <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Procurement & Purchasing" required value="{{ old('name') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi / Cakupan Kerja</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Keterangan divisi atau tugas utama...">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active (Aktif)</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive (Nonaktif)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bx bx-check me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT DEPARTEMEN --}}
<div class="modal fade" id="mdlEditDept" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold"><i class="bx bx-edit-alt text-primary me-2"></i>Edit Departemen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditDept" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Kode Divisi <span class="text-danger">*</span></label>
                            <input type="text" id="edit_code" name="code" class="form-control text-uppercase font-monospace" required>
                            <small class="text-muted">Kode unik divisi</small>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Nama Departemen <span class="text-danger">*</span></label>
                            <input type="text" id="edit_name" name="name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi / Cakupan Kerja</label>
                            <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select id="edit_status" name="status" class="form-select" required>
                                <option value="active">Active (Aktif)</option>
                                <option value="inactive">Inactive (Nonaktif)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bx bx-check me-1"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function() {
    const table = $('#tblDepartments').DataTable({
        order: [[1, 'asc']],
        columnDefs: [
            { targets: [0, 6], orderable: false }
        ],
        pageLength: 10,
        lengthChange: false,
        searching: true,
        info: true,
        dom: 't<"d-flex justify-content-between align-items-center p-3 pt-2"ip>'
    });

    // Connect status filter
    $('#f_status').on('change', function() {
        const v = (this.value || '').toLowerCase();
        if (!v) {
            table.column(5).search('').draw();
            return;
        }
        const label = (v === 'active') ? 'Active' : 'Inactive';
        table.column(5).search(label, false, true).draw();
    });

    // Edit modal trigger
    const editModalEl = document.getElementById('mdlEditDept');
    const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
    const formEdit = document.getElementById('formEditDept');
    const baseUrl = @json(route('erp.departments.index'));

    $(document).on('click', '.js-edit', function(e) {
        e.preventDefault();
        const d = this.dataset;
        formEdit.setAttribute('action', baseUrl + '/' + d.id);
        $('#edit_code').val(d.code || '');
        $('#edit_name').val(d.name || '');
        $('#edit_description').val(d.description || '');
        $('#edit_status').val(d.status || 'active');
        editModal?.show();
    });

    // Delete single
    $(document).on('click', '.js-del', function(e) {
        e.preventDefault();
        const id = this.dataset.id;
        const name = this.dataset.name || 'Departemen';
        const empCount = parseInt(this.dataset.employees, 10) || 0;

        if (empCount > 0) {
            Swal.fire({
                title: 'Tidak Dapat Dihapus',
                html: `Departemen <b>${name}</b> masih digunakan oleh <b>${empCount}</b> karyawan.<br>Silakan mutasi karyawan ke departemen lain terlebih dahulu.`,
                icon: 'warning'
            });
            return;
        }

        Swal.fire({
            title: 'Hapus Departemen?',
            html: `Departemen <b>${name}</b> akan dihapus secara permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33'
        }).then(res => {
            if (!res.isConfirmed) return;
            fetch(baseUrl + '/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).then(async r => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    throw new Error(data.error || 'Gagal menghapus departemen.');
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: data.success || 'Departemen berhasil dihapus.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            }).catch(err => {
                Swal.fire('Error', err.message || 'Gagal menghapus.', 'error');
            });
        });
    });

    @if (session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: @json(session('success')),
            timer: 1800,
            showConfirmButton: false
        });
    @endif

    @if (session('edit_success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: @json(session('edit_success')),
            timer: 1800,
            showConfirmButton: false
        });
    @endif
});
</script>
@endpush
