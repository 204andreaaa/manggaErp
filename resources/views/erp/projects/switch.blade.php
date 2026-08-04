@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap');

    body {
        background-color: #f0f3fa !important;
        font-family: 'Public Sans', sans-serif !important;
        color: #1e293b;
        margin: 0;
        padding: 0;
    }

    /* Top Navbar */
    .mangga-header {
        background: linear-gradient(135deg, #574ae8 0%, #3e32dc 100%);
        height: 76px;
        padding: 0 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 4px 20px rgba(62, 50, 220, 0.15);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .mangga-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #ffffff;
        text-decoration: none;
    }

    .mangga-brand-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .mangga-brand-text {
        font-size: 20px;
        font-weight: 800;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: #ffffff;
    }

    /* Navbar Overview Pills */
    .mangga-overview {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .overview-title {
        color: rgba(255, 255, 255, 0.75);
        font-size: 13px;
        font-weight: 500;
    }

    .overview-pill {
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        padding: 6px 18px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        backdrop-filter: blur(4px);
        transition: background 0.2s ease;
    }

    .overview-pill:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* Right Navbar Actions */
    .mangga-header-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .total-pill {
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        padding: 6px 18px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
    }

    .btn-add-project {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #ffffff;
        color: #574ae8;
        font-size: 22px;
        font-weight: 700;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        transition: transform 0.2s ease, background 0.2s ease;
        line-height: 1;
        padding: 0;
    }

    .btn-add-project:hover {
        transform: scale(1.08);
        background: #f8fafc;
    }

    .user-profile-box {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: 6px;
    }

    .user-details {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .user-name-text {
        color: #ffffff;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.2;
    }

    .user-role-text {
        color: rgba(255, 255, 255, 0.7);
        font-size: 11px;
        font-weight: 500;
    }

    .user-avatar-img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.6);
        background: #ffffff;
    }

    .btn-logout {
        background: transparent;
        border: 1.5px solid rgba(255, 255, 255, 0.45);
        color: #ffffff;
        padding: 6px 16px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-logout:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: #ffffff;
        color: #ffffff;
    }

    /* Main Section */
    .mangga-container {
        padding: 2.5rem 3rem;
        max-width: 1440px;
        margin: 0 auto;
    }

    /* Grid Layout */
    .projects-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 28px;
    }

    /* Project Card */
    .project-card {
        background: linear-gradient(135deg, #574ae8 0%, #3e32dc 100%);
        border-radius: 20px;
        padding: 26px;
        box-shadow: 0 12px 30px rgba(62, 50, 220, 0.22);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        position: relative;
    }

    .project-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 40px rgba(62, 50, 220, 0.32);
    }

    /* Card Top Header */
    .card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 22px;
    }

    .card-project-tag {
        color: rgba(255, 255, 255, 0.65);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        margin-bottom: 4px;
        display: block;
    }

    .card-project-name {
        color: #ffffff;
        font-size: 28px;
        font-weight: 800;
        margin: 0;
        line-height: 1.2;
    }

    /* Logo Badges */
    .logo-container {
        height: 52px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    .imprima-logo-wrapper {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .mandau-logo-img {
        max-height: 44px;
        max-width: 140px;
        object-fit: contain;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
    }

    .initials-logo-box {
        background: rgba(255, 255, 255, 0.16);
        color: #ffffff;
        width: 58px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 800;
        backdrop-filter: blur(6px);
        letter-spacing: 0.5px;
    }

    /* Details Box Inside Card */
    .card-details-box {
        background: rgba(0, 0, 0, 0.15);
        border-radius: 14px;
        padding: 18px 20px;
        margin-bottom: 22px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .detail-label {
        color: rgba(255, 255, 255, 0.72);
        font-size: 13px;
        font-weight: 500;
    }

    .detail-db {
        color: #ff6b81;
        font-family: monospace;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .detail-value {
        color: #ffffff;
        font-size: 13px;
        font-weight: 700;
    }

    .badge-status {
        font-size: 11px;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 6px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        display: inline-block;
    }

    .badge-status-active {
        background: #39db6e;
        color: #ffffff;
    }

    .badge-status-inactive {
        background: #ef4444;
        color: #ffffff;
    }

    /* Card Bottom Actions */
    .card-actions-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .action-icon-btn {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.2s ease;
        padding: 0;
    }

    .btn-delete-card {
        background: #ffe4e6;
        color: #f43f5e;
    }

    .btn-delete-card:hover {
        background: #fecdd3;
        color: #e11d48;
        transform: scale(1.05);
    }

    .btn-edit-card {
        background: #fef3c7;
        color: #d97706;
    }

    .btn-edit-card:hover {
        background: #fde68a;
        color: #b45309;
        transform: scale(1.05);
    }

    .btn-view-card {
        width: 100%;
        height: 44px;
        border-radius: 12px;
        background: #39db6e;
        color: #ffffff;
        border: none;
        font-size: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(57, 219, 110, 0.3);
    }

    .btn-view-card:hover {
        background: #2ebd5d;
        box-shadow: 0 6px 16px rgba(57, 219, 110, 0.4);
        transform: scale(1.02);
    }

    .btn-view-card:disabled {
        background: #94a3b8;
        box-shadow: none;
        cursor: not-allowed;
        transform: none;
    }
</style>

<!-- Top Navbar -->
<header class="mangga-header">
    <a href="#" class="mangga-brand">
        <div class="mangga-brand-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"></path>
                <path d="m22 12.5-8.58 3.91a2 2 0 0 1-1.66 0L3.18 12.5"></path>
                <path d="m22 17.5-8.58 3.91a2 2 0 0 1-1.66 0L3.18 17.5"></path>
            </svg>
        </div>
        <span class="mangga-brand-text">MANGGA PROJECTS</span>
    </a>

    <!-- Overview Badges -->
    <div class="mangga-overview d-none d-md-flex">
        <span class="overview-title">Overview:</span>
        @foreach($projects as $p)
            <span class="overview-pill">{{ $p->name }}</span>
        @endforeach
    </div>

    <!-- Header Actions -->
    <div class="mangga-header-right">
        <div class="total-pill">Total: {{ $projects->count() }}</div>
        
        @if(auth()->user()->hasRole('superadmin'))
            <button type="button" class="btn-add-project" data-bs-toggle="modal" data-bs-target="#modalAddProject" title="Tambah Project Baru">+</button>
        @endif

        <div class="user-profile-box">
            <div class="user-details d-none d-sm-flex">
                <span class="user-name-text">{{ auth()->user()->name }}</span>
                <span class="user-role-text">{{ auth()->user()->roles->pluck('name')->first() ?? 'User' }}</span>
            </div>
            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&color=574ae8&background=ffffff" alt="User Avatar" class="user-avatar-img" />
        </div>

        <form action="{{ route('logout') }}" method="POST" class="d-inline mb-0">
            @csrf
            <button type="submit" class="btn-logout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </button>
        </form>
    </div>
</header>

<!-- Main Container -->
<main class="mangga-container">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="projects-grid">
        @foreach($projects as $p)
            <div class="project-card">
                <div>
                    <!-- Top Info & Logo -->
                    <div class="card-top">
                        <div>
                            <span class="card-project-tag">PROJECT</span>
                            <h3 class="card-project-name">{{ $p->name }}</h3>
                        </div>
                        <div class="logo-container">
                            @if(\Illuminate\Support\Str::contains(strtolower($p->name), 'imprima'))
                                <div class="imprima-logo-wrapper">
                                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="9" stroke="#a3e635" stroke-width="3.5" fill="none"/>
                                    </svg>
                                    <span style="font-weight: 800; color: #ffffff; font-size: 20px; font-family: sans-serif; letter-spacing: -0.5px;">imprima</span>
                                </div>
                            @elseif(\Illuminate\Support\Str::contains(strtolower($p->name), 'mandau'))
                                @if(file_exists(public_path('ImageAsset/logo-mandau.png')))
                                    <img src="{{ asset('ImageAsset/logo-mandau.png') }}" alt="Mandau" class="mandau-logo-img" />
                                @else
                                    <div class="initials-logo-box">MD</div>
                                @endif
                            @else
                                <div class="initials-logo-box">
                                    {{ strtoupper(substr($p->name, 0, 2)) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Details Box -->
                    <div class="card-details-box">
                        <div class="detail-row">
                            <span class="detail-label">Database</span>
                            <span class="detail-db">{{ $p->db_name }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Dibuat</span>
                            <span class="detail-value">{{ $p->created_at ? $p->created_at->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status</span>
                            <span class="badge-status {{ $p->is_active ? 'badge-status-active' : 'badge-status-inactive' }}">
                                {{ $p->is_active ? 'AKTIF' : 'NONAKTIF' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Bottom Actions -->
                <div class="card-actions-row">
                    @if(auth()->user()->hasRole('superadmin'))
                        <button type="button" class="action-icon-btn btn-delete-card" data-bs-toggle="modal" data-bs-target="#modalDeleteProject{{ $p->id }}" title="Hapus Project">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                        <button type="button" class="action-icon-btn btn-edit-card" data-bs-toggle="modal" data-bs-target="#modalEditProject{{ $p->id }}" title="Edit Project">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                    @endif

                    <form action="{{ route('projects.switch.process') }}" method="POST" class="flex-grow-1 mb-0">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $p->id }}">
                        <button type="submit" class="btn-view-card" {{ !$p->is_active ? 'disabled' : '' }}>
                            <span>&rarr;</span> View Project
                        </button>
                    </form>
                </div>
            </div>

            @if(auth()->user()->hasRole('superadmin'))
                <!-- Modal Edit -->
                <div class="modal fade" id="modalEditProject{{ $p->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('erp.projects.update', $p->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Project: {{ $p->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Project</label>
                                        <input type="text" name="name" class="form-control" value="{{ $p->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status Project</label>
                                        <select name="is_active" class="form-select">
                                            <option value="1" {{ $p->is_active ? 'selected' : '' }}>Aktif</option>
                                            <option value="0" {{ !$p->is_active ? 'selected' : '' }}>Nonaktif</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Delete -->
                <div class="modal fade" id="modalDeleteProject{{ $p->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('erp.projects.destroy', $p->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="modal-header">
                                    <h5 class="modal-title">Konfirmasi Hapus Project</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Apakah Anda yakin ingin menghapus project <strong>{{ $p->name }}</strong> (<code>{{ $p->db_name }}</code>)?</p>
                                    <small class="text-danger">Tindakan ini tidak dapat dibatalkan!</small>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-danger">Hapus Project</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</main>

@if(auth()->user()->hasRole('superadmin'))
    <!-- Modal Add Project -->
    <div class="modal fade" id="modalAddProject" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('erp.projects.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Project Baru (Tenant)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Project</label>
                            <input type="text" id="addProjectName" name="name" class="form-control" placeholder="Misal: Cabang Jakarta" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Database</label>
                            <input type="text" id="addDbName" name="db_name" class="form-control" placeholder="Misal: mangga_jkt_db" required>
                            <small class="text-muted">Gunakan huruf kecil, angka, dan underscore.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Buat Project & Database</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
