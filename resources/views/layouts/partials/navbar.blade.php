{{-- resources/views/layouts/partials/navbar.blade.php --}}
@php
    $user      = auth()->user();
    $userName  = $user?->name ?? 'Guest';
    $userRole  = $user?->roles?->first()?->name ?? '-';
@endphp

<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
  {{-- Toggle menu (desktop & mobile) --}}
  <div class="layout-menu-toggle navbar-nav align-items-center me-3" id="main-sidebar-toggle-wrapper">
    <a class="nav-item nav-link px-0 me-xl-4 text-dark" href="javascript:void(0)" title="Hide / Show Sidebar">
      <i class="bx bx-menu bx-sm"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center w-100" id="navbar-collapse">

    {{-- GLOBAL SEARCH --}}
    <div class="navbar-nav align-items-center flex-grow-1">
      <div class="nav-item d-flex align-items-center w-100" style="max-width: 420px;">
        <span class="d-inline-flex align-items-center justify-content-center me-2">
          <i class="bx bx-search fs-4 lh-1 text-muted"></i>
        </span>
        <input
          id="globalSearch"
          type="text"
          class="form-control border-0 shadow-none bg-transparent"
          placeholder="Cari menu, data, user..."
          aria-label="Search"
          autocomplete="off"
        >
      </div>
    </div>

    <ul class="navbar-nav flex-row align-items-center ms-auto gap-2">

      {{-- 1. SWITCHER NAV LAYOUT: SIDEBAR VS HEADER BAR --}}
      <li class="nav-item">
        <a class="nav-link btn-icon rounded-circle js-toggle-layout" href="javascript:void(0);" 
           data-bs-toggle="tooltip" data-bs-placement="bottom" title="Ganti Navigasi: Sidebar / Header Bar" id="btnToggleNavLayout">
          <i class="bx bx-dock-top bx-sm" id="iconNavLayout"></i>
        </a>
      </li>

      {{-- 2. SWITCHER THEME: DARK / LIGHT MODE --}}
      <li class="nav-item">
        <a class="nav-link btn-icon rounded-circle js-toggle-theme" href="javascript:void(0);" 
           data-bs-toggle="tooltip" data-bs-placement="bottom" title="Ganti Mode: Gelap / Terang" id="btnToggleTheme">
          <i class="bx bx-moon bx-sm" id="iconTheme"></i>
        </a>
      </li>

      {{-- 3. TEMPLATE CUSTOMIZER DROPDOWN (PENGATURAN TEMA & WARNA) --}}
      <li class="nav-item dropdown">
        <a class="nav-link btn-icon rounded-circle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" 
           data-bs-auto-close="outside" title="Pengaturan Tampilan & Warna">
          <i class="bx bx-palette bx-sm"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-end p-4 shadow-lg border-0" style="width: 320px; border-radius: 1rem;">
          <h6 class="fw-bold mb-3 d-flex align-items-center">
            <i class="bx bx-palette me-2 text-primary"></i> Kustomisasi Tampilan
          </h6>

          {{-- Mode Tema --}}
          <div class="mb-3">
            <label class="form-label text-muted small fw-semibold text-uppercase">Mode Tema</label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="opt_theme" id="theme_light" value="light">
              <label class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center gap-1" for="theme_light">
                <i class="bx bx-sun"></i> Terang
              </label>

              <input type="radio" class="btn-check" name="opt_theme" id="theme_dark" value="dark">
              <label class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center gap-1" for="theme_dark">
                <i class="bx bx-moon"></i> Gelap
              </label>

              <input type="radio" class="btn-check" name="opt_theme" id="theme_auto" value="auto">
              <label class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center gap-1" for="theme_auto">
                <i class="bx bx-desktop"></i> Auto
              </label>
            </div>
          </div>

          {{-- Posisi Navigasi --}}
          <div class="mb-3">
            <label class="form-label text-muted small fw-semibold text-uppercase">Navigasi Menu</label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="opt_layout" id="layout_vertical" value="vertical">
              <label class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center gap-1" for="layout_vertical">
                <i class="bx bx-sidebar"></i> Sidebar Kiri
              </label>

              <input type="radio" class="btn-check" name="opt_layout" id="layout_horizontal" value="horizontal">
              <label class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center gap-1" for="layout_horizontal">
                <i class="bx bx-dock-top"></i> Header Bar
              </label>
            </div>
          </div>

          {{-- Pilihan Warna Aksen (Color Preset) --}}
          <div class="mb-3">
            <label class="form-label text-muted small fw-semibold text-uppercase">Warna Aksen Brand</label>
            <div class="d-flex align-items-center justify-content-between gap-2 pt-1">
              <button type="button" class="btn p-0 rounded-circle js-set-accent border" style="width: 28px; height: 28px; background-color: #696cff;" data-accent="#696cff" title="Indigo (Default)"></button>
              <button type="button" class="btn p-0 rounded-circle js-set-accent border" style="width: 28px; height: 28px; background-color: #0ea5e9;" data-accent="#0ea5e9" title="Ocean Blue"></button>
              <button type="button" class="btn p-0 rounded-circle js-set-accent border" style="width: 28px; height: 28px; background-color: #10b981;" data-accent="#10b981" title="Emerald Green"></button>
              <button type="button" class="btn p-0 rounded-circle js-set-accent border" style="width: 28px; height: 28px; background-color: #f59e0b;" data-accent="#f59e0b" title="Amber Gold"></button>
              <button type="button" class="btn p-0 rounded-circle js-set-accent border" style="width: 28px; height: 28px; background-color: #ef4444;" data-accent="#ef4444" title="Crimson Red"></button>
              <button type="button" class="btn p-0 rounded-circle js-set-accent border" style="width: 28px; height: 28px; background-color: #8b5cf6;" data-accent="#8b5cf6" title="Royal Purple"></button>
            </div>
          </div>

          <div class="pt-2 border-top">
            <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="btnResetThemeDefaults">
              <i class="bx bx-reset me-1"></i> Reset Pengaturan Awal
            </button>
          </div>
        </div>
      </li>

      {{-- 4. NOTIFICATION DROPDOWN --}}
      <li class="nav-item dropdown-notifications navbar-dropdown dropdown">
        <a class="nav-link dropdown-toggle hide-arrow btn-icon rounded-circle" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="navbarNotificationDropdown">
          <i class="bx bx-bell bx-sm"></i>
          <span class="badge bg-danger rounded-pill badge-notifications d-none" id="navbarNotificationBadge">0</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end py-0" style="width: 350px;">
          <li class="dropdown-menu-header border-bottom">
            <div class="dropdown-header d-flex align-items-center py-3">
              <h5 class="text-body mb-0 me-auto">Notifikasi</h5>
              <a href="javascript:void(0)" class="dropdown-notifications-all text-body" data-bs-toggle="tooltip" data-bs-placement="top" title="Tandai semua dibaca" id="markAllReadBtn">
                <i class="bx fs-4 bx-envelope-open"></i>
              </a>
            </div>
          </li>
          <li class="dropdown-notifications-list scrollable-container">
            <ul class="list-group list-group-flush" id="navbarNotificationList">
              <li class="list-group-item list-group-item-action dropdown-notifications-item">
                <div class="d-flex">
                  <div class="flex-grow-1">
                    <h6 class="mb-1">Memuat notifikasi...</h6>
                  </div>
                </div>
              </li>
            </ul>
          </li>
          <li class="dropdown-menu-footer border-top p-3">
            <button class="btn btn-primary btn-sm w-100" onclick="window.location.reload()">Refresh Notifikasi</button>
          </li>
        </ul>
      </li>

      {{-- 5. USER DROPDOWN --}}
      <li class="nav-item dropdown dropdown-user ms-1">
        <a class="nav-link dropdown-toggle hide-arrow" href="#" data-bs-toggle="dropdown">
          <div class="avatar avatar-online">
            <img src="{{ asset('sneat/assets/img/avatars/1.png') }}"
                 class="w-px-40 h-auto rounded-circle"
                 alt="User avatar">
          </div>
        </a>

        <ul class="dropdown-menu dropdown-menu-end">
          <li class="px-3 py-2">
            <div class="d-flex align-items-center">
              <div class="avatar me-3">
                <img src="{{ asset('sneat/assets/img/avatars/1.png') }}"
                     class="w-px-40 h-auto rounded-circle"
                     alt="User avatar">
              </div>
              <div class="d-flex flex-column">
                <span class="fw-semibold">{{ $userName }}</span>
                <small class="text-muted">{{ $userRole }}</small>
              </div>
            </div>
          </li>

          <li><div class="dropdown-divider"></div></li>

          <li>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="dropdown-item">
                <i class="bx bx-power-off me-2"></i>
                <span>Log Out</span>
              </button>
            </form>
          </li>
        </ul>
      </li>

    </ul>
  </div>
</nav>
