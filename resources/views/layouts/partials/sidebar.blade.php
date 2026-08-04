@php
use Illuminate\Support\Facades\Route as R;

$u    = auth()->user();
$role = $u?->primaryRole()?->slug ?? 'guest';
$displayName = $u?->name ?? 'Guest';

// helper route aman
$rl = function (string $name, array $params = []) {
    return R::has($name) ? route($name, $params) : '#';
};

// keys yang boleh (dari role)
$allowed = $u ? $u->allowedMenuKeys() : [];

$dashboardRoute = 'dashboard';

// Check if user has access to products menu
$hasErp = in_array('products', $allowed, true) || in_array('po', $allowed, true);
$erpMasterOpen = request()->routeIs('erp.uoms.*')
    || request()->routeIs('erp.product-families.*')
    || request()->routeIs('erp.product-types.*')
    || request()->routeIs('erp.brands.*')
    || request()->routeIs('erp.product-models.*')
    || request()->routeIs('erp.currencies.*')
    || request()->routeIs('erp.suppliers.*')
    || request()->routeIs('erp.warehouses.*')
    || request()->routeIs('erp.payment-terms.*')
    || request()->routeIs('erp.stocks.*');
$erpOpen = request()->routeIs('erp.products.*') || request()->routeIs('erp.request-form.*') || request()->routeIs('erp.procurement.dashboard') || request()->routeIs('erp.purchase-orders.*') || $erpMasterOpen;
@endphp
<style>
    /* Hide bullets for 3rd level submenus */
    .menu-sub .menu-sub .menu-item .menu-link::before {
        display: none !important;
    }
    /* Indent 3rd level submenus and make font size smaller */
    .menu-sub .menu-sub .menu-item {
        padding-left: 1.25rem !important;
    }
    .menu-sub .menu-sub .menu-item .menu-link {
        font-size: 0.78rem !important;
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
        {{-- Dashboard (selalu ada) --}}
        <li class="menu-item {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}">
            <a href="{{ $rl($dashboardRoute) }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div>Dashboard</div>
            </a>
        </li>

        {{-- ERP Menu Items --}}
        @if($hasErp || true)
            {{-- Products --}}
            <li id="menu-item-erp-products" class="menu-item {{ request()->routeIs('erp.products.*') ? 'active' : '' }}">
                <a href="{{ $rl('erp.products.index') }}" class="menu-link d-flex align-items-center">
                    <i class="menu-icon tf-icons bx bx-package"></i>
                    <div class="text-truncate">Products</div>
                </a>
            </li>

            {{-- Master Data sub-dropdown --}}
            <li class="menu-item {{ $erpMasterOpen ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-data"></i>
                    <div>Master Data ERP</div>
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
                    <li id="menu-item-erp-suppliers" class="menu-item {{ request()->routeIs('erp.suppliers.*') ? 'active' : '' }}">
                        <a href="{{ route('erp.suppliers.index') }}" class="menu-link">
                            <div class="text-truncate">ERP Suppliers</div>
                        </a>
                    </li>
                    <li id="menu-item-erp-warehouses" class="menu-item {{ request()->routeIs('erp.warehouses.*') ? 'active' : '' }}">
                        <a href="{{ route('erp.warehouses.index') }}" class="menu-link">
                            <div class="text-truncate">ERP Destinations / Warehouses</div>
                        </a>
                    </li>
                    <li id="menu-item-erp-payment-terms" class="menu-item {{ request()->routeIs('erp.payment-terms.*') ? 'active' : '' }}">
                        <a href="{{ route('erp.payment-terms.index') }}" class="menu-link">
                            <div class="text-truncate">Payment Terms (TOP)</div>
                        </a>
                    </li>
                    <li id="menu-item-erp-stocks" class="menu-item {{ request()->routeIs('erp.stocks.*') ? 'active' : '' }}">
                        <a href="{{ route('erp.stocks.index') }}" class="menu-link">
                            <div class="text-truncate">Inventory Stocks</div>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Divider --}}
            <li class="menu-item">
                <div style="border-top: 1px solid rgba(67,89,113,0.15); margin: 4px 16px;"></div>
            </li>

            {{-- Request Form --}}
            <li id="menu-item-erp-request-form" class="menu-item {{ request()->routeIs('erp.request-form.*') ? 'active' : '' }}">
                <a href="{{ $rl('erp.request-form.index') }}" class="menu-link d-flex align-items-center">
                    <i class="menu-icon tf-icons bx bx-file"></i>
                    <div class="text-truncate">Request Form</div>
                </a>
            </li>

            {{-- Master Purchase Orders List --}}
            <li id="menu-item-erp-po-list" class="menu-item {{ request()->routeIs('erp.purchase-orders.index') ? 'active' : '' }}">
                <a href="{{ route('erp.purchase-orders.index') }}" class="menu-link d-flex align-items-center">
                    <i class="menu-icon tf-icons bx bx-list-check"></i>
                    <div class="text-truncate">Purchase Orders List</div>
                </a>
            </li>

            {{-- PO Request --}}
            @if(auth()->user()->hasRole(['procurement', 'ceo', 'superadmin']))
            <li id="menu-item-erp-po-request" class="menu-item {{ request()->routeIs('erp.procurement.dashboard') ? 'active' : '' }}">
                <a href="{{ $rl('erp.procurement.dashboard') }}" class="menu-link d-flex align-items-center">
                    <i class="menu-icon tf-icons bx bx-cart"></i>
                    <div class="text-truncate">PO Request</div>
                </a>
            </li>
            @endif

            {{-- Payment Advice --}}
            <li id="menu-item-erp-payment-advice" class="menu-item {{ request()->routeIs('erp.payment-advices.*') || request()->routeIs('erp.payment-advice-details.*') ? 'active' : '' }}">
                <a href="{{ route('erp.payment-advices.index') }}" class="menu-link d-flex align-items-center">
                    <i class="menu-icon tf-icons bx bx-credit-card-front"></i>
                    <div class="text-truncate">Payment Advice</div>
                </a>
            </li>
            
            @if(auth()->user()->hasRole('superadmin'))
            {{-- Approval Configs --}}
            <li id="menu-item-erp-approval-configs" class="menu-item {{ request()->routeIs('erp.approval-configs.*') ? 'active' : '' }}">
                <a href="{{ $rl('erp.approval-configs.index') }}" class="menu-link d-flex align-items-center">
                    <i class="menu-icon tf-icons bx bx-cog"></i>
                    <div class="text-truncate">Approval Configs</div>
                </a>
            </li>
            
            {{-- Projects (Tenants) --}}
            <li id="menu-item-erp-projects" class="menu-item {{ request()->routeIs('erp.projects.*') ? 'active' : '' }}">
                <a href="{{ $rl('erp.projects.index') }}" class="menu-link d-flex align-items-center">
                    <i class="menu-icon tf-icons bx bx-buildings"></i>
                    <div class="text-truncate">Projects</div>
                </a>
            </li>
            
            {{-- Users & Roles --}}
            <li class="menu-item {{ request()->routeIs('erp.users.*') || request()->routeIs('erp.roles.*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-group"></i>
                    <div>User Management</div>
                </a>
                <ul class="menu-sub">
                    <li id="menu-item-erp-users" class="menu-item {{ request()->routeIs('erp.users.*') ? 'active' : '' }}">
                        <a href="{{ $rl('erp.users.index') }}" class="menu-link">
                            <div class="text-truncate">Users</div>
                        </a>
                    </li>
                    <li id="menu-item-erp-roles" class="menu-item {{ request()->routeIs('erp.roles.*') ? 'active' : '' }}">
                        <a href="{{ $rl('erp.roles.index') }}" class="menu-link">
                            <div class="text-truncate">Roles</div>
                        </a>
                    </li>
                </ul>
            </li>
            @endif
        @endif
    </ul>
</aside>

<style>
/* Sembunyikan bullet dots pada submenu yang memiliki icon */
.layout-menu .menu-sub > .menu-item > .menu-link:has(.menu-icon):before {
    display: none !important;
}
</style>
