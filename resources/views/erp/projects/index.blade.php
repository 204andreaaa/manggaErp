@extends('layouts.home')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Master Data /</span> Perusahaan (Tenant)</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Perusahaan</h5>
            <small class="text-muted d-none d-md-inline">Satu perusahaan = satu PT dengan database sendiri; identitasnya jadi kop surat.</small>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddProject">Tambah Perusahaan</button>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Nama Legal PT</th>
                        <th>Database Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($projects as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td><strong>{{ $p->name }}</strong></td>
                        <td>{{ $p->legal_name ?: '-' }}</td>
                        <td><code>{{ $p->db_name }}</code></td>
                        <td>
                            @if($p->is_active)
                                <span class="badge bg-label-success">Active</span>
                            @else
                                <span class="badge bg-label-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-warning btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modalEditProject{{ $p->id }}">
                                <i class="bx bx-edit-alt me-1"></i> Edit
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalDeleteProject{{ $p->id }}">
                                <i class="bx bx-trash me-1"></i> Hapus
                            </button>
                        </td>
                    </tr>

                    @include('erp.projects._edit_modal', ['p' => $p])

                    <!-- Modal Delete -->
                    <div class="modal fade" id="modalDeleteProject{{ $p->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('erp.projects.destroy', $p->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Konfirmasi Hapus Perusahaan</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Apakah Anda yakin ingin menghapus perusahaan <strong>{{ $p->name }}</strong> (<code>{{ $p->db_name }}</code>)?</p>
                                        <small class="text-danger">Tindakan ini tidak dapat dibatalkan!</small>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-danger">Hapus Perusahaan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Belum ada perusahaan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="modalAddProject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('erp.projects.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Perusahaan Baru (Tenant)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col mb-3">
                            <label class="form-label">Nama Perusahaan</label>
                            <input type="text" name="name" class="form-control" required placeholder="Misal: Imprima">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col mb-3">
                            <label class="form-label">Nama Database</label>
                            <input type="text" name="db_name" class="form-control" required placeholder="Misal: erp_jkt">
                            <small class="text-muted">Database baru akan otomatis dibuat berdasarkan nama ini.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Buat Perusahaan & Database</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
