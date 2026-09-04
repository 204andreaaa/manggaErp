    <!DOCTYPE html>
    <html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default"
        data-assets-path="{{ asset('sneat/assets/') }}" data-template="vertical-menu-template-free">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>@yield('title', 'Dashboard')</title>

        {{-- Favicon --}}
        <link rel="icon" type="image/x-icon" href="{{ asset('sneat/assets/img/favicon/favicon.ico') }}" />

        {{-- Fonts & Icons --}}
        <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap"
            rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/fonts/boxicons.css') }}" />

        {{-- Core CSS --}}
        <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/css/core.css') }}"
            class="template-customizer-core-css" />
        <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/css/theme-default.css') }}"
            class="template-customizer-theme-css" />
        <link rel="stylesheet" href="{{ asset('sneat/assets/css/demo.css') }}" />
        <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
        <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/libs/apex-charts/apex-charts.css') }}" />
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

        <!-- Helpers & Config -->
        <script src="{{ asset('sneat/assets/vendor/js/helpers.js') }}"></script>
        <script src="{{ asset('sneat/assets/js/config.js') }}"></script>

        {{-- Custom Theme (dark/light sync) --}}
        <style>
            :root {
                --bg-body: #f5f7fb;
                --bg-card: #ffffff;
                --text: #1f2937;
                --muted: #6b7280;
                --border: #e5e7eb;
                --menu-bg: #ffffff;
                --navbar-bg: #ffffff;
                --footer-bg: #ffffff;
                --input-bg: #ffffff;
            }

            body {
                background: var(--bg-body);
                color: var(--text);
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
                .layout-menu-toggle i {
                    transition: transform 0.25s ease;
                }
                html.layout-menu-collapsed .layout-menu-toggle i.bx-chevron-left {
                    transform: rotate(180deg);
                }
            }

            .layout-menu.menu {
                background: var(--menu-bg) !important;
            }

            .bg-navbar-theme {
                background: var(--navbar-bg) !important;
            }

            .footer.bg-footer-theme {
                background: var(--footer-bg) !important;
            }

            .card,
            .modal-content,
            .dropdown-menu,
            .offcanvas,
            .list-group,
            .toast {
                background: var(--bg-card);
                color: var(--text);
                border-color: var(--border);
            }

            .form-control,
            .form-select {
                background: var(--input-bg) !important;
                color: var(--text) !important;
                border-color: var(--border) !important;
            }

            /* Full Width Fluid Layout (No empty side gaps on zoom out / wide screens) */
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

            .card,
            .table-responsive {
                width: 100% !important;
            }
        </style>

        {{-- Helpers & Config --}}
        <script src="{{ asset('sneat/assets/vendor/js/helpers.js') }}"></script>
        <script src="{{ asset('sneat/assets/js/config.js') }}"></script>

        {{-- SweetAlert2 --}}
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        {{-- Mode system/dark/light --}}
        <script>
            (function() {
                const key = 'theme-preference',
                    pref = localStorage.getItem(key) || 'system';
                const isDark = pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)')
                    .matches);
                document.documentElement.setAttribute('data-color-scheme', isDark ? 'dark' : 'light');
            })();
        </script>

        @vite(['resources/js/app.js'])


        {{-- 🔔 CONSOLIDATED REAL-TIME NOTIFICATION SYSTEM (Echo + Sound + Navbar + Sidebar) --}}
        <script>
            // Map: menu key → notification type(s) yang relevan buat sidebar badge
            const SIDEBAR_BADGE_MAP = {
                'sales_return_approval': 'new_return', // WH Admin & Superadmin
                'sales_return': 'return_rejected', // Sales (perlu resubmit)
                'wh_reconcile': 'handover_payment_submitted', // WH Admin
                'sales_otp': 'TASK_issued_stock', // Sales (Issued Stock History)
                'sales-handover-otp': 'TASK_waiting_otp', // Sales (Handover Verification)
                'sales_request_approval': 'TASK_pending_stock_request', // WH Admin (Approval)
                'sales_request': 'stock_request_approved,stock_request_rejected', // Sales (Request)
                'wh_transfers': 'TASK_pending_transfer', // WH Transfer (Pending/Approved)
                'wh_settlements': 'TASK_pending_settlement', // WH Pending Settlement
                'finance_settlements': 'new_settlement_submitted', // Finance New Submission
            };

            const NOTIF_SOUND_URL = "{{ asset('assets/sounds/notif.mp3') }}";

            let lastNotifTime = 0;

            function playNotificationSound() {
                const now = Date.now();
                if (now - lastNotifTime < 1500) return; // Cooldown 1.5s to prevent noise if multi-tab/event
                lastNotifTime = now;

                const audio = new Audio(NOTIF_SOUND_URL);
                audio.play().then(() => {
                    console.log('🔔 [Notif] Sound played successfully');
                }).catch(e => {
                    console.warn('🔔 [Notif] Autoplay blocked or file error:', e);
                });
            }

            function refreshSidebarBadges() {
                Object.entries(SIDEBAR_BADGE_MAP).forEach(([menuKey, type]) => {
                    const container = document.querySelector(`#menu-item-${menuKey} .badge-container`);
                    if (!container) return;

                    fetch(`/notifications/badge?type=${type}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            container.innerHTML = (data.count > 0) ?
                                `<span class="badge rounded-pill bg-danger" style="font-size:10px;min-width:18px;">${data.count}</span>` :
                                '';
                        });
                });
            }

            function fetchNavbarNotifications() {
                fetch('/notifications', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
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

                        if (data.notifications.length === 0) {
                            list.innerHTML =
                                '<li class="list-group-item text-center py-4 text-muted">No notifications yet.</li>';
                            return;
                        }

                        // Kita pisah: Penting (Unread) vs Lainnya (Read) ala screenshot
                        const unreadNotifs = data.notifications.filter(n => !n.is_read);
                        const readNotifs = data.notifications.filter(n => n.is_read);

                        let finalHtml = '';

                        if (unreadNotifs.length > 0) {
                            finalHtml +=
                                `<li class="dropdown-header bg-light py-2 fw-semibold" style="font-size:11px; color:#696cff;">Penting</li>`;
                            finalHtml += unreadNotifs.map(n => renderNotifItem(n)).join('');
                        }

                        if (readNotifs.length > 0) {
                            finalHtml +=
                                `<li class="dropdown-header bg-light py-2 fw-semibold" style="font-size:11px; color:#8592a3;">Notifikasi lainnya</li>`;
                            finalHtml += readNotifs.map(n => renderNotifItem(n)).join('');
                        }

                        list.innerHTML = finalHtml ||
                            '<li class="list-group-item text-center py-4 text-muted">No notifications yet.</li>';
                    });
            }

            function renderNotifItem(n) {
                // Mapping Type ke Icon & Warna ala Dashboard Premium
                const config = {
                    'po_approval_needed': {
                        icon: 'bx-check-shield',
                        color: 'bg-label-warning',
                        text: 'text-warning'
                    },
                    'rf_approval_needed': {
                        icon: 'bx-file',
                        color: 'bg-label-primary',
                        text: 'text-primary'
                    },
                    'pa_approval_needed': {
                        icon: 'bx-credit-card',
                        color: 'bg-label-success',
                        text: 'text-success'
                    },
                    'gr_ready': {
                        icon: 'bx-truck',
                        color: 'bg-label-info',
                        text: 'text-info'
                    },
                    'po_approved': {
                        icon: 'bx-check-circle',
                        color: 'bg-label-success',
                        text: 'text-success'
                    },
                    'po_rejected': {
                        icon: 'bx-error-circle',
                        color: 'bg-label-danger',
                        text: 'text-danger'
                    },
                    'stock_request_approved': {
                        icon: 'bx-check-circle',
                        color: 'bg-label-success',
                        text: 'text-success'
                    },
                    'stock_request_rejected': {
                        icon: 'bx-error-circle',
                        color: 'bg-label-danger',
                        text: 'text-danger'
                    },
                    'new_stock_request': {
                        icon: 'bx-cart',
                        color: 'bg-label-primary',
                        text: 'text-primary'
                    },
                    'default': {
                        icon: 'bx-bell',
                        color: 'bg-label-secondary',
                        text: 'text-secondary'
                    }
                };

                const setting = config[n.type] || config['default'];

                return `
                <li class="list-group-item list-group-item-action dropdown-notifications-item ${n.is_read ? '' : 'unread'}" 
                    style="cursor: pointer;"
                    onclick="handleNotifClick('${n.id}', '${n.url}')">
                    <div class="d-flex align-items-center position-relative">
                        ${!n.is_read ? '<span class="notif-dot"></span>' : ''}
                        <div class="avatar-wrapper me-3">
                            <div class="avatar-initial rounded-circle ${setting.color} ${setting.text} d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px; font-size:20px;">
                                <i class="bx ${setting.icon}"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <h6 class="mb-0 text-truncate" style="font-size:13px; font-weight: ${n.is_read ? '400' : '600'}">${n.title}</h6>
                            <p class="mb-0 text-muted text-truncate" style="font-size:12px">${n.body || ''}</p>
                            <small class="text-muted" style="font-size:11px">${n.created_at || n.time_ago || 'Baru saja'}</small>
                        </div>
                    </div>
                </li>
            `;
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

            document.addEventListener('DOMContentLoaded', function() {
                // Initial Load
                refreshSidebarBadges();
                fetchNavbarNotifications();

                // Init Tooltips
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                // Initialize Echo Listeners
                // Mark All Read Handler
                const markAllBtn = document.getElementById('markAllReadBtn');
                if (markAllBtn) {
                    markAllBtn.addEventListener('click', function() {
                        fetch('/notifications/mark-all-read', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content,
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }).then(() => {
                            fetchNavbarNotifications();
                            refreshSidebarBadges();
                        });
                    });
                }
                
                // Global Modal Focus Fix: Prevents "Blocked aria-hidden" warnings in console
                document.addEventListener('hidden.bs.modal', function() {
                    if (document.activeElement && document.activeElement.tagName !== 'BODY') {
                        document.activeElement.blur();
                    }
                });
            });

            // Global exposing
            window.refreshSidebarBadges = refreshSidebarBadges;
            window.fetchNavbarNotifications = fetchNavbarNotifications;
            window.handleNotifClick = handleNotifClick;
            window.playNotificationSound = playNotificationSound;
        </script>

        <style>
            .dropdown-notifications-list {
                max-height: 480px;
                overflow-y: auto;
                scrollbar-width: thin;
                /* Firefox */
            }

            /* Custom Scrollbar for Chrome/Safari */
            .dropdown-notifications-list::-webkit-scrollbar {
                width: 5px;
            }

            .dropdown-notifications-list::-webkit-scrollbar-track {
                background: transparent;
            }

            .dropdown-notifications-list::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            .dropdown-notifications-list::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }

            .dropdown-notifications-item {
                cursor: pointer;
                transition: all 0.2s;
                padding: 12px 20px !important;
                border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            }

            .dropdown-notifications-item:hover {
                background-color: rgba(69, 71, 255, 0.04);
            }

            .dropdown-notifications-item.unread {
                background-color: #f4f6ff;
                /* Tint biru sangat muda ala screenshot */
            }

            .dropdown-notifications-item.unread:hover {
                background-color: #ebf0ff;
            }

            .dropdown-notifications-item .notif-dot {
                position: absolute;
                left: -12px;
                top: 50%;
                transform: translateY(-50%);
                width: 8px;
                height: 8px;
                background: #696cff;
                border-radius: 50%;
                display: inline-block;
                box-shadow: 0 0 0 2px #fff;
            }
        </style>

        {{-- DataTables CSS --}}
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

        @stack('styles')
    </head>

    <body>
        <div class="layout-wrapper layout-content-navbar">
            <div class="layout-container">

                {{-- Sidebar --}}
                @include('layouts.partials.sidebar')

                <div class="layout-page">
                    {{-- Navbar --}}
                    @include('layouts.partials.navbar')

                    {{-- Content --}}
                    <div class="content-wrapper">
                        @yield('content')
                    </div>

                    {{-- Footer --}}
                    <footer class="content-footer footer bg-footer-theme">
                        <div
                            class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                            <div class="mb-2 mb-md-0">
                                ©
                                <script>
                                    document.write(new Date().getFullYear())
                                </script>, made with MANDAU || V.0.3.2

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
        {{-- <script src="{{ asset('sneat/assets/vendor/libs/apex-charts/apex-charts.js') }}"></script> --}}
        <script src="{{ asset('sneat/assets/js/main.js') }}"></script>

        {{-- DataTables JS --}}
        <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

        @stack('scripts')

        <!-- Global Anti Double-Click & Submit Loader -->
        <script>
            // ✅ FUNGSI SAPU BERSIH GLOBAL: Reset tombol biar nggak macet
            window.resetSubmitButton = function(form) {
                if (!form) return;
                form.dataset.submitting = 'false';

                const btns = form.querySelectorAll('button[type="submit"], button.btn, input[type="submit"]');
                btns.forEach(btn => {
                    if (btn.dataset.originalHtml) {
                        btn.innerHTML = btn.dataset.originalHtml;
                        btn.style.pointerEvents = 'auto';
                        btn.style.opacity = '1';
                        btn.style.width = btn.dataset.originalWidth || '';

                        delete btn.dataset.originalHtml;
                        delete btn.dataset.originalWidth;
                    }
                    btn.disabled = false; // Failsafe buat button yang di-disabled manual
                });
            };

            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (form.dataset.submitting === 'true') {
                    e.preventDefault();
                    return;
                }

                const btn = e.submitter || form.querySelector('button[type="submit"]');
                if (btn && !btn.dataset.originalHtml) {
                    form.dataset.submitting = 'true';
                    btn.dataset.originalHtml = btn.innerHTML;
                    btn.dataset.originalWidth = btn.style.getPropertyValue('width') || '';

                    const currentWidth = btn.getBoundingClientRect().width;
                    btn.style.width = currentWidth + 'px';

                    btn.style.pointerEvents = 'none';
                    btn.style.opacity = '0.7';

                    if (!btn.innerHTML.includes('spinner-border')) {
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + btn.innerHTML;
                    }
                }

                setTimeout(() => {
                    if (form.dataset.submitting === 'true') window.resetSubmitButton(form);
                }, 10000);
            });

            // ✅ 1. RESET UNTUK JQUERY AJAX
            if (window.jQuery) {
                $(document).ajaxComplete(function() {
                    document.querySelectorAll('form[data-submitting="true"]').forEach(form => window.resetSubmitButton(form));
                });
            }

            // ✅ 2. RESET UNTUK NATIVE FETCH (Sering dipake di menu baru)
            const originalFetch = window.fetch;
            window.fetch = async (...args) => {
                try {
                    return await originalFetch(...args);
                } finally {
                    document.querySelectorAll('form[data-submitting="true"]').forEach(form => window.resetSubmitButton(form));
                }
            };

            // ✅ GLOBAL DESKTOP & MOBILE SIDEBAR TOGGLE HANDLER
            document.addEventListener('DOMContentLoaded', function () {
                if (localStorage.getItem('mangga_menu_collapsed') === 'true' && window.innerWidth >= 1200) {
                    document.documentElement.classList.add('layout-menu-collapsed');
                }

                document.querySelectorAll('.layout-menu-toggle').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (window.innerWidth >= 1200) {
                            const isCollapsed = document.documentElement.classList.toggle('layout-menu-collapsed');
                            localStorage.setItem('mangga_menu_collapsed', isCollapsed ? 'true' : 'false');
                        } else {
                            document.documentElement.classList.toggle('layout-menu-expanded');
                        }
                    });
                });
            });
        </script>
    </body>

    </html>
