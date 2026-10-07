<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\AnnualBudgetPlan;
use App\Models\Erp\ErpBudgetParent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnnualBudgetPlanController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.view'), 403);

        $budgetParents = ErpBudgetParent::orderBy('name')->get();

        $plans = AnnualBudgetPlan::with('budgetParent')
            ->when($request->query('budget_parent_id'), fn ($q, $id) => $q->where('budget_parent_id', $id))
            ->withCount('monthlyBudgetPlans')
            ->latest()
            ->get();

        return view('erp.budget.annual_plans.index', compact('budgetParents', 'plans'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.create'), 403);

        $data = $request->validate([
            'budget_parent_id' => ['required', 'exists:erp_budget_parents,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'area' => ['required', 'string', 'max:150'],
            'work_type' => ['required', 'string', 'max:80'],
            'work_subtype' => ['nullable', 'string', 'max:150'],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        $plan = $this->createWithGeneratedCode($data);

        return redirect()->route('erp.annual-budget-plans.show', $plan)->with('success', 'Annual Budget Plan berhasil dibuat.');
    }

    public function show(AnnualBudgetPlan $annualBudgetPlan)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.view'), 403);

        $annualBudgetPlan->load(['budgetParent', 'monthlyBudgetPlans' => fn ($q) => $q->orderBy('month')]);

        return view('erp.budget.annual_plans.show', compact('annualBudgetPlan'));
    }

    public function update(Request $request, AnnualBudgetPlan $annualBudgetPlan)
    {
        abort_unless(auth()->user()->hasPermission('annual_budget_plans.create'), 403);

        $data = $request->validate([
            'budget_parent_id' => ['required', 'exists:erp_budget_parents,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'area' => ['required', 'string', 'max:150'],
            'work_type' => ['required', 'string', 'max:80'],
            'work_subtype' => ['nullable', 'string', 'max:150'],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        $exists = AnnualBudgetPlan::where('id', '!=', $annualBudgetPlan->id)
            ->where('budget_parent_id', $data['budget_parent_id'])
            ->where('year', $data['year'])
            ->where('area', $data['area'])
            ->where('work_type', $data['work_type'])
            ->where('work_subtype', $data['work_subtype'] ?? null)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Kombinasi Budget Parent + Year + Area + Work Type + Work Subtype ini sudah ada.');
        }

        $annualBudgetPlan->update($data);

        return redirect()->route('erp.annual-budget-plans.show', $annualBudgetPlan)->with('success', 'Annual Budget Plan berhasil diperbarui.');
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
