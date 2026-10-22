<?php

namespace App\Http\Controllers\Erp;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\Erp\AnnualBudgetPlan;
use App\Models\Erp\MonthlyBudgetPlan;
use App\Services\BudgetGuard;
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
            'budget_amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'description' => ['nullable', 'string'],
        ]);

        $exists = $annualBudgetPlan->monthlyBudgetPlans()->where('month', $data['month'])->exists();
        if ($exists) {
            return redirect()->back()->with('error', 'Bulan ini sudah ada Monthly Budget Plan-nya.');
        }

        // The month's allocation comes out of what the Annual plan hasn't handed out yet.
        $annualLeft = (float) $annualBudgetPlan->budget_amount - (float) $annualBudgetPlan->monthlyBudgetPlans()->sum('budget_amount');
        if ((float) $data['budget_amount'] > $annualLeft + 0.005) {
            return redirect()->back()->withInput()->with('error', "Sisa Annual Budget Plan {$annualBudgetPlan->code} tidak cukup: sisa Rp ".BudgetGuard::money($annualLeft).', diminta Rp '.BudgetGuard::money($data['budget_amount']).'.');
        }

        $data['year'] = $annualBudgetPlan->year;
        $data['annual_budget_plan_id'] = $annualBudgetPlan->id;

        $monthly = $this->createWithGeneratedCode($data);
        BudgetRollupService::recalculateMonthly($monthly->id);

        NotificationHelper::pushLive('monthly_budget_plan', $monthly->id, 'created');
        NotificationHelper::pushLive('annual_budget_plan', $annualBudgetPlan->id, 'updated');

        return redirect()->route('erp.monthly-budget-plans.show', $monthly->fresh())->with('success', 'Monthly Budget Plan berhasil dibuat.');
    }

    public function show(MonthlyBudgetPlan $monthlyBudgetPlan)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.view'), 403);

        $monthlyBudgetPlan->load(['annualBudgetPlan.budgetParent', 'annualBudgetPlan.workItem', 'budgetPlanDetails']);

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
            'budget_amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'description' => ['nullable', 'string'],
        ]);

        $annual = $monthlyBudgetPlan->annualBudgetPlan;
        $exists = $annual->monthlyBudgetPlans()
            ->where('id', '!=', $monthlyBudgetPlan->id)
            ->where('month', $data['month'])
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Bulan ini sudah ada Monthly Budget Plan-nya.');
        }

        $annualLeft = (float) $annual->budget_amount
            - (float) $annual->monthlyBudgetPlans()->where('id', '!=', $monthlyBudgetPlan->id)->sum('budget_amount');
        if ((float) $data['budget_amount'] > $annualLeft + 0.005) {
            return redirect()->back()->withInput()->with('error', "Sisa Annual Budget Plan {$annual->code} tidak cukup: sisa Rp ".BudgetGuard::money($annualLeft).', diminta Rp '.BudgetGuard::money($data['budget_amount']).'.');
        }

        // Detail plans are Amount x the month's Qty, so a new Qty re-sizes every detail.
        $details = $monthlyBudgetPlan->budgetPlanDetails()->get();
        $newDetailTotal = (float) $details->sum('amount') * (int) $data['qty'];
        if ($newDetailTotal > (float) $data['budget_amount'] + 0.005) {
            return redirect()->back()->withInput()->with('error', 'Budget Plan Detail bulan ini (Rp '.BudgetGuard::money($newDetailTotal).') melebihi Budget Amount yang diisi (Rp '.BudgetGuard::money($data['budget_amount']).').');
        }
        foreach ($details as $d) {
            $used = (float) $d->total_expense_plan - (float) $d->remaining_budget;
            if ((float) $d->amount * (int) $data['qty'] + 0.005 < $used) {
                return redirect()->back()->withInput()->with('error', "Qty ini membuat plan {$d->expense_type} ({$d->code}) lebih kecil dari yang sudah terpakai (Rp ".BudgetGuard::money($used).').');
            }
        }

        $monthlyBudgetPlan->update($data);
        foreach ($details as $d) {
            BudgetRollupService::recalculateDetail($d->id);
        }
        BudgetRollupService::recalculateMonthly($monthlyBudgetPlan->id);

        NotificationHelper::pushLive('monthly_budget_plan', $monthlyBudgetPlan->id, 'updated');
        NotificationHelper::pushLive('annual_budget_plan', $monthlyBudgetPlan->annual_budget_plan_id, 'updated');

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
