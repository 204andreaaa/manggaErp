{{-- Budget items: in the Project menu, or in Finance for finance people (see horizontal_menu.blade.php) --}}
@if($canSeeDeptSpending)
<li>
    <a class="dropdown-item {{ request()->routeIs('erp.department-spending.*') ? 'active' : '' }}" href="{{ $rl('erp.department-spending.index') }}">
        <i class="bx bx-buildings me-2"></i> Pengeluaran Departemen
    </a>
</li>
@endif
@if($canSeeBudgetPlans)
<li>
    <a class="dropdown-item {{ request()->routeIs('erp.annual-budget-plans.*') || request()->routeIs('erp.monthly-budget-plans.*') ? 'active' : '' }}" href="{{ $rl('erp.annual-budget-plans.index') }}">
        <i class="bx bx-calendar me-2"></i> Budget Plans
    </a>
</li>
@endif
