@php
use Illuminate\Support\Facades\Route as R;

$u    = auth()->user();
$role = $u?->primaryRole()?->slug ?? 'guest';

$rl = function (string $name, array $params = []) {
    return R::has($name) ? route($name, $params) : '#';
};

$dashboardRoute = 'dashboard';

// Role checks
$isSuperAdmin   = $u?->hasRole('superadmin') || $u?->hasRole('admin');
$isAdminProject = $isSuperAdmin || $u?->hasRole(['admin_project', 'project_admin']) || $u?->canSeeMenu('work_items') || $u?->canSeeMenu('budget_parents') || $u?->canSeeMenu('sub_projects');
$isGA           = $isSuperAdmin || $u?->hasRole(['ga', 'general_affair']) || $u?->canSeeMenu('goods_receipts');
$isProcurement  = $isSuperAdmin || $u?->hasRole('procurement') || $u?->canSeeMenu('purchase_orders') || $u?->canSeeMenu('suppliers');
$isFinance      = $isSuperAdmin || $u?->hasRole('finance') || $u?->canSeeMenu('payment_advices') || $u?->canSeeMenu('payment_advice_details');
$isLogistik     = $isSuperAdmin || $u?->hasRole(['logistik', 'warehouse']) || $u?->canSeeMenu('stocks') || $u?->canSeeMenu('warehouses');
$isHRIS         = $isSuperAdmin || $u?->hasRole(['hrd', 'hr_manager']) || $u?->canSeeMenu('employees') || $u?->canSeeMenu('departments') || $u?->canSeeMenu('hr_attendances') || $u?->canSeeMenu('hr_payroll');
$isCEO          = $isSuperAdmin || $u?->hasRole('ceo');
$isMaster       = $isSuperAdmin || $u?->hasRole('procurement') || $u?->canSeeMenu('products') || $u?->canSeeMenu('uoms');
$isSystem       = $isSuperAdmin || $u?->canSeeMenu('users') || $u?->canSeeMenu('roles') || $u?->canSeeMenu('projects') || $u?->canSeeMenu('approval_configs');
@endphp

<div id="layout-horizontal-menu" class="horizontal-nav-wrapper">
    <div class="container-xxl">
        <ul class="nav nav-pills horizontal-nav-list align-items-center">
            
            {{-- Dashboard --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}" href="{{ $rl($dashboardRoute) }}">
                    <i class="bx bx-home-circle me-1"></i> Dashboard
                </a>
            </li>

            {{-- Pengajuan Umum --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('erp.request-form.*') || request()->routeIs('erp.purchase-orders.*') ? 'active' : '' }}" 
                   href="javascript:void(0)" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bx bx-file me-1"></i> Pengajuan
                </a>
                <ul class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.request-form.*') ? 'active' : '' }}" href="{{ $rl('erp.request-form.index') }}">
                            <i class="bx bx-file text-primary me-2"></i> Request Form (RF)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.purchase-orders.*') ? 'active' : '' }}" href="{{ $rl('erp.purchase-orders.index') }}">
                            <i class="bx bx-cart text-info me-2"></i> Purchase Orders (PO)
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Admin Project --}}
            @if($isAdminProject)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('erp.work-items.*') || request()->routeIs('erp.sub-projects.*') || request()->routeIs('erp.budget-parents.*') ? 'active' : '' }}" 
                   href="javascript:void(0)" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bx bx-briefcase me-1 text-warning"></i> Project
                </a>
                <ul class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.work-items.*') ? 'active' : '' }}" href="{{ $rl('erp.work-items.index') }}">
                            <i class="bx bx-task me-2"></i> Work Items (WID)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.sub-projects.*') ? 'active' : '' }}" href="{{ $rl('erp.sub-projects.index') }}">
                            <i class="bx bx-git-repo-forked me-2"></i> Sub Projects
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.budget-parents.*') ? 'active' : '' }}" href="{{ $rl('erp.budget-parents.index') }}">
                            <i class="bx bx-wallet-alt me-2"></i> Budget Parents
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            {{-- Procurement --}}
            @if($isProcurement)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('erp.procurement.dashboard') || request()->routeIs('erp.suppliers.*') || request()->routeIs('erp.payment-terms.*') ? 'active' : '' }}" 
                   href="javascript:void(0)" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bx bx-cart me-1 text-primary"></i> Procurement
                </a>
                <ul class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.procurement.dashboard') ? 'active' : '' }}" href="{{ $rl('erp.procurement.dashboard') }}">
                            <i class="bx bx-bell me-2"></i> PO Request
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.purchase-orders.*') ? 'active' : '' }}" href="{{ route('erp.purchase-orders.index') }}">
                            <i class="bx bx-list-check me-2"></i> Purchase Orders (PO)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.suppliers.*') ? 'active' : '' }}" href="{{ route('erp.suppliers.index') }}">
                            <i class="bx bx-store-alt me-2"></i> ERP Suppliers
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.payment-terms.*') ? 'active' : '' }}" href="{{ route('erp.payment-terms.index') }}">
                            <i class="bx bx-timer me-2"></i> Payment Terms (TOP)
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            {{-- GA --}}
            @if($isGA)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('erp.goods-receipts.*') ? 'active' : '' }}" 
                   href="javascript:void(0)" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bx bx-shield-quarter me-1 text-secondary"></i> GA
                </a>
                <ul class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.goods-receipts.*') ? 'active' : '' }}" href="{{ route('erp.purchase-orders.index') }}">
                            <i class="bx bx-package me-2"></i> Penerimaan Barang (GR/DO)
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            {{-- Finance --}}
            @if($isFinance)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('erp.payment-advices.*') || request()->routeIs('erp.payment-advice-details.*') ? 'active' : '' }}" 
                   href="javascript:void(0)" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bx bx-credit-card me-1 text-success"></i> Finance
                </a>
                <ul class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.payment-advices.*') ? 'active' : '' }}" href="{{ route('erp.payment-advices.index') }}">
                            <i class="bx bx-money me-2"></i> Payment Advice (PA)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('erp.purchase-orders.index') }}">
                            <i class="bx bx-check-shield me-2"></i> PO Verification
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            {{-- Logistik & Gudang --}}
            @if($isLogistik)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('erp.stocks.*') || request()->routeIs('erp.warehouses.*') ? 'active' : '' }}" 
                   href="javascript:void(0)" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bx bx-box me-1 text-info"></i> Logistik
                </a>
                <ul class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.stocks.*') ? 'active' : '' }}" href="{{ route('erp.stocks.index') }}">
                            <i class="bx bx-layer me-2"></i> Inventory Stocks
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.warehouses.*') ? 'active' : '' }}" href="{{ route('erp.warehouses.index') }}">
                            <i class="bx bx-building me-2"></i> Warehouses / Dest.
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            {{-- Human Resource (HRIS) --}}
            @if($isHRIS)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('erp.hr.*') || request()->routeIs('erp.departments.*') ? 'active' : '' }}" 
                   href="javascript:void(0)" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bx bx-user-pin me-1 text-primary"></i> HRIS
                </a>
                <ul class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.departments.*') ? 'active' : '' }}" href="{{ route('erp.departments.index') }}">
                            <i class="bx bx-sitemap me-2 text-primary"></i> Master Departemen
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.hr.employees.*') ? 'active' : '' }}" href="{{ route('erp.hr.employees.index') }}">
                            <i class="bx bx-id-card me-2"></i> Data Karyawan
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.hr.attendances.*') ? 'active' : '' }}" href="{{ route('erp.hr.attendances.index') }}">
                            <i class="bx bx-calendar-check me-2"></i> Absensi & Cuti
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.hr.payroll.*') ? 'active' : '' }}" href="{{ route('erp.hr.payroll.index') }}">
                            <i class="bx bx-wallet-alt me-2"></i> Payroll / Gaji
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            {{-- Master Data --}}
            @if($isMaster)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('erp.products.*') || request()->routeIs('erp.uoms.*') || request()->routeIs('erp.product-families.*') || request()->routeIs('erp.brands.*') ? 'active' : '' }}" 
                   href="javascript:void(0)" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bx bx-data me-1"></i> Master
                </a>
                <ul class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.products.*') ? 'active' : '' }}" href="{{ $rl('erp.products.index') }}">
                            <i class="bx bx-box me-2"></i> Products Catalog
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.uoms.*') ? 'active' : '' }}" href="{{ $rl('erp.uoms.index') }}">Units of Measure</a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.product-families.*') ? 'active' : '' }}" href="{{ $rl('erp.product-families.index') }}">Product Families</a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.product-types.*') ? 'active' : '' }}" href="{{ $rl('erp.product-types.index') }}">Product Types</a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.brands.*') ? 'active' : '' }}" href="{{ $rl('erp.brands.index') }}">Brands</a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.product-models.*') ? 'active' : '' }}" href="{{ $rl('erp.product-models.index') }}">Product Models</a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.currencies.*') ? 'active' : '' }}" href="{{ $rl('erp.currencies.index') }}">Currencies</a>
                    </li>
                </ul>
            </li>
            @endif

            {{-- Reports --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('erp.reports.*') ? 'active' : '' }}" href="{{ $rl('erp.reports.index') }}">
                    <i class="bx bx-bar-chart-alt-2 me-1 text-primary"></i> Laporan
                </a>
            </li>

            {{-- System & Security --}}
            @if($isSuperAdmin)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('erp.users.*') || request()->routeIs('erp.roles.*') || request()->routeIs('erp.projects.*') || request()->routeIs('erp.approval-configs.*') ? 'active' : '' }}" 
                   href="javascript:void(0)" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bx bx-slider-alt me-1"></i> System
                </a>
                <ul class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.users.*') ? 'active' : '' }}" href="{{ $rl('erp.users.index') }}">
                            <i class="bx bx-user me-2"></i> Users Management
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.roles.*') ? 'active' : '' }}" href="{{ $rl('erp.roles.index') }}">
                            <i class="bx bx-shield-quarter me-2"></i> Roles & Permissions
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.projects.*') ? 'active' : '' }}" href="{{ $rl('erp.projects.index') }}">
                            <i class="bx bx-buildings me-2"></i> Projects (Tenants)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item {{ request()->routeIs('erp.approval-configs.*') ? 'active' : '' }}" href="{{ $rl('erp.approval-configs.index') }}">
                            <i class="bx bx-check-shield me-2"></i> Approval Configs
                        </a>
                    </li>
                </ul>
            </li>
            @endif

        </ul>
    </div>
</div>
