<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\AnnualBudgetPlan;
use App\Models\Erp\MonthlyBudgetPlan;
use App\Services\BudgetRollupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthlyBudgetPlanController extends Controller
{
    public function store(Request $request, AnnualBudgetPlan $annualBudgetPlan)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.create'), 403);

        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'unit' => ['nullable', 'string', 'max:50'],
            'qty' => ['required', 'integer', 'min:0'],
            'revenue_plan' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $exists = $annualBudgetPlan->monthlyBudgetPlans()->where('month', $data['month'])->exists();
        if ($exists) {
            return redirect()->back()->with('error', 'Bulan ini sudah ada Monthly Budget Plan-nya.');
        }

        $data['year'] = $annualBudgetPlan->year;
        $data['annual_budget_plan_id'] = $annualBudgetPlan->id;

        $monthly = $this->createWithGeneratedCode($data);
        BudgetRollupService::recalculateMonthly($monthly->id);

        return redirect()->route('erp.monthly-budget-plans.show', $monthly->fresh())->with('success', 'Monthly Budget Plan berhasil dibuat.');
    }

    public function show(MonthlyBudgetPlan $monthlyBudgetPlan)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.view'), 403);

        $monthlyBudgetPlan->load(['annualBudgetPlan.budgetParent', 'budgetPlanDetails']);

        return view('erp.budget.monthly_plans.show', compact('monthlyBudgetPlan'));
    }

    public function update(Request $request, MonthlyBudgetPlan $monthlyBudgetPlan)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.create'), 403);

        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'unit' => ['nullable', 'string', 'max:50'],
            'qty' => ['required', 'integer', 'min:0'],
            'revenue_plan' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $exists = $monthlyBudgetPlan->annualBudgetPlan->monthlyBudgetPlans()
            ->where('id', '!=', $monthlyBudgetPlan->id)
            ->where('month', $data['month'])
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Bulan ini sudah ada Monthly Budget Plan-nya.');
        }

        $monthlyBudgetPlan->update($data);
        BudgetRollupService::recalculateMonthly($monthlyBudgetPlan->id);

        return redirect()->route('erp.monthly-budget-plans.show', $monthlyBudgetPlan)->with('success', 'Monthly Budget Plan berhasil diperbarui.');
    }

    /**
     * Code format BP-YYYY-MM-NNNNN — the YYYY-MM prefix is the date the record was
     * CREATED (today), not the budget's own year/month. Confirmed from the spec's
     * own worked example (BP-2019-03-10857 created in March, for budget month 11).
     */
    private function createWithGeneratedCode(array $data): MonthlyBudgetPlan
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $prefix = 'BP-'.now()->format('Y-m').'-';
                    $latest = MonthlyBudgetPlan::withTrashed()
                        ->where('code', 'like', $prefix.'%')
                        ->lockForUpdate()
                        ->orderByRaw('CAST(SUBSTRING(code, '.(strlen($prefix) + 1).') AS UNSIGNED) DESC')
                        ->value('code');

                    $num = 0;
                    if ($latest && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $latest, $m)) {
                        $num = (int) $m[1];
                    }

                    $data['code'] = $prefix.str_pad($num + 1, 5, '0', STR_PAD_LEFT);

                    return MonthlyBudgetPlan::create($data);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempt === 2) {
                    throw $e;
                }
            }
        }
    }
}
