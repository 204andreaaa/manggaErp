<?php

namespace Tests\Feature\Erp\Budget;

use Tests\ErpTestCase;

class BudgetPagesAndPermissionsTest extends ErpTestCase
{
    use BudgetFixtures;

    public function test_list_and_detail_pages_open_for_a_budget_admin(): void
    {
        $this->loginAsBudgetAdmin();
        $parent = $this->createParent(1000000, ['budget_code' => 'BP-PAGE']);
        $sub = $this->createSubProject($parent, 'SP-PAGE');
        $wid = $this->createWid($sub, 400000, 'WID-PAGE-001');
        $annual = $this->createAnnual($wid, 200000);
        $monthly = $this->createMonthly($annual, 100000);

        $this->get(route('erp.budget-parents.index'))->assertOk();
        $this->get(route('erp.sub-projects.index'))->assertOk();
        $this->get(route('erp.work-items.index'))->assertOk();
        $this->get(route('erp.annual-budget-plans.index'))->assertOk()->assertSee($annual->code);
        $this->get(route('erp.annual-budget-plans.index', ['budget_parent_id' => $parent->id]))->assertOk()->assertSee($annual->code);
        $this->get(route('erp.annual-budget-plans.show', $annual))->assertOk()->assertSee($monthly->code);
        $this->get(route('erp.monthly-budget-plans.show', $monthly))->assertOk()->assertSee($annual->code);
    }

    public function test_datatables_return_rows(): void
    {
        $this->loginAsBudgetAdmin();
        $parent = $this->createParent(1000000, ['budget_code' => 'BP-DT']);
        $this->createWid($this->createSubProject($parent, 'SP-DT'), 1000, 'WID-DT-001');

        $this->postJson(route('erp.budget-parents.datatable'), ['draw' => 1, 'search' => ['value' => 'BP-DT']])
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.remaining_budget', '999,000.00');
        $this->postJson(route('erp.sub-projects.datatable'), ['draw' => 1])
            ->assertOk()->assertJsonPath('data.0.sub_project_code', 'SP-DT');
        $this->postJson(route('erp.work-items.datatable'), ['draw' => 1])
            ->assertOk()->assertJsonPath('data.0.wid_code', 'WID-DT-001');
    }

    public function test_budget_parent_detail_page_lists_plans_projects_works_kasbon_and_declarations(): void
    {
        $this->loginAsBudgetAdmin();
        $parent = $this->createParent(1000000, ['budget_code' => 'BP-SHOW']);
        $sub = $this->createSubProject($parent, 'SP-SHOW');
        $wid = $this->createWid($sub, 400000, 'WID-SHOW-001');
        $annual = $this->createAnnual($wid, 200000);
        $detail = $this->createDetail($this->createMonthly($annual, 100000), 'Office', 50000);
        $kasbonId = $this->approvedKasbon($detail, 10000);
        \Illuminate\Support\Facades\DB::connection('tenant')->table('erp_expense_declarations')->insert([
            'code' => 'ED-SHOW-1', 'record_type' => 'with_advance', 'budget_plan_detail_id' => $detail->id,
            'advance_request_id' => $kasbonId, 'work_id' => $wid->id, 'request_by_id' => 1,
            'request_date' => '2026-01-20', 'status' => 'Draft', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $kasbonCode = \App\Models\Erp\AdvanceRequest::find($kasbonId)->code;

        $this->get(route('erp.budget-parents.show', $parent))
            ->assertOk()
            ->assertSee('BP-SHOW')
            ->assertSee($annual->code)
            ->assertSee('SP-SHOW')
            ->assertSee('WID-SHOW-001')
            ->assertSee($kasbonCode)
            ->assertSee('ED-SHOW-1');
    }

    public function test_user_without_budget_permissions_is_refused_everywhere(): void
    {
        $this->loginAsBudgetAdmin();
        $parent = $this->createParent(1000000);
        $sub = $this->createSubProject($parent);
        $wid = $this->createWid($sub, 400000);
        $annual = $this->createAnnual($wid, 200000);
        $monthly = $this->createMonthly($annual, 100000);
        $detail = $this->createDetail($monthly, 'Office', 1000);

        $this->actingInProject($this->makeUser('staff'));

        $this->get(route('erp.budget-parents.index'))->assertForbidden();
        $this->get(route('erp.budget-parents.show', $parent))->assertForbidden();
        $this->postJson(route('erp.budget-parents.store'), ['budget_code' => 'X', 'name' => 'X', 'total_budget' => 1, 'status' => 'A'])->assertForbidden();
        $this->putJson(route('erp.budget-parents.update', $parent), [])->assertForbidden();
        $this->deleteJson(route('erp.budget-parents.destroy', $parent))->assertForbidden();
        $this->get(route('erp.sub-projects.index'))->assertForbidden();
        $this->postJson(route('erp.sub-projects.store'), [])->assertForbidden();
        $this->deleteJson(route('erp.sub-projects.destroy', $sub))->assertForbidden();
        $this->get(route('erp.work-items.index'))->assertForbidden();
        $this->postJson(route('erp.work-items.store'), ['sub_project_id' => $sub->id])->assertForbidden();
        $this->putJson(route('erp.work-items.update', $wid), [])->assertForbidden();
        $this->deleteJson(route('erp.work-items.destroy', $wid))->assertForbidden();
        $this->get(route('erp.annual-budget-plans.index'))->assertForbidden();
        $this->get(route('erp.annual-budget-plans.show', $annual))->assertForbidden();
        $this->post(route('erp.annual-budget-plans.store'), $this->annualData($wid, 1))->assertForbidden();
        $this->put(route('erp.annual-budget-plans.update', $annual), $this->annualData($wid, 1))->assertForbidden();
        $this->post(route('erp.annual-budget-plans.monthly-plans.store', $annual), $this->monthlyData(1, 1, ['month' => 2]))->assertForbidden();
        $this->get(route('erp.monthly-budget-plans.show', $monthly))->assertForbidden();
        $this->put(route('erp.monthly-budget-plans.update', $monthly), $this->monthlyData(1))->assertForbidden();
        $this->post(route('erp.monthly-budget-plans.budget-plan-details.store', $monthly), ['expense_type' => 'Utilities', 'amount' => 1])->assertForbidden();
        $this->put(route('erp.budget-plan-details.update', $detail), ['expense_type' => 'Office', 'amount' => 1])->assertForbidden();

        $this->assertNull($wid->fresh()->deleted_at);
        $this->assertSame(200000.0, $this->remaining($wid));
    }

    public function test_view_only_permissions_open_pages_but_cannot_change_budgets(): void
    {
        $this->loginAsBudgetAdmin();
        $parent = $this->createParent(1000000);
        $wid = $this->createWid($this->createSubProject($parent), 400000);

        $this->actingInProject($this->makeUser('viewer', ['budgets.view', 'annual_budget_plans.view']));

        $this->get(route('erp.budget-parents.index'))->assertOk();
        $this->get(route('erp.budget-parents.show', $parent))->assertOk();
        $this->get(route('erp.work-items.index'))->assertOk();
        $this->get(route('erp.annual-budget-plans.index'))->assertOk();
        $this->postJson(route('erp.work-items.store'), ['sub_project_id' => $wid->sub_project_id, 'name' => 'x', 'allocated_budget' => 1])->assertForbidden();
        $this->post(route('erp.annual-budget-plans.store'), $this->annualData($wid, 1))->assertForbidden();
        $this->assertSame(400000.0, $this->remaining($wid));
    }

    public function test_granular_work_item_permission_allows_creating_a_wid(): void
    {
        $this->loginAsBudgetAdmin();
        $sub = $this->createSubProject($this->createParent(1000000));

        $this->actingInProject($this->makeUser('planner', ['work_items.create']));

        $this->postJson(route('erp.work-items.store'), [
            'sub_project_id' => $sub->id, 'wid_code' => 'WID-PERM-1', 'name' => 'x', 'allocated_budget' => 1000,
        ])->assertOk();
    }

    public function test_granular_work_item_permission_does_not_grant_budget_parent_create(): void
    {
        $this->loginAsBudgetAdmin();
        $this->actingInProject($this->makeUser('planner', ['work_items.create']));

        $this->postJson(route('erp.budget-parents.store'), ['budget_code' => 'X', 'name' => 'X', 'total_budget' => 1, 'status' => 'A'])->assertForbidden();
    }

    public function test_legacy_budgets_permission_still_grants_every_budget_screen(): void
    {
        $this->actingInProject($this->makeUser('budget_legacy', ['budgets.create']));

        $this->postJson(route('erp.budget-parents.store'), ['budget_code' => 'LEGACY', 'name' => 'Legacy', 'total_budget' => 1000, 'status' => 'Active'])->assertOk();
    }
}
