<?php

namespace Tests\Feature\Erp\Budget;

use App\Models\Erp\AnnualBudgetPlan;
use App\Models\Erp\BudgetPlanDetail;
use App\Models\Erp\ErpBudgetParent;
use App\Models\Erp\ErpSubProject;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\MonthlyBudgetPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds the budget hierarchy through the same HTTP endpoints the UI uses,
 * so the fixtures themselves go through the allocation rules.
 */
trait BudgetFixtures
{
    protected function loginAsBudgetAdmin(): User
    {
        $user = $this->makeUser('superadmin');
        $this->actingInProject($user);

        return $user;
    }

    protected function createParent(float $total = 1000000, array $overrides = []): ErpBudgetParent
    {
        $code = $overrides['budget_code'] ?? 'BP-'.Str::upper(Str::random(6));
        $this->postJson(route('erp.budget-parents.store'), array_merge([
            'budget_code' => $code,
            'name' => 'Parent '.$code,
            'total_budget' => $total,
            'status' => 'Active',
        ], $overrides))->assertOk();

        return ErpBudgetParent::where('budget_code', $code)->firstOrFail();
    }

    protected function createSubProject(ErpBudgetParent $parent, ?string $code = null): ErpSubProject
    {
        $code ??= 'SP-'.Str::upper(Str::random(6));
        $this->postJson(route('erp.sub-projects.store'), [
            'budget_parent_id' => $parent->id,
            'sub_project_code' => $code,
            'name' => 'Sub '.$code,
        ])->assertOk();

        return ErpSubProject::where('sub_project_code', $code)->firstOrFail();
    }

    protected function createWid(ErpSubProject $sub, float $allocated, ?string $code = null): ErpWorkItem
    {
        $code ??= 'WID-'.Str::upper(Str::random(6));
        $this->postJson(route('erp.work-items.store'), [
            'sub_project_id' => $sub->id,
            'wid_code' => $code,
            'name' => 'Work '.$code,
            'allocated_budget' => $allocated,
        ])->assertOk();

        return ErpWorkItem::where('wid_code', $code)->firstOrFail();
    }

    /** Parent (total) -> Sub Project -> WID (allocated). */
    protected function widWithBudget(float $allocated = 500000, float $parentTotal = 1000000): ErpWorkItem
    {
        $parent = $this->createParent($parentTotal);

        return $this->createWid($this->createSubProject($parent), $allocated);
    }

    protected function annualData(ErpWorkItem $wid, float $amount, array $overrides = []): array
    {
        return array_merge([
            'work_item_id' => $wid->id,
            'year' => 2026,
            'area' => 'Jakarta',
            'work_type' => 'Maintenance',
            'work_subtype' => null,
            'currency' => 'IDR',
            'budget_amount' => $amount,
        ], $overrides);
    }

    protected function createAnnual(ErpWorkItem $wid, float $amount, array $overrides = []): AnnualBudgetPlan
    {
        $this->post(route('erp.annual-budget-plans.store'), $this->annualData($wid, $amount, $overrides))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error')
            ->assertRedirect();

        return AnnualBudgetPlan::where('work_item_id', $wid->id)->latest('id')->firstOrFail();
    }

    protected function monthlyData(float $amount, int $qty = 1, array $overrides = []): array
    {
        return array_merge([
            'month' => 1,
            'unit' => 'Site',
            'qty' => $qty,
            'revenue_plan' => 0,
            'budget_amount' => $amount,
        ], $overrides);
    }

    protected function createMonthly(AnnualBudgetPlan $annual, float $amount, int $qty = 1, array $overrides = []): MonthlyBudgetPlan
    {
        $this->post(route('erp.annual-budget-plans.monthly-plans.store', $annual), $this->monthlyData($amount, $qty, $overrides))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error')
            ->assertRedirect();

        return $annual->monthlyBudgetPlans()->latest('id')->firstOrFail();
    }

    protected function createDetail(MonthlyBudgetPlan $monthly, string $type, float $amount, bool $indirect = false): BudgetPlanDetail
    {
        $this->post(route('erp.monthly-budget-plans.budget-plan-details.store', $monthly), [
            'expense_type' => $type,
            'is_indirect' => $indirect ? 1 : 0,
            'amount' => $amount,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error')->assertRedirect();

        return $monthly->budgetPlanDetails()->where('expense_type', $type)->where('is_indirect', $indirect)->firstOrFail();
    }

    /**
     * An Approved kasbon against a detail, inserted directly (kasbon flows are
     * another module), then rolled up so the detail counts it as used.
     */
    protected function approvedKasbon(BudgetPlanDetail $detail, float $amount): int
    {
        $workId = $detail->monthlyBudgetPlan->annualBudgetPlan->work_item_id;
        $id = DB::connection('tenant')->table('erp_advance_requests')->insertGetId([
            'code' => 'AR-TEST-'.Str::upper(Str::random(6)),
            'budget_plan_detail_id' => $detail->id,
            'work_id' => $workId,
            'budget_period' => '2026-01-01',
            'advance_request_date' => '2026-01-15',
            'request_by_id' => auth()->id() ?? 1,
            'actual_amount' => $amount,
            'total_amount_advance' => $amount,
            'advance_outstanding' => $amount,
            'status' => 'Approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Services\BudgetRollupService::recalculateDetail($detail->id);

        return $id;
    }

    protected function remaining($model): float
    {
        return (float) $model->fresh()->remaining_budget;
    }
}
