<?php

namespace App\Http\Controllers\Erp;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\Erp\AnnualBudgetPlan;
use App\Models\Erp\ErpBudgetParent;
use App\Models\Erp\ErpWorkItem;
use App\Services\BudgetGuard;
use App\Services\BudgetRollupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnnualBudgetPlanController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.view'), 403);

        $budgetParents = ErpBudgetParent::orderBy('name')->get();
        $workItems = ErpWorkItem::with('subProject.budgetParent')->orderBy('wid_code')->get();

        $plans = AnnualBudgetPlan::with(['budgetParent', 'workItem'])
            ->when($request->query('budget_parent_id'), fn ($q, $id) => $q->where('budget_parent_id', $id))
            ->withCount('monthlyBudgetPlans')
            ->latest()
            ->get();

        return view('erp.budget.annual_plans.index', compact('budgetParents', 'workItems', 'plans'));
    }

    private const RULES = [
        'work_item_id' => ['required', 'exists:erp_work_items,id'],
        'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        'area' => ['required', 'string', 'max:150'],
        'work_type' => ['required', 'string', 'max:80'],
        'work_subtype' => ['nullable', 'string', 'max:150'],
        'currency' => ['nullable', 'string', 'max:10'],
        'budget_amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
    ];

    /**
     * Annual Budget Plan takes its allocation out of the WID's Remaining Budget
     * (whatever is left after PO), so creating one cuts the WID right away.
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.create'), 403);

        $data = $request->validate(self::RULES);

        return DB::transaction(function () use ($data) {
            $work = ErpWorkItem::with('subProject')->where('id', $data['work_item_id'])->lockForUpdate()->firstOrFail();
            $data['budget_parent_id'] = $work->subProject?->budget_parent_id;

            if ($error = $this->duplicateError($data) ?? $this->widShortfall($work, (float) $data['budget_amount'])) {
                return redirect()->back()->withInput()->with('error', $error);
            }

            $plan = $this->createWithGeneratedCode($data);
            $work->update(['remaining_budget' => (float) $work->remaining_budget - (float) $data['budget_amount']]);
            BudgetRollupService::recalculateAnnual($plan->id);

            NotificationHelper::pushLive('annual_budget_plan', $plan->id, 'created');
            NotificationHelper::pushLive('work_item', $work->id, 'updated');

            return redirect()->route('erp.annual-budget-plans.show', $plan)->with('success', 'Annual Budget Plan berhasil dibuat. Remaining WID '.$work->wid_code.' berkurang Rp '.BudgetGuard::money($data['budget_amount']).'.');
        });
    }

    public function show(AnnualBudgetPlan $annualBudgetPlan)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.view'), 403);

        $annualBudgetPlan->load(['budgetParent', 'workItem', 'monthlyBudgetPlans' => fn ($q) => $q->orderBy('month')]);

        return view('erp.budget.annual_plans.show', compact('annualBudgetPlan'));
    }

    public function update(Request $request, AnnualBudgetPlan $annualBudgetPlan)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.create'), 403);

        $data = $request->validate(self::RULES);

        return DB::transaction(function () use ($data, $annualBudgetPlan) {
            $plan = AnnualBudgetPlan::where('id', $annualBudgetPlan->id)->lockForUpdate()->firstOrFail();
            $newWork = ErpWorkItem::with('subProject')->where('id', $data['work_item_id'])->lockForUpdate()->firstOrFail();
            $oldWork = $plan->work_item_id && $plan->work_item_id != $newWork->id
                ? ErpWorkItem::where('id', $plan->work_item_id)->lockForUpdate()->first()
                : null;
            $data['budget_parent_id'] = $newWork->subProject?->budget_parent_id;

            $newAmount = (float) $data['budget_amount'];
            $oldAmount = (float) $plan->budget_amount;
            // Moving to another WID takes the whole amount from it; same WID only the difference.
            $sameWid = $plan->work_item_id == $newWork->id;
            $takeFromNew = $sameWid ? $newAmount - $oldAmount : $newAmount;
            $allocatedToMonths = (float) $plan->monthlyBudgetPlans()->sum('budget_amount');

            $error = $this->duplicateError($data, $plan->id)
                ?? ($newAmount + 0.005 < $allocatedToMonths
                    ? 'Budget Amount tidak boleh lebih kecil dari yang sudah dibagi ke Monthly Budget Plan (Rp '.BudgetGuard::money($allocatedToMonths).').'
                    : null)
                ?? ($takeFromNew > 0 ? $this->widShortfall($newWork, $takeFromNew) : null);
            if ($error) {
                return redirect()->back()->withInput()->with('error', $error);
            }

            $plan->update($data);
            $newWork->update(['remaining_budget' => (float) $newWork->remaining_budget - $takeFromNew]);
            if ($oldWork) {
                $oldWork->update(['remaining_budget' => (float) $oldWork->remaining_budget + $oldAmount]);
                NotificationHelper::pushLive('work_item', $oldWork->id, 'updated');
            }
            BudgetRollupService::recalculateAnnual($plan->id);

            NotificationHelper::pushLive('annual_budget_plan', $plan->id, 'updated');
            NotificationHelper::pushLive('work_item', $newWork->id, 'updated');

            return redirect()->route('erp.annual-budget-plans.show', $plan)->with('success', 'Annual Budget Plan berhasil diperbarui.');
        });
    }

    private function duplicateError(array $data, ?int $ignoreId = null): ?string
    {
        $exists = AnnualBudgetPlan::when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('work_item_id', $data['work_item_id'])
            ->where('year', $data['year'])
            ->where('area', $data['area'])
            ->where('work_type', $data['work_type'])
            ->where('work_subtype', $data['work_subtype'] ?? null)
            ->exists();

        return $exists ? 'Kombinasi WID + Year + Area + Work Type + Work Subtype ini sudah ada.' : null;
    }

    private function widShortfall(ErpWorkItem $work, float $amount): ?string
    {
        if ($amount <= (float) $work->remaining_budget + 0.005) {
            return null;
        }

        return "Remaining WID {$work->wid_code} tidak cukup: sisa Rp ".BudgetGuard::money($work->remaining_budget)
            .', diminta Rp '.BudgetGuard::money($amount).'.';
    }

    private function createWithGeneratedCode(array $data): AnnualBudgetPlan
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $prefix = $data['year'].'-';
                    $latest = AnnualBudgetPlan::withTrashed()
                        ->where('code', 'like', $prefix.'%')
                        ->lockForUpdate()
                        ->orderByRaw('CAST(SUBSTRING(code, '.(strlen($prefix) + 1).') AS UNSIGNED) DESC')
                        ->value('code');

                    $num = 0;
                    if ($latest && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $latest, $m)) {
                        $num = (int) $m[1];
                    }

                    $data['code'] = $prefix.str_pad($num + 1, 4, '0', STR_PAD_LEFT);

                    return AnnualBudgetPlan::create($data);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempt === 2) {
                    throw $e;
                }
            }
        }
    }
}
