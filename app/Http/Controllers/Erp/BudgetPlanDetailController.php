<?php

namespace App\Http\Controllers\Erp;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\Erp\BudgetPlanDetail;
use App\Models\Erp\MonthlyBudgetPlan;
use App\Services\BudgetGuard;
use App\Services\BudgetRollupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BudgetPlanDetailController extends Controller
{
    private const EXPENSE_TYPES = [
        'Personnel', 'Materials', 'Materials-Subcon', 'Transportation & Telecommunication',
        'Office', 'Utilities', 'Other Expense',
    ];

    public function store(Request $request, MonthlyBudgetPlan $monthlyBudgetPlan)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.create'), 403);

        $data = $request->validate([
            'expense_type' => ['required', Rule::in(self::EXPENSE_TYPES)],
            'is_indirect' => ['nullable', 'boolean'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $data['is_indirect'] = (bool) ($data['is_indirect'] ?? false);

        $exists = $monthlyBudgetPlan->budgetPlanDetails()
            ->where('expense_type', $data['expense_type'])
            ->where('is_indirect', $data['is_indirect'])
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Expense type (dengan direct/indirect yang sama) sudah ada di bulan ini.');
        }

        if ($error = $this->monthlyShortfall($monthlyBudgetPlan, (float) $data['amount'] * $monthlyBudgetPlan->qty)) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $data['monthly_budget_plan_id'] = $monthlyBudgetPlan->id;

        $detail = $this->createWithGeneratedCode($data);
        BudgetRollupService::recalculateDetail($detail->id);

        NotificationHelper::pushLive('monthly_budget_plan', $monthlyBudgetPlan->id, 'updated');

        return redirect()
            ->route('erp.monthly-budget-plans.show', $monthlyBudgetPlan)
            ->with('success', 'Budget Plan Detail berhasil dibuat.');
    }

    public function update(Request $request, BudgetPlanDetail $budgetPlanDetail)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.create'), 403);

        $data = $request->validate([
            'expense_type' => ['required', Rule::in(self::EXPENSE_TYPES)],
            'is_indirect' => ['nullable', 'boolean'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $data['is_indirect'] = (bool) ($data['is_indirect'] ?? false);

        $exists = $budgetPlanDetail->monthlyBudgetPlan->budgetPlanDetails()
            ->where('id', '!=', $budgetPlanDetail->id)
            ->where('expense_type', $data['expense_type'])
            ->where('is_indirect', $data['is_indirect'])
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Expense type (dengan direct/indirect yang sama) sudah ada di bulan ini.');
        }

        $monthly = $budgetPlanDetail->monthlyBudgetPlan;
        $newTotal = (float) $data['amount'] * $monthly->qty;
        $used = (float) $budgetPlanDetail->total_expense_plan - (float) $budgetPlanDetail->remaining_budget;
        $error = $this->monthlyShortfall($monthly, $newTotal, $budgetPlanDetail->id)
            ?? ($newTotal + 0.005 < $used
                ? "Plan {$budgetPlanDetail->expense_type} tidak boleh lebih kecil dari yang sudah terpakai kasbon/deklarasi (Rp ".BudgetGuard::money($used).').'
                : null);
        if ($error) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $budgetPlanDetail->update($data);
        BudgetRollupService::recalculateDetail($budgetPlanDetail->id);

        NotificationHelper::pushLive('monthly_budget_plan', $budgetPlanDetail->monthly_budget_plan_id, 'updated');

        return redirect()
            ->route('erp.monthly-budget-plans.show', $budgetPlanDetail->monthly_budget_plan_id)
            ->with('success', 'Budget Plan Detail berhasil diperbarui.');
    }

    /**
     * A detail's plan (Amount x the month's Qty) comes out of what the Monthly
     * plan hasn't handed out to its other details yet.
     */
    private function monthlyShortfall(MonthlyBudgetPlan $monthly, float $total, ?int $ignoreDetailId = null): ?string
    {
        $left = (float) $monthly->budget_amount - (float) $monthly->budgetPlanDetails()
            ->when($ignoreDetailId, fn ($q) => $q->where('id', '!=', $ignoreDetailId))
            ->sum('total_expense_plan');

        if ($total <= $left + 0.005) {
            return null;
        }

        return "Sisa Monthly Budget Plan {$monthly->code} tidak cukup: sisa Rp ".BudgetGuard::money($left)
            .', diminta Rp '.BudgetGuard::money($total).' (Amount x Qty '.$monthly->qty.').';
    }

    private function createWithGeneratedCode(array $data): BudgetPlanDetail
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $prefix = 'BPD-'.now()->format('Y-m').'-';
                    $latest = BudgetPlanDetail::withTrashed()
                        ->where('code', 'like', $prefix.'%')
                        ->lockForUpdate()
                        ->orderByRaw('CAST(SUBSTRING(code, '.(strlen($prefix) + 1).') AS UNSIGNED) DESC')
                        ->value('code');

                    $num = 0;
                    if ($latest && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $latest, $m)) {
                        $num = (int) $m[1];
                    }

                    $data['code'] = $prefix.str_pad($num + 1, 5, '0', STR_PAD_LEFT);

                    return BudgetPlanDetail::create($data);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempt === 2) {
                    throw $e;
                }
            }
        }
    }
}
