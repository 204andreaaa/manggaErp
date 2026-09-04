@extends('layouts.home')

@section('title', 'Roles & Permissions')

@push('styles')
<style>
    /* Modal Dialog & Scrollbar Enhancements */
    #mdlAddRole .modal-dialog,
    #mdlEditRole .modal-dialog {
        max-height: 90vh;
        margin-top: 5vh;
        margin-bottom: 5vh;
    }
    #mdlAddRole .modal-content,
    #mdlEditRole .modal-content {
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 0.75rem;
    }
    #formAddRole,
    #formEditRole {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        overflow: hidden;
        min-height: 0;
    }
    #mdlAddRole .modal-body,
    #mdlEditRole .modal-body {
        overflow-y: auto !important;
        flex: 1 1 auto;
        max-height: calc(90vh - 135px) !important;
        scrollbar-width: thin;
        scrollbar-color: #696cff #f1f1f1;
        padding: 1.5rem !important;
    }
    #mdlAddRole .modal-body::-webkit-scrollbar,
    #mdlEditRole .modal-body::-webkit-scrollbar {
        width: 8px;
    }
    #mdlAddRole .modal-body::-webkit-scrollbar-track,
    #mdlEditRole .modal-body::-webkit-scrollbar-track {
        background: #f1f2f6;
        border-radius: 4px;
    }
    #mdlAddRole .modal-body::-webkit-scrollbar-thumb,
    #mdlEditRole .modal-body::-webkit-scrollbar-thumb {
        background: #b0b5c0;
        border-radius: 4px;
    }
    #mdlAddRole .modal-body::-webkit-scrollbar-thumb:hover,
    #mdlEditRole .modal-body::-webkit-scrollbar-thumb:hover {
        background: #696cff;
    }
    #mdlAddRole .modal-footer,
    #mdlEditRole .modal-footer {
        flex-shrink: 0;
        background: #f8f9fa;
        border-top: 1px solid #e7eaf3;
        z-index: 5;
    }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Breadcrumb & Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bx bx-shield-quarter text-primary me-2"></i>Roles & Sidebar Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item text-muted">System & Security</li>
                    <li class="breadcrumb-item active fw-semibold">Roles & Sidebar</li>
                </ol>
            </nav>
        </div>
        <div>
            @if (auth()->user()->hasPermission('roles.create'))
                <button class="btn btn-primary d-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#mdlAddRole">
                    <i class="bx bx-plus me-1"></i> New Role
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Roles Table Card --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold"><i class="bx bx-list-check me-2 text-primary"></i>Daftar Role & Akses Fitur</h5>
            <span class="badge bg-label-primary px-3 py-2 rounded-pill">{{ count($roles) }} Total Roles</span>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px" class="text-center">ID</th>
                        <th>Role Slug</th>
                        <th>Role Name</th>
                        <th class="text-center">Sidebar Menus</th>
                        <th class="text-center">Action Permissions</th>
                        <th>Home Route</th>
                        <th style="width:130px" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $r)
                        @php
                            $menuCount = is_array($r->menu_keys) ? count($r->menu_keys) : 0;
                            $permCount = is_array($r->permissions) ? count($r->permissions) : 0;
                        @endphp
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $r->id }}</td>
                            <td>
                                <span class="badge bg-label-secondary font-monospace px-2 py-1">{{ $r->slug }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xs me-2">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            <i class="bx bx-user text-primary" style="font-size: 0.9rem;"></i>
                                        </span>
                                    </div>
                                    <span class="fw-bold text-dark">{{ $r->name }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $menuCount > 0 ? 'bg-label-info' : 'bg-label-secondary' }} px-2 py-1">
                                    <i class="bx bx-menu me-1"></i>{{ $menuCount }} Menu
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $permCount > 0 ? 'bg-label-success' : 'bg-label-secondary' }} px-2 py-1">
                                    <i class="bx bx-check-shield me-1"></i>{{ $permCount }} Hak Akses
                                </span>
                            </td>
                            <td>
                                @if($r->home_route)
                                    <span class="badge bg-label-dark font-monospace" style="font-size: 0.75rem;">
                                        <i class="bx bx-home-alt me-1 text-muted"></i>{{ $r->home_route }}
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    @if (auth()->user()->hasPermission('roles.update'))
                                        <button type="button" class="btn btn-sm btn-outline-primary js-edit"
                                            data-id="{{ $r->id }}" 
                                            data-slug="{{ $r->slug }}"
                                            data-name="{{ $r->name }}" 
                                            data-home_route="{{ $r->home_route }}"
                                            data-menu_keys='@json($r->menu_keys ?? [])'
                                            data-permissions='@json($r->permissions ?? [])'>
                                            <i class="bx bx-edit-alt me-1"></i> Edit
                                        </button>
                                    @endif

                                    @if (auth()->user()->hasPermission('roles.delete') && !in_array($r->slug, ['superadmin', 'admin']))
                                        <button type="button" class="btn btn-sm btn-outline-danger js-del" data-id="{{ $r->id }}" data-name="{{ $r->name }}">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada role terdaftar</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- =========================================================================
     MODAL ADD ROLE
========================================================================= --}}
@if (auth()->user()->hasPermission('roles.create'))
    <div class="modal fade" id="mdlAddRole" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white"><i class="bx bx-plus-circle me-1"></i> Buat Role Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form id="formAddRole" method="POST" action="{{ route('erp.roles.store') }}">
                    @csrf
                    <div class="modal-body p-4">
                        {{-- Top Meta Inputs --}}
                        <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Role Slug <span class="text-danger">*</span></label>
                                <input name="slug" class="form-control" placeholder="contoh: staff_procurement" required>
                                <small class="text-muted">Gunakan huruf kecil & underscore</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Role Name <span class="text-danger">*</span></label>
                                <input name="name" class="form-control" placeholder="contoh: Staff Procurement" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Home Route (Default Landing Page)</label>
                                <select name="home_route" class="form-select">
                                    <option value="">(Otomatis sesuai menu pertama)</option>
                                    @foreach ($homeCandidates as $c)
                                        <option value="{{ $c['route'] }}">{{ $c['label'] }} ({{ $c['route'] }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Permission & Menu Grid --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bx bx-check-double text-primary me-1"></i>Pengaturan Sidebar Menu & Hak Akses Tombol</h6>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary js-select-all-add">Pilih Semua</button>
                                <button type="button" class="btn btn-outline-secondary js-unselect-all-add">Batal Semua</button>
                            </div>
                        </div>

                        <div class="row g-3">
                            @foreach ($configGroups as $groupKey => $groupData)
                                @php
                                    $groupItems = $groups[$groupKey] ?? collect();
                                @endphp
                                @if($groupItems->isNotEmpty())
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card h-100 border shadow-none bg-white">
                                            <div class="card-header bg-label-primary py-2 px-3 d-flex justify-content-between align-items-center">
                                                <span class="fw-bold fs-7">
                                                    <i class="{{ $groupData['icon'] ?? 'bx bx-folder' }} me-1"></i>{{ $groupData['label'] }}
                                                </span>
                                                <a href="javascript:void(0);" class="text-primary small fw-semibold js-toggle-group-add" data-group="{{ $groupKey }}">Toggle</a>
                                            </div>
                                            <div class="card-body p-3">
                                                @foreach ($groupItems as $it)
                                                    <div class="mb-3 pb-2 border-bottom border-light">
                                                        {{-- Menu Level Checkbox --}}
                                                        <div class="form-check form-switch mb-1">
                                                            <input class="form-check-input js-menu-checkbox add-menu-{{ $groupKey }}" 
                                                                type="checkbox" 
                                                                name="menu_keys[]" 
                                                                value="{{ $it['key'] }}" 
                                                                id="add_menu_{{ $it['key'] }}">
                                                            <label class="form-check-label fw-bold text-dark" for="add_menu_{{ $it['key'] }}">
                                                                <i class="{{ $it['icon'] ?? 'bx bx-chevron-right' }} me-1 text-primary"></i>{{ $it['label'] }}
                                                            </label>
                                                        </div>

                                                        {{-- Granular Actions --}}
                                                        @if(!empty($it['permissions']))
                                                            <div class="ms-4 ps-2 mt-1 d-flex flex-wrap gap-2">
                                                                @foreach($it['permissions'] as $perm)
                                                                    @php
                                                                        $permLabel = explode('.', $perm)[1] ?? $perm;
                                                                        $permLabelFormatted = ucwords(str_replace('_', ' ', $permLabel));
                                                                    @endphp
                                                                    <div class="form-check form-check-inline m-0">
                                                                        <input class="form-check-input js-perm-checkbox add-perm-{{ $it['key'] }} add-perm-all-{{ $groupKey }}" 
                                                                            type="checkbox" 
                                                                            name="permissions[]" 
                                                                            value="{{ $perm }}" 
                                                                            id="add_perm_{{ str_replace('.', '_', $perm) }}">
                                                                        <label class="form-check-label small text-muted" for="add_perm_{{ str_replace('.', '_', $perm) }}">
                                                                            {{ $permLabelFormatted }}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Simpan Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- =========================================================================
     MODAL EDIT ROLE
========================================================================= --}}
@if (auth()->user()->hasPermission('roles.update'))
    <div class="modal fade" id="mdlEditRole" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white"><i class="bx bx-edit-alt me-1"></i> Edit Role & Hak Akses</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form id="formEditRole" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        {{-- Top Meta Inputs --}}
                        <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Role Slug <span class="text-danger">*</span></label>
                                <input name="slug" id="edit_slug" class="form-control" required>
                                <small class="text-muted">Slug identifier sistem</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Role Name <span class="text-danger">*</span></label>
                                <input name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Home Route</label>
                                <select name="home_route" id="edit_home_route" class="form-select">
                                    <option value="">(Otomatis)</option>
                                    @foreach ($homeCandidates as $c)
                                        <option value="{{ $c['route'] }}">{{ $c['label'] }} ({{ $c['route'] }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Permission & Menu Grid --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bx bx-check-double text-primary me-1"></i>Pengaturan Sidebar Menu & Hak Akses Tombol</h6>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary js-select-all-edit">Pilih Semua</button>
                                <button type="button" class="btn btn-outline-secondary js-unselect-all-edit">Batal Semua</button>
                            </div>
                        </div>

                        <div class="row g-3">
                            @foreach ($configGroups as $groupKey => $groupData)
                                @php
                                    $groupItems = $groups[$groupKey] ?? collect();
                                @endphp
                                @if($groupItems->isNotEmpty())
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card h-100 border shadow-none bg-white">
                                            <div class="card-header bg-label-primary py-2 px-3 d-flex justify-content-between align-items-center">
                                                <span class="fw-bold fs-7">
                                                    <i class="{{ $groupData['icon'] ?? 'bx bx-folder' }} me-1"></i>{{ $groupData['label'] }}
                                                </span>
                                                <a href="javascript:void(0);" class="text-primary small fw-semibold js-toggle-group-edit" data-group="{{ $groupKey }}">Toggle</a>
                                            </div>
                                            <div class="card-body p-3">
                                                @foreach ($groupItems as $it)
                                                    <div class="mb-3 pb-2 border-bottom border-light">
                                                        {{-- Menu Level Checkbox --}}
                                                        <div class="form-check form-switch mb-1">
                                                            <input class="form-check-input js-menu-checkbox-edit edit-menu-{{ $groupKey }}" 
                                                                type="checkbox" 
                                                                name="menu_keys[]" 
                                                                value="{{ $it['key'] }}" 
                                                                id="edit_menu_{{ $it['key'] }}">
                                                            <label class="form-check-label fw-bold text-dark" for="edit_menu_{{ $it['key'] }}">
                                                                <i class="{{ $it['icon'] ?? 'bx bx-chevron-right' }} me-1 text-primary"></i>{{ $it['label'] }}
                                                            </label>
                                                        </div>

                                                        {{-- Granular Actions --}}
                                                        @if(!empty($it['permissions']))
                                                            <div class="ms-4 ps-2 mt-1 d-flex flex-wrap gap-2">
                                                                @foreach($it['permissions'] as $perm)
                                                                    @php
                                                                        $permLabel = explode('.', $perm)[1] ?? $perm;
                                                                        $permLabelFormatted = ucwords(str_replace('_', ' ', $permLabel));
                                                                    @endphp
                                                                    <div class="form-check form-check-inline m-0">
                                                                        <input class="form-check-input js-perm-checkbox-edit edit-perm-{{ $it['key'] }} edit-perm-all-{{ $groupKey }}" 
                                                                            type="checkbox" 
                                                                            name="permissions[]" 
                                                                            value="{{ $perm }}" 
                                                                            id="edit_perm_{{ str_replace('.', '_', $perm) }}">
                                                                        <label class="form-check-label small text-muted" for="edit_perm_{{ str_replace('.', '_', $perm) }}">
                                                                            {{ $permLabelFormatted }}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Update Perubahan Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ==========================================
    // 1. ADD MODAL HELPERS
    // ==========================================
    document.querySelector('.js-select-all-add')?.addEventListener('click', function () {
        document.querySelectorAll('#formAddRole input[type="checkbox"]').forEach(cb => cb.checked = true);
    });
    document.querySelector('.js-unselect-all-add')?.addEventListener('click', function () {
        document.querySelectorAll('#formAddRole input[type="checkbox"]').forEach(cb => cb.checked = false);
    });

    document.querySelectorAll('.js-toggle-group-add').forEach(btn => {
        btn.addEventListener('click', function () {
            const group = this.getAttribute('data-group');
            const checkboxes = document.querySelectorAll(`.add-menu-${group}, .add-perm-all-${group}`);
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
        });
    });

    // When a menu checkbox is clicked, toggle child permissions
    document.querySelectorAll('#formAddRole .js-menu-checkbox').forEach(menuCb => {
        menuCb.addEventListener('change', function () {
            const key = this.value;
            const childPerms = document.querySelectorAll(`.add-perm-${key}`);
            childPerms.forEach(p => p.checked = this.checked);
        });
    });

    // When a child permission is checked, ensure parent menu is checked
    document.querySelectorAll('#formAddRole .js-perm-checkbox').forEach(permCb => {
        permCb.addEventListener('change', function () {
            if (this.checked) {
                const parentGroup = this.className.match(/add-perm-([a-zA-Z0-9_]+)/);
                if (parentGroup && parentGroup[1]) {
                    const parentMenu = document.getElementById(`add_menu_${parentGroup[1]}`);
                    if (parentMenu) parentMenu.checked = true;
                }
            }
        });
    });


    // ==========================================
    // 2. EDIT MODAL HANDLERS
    // ==========================================
    const editModal = new bootstrap.Modal(document.getElementById('mdlEditRole'));
    const formEdit = document.getElementById('formEditRole');

    document.querySelectorAll('.js-edit').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const slug = this.getAttribute('data-slug');
            const name = this.getAttribute('data-name');
            const homeRoute = this.getAttribute('data-home_route') || '';
            const menuKeys = JSON.parse(this.getAttribute('data-menu_keys') || '[]');
            const permissions = JSON.parse(this.getAttribute('data-permissions') || '[]');

            formEdit.action = `/erp/roles/${id}`;
            document.getElementById('edit_slug').value = slug;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_home_route').value = homeRoute;

            // Reset all edit checkboxes
            formEdit.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);

            // Check assigned menus
            menuKeys.forEach(key => {
                const cb = document.getElementById(`edit_menu_${key}`);
                if (cb) cb.checked = true;
            });

            // Check assigned permissions
            permissions.forEach(perm => {
                const idStr = perm.replace(/\./g, '_');
                const cb = document.getElementById(`edit_perm_${idStr}`);
                if (cb) cb.checked = true;
            });

            editModal.show();
        });
    });

    document.querySelector('.js-select-all-edit')?.addEventListener('click', function () {
        document.querySelectorAll('#formEditRole input[type="checkbox"]').forEach(cb => cb.checked = true);
    });
    document.querySelector('.js-unselect-all-edit')?.addEventListener('click', function () {
        document.querySelectorAll('#formEditRole input[type="checkbox"]').forEach(cb => cb.checked = false);
    });

    document.querySelectorAll('.js-toggle-group-edit').forEach(btn => {
        btn.addEventListener('click', function () {
            const group = this.getAttribute('data-group');
            const checkboxes = document.querySelectorAll(`.edit-menu-${group}, .edit-perm-all-${group}`);
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
        });
    });

    document.querySelectorAll('#formEditRole .js-menu-checkbox-edit').forEach(menuCb => {
        menuCb.addEventListener('change', function () {
            const key = this.value;
            const childPerms = document.querySelectorAll(`.edit-perm-${key}`);
            childPerms.forEach(p => p.checked = this.checked);
        });
    });

    document.querySelectorAll('#formEditRole .js-perm-checkbox-edit').forEach(permCb => {
        permCb.addEventListener('change', function () {
            if (this.checked) {
                const parentGroup = this.className.match(/edit-perm-([a-zA-Z0-9_]+)/);
                if (parentGroup && parentGroup[1]) {
                    const parentMenu = document.getElementById(`edit_menu_${parentGroup[1]}`);
                    if (parentMenu) parentMenu.checked = true;
                }
            }
        });
    });


    // ==========================================
    // 3. DELETE ROLE HANDLER
    // ==========================================
    document.querySelectorAll('.js-del').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');

            Swal.fire({
                title: 'Hapus Role?',
                text: `Apakah Anda yakin ingin menghapus role "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/erp/roles/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Terhapus!', 'Role berhasil dihapus.', 'success')
                                .then(() => window.location.reload());
                        } else {
                            Swal.fire('Gagal!', data.message || 'Terjadi kesalahan saat menghapus.', 'error');
                        }
                    })
                    .catch(() => {
                        Swal.fire('Error!', 'Tidak dapat terhubung ke server.', 'error');
                    });
                }
            });
        });
    });
});
</script>
@endpush
@endsection
