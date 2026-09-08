<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="light" data-layout="vertical"
    data-assets-path="{{ asset('sneat/assets/') }}" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'ERP Dashboard')</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('sneat/assets/img/favicon/favicon.ico') }}" />

    {{-- Fonts & Icons --}}
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/fonts/boxicons.css') }}" />

    {{-- Core CSS --}}
    <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/css/core.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/css/theme-default.css') }}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('sneat/assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    {{-- Instant Theme & Layout Initialization (Zero Flicker) --}}
    <script>
        (function() {
            const savedTheme = localStorage.getItem('mangga_theme') || 'light';
            const savedLayout = localStorage.getItem('mangga_nav_layout') || 'vertical';
            const savedAccent = localStorage.getItem('mangga_accent_color') || '#696cff';

            let effectiveTheme = savedTheme;
            if (savedTheme === 'auto') {
                effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            document.documentElement.setAttribute('data-theme', effectiveTheme);
            document.documentElement.setAttribute('data-layout', savedLayout);
            document.documentElement.style.setProperty('--primary-accent', savedAccent);

            if (effectiveTheme === 'dark') {
                document.documentElement.classList.add('dark-style');
                document.documentElement.classList.remove('light-style');
            } else {
                document.documentElement.classList.add('light-style');
                document.documentElement.classList.remove('dark-style');
            }
        })();
    </script>

    {{-- Comprehensive Theme (Dark/Light) & Navigation CSS Engine --}}
    <style>
        /* ================== LIGHT THEME TOKENS ================== */
        :root {
            --primary-accent: #696cff;
            --bg-body: #f5f7fb;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --menu-bg: #ffffff;
            --navbar-bg: #ffffff;
            --footer-bg: #ffffff;
            --input-bg: #ffffff;
            --table-hover: #f8fafc;
            --table-header: #f8f9fa;
        }

        /* ================== DARK THEME TOKENS ================== */
        html[data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --menu-bg: #1e293b;
            --navbar-bg: #1e293b;
            --footer-bg: #1e293b;
            --input-bg: #0f172a;
            --table-hover: #334155;
            --table-header: #1e293b;
        }

        body {
            background-color: var(--bg-body) !important;
            color: var(--text-main) !important;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        /* Typography Auto Adapt */
        html[data-theme="light"] h1:not(.text-white),
        html[data-theme="light"] h2:not(.text-white),
        html[data-theme="light"] h3:not(.text-white),
        html[data-theme="light"] h4:not(.text-white),
        html[data-theme="light"] h5:not(.text-white),
        html[data-theme="light"] h6:not(.text-white),
        html[data-theme="light"] .text-dark {
            color: #1e293b !important;
        }

        html[data-theme="dark"] h1:not(.text-white):not(.text-primary):not(.text-warning):not(.text-success):not(.text-danger):not(.text-info),
        html[data-theme="dark"] h2:not(.text-white):not(.text-primary):not(.text-warning):not(.text-success):not(.text-danger):not(.text-info),
        html[data-theme="dark"] h3:not(.text-white):not(.text-primary):not(.text-warning):not(.text-success):not(.text-danger):not(.text-info),
        html[data-theme="dark"] h4:not(.text-white):not(.text-primary):not(.text-warning):not(.text-success):not(.text-danger):not(.text-info),
        html[data-theme="dark"] h5:not(.text-white):not(.text-primary):not(.text-warning):not(.text-success):not(.text-danger):not(.text-info),
        html[data-theme="dark"] h6:not(.text-white):not(.text-primary):not(.text-warning):not(.text-success):not(.text-danger):not(.text-info),
        html[data-theme="dark"] .text-dark {
            color: #f1f5f9 !important;
        }

        html[data-theme="dark"] .text-muted {
            color: var(--text-muted) !important;
        }

        /* Card & Containers Styling (Never override colored cards like bg-primary) */
        .card:not([class*="bg-"]),
        .modal-content,
        .dropdown-menu,
        .offcanvas,
        .toast {
            background-color: var(--bg-card) !important;
            color: var(--text-main) !important;
            border-color: var(--border-color) !important;
        }

        /* Preserve and Enhance bg-primary Banners in both modes */
        .card.bg-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%) !important;
            color: #ffffff !important;
        }
        .card.bg-primary .text-white {
            color: #ffffff !important;
        }
        html[data-theme="dark"] .card.bg-primary {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #1e293b 100%) !important;
            border: 1px solid #4338ca !important;
            color: #ffffff !important;
        }

        .card-header:not([class*="bg-"]),
        .modal-header,
        .modal-footer {
            background-color: var(--bg-card) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }

        .layout-menu.menu {
            background-color: var(--menu-bg) !important;
            border-color: var(--border-color) !important;
        }

        .bg-navbar-theme {
            background-color: var(--navbar-bg) !important;
            color: var(--text-main) !important;
            border-color: var(--border-color) !important;
        }

        .footer.bg-footer-theme {
            background-color: var(--footer-bg) !important;
            color: var(--text-muted) !important;
            border-top: 1px solid var(--border-color);
        }

        .form-control,
        .form-select {
            background-color: var(--input-bg) !important;
            color: var(--text-main) !important;
            border-color: var(--border-color) !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-accent) !important;
            box-shadow: 0 0 0 0.25rem rgba(105, 108, 255, 0.25) !important;
        }

        /* Table & DataTables Dark Mode Adaptations */
        .table {
            color: var(--text-main) !important;
            border-color: var(--border-color) !important;
        }
        .table thead th,
        .table-light {
            background-color: var(--table-header) !important;
            color: var(--text-main) !important;
            border-color: var(--border-color) !important;
        }
        .table-hover tbody tr:hover td {
            background-color: var(--table-hover) !important;
        }

        html[data-theme="dark"] .dataTables_wrapper .dataTables_info,
        html[data-theme="dark"] .dataTables_wrapper .dataTables_length,
        html[data-theme="dark"] .dataTables_wrapper .dataTables_filter {
            color: var(--text-muted) !important;
        }

        html[data-theme="dark"] .dropdown-item {
            color: var(--text-main) !important;
        }
        html[data-theme="dark"] .dropdown-item:hover {
            background-color: #334155 !important;
            color: #ffffff !important;
        }
        html[data-theme="dark"] .dropdown-divider {
            border-color: var(--border-color) !important;
        }
        html[data-theme="dark"] .bg-light {
            background-color: #1e293b !important;
        }
        html[data-theme="dark"] .border-bottom,
        html[data-theme="dark"] .border-top,
        html[data-theme="dark"] .border {
            border-color: var(--border-color) !important;
        }
        html[data-theme="dark"] .list-group-item {
            background-color: var(--bg-card) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }

        /* ================== HORIZONTAL NAV LAYOUT CSS (ISOLATED CLEAN DROPDOWNS) ================== */
        .horizontal-nav-wrapper {
            background-color: var(--navbar-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 0.35rem 0;
            position: sticky;
            top: 0;
            z-index: 1040;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            overflow: visible !important;
        }

        .horizontal-nav-list {
            gap: 0.3rem;
            flex-wrap: wrap;
            overflow: visible !important;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .horizontal-nav-list .nav-item {
            position: relative !important;
        }

        .horizontal-nav-list .nav-link {
            color: var(--text-main);
            font-size: 0.83rem;
            font-weight: 500;
            padding: 0.42rem 0.8rem;
            border-radius: 0.5rem;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .horizontal-nav-list .nav-link:hover {
            background-color: rgba(105, 108, 255, 0.1);
            color: var(--primary-accent) !important;
        }

        .horizontal-nav-list .nav-link.active {
            background-color: var(--primary-accent) !important;
            color: #ffffff !important;
            box-shadow: 0 2px 4px rgba(105, 108, 255, 0.3);
        }

        /* STRICT DROPDOWN POPOVER: Hidden by default, only active one shows */
        .horizontal-nav-list .dropdown-menu {
            display: none !important;
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: auto !important;
            z-index: 1080 !important;
            min-width: 220px !important;
            border-radius: 0.65rem !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            background-color: var(--bg-card) !important;
            border: 1px solid var(--border-color) !important;
            padding: 0.45rem 0 !important;
            margin-top: 0.25rem !important;
        }

        /* Show ONLY the single hovered or clicked dropdown */
        .horizontal-nav-list .nav-item.dropdown:hover > .dropdown-menu,
        .horizontal-nav-list .dropdown-menu.show {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .horizontal-nav-list .dropdown-item {
            padding: 0.5rem 1rem !important;
            font-size: 0.82rem !important;
            color: var(--text-main) !important;
            display: flex;
            align-items: center;
            transition: background 0.15s ease;
        }

        .horizontal-nav-list .dropdown-item:hover {
            background-color: rgba(105, 108, 255, 0.1) !important;
            color: var(--primary-accent) !important;
        }

        /* Mode: Horizontal Layout Display Rules */
        html[data-layout="horizontal"] #layout-menu {
            display: none !important;
        }
        html[data-layout="horizontal"] .layout-page {
            padding-left: 0 !important;
            margin-left: 0 !important;
            width: 100% !important;
        }
        html[data-layout="horizontal"] #main-sidebar-toggle-wrapper {
            display: none !important;
        }
        html[data-layout="horizontal"] .horizontal-nav-wrapper {
            display: block !important;
        }

        /* Mode: Vertical Layout Display Rules */
        html[data-layout="vertical"] .horizontal-nav-wrapper {
            display: none !important;
        }
        html[data-layout="vertical"] #main-sidebar-toggle-wrapper {
            display: flex !important;
        }

        /* Full Width Fluid Layout */
        .container-xxl,
        .container-xl,
        .container-lg,
        .container-md,
        .container-sm,
        .container {
            max-width: 100% !important;
            width: 100% !important;
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        .layout-page,
        .content-wrapper {
            width: 100% !important;
        }

        /* Smooth Sidebar Collapse for Desktop */
        @media (min-width: 1200px) {
            #layout-menu {
                transition: transform 0.25s ease-in-out, width 0.25s ease-in-out !important;
            }
            .layout-page {
                transition: padding-left 0.25s ease-in-out !important;
            }
            html.layout-menu-collapsed #layout-menu {
                transform: translate3d(-100%, 0, 0) !important;
            }
            html.layout-menu-collapsed .layout-page {
                padding-left: 0 !important;
            }
        }
    </style>

    {{-- Helpers & Config --}}
    <script src="{{ asset('sneat/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('sneat/assets/js/config.js') }}"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @vite(['resources/js/app.js'])

    {{-- 🔔 CONSOLIDATED NOTIFICATION LOGIC --}}
    <script>
        const NOTIF_SOUND_URL = "{{ asset('assets/sounds/notif.mp3') }}";
        let lastNotifTime = 0;

        function playNotificationSound() {
            const now = Date.now();
            if (now - lastNotifTime < 1500) return;
            lastNotifTime = now;
            const audio = new Audio(NOTIF_SOUND_URL);
            audio.play().catch(e => console.warn('🔔 [Notif] Sound error:', e));
        }

        function fetchNavbarNotifications() {
            fetch('/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    const list = document.getElementById('navbarNotificationList');
                    const badge = document.getElementById('navbarNotificationBadge');
                    if (!list || !badge) return;

                    const unreadCount = data.unread_count || 0;
                    if (unreadCount > 0) {
                        badge.innerText = unreadCount;
                        badge.classList.remove('d-none');
                    } else {
                        badge.classList.add('d-none');
                    }

                    if (!data.notifications || data.notifications.length === 0) {
                        list.innerHTML = '<li class="list-group-item text-center py-4 text-muted">Belum ada notifikasi.</li>';
                        return;
                    }

                    const unreadNotifs = data.notifications.filter(n => !n.is_read);
                    const readNotifs = data.notifications.filter(n => n.is_read);

                    let finalHtml = '';
                    if (unreadNotifs.length > 0) {
                        finalHtml += `<li class="dropdown-header bg-light py-2 fw-semibold" style="font-size:11px; color:#696cff;">Penting</li>`;
                        finalHtml += unreadNotifs.map(n => renderNotifItem(n)).join('');
                    }
                    if (readNotifs.length > 0) {
                        finalHtml += `<li class="dropdown-header bg-light py-2 fw-semibold" style="font-size:11px; color:#8592a3;">Notifikasi lainnya</li>`;
                        finalHtml += readNotifs.map(n => renderNotifItem(n)).join('');
                    }

                    list.innerHTML = finalHtml || '<li class="list-group-item text-center py-4 text-muted">Belum ada notifikasi.</li>';
                }).catch(() => {});
        }

        function renderNotifItem(n) {
            return `
            <li class="list-group-item list-group-item-action dropdown-notifications-item ${n.is_read ? '' : 'unread'}" 
                style="cursor: pointer;" onclick="handleNotifClick('${n.id}', '${n.url}')">
                <div class="d-flex align-items-center position-relative">
                    <div class="avatar-wrapper me-3">
                        <div class="avatar-initial rounded-circle bg-label-primary d-flex align-items-center justify-content-center" 
                             style="width: 38px; height: 38px; font-size:18px;">
                            <i class="bx bx-bell"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <h6 class="mb-0 text-truncate" style="font-size:13px; font-weight: ${n.is_read ? '400' : '600'}">${n.title}</h6>
                        <p class="mb-0 text-muted text-truncate" style="font-size:12px">${n.body || ''}</p>
                        <small class="text-muted" style="font-size:11px">${n.created_at || 'Baru saja'}</small>
                    </div>
                </div>
            </li>`;
        }

        function handleNotifClick(id, url) {
            fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).finally(() => {
                if (url) window.location.href = url;
                else fetchNavbarNotifications();
            });
        }
    </script>

    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    @stack('styles')
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            {{-- 1. Sidebar (Vertical Mode) --}}
            @include('layouts.partials.sidebar')

            <div class="layout-page">
                {{-- 2. Top Navbar --}}
                @include('layouts.partials.navbar')

                {{-- 3. Horizontal Nav (Header Bar Mode) --}}
                @include('layouts.partials.horizontal_menu')

                {{-- 4. Main Content Area --}}
                <div class="content-wrapper">
                    @yield('content')
                </div>

                {{-- 5. Footer --}}
                <footer class="content-footer footer bg-footer-theme">
                    <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                        <div class="mb-2 mb-md-0">
                            © <script>document.write(new Date().getFullYear())</script> MANDAU ERP || V.0.3.5
                        </div>
                    </div>
                </footer>

                <div class="content-backdrop fade"></div>
            </div>
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    {{-- Core JS --}}
    <script src="{{ asset('sneat/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('sneat/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('sneat/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('sneat/assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('sneat/assets/js/main.js') }}"></script>

    {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    @stack('scripts')

    {{-- 🌓 THEME & LAYOUT CONTROLLER JS ENGINE --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Initial State Sync
            const currentTheme = localStorage.getItem('mangga_theme') || 'light';
            const currentLayout = localStorage.getItem('mangga_nav_layout') || 'vertical';

            updateThemeUI(currentTheme);
            updateLayoutUI(currentLayout);

            // 2. Theme Toggle Button in Navbar (Sun / Moon)
            const btnToggleTheme = document.getElementById('btnToggleTheme');
            if (btnToggleTheme) {
                btnToggleTheme.addEventListener('click', function () {
                    const activeTheme = document.documentElement.getAttribute('data-theme');
                    const newTheme = (activeTheme === 'dark') ? 'light' : 'dark';
                    setTheme(newTheme);
                });
            }

            // 3. Layout Toggle Button in Navbar (Sidebar / Topbar)
            const btnToggleNavLayout = document.getElementById('btnToggleNavLayout');
            if (btnToggleNavLayout) {
                btnToggleNavLayout.addEventListener('click', function () {
                    const activeLayout = document.documentElement.getAttribute('data-layout');
                    const newLayout = (activeLayout === 'horizontal') ? 'vertical' : 'horizontal';
                    setLayout(newLayout);
                });
            }

            // 4. Radio Buttons in Customizer Dropdown
            document.querySelectorAll('input[name="opt_theme"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    setTheme(this.value);
                });
            });

            document.querySelectorAll('input[name="opt_layout"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    setLayout(this.value);
                });
            });

            // 5. Accent Color Preset Buttons
            document.querySelectorAll('.js-set-accent').forEach(btn => {
                btn.addEventListener('click', function () {
                    const color = this.dataset.accent;
                    document.documentElement.style.setProperty('--primary-accent', color);
                    localStorage.setItem('mangga_accent_color', color);
                    Swal.fire({
                        icon: 'success',
                        title: 'Warna Tema Diubah',
                        timer: 1200,
                        showConfirmButton: false
                    });
                });
            });

            // 6. Reset Defaults
            const btnReset = document.getElementById('btnResetThemeDefaults');
            if (btnReset) {
                btnReset.addEventListener('click', function () {
                    localStorage.removeItem('mangga_theme');
                    localStorage.removeItem('mangga_nav_layout');
                    localStorage.removeItem('mangga_accent_color');
                    setTheme('light');
                    setLayout('vertical');
                    document.documentElement.style.setProperty('--primary-accent', '#696cff');
                    Swal.fire({
                        icon: 'success',
                        title: 'Pengaturan Direset',
                        text: 'Tampilan kembali ke pengaturan standar.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                });
            }

            // 7. Horizontal Menu Clean Handler (Strictly only ONE open menu at a time)
            document.querySelectorAll('.horizontal-nav-list .nav-item.dropdown').forEach(item => {
                const menu = item.querySelector('.dropdown-menu');
                if (!menu) return;

                item.addEventListener('mouseenter', function() {
                    document.querySelectorAll('.horizontal-nav-list .dropdown-menu').forEach(m => {
                        if (m !== menu) m.classList.remove('show');
                    });
                });

                const toggle = item.querySelector('.dropdown-toggle');
                if (toggle) {
                    toggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const isShown = menu.classList.contains('show');
                        document.querySelectorAll('.horizontal-nav-list .dropdown-menu').forEach(m => m.classList.remove('show'));
                        if (!isShown) {
                            menu.classList.add('show');
                        }
                    });
                }
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.horizontal-nav-list')) {
                    document.querySelectorAll('.horizontal-nav-list .dropdown-menu').forEach(m => m.classList.remove('show'));
                }
            });

            function setTheme(theme) {
                localStorage.setItem('mangga_theme', theme);
                let eff = theme;
                if (theme === 'auto') {
                    eff = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-theme', eff);
                if (eff === 'dark') {
                    document.documentElement.classList.add('dark-style');
                    document.documentElement.classList.remove('light-style');
                } else {
                    document.documentElement.classList.add('light-style');
                    document.documentElement.classList.remove('dark-style');
                }
                updateThemeUI(theme);
            }

            function setLayout(layout) {
                localStorage.setItem('mangga_nav_layout', layout);
                document.documentElement.setAttribute('data-layout', layout);
                updateLayoutUI(layout);
            }

            function updateThemeUI(theme) {
                const iconTheme = document.getElementById('iconTheme');
                const eff = document.documentElement.getAttribute('data-theme');
                if (iconTheme) {
                    if (eff === 'dark') {
                        iconTheme.className = 'bx bx-sun bx-sm text-warning';
                    } else {
                        iconTheme.className = 'bx bx-moon bx-sm text-secondary';
                    }
                }
                const radio = document.querySelector(`input[name="opt_theme"][value="${theme}"]`);
                if (radio) radio.checked = true;
            }

            function updateLayoutUI(layout) {
                const iconLayout = document.getElementById('iconNavLayout');
                if (iconLayout) {
                    if (layout === 'horizontal') {
                        iconLayout.className = 'bx bx-sidebar bx-sm text-primary';
                    } else {
                        iconLayout.className = 'bx bx-dock-top bx-sm text-secondary';
                    }
                }
                const radio = document.querySelector(`input[name="opt_layout"][value="${layout}"]`);
                if (radio) radio.checked = true;
            }

            // Init Notifications
            fetchNavbarNotifications();

            // Init Tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>
