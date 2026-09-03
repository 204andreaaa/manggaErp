@php
use Illuminate\Support\Facades\Route as R;

$u    = auth()->user();
$role = $u?->primaryRole()?->slug ?? 'guest';
$displayName = $u?->name ?? 'Guest';

// helper route aman
$rl = function (string $name, array $params = []) {
    return R::has($name) ? route($name, $params) : '#';
};

$dashboardRoute = 'dashboard';

// Role checks
$isSuperAdmin = $u?->hasRole('superadmin') || $u?->hasRole('admin');
$isAdminProject = $isSuperAdmin || $u?->hasRole(['admin_project', 'project_admin']) || $u?->canSeeMenu('work_items') || $u?->canSeeMenu('budget_parents') || $u?->canSeeMenu('sub_projects');
$isGA = $isSuperAdmin || $u?->hasRole(['ga', 'general_affair']) || $u?->canSeeMenu('goods_receipts');
$isProcurement = $isSuperAdmin || $u?->hasRole('procurement') || $u?->canSeeMenu('purchase_orders') || $u?->canSeeMenu('suppliers');
$isFinance = $isSuperAdmin || $u?->hasRole('finance') || $u?->canSeeMenu('payment_advices') || $u?->canSeeMenu('payment_advice_details');
$isLogistik = $isSuperAdmin || $u?->hasRole(['logistik', 'warehouse']) || $u?->canSeeMenu('stocks') || $u?->canSeeMenu('warehouses');
$isCEO = $isSuperAdmin || $u?->hasRole('ceo');
$isMaster = $isSuperAdmin || $u?->hasRole('procurement') || $u?->canSeeMenu('products') || $u?->canSeeMenu('uoms');
$isSystem = $isSuperAdmin || $u?->canSeeMenu('users') || $u?->canSeeMenu('roles') || $u?->canSeeMenu('projects') || $u?->canSeeMenu('approval_configs');

// Route Active States
$isProjectOpen = request()->routeIs('erp.budget-parents.*')
    || request()->routeIs('erp.sub-projects.*')
    || request()->routeIs('erp.work-items.*');

$isGaOpen = request()->routeIs('erp.goods-receipts.*');

$isProcurementOpen = request()->routeIs('erp.procurement.dashboard')
    || request()->routeIs('erp.purchase-orders.*')
    || request()->routeIs('erp.suppliers.*')
    || request()->routeIs('erp.payment-terms.*');

$isFinanceOpen = request()->routeIs('erp.payment-advices.*')
    || request()->routeIs('erp.payment-advice-details.*');

$isLogistikOpen = request()->routeIs('erp.stocks.*')
    || request()->routeIs('erp.warehouses.*');

$isCeoOpen = false;

$isMasterOpen = request()->routeIs('erp.products.*')
    || request()->routeIs('erp.uoms.*')
    || request()->routeIs('erp.product-families.*')
    || request()->routeIs('erp.product-types.*')
    || request()->routeIs('erp.brands.*')
    || request()->routeIs('erp.product-models.*')
    || request()->routeIs('erp.currencies.*');

$isSystemOpen = request()->routeIs('erp.users.*')
    || request()->routeIs('erp.roles.*')
    || request()->routeIs('erp.projects.*')
    || request()->routeIs('erp.approval-configs.*');
@endphp

<style>
    /* Styling for clear section headers and modern look */
    .layout-menu .menu-header {
        margin-top: 1.1rem !important;
        margin-bottom: 0.35rem !important;
        padding-left: 1.25rem !important;
    }
    .layout-menu .menu-header .menu-header-text {
        font-size: 0.72rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.08em;
        color: #8592a3;
        text-transform: uppercase;
    }
    /* Hide bullets for all submenus */
    .layout-menu .menu-sub .menu-item .menu-link::before,
    .layout-menu .menu-sub > .menu-item > .menu-link::before,
    .menu-vertical .menu-sub > .menu-item > .menu-link::before {
        display: none !important;
        content: none !important;
    }
    .layout-menu .menu-sub .menu-item .menu-link {
        padding-left: 2.2rem !important;
        font-size: 0.83rem !important;
    }
    .layout-menu .menu-item .badge {
        font-size: 0.68rem;
        padding: 0.25rem 0.45rem;
    }
</style>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ $rl($dashboardRoute) }}" class="app-brand-link">
            <span
                class="app-brand-text demo menu-text fw-bolder ms-2"
                title="{{ $displayName }}"
                style="
                    display:block;
                    max-width:190px;
                    white-space:normal;
                    overflow-wrap:anywhere;
                    line-height:1.15;
                    letter-spacing:0;
                    font-size:1.5rem !important;
                    text-transform:capitalize;
                "
            >
                {{ strtolower($displayName) }}
            </span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        {{-- ==================== DASHBOARD ==================== --}}
        <li class="menu-item {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}">
            <a href="{{ $rl($dashboardRoute) }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div class="text-truncate">Dashboard</div>
            </a>
        </li>

        {{-- ==================== GENERAL REQUESTS ==================== --}}
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Pengajuan Umum</span>
        </li>

        {{-- Request Form (RF) - Accessible to all divisions --}}
        <li id="menu-item-erp-request-form" class="menu-item {{ request()->routeIs('erp.request-form.*') ? 'active' : '' }}">
            <a href="{{ $rl('erp.request-form.index') }}" class="menu-link d-flex align-items-center">
                <i class="menu-icon tf-icons bx bx-file text-primary"></i>
                <div class="text-truncate">Request Form (RF)</div>
            </a>
        </li>

        {{-- Purchase Orders (PO) - Monitoring progress for all divisions --}}
        <li id="menu-item-erp-purchase-orders" class="menu-item {{ request()->routeIs('erp.purchase-orders.*') ? 'active' : '' }}">
            <a href="{{ $rl('erp.purchase-orders.index') }}" class="menu-link d-flex align-items-center">
                <i class="menu-icon tf-icons bx bx-cart text-info"></i>
                <div class="text-truncate">Purchase Orders (PO)</div>
            </a>
        </li>

        {{-- ==================== DEPARTMENTS & DIVISIONS ==================== --}}
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Divisi & Departemen</span>
        </li>

        {{-- 1. ADMIN PROJECT --}}
        @if($isAdminProject)
        <li class="menu-item {{ $isProjectOpen ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-briefcase text-warning"></i>
                <div class="text-truncate">Admin Project</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('erp.work-items.*') ? 'active' : '' }}">
                    <a href="{{ $rl('erp.work-items.index') }}" class="menu-link">
                        <i class="bx bx-task me-2"></i>
                        <div class="text-truncate">Work Items (WID)</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('erp.sub-projects.*') ? 'active' : '' }}">
                    <a href="{{ $rl('erp.sub-projects.index') }}" class="menu-link">
                        <i class="bx bx-git-repo-forked me-2"></i>
                        <div class="text-truncate">Sub Projects</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('erp.budget-parents.*') ? 'active' : '' }}">
                    <a href="{{ $rl('erp.budget-parents.index') }}" class="menu-link">
                        <i class="bx bx-wallet-alt me-2"></i>
                        <div class="text-truncate">Budget Parents</div>
                    </a>
                </li>
            </ul>
        </li>
        @endif

        {{-- 2. GENERAL AFFAIR (GA) --}}
        @if($isGA)
        <li class="menu-item {{ $isGaOpen ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-shield-quarter text-secondary"></i>
                <div class="text-truncate">General Affair (GA)</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('erp.goods-receipts.*') ? 'active' : '' }}">
                    <a href="{{ route('erp.purchase-orders.index') }}" class="menu-link">
                        <i class="bx bx-package me-2"></i>
                        <div class="text-truncate">Penerimaan Barang (GR/DO)</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link" onclick="Swal.fire({icon:'info', title:'Rental Kendaraan', text:'Modul Penyewaan Mobil Rental GA sedang dalam pengembangan.', confirmButtonColor: '#4f46e5'})">
                        <i class="bx bx-car me-2"></i>
                        <div class="text-truncate">Rental Mobil GA</div>
                        <span class="badge bg-label-info ms-auto">Soon</span>
                    </a>
                </li>
            </ul>
        </li>
        @endif

        {{-- 3. PROCUREMENT --}}
        @if($isProcurement)
        <li class="menu-item {{ $isProcurementOpen ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-cart text-primary"></i>
                <div class="text-truncate">Procurement</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('erp.procurement.dashboard') ? 'active' : '' }}">
                    <a href="{{ $rl('erp.procurement.dashboard') }}" class="menu-link">
                        <i class="bx bx-bell me-2"></i>
                        <div class="text-truncate">PO Request</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('erp.purchase-orders.*') ? 'active' : '' }}">
                    <a href="{{ route('erp.purchase-orders.index') }}" class="menu-link">
                        <i class="bx bx-list-check me-2"></i>
                        <div class="text-truncate">Purchase Orders (PO)</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('erp.suppliers.*') ? 'active' : '' }}">
                    <a href="{{ route('erp.suppliers.index') }}" class="menu-link">
                        <i class="bx bx-store-alt me-2"></i>
                        <div class="text-truncate">ERP Suppliers</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('erp.payment-terms.*') ? 'active' : '' }}">
                    <a href="{{ route('erp.payment-terms.index') }}" class="menu-link">
                        <i class="bx bx-timer me-2"></i>
                        <div class="text-truncate">Payment Terms (TOP)</div>
                    </a>
                </li>
            </ul>
        </li>
        @endif

        {{-- 4. FINANCE --}}
        @if($isFinance)
        <li class="menu-item {{ $isFinanceOpen ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-credit-card text-success"></i>
                <div class="text-truncate">Finance</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('erp.payment-advices.*') || request()->routeIs('erp.payment-advice-details.*') ? 'active' : '' }}">
                    <a href="{{ route('erp.payment-advices.index') }}" class="menu-link">
                        <i class="bx bx-money me-2"></i>
                        <div class="text-truncate">Payment Advice (PA)</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('erp.purchase-orders.*') ? 'active' : '' }}">
                    <a href="{{ route('erp.purchase-orders.index') }}" class="menu-link">
                        <i class="bx bx-check-shield me-2"></i>
                        <div class="text-truncate">PO Verification / List</div>
                    </a>
                </li>
            </ul>
        </li>
        @endif

        {{-- 5. LOGISTIK & GUDANG --}}
        @if($isLogistik)
        <li class="menu-item {{ $isLogistikOpen ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-box text-info"></i>
                <div class="text-truncate">Logistik & Gudang</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('erp.stocks.*') ? 'active' : '' }}">
                    <a href="{{ route('erp.stocks.index') }}" class="menu-link">
                        <i class="bx bx-layer me-2"></i>
                        <div class="text-truncate">Inventory Stocks</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('erp.warehouses.*') ? 'active' : '' }}">
                    <a href="{{ route('erp.warehouses.index') }}" class="menu-link">
                        <i class="bx bx-building me-2"></i>
                        <div class="text-truncate">Warehouses / Dest.</div>
                    </a>
                </li>
            </ul>
        </li>
        @endif

        {{-- 6. CEO & EXECUTIVE --}}
        @if($isCEO)
        <li class="menu-item {{ request()->routeIs('erp.purchase-orders.index') || request()->routeIs('erp.payment-advices.index') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-crown text-danger"></i>
                <div class="text-truncate">CEO / Executive</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('erp.purchase-orders.index') ? 'active' : '' }}">
                    <a href="{{ route('erp.purchase-orders.index') }}" class="menu-link">
                        <i class="bx bx-check-shield me-2"></i>
                        <div class="text-truncate">PO Approvals</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('erp.payment-advices.index') ? 'active' : '' }}">
                    <a href="{{ route('erp.payment-advices.index') }}" class="menu-link">
                        <i class="bx bx-wallet me-2"></i>
                        <div class="text-truncate">Payment Approvals</div>
                    </a>
                </li>
            </ul>
        </li>
        @endif

        {{-- ==================== MASTER DATA ==================== --}}
        @if($isMaster)
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Master Data</span>
        </li>

        {{-- Products Catalog --}}
        <li id="menu-item-erp-products" class="menu-item {{ request()->routeIs('erp.products.*') ? 'active' : '' }}">
            <a href="{{ $rl('erp.products.index') }}" class="menu-link d-flex align-items-center">
                <i class="menu-icon tf-icons bx bx-box"></i>
                <div class="text-truncate">Products Catalog</div>
            </a>
        </li>

        {{-- Master Attributes --}}
        <li class="menu-item {{ $isMasterOpen && !request()->routeIs('erp.products.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div class="text-truncate">Master Attributes</div>
            </a>
            <ul class="menu-sub">
                <li id="menu-item-erp-uoms" class="menu-item {{ request()->routeIs('erp.uoms.*') ? 'active' : '' }}">
                    <a href="{{ $rl('erp.uoms.index') }}" class="menu-link">
                        <div class="text-truncate">Units of Measure</div>
                    </a>
                </li>
                <li id="menu-item-erp-families" class="menu-item {{ request()->routeIs('erp.product-families.*') ? 'active' : '' }}">
                    <a href="{{ $rl('erp.product-families.index') }}" class="menu-link">
                        <div class="text-truncate">Product Families</div>
                    </a>
                </li>
                <li id="menu-item-erp-types" class="menu-item {{ request()->routeIs('erp.product-types.*') ? 'active' : '' }}">
                    <a href="{{ $rl('erp.product-types.index') }}" class="menu-link">
                        <div class="text-truncate">Product Types</div>
                    </a>
                </li>
                <li id="menu-item-erp-brands" class="menu-item {{ request()->routeIs('erp.brands.*') ? 'active' : '' }}">
                    <a href="{{ $rl('erp.brands.index') }}" class="menu-link">
                        <div class="text-truncate">Brands</div>
                    </a>
                </li>
                <li id="menu-item-erp-models" class="menu-item {{ request()->routeIs('erp.product-models.*') ? 'active' : '' }}">
                    <a href="{{ $rl('erp.product-models.index') }}" class="menu-link">
                        <div class="text-truncate">Product Models</div>
                    </a>
                </li>
                <li id="menu-item-erp-currencies" class="menu-item {{ request()->routeIs('erp.currencies.*') ? 'active' : '' }}">
                    <a href="{{ $rl('erp.currencies.index') }}" class="menu-link">
                        <div class="text-truncate">Currencies</div>
                    </a>
                </li>
            </ul>
        </li>
        @endif

        {{-- ==================== SYSTEM & SETTINGS ==================== --}}
        @if($isSuperAdmin)
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">System & Security</span>
        </li>

        {{-- Projects (Tenants) --}}
        <li id="menu-item-erp-projects" class="menu-item {{ request()->routeIs('erp.projects.*') ? 'active' : '' }}">
            <a href="{{ $rl('erp.projects.index') }}" class="menu-link d-flex align-items-center">
                <i class="menu-icon tf-icons bx bx-buildings"></i>
                <div class="text-truncate">Projects (Tenants)</div>
            </a>
        </li>

        {{-- Approval Configs (Superadmin Only) --}}
        <li id="menu-item-erp-approval-configs" class="menu-item {{ request()->routeIs('erp.approval-configs.*') ? 'active' : '' }}">
            <a href="{{ $rl('erp.approval-configs.index') }}" class="menu-link d-flex align-items-center">
                <i class="menu-icon tf-icons bx bx-slider-alt"></i>
                <div class="text-truncate">Approval & Verif Configs</div>
            </a>
        </li>

        {{-- User Management --}}
        <li class="menu-item {{ request()->routeIs('erp.users.*') || request()->routeIs('erp.roles.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-user-check"></i>
                <div class="text-truncate">User Management</div>
            </a>
            <ul class="menu-sub">
                <li id="menu-item-erp-users" class="menu-item {{ request()->routeIs('erp.users.*') ? 'active' : '' }}">
                    <a href="{{ $rl('erp.users.index') }}" class="menu-link">
                        <div class="text-truncate">Users</div>
                    </a>
                </li>
                <li id="menu-item-erp-roles" class="menu-item {{ request()->routeIs('erp.roles.*') ? 'active' : '' }}">
                    <a href="{{ $rl('erp.roles.index') }}" class="menu-link">
                        <div class="text-truncate">Roles & Permissions</div>
                    </a>
                </li>
            </ul>
        </li>
        @endif
    </ul>
</aside>

<style>
/* Sembunyikan bullet dots pada submenu yang memiliki icon */
.layout-menu .menu-sub > .menu-item > .menu-link:has(.menu-icon):before {
    display: none !important;
}
</style>
