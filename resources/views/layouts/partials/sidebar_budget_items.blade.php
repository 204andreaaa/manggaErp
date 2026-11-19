{{-- Budget items: shown in Admin Project, or in Finance for finance people (see sidebar.blade.php) --}}
@if($canSeeDeptSpending)
<li class="menu-item {{ request()->routeIs('erp.department-spending.*') ? 'active' : '' }}">
    <a href="{{ $rl('erp.department-spending.index') }}" class="menu-link">
        <i class="bx bx-buildings me-2"></i>
        <div class="text-truncate">Pengeluaran Departemen</div>
    </a>
</li>
@endif
@if($canSeeBudgetPlans)
<li class="menu-item {{ request()->routeIs('erp.annual-budget-plans.*') || request()->routeIs('erp.monthly-budget-plans.*') ? 'active' : '' }}">
    <a href="{{ $rl('erp.annual-budget-plans.index') }}" class="menu-link">
        <i class="bx bx-calendar me-2"></i>
        <div class="text-truncate">Budget Plans</div>
    </a>
</li>
@endif
