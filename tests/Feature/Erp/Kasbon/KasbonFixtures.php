<?php

namespace Tests\Feature\Erp\Kasbon;

use App\Http\Controllers\Erp\AdvanceRequestController;
use App\Http\Controllers\Erp\ExpenseDeclarationController;
use App\Models\Erp\AdvanceRequest;
use App\Models\Erp\AnnualBudgetPlan;
use App\Models\Erp\BudgetPlanDetail;
use App\Models\Erp\ErpApproval;
use App\Models\Erp\ErpApprovalConfig;
use App\Models\Erp\ErpBudgetParent;
use App\Models\Erp\ErpSubProject;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\MonthlyBudgetPlan;
use App\Models\Role;
use App\Models\User;
use App\Services\BudgetRollupService;

/**
 * Budget chain (Budget Parent -> Sub Project -> WID -> Annual -> Monthly -> Detail),
 * users and approval configs for the kasbon / expense declaration tests.
 */
trait KasbonFixtures
{
    protected ErpWorkItem $wid;
    protected AnnualBudgetPlan $annual;
    protected MonthlyBudgetPlan $monthly;
    protected User $requester;
    protected User $approver;
    protected User $approver2;
    protected User $finance;

    protected const AREA = 'Jakarta';
    protected const WORK_TYPE = 'Survey';
    protected const DATE = '2026-01-15';
    protected const WID_REMAINING = 500000000;

    private int $codeSeq = 0;

    protected function setUpKasbonWorld(int $approvalLevels = 1): void
    {
        $parent = ErpBudgetParent::create([
            'budget_code' => 'BP-TEST-1', 'name' => 'Test Account',
            'total_budget' => 1000000000, 'remaining_budget' => 0, 'status' => 'Active',
        ]);
        $sub = ErpSubProject::create(['budget_parent_id' => $parent->id, 'sub_project_code' => 'SP-TEST-1', 'name' => 'Sub']);
        $this->wid = ErpWorkItem::create([
            'sub_project_id' => $sub->id, 'wid_code' => 'WID-TEST-001', 'name' => 'Work Item',
            'allocated_budget' => 1000000000, 'remaining_budget' => self::WID_REMAINING,
        ]);

        $this->annual = $this->makeAnnual($this->wid, 2026, self::AREA, self::WORK_TYPE, null, 120000000);
        $this->monthly = $this->makeMonthly($this->annual, 1, 60000000);
        $this->makeDetail($this->monthly, 'Office', 50000000);
        $this->makeDetail($this->monthly, 'Other Expense', 10000000);

        $perms = [
            'advance_requests.view', 'advance_requests.create',
            'expense_declarations.view', 'expense_declarations.create',
        ];
        $this->requester = $this->makeUser('staff', $perms);
        $this->approver = $this->makeUser('kasbon_approver', array_merge($perms, ['advance_requests.approve', 'expense_declarations.approve']));
        $this->approver2 = $this->makeUser('kasbon_approver2', array_merge($perms, ['advance_requests.approve', 'expense_declarations.approve']));
        $this->finance = $this->makeUser('finance', $perms);

        $this->configureApprovals($approvalLevels);
    }

    /** Level 1 = kasbon_approver role, level 2 (if asked) = kasbon_approver2 role. */
    protected function configureApprovals(int $levels): void
    {
        $roles = ['kasbon_approver', 'kasbon_approver2'];
        foreach (['advance_request', 'expense_declaration'] as $recordType) {
            for ($level = 1; $level <= $levels; $level++) {
                ErpApprovalConfig::create([
                    'record_type' => $recordType,
                    'level' => $level,
                    'name' => "Step {$level}",
                    'role_id' => Role::where('slug', $roles[$level - 1])->value('id'),
                ]);
            }
        }
    }

    protected function makeAnnual(ErpWorkItem $wid, int $year, string $area, string $type, ?string $subtype, float $budget): AnnualBudgetPlan
    {
        return AnnualBudgetPlan::create([
            'code' => 'ABP-TEST-'.(++$this->codeSeq),
            'budget_parent_id' => $wid->subProject->budget_parent_id,
            'work_item_id' => $wid->id,
            'year' => $year, 'area' => $area, 'work_type' => $type, 'work_subtype' => $subtype,
            'currency' => 'IDR', 'budget_amount' => $budget, 'remaining_budget' => $budget,
        ]);
    }

    protected function makeMonthly(AnnualBudgetPlan $annual, int $month, float $budget, int $qty = 1): MonthlyBudgetPlan
    {
        return MonthlyBudgetPlan::create([
            'code' => 'MBP-TEST-'.(++$this->codeSeq),
            'annual_budget_plan_id' => $annual->id,
            'year' => $annual->year, 'month' => $month, 'unit' => 'Site', 'qty' => $qty,
            'revenue_plan' => 0, 'budget_amount' => $budget, 'remaining_budget' => $budget,
        ]);
    }

    protected function makeDetail(MonthlyBudgetPlan $monthly, string $type, float $amount): BudgetPlanDetail
    {
        $detail = BudgetPlanDetail::create([
            'code' => 'BPD-TEST-'.(++$this->codeSeq),
            'monthly_budget_plan_id' => $monthly->id,
            'expense_type' => $type, 'is_indirect' => false, 'amount' => $amount,
        ]);
        BudgetRollupService::recalculateDetail($detail->id);

        return $detail->fresh();
    }

    protected function detail(string $type, ?MonthlyBudgetPlan $monthly = null): BudgetPlanDetail
    {
        return ($monthly ?? $this->monthly)->budgetPlanDetails()->where('expense_type', $type)->firstOrFail();
    }

    protected function remaining(string $type): float
    {
        return (float) $this->detail($type)->remaining_budget;
    }

    /** ['Office' => 1000] -> form `lines` array indexed like the form's expense-type rows. */
    protected function lines(array $amounts, array $types = AdvanceRequestController::EXPENSE_TYPES): array
    {
        $lines = [];
        foreach ($amounts as $type => $amount) {
            $index = array_search($type, $types, true);
            $this->assertNotFalse($index, "Unknown expense type {$type}");
            $lines[$index] = ['checked' => 1, 'amount' => $amount, 'description' => "Test {$type}"];
        }

        return $lines;
    }

    protected function kasbonPayload(array $amounts, array $overrides = []): array
    {
        return array_merge([
            'advance_request_date' => self::DATE,
            'area' => self::AREA,
            'work_type' => self::WORK_TYPE,
            'work_subtype' => null,
            'work_id' => $this->wid->id,
            'request_by_id' => $this->requester->id,
            'lines' => $this->lines($amounts),
        ], $overrides);
    }

    /** Posts the kasbon form as the requester; returns the test response. */
    protected function postKasbon(array $amounts, array $overrides = [])
    {
        $this->actingInProject($this->requester);

        return $this->post(route('erp.advance-requests.store'), $this->kasbonPayload($amounts, $overrides));
    }

    protected function createKasbon(string $type, float $amount): AdvanceRequest
    {
        $before = AdvanceRequest::max('id') ?? 0;
        $this->postKasbon([$type => $amount])->assertSessionHasNoErrors();

        return AdvanceRequest::where('id', '>', $before)->firstOrFail();
    }

    protected function submitKasbon(AdvanceRequest $kasbon)
    {
        $this->actingInProject($this->requester);

        return $this->post(route('erp.advance-requests.submit', $kasbon));
    }

    protected function pendingApproval(AdvanceRequest|\App\Models\Erp\ExpenseDeclaration $record): ErpApproval
    {
        return $record->approvals()->where('status', 'Pending')->firstOrFail();
    }

    /** Approves every pending level with the designated approver for that level. */
    protected function approveKasbonFully(AdvanceRequest $kasbon): void
    {
        while ($approval = $kasbon->approvals()->where('status', 'Pending')->first()) {
            $this->actingInProject($approval->level == 1 ? $this->approver : $this->approver2);
            $this->post(route('erp.advance-requests.approvals.approve', $approval), ['comments' => 'ok'])
                ->assertSessionHas('success');
        }
    }

    protected function approvedKasbon(string $type, float $amount): AdvanceRequest
    {
        $kasbon = $this->createKasbon($type, $amount);
        $this->submitKasbon($kasbon)->assertSessionHas('success');
        $this->approveKasbonFully($kasbon);

        return $kasbon->fresh();
    }

    protected function paidKasbon(string $type, float $amount): AdvanceRequest
    {
        $kasbon = $this->approvedKasbon($type, $amount);
        $this->actingInProject($this->finance);
        $this->post(route('erp.advance-requests.pay', $kasbon))->assertSessionHas('success');

        return $kasbon->fresh();
    }

    protected function declarationWithAdvance(AdvanceRequest $kasbon, array $amounts, array $overrides = [])
    {
        $this->actingInProject($this->requester);

        return $this->post(route('erp.expense-declarations.store'), array_merge([
            'record_type' => 'with_advance',
            'advance_request_id' => $kasbon->id,
            'request_by_id' => $this->requester->id,
            'request_date' => '2026-01-16',
            'lines' => $this->lines($amounts, ExpenseDeclarationController::EXPENSE_TYPES),
        ], $overrides));
    }

    protected function declarationWithoutAdvance(array $amounts, array $overrides = [])
    {
        $this->actingInProject($this->requester);

        return $this->post(route('erp.expense-declarations.store'), array_merge([
            'record_type' => 'without_advance',
            'work_id' => $this->wid->id,
            'area' => self::AREA,
            'work_type' => self::WORK_TYPE,
            'request_by_id' => $this->requester->id,
            'request_date' => '2026-01-17',
            'lines' => $this->lines($amounts, ExpenseDeclarationController::EXPENSE_TYPES),
        ], $overrides));
    }
}
