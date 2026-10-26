<?php

namespace Tests\Feature\Erp\Budget;

use App\Models\Erp\AnnualBudgetPlan;
use Tests\ErpTestCase;

class AnnualBudgetPlanTest extends ErpTestCase
{
    use BudgetFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsBudgetAdmin();
    }

    public function test_creating_an_annual_plan_takes_its_amount_out_of_the_wid(): void
    {
        $wid = $this->widWithBudget(500000);

        $response = $this->post(route('erp.annual-budget-plans.store'), $this->annualData($wid, 200000));

        $annual = AnnualBudgetPlan::where('work_item_id', $wid->id)->firstOrFail();
        $response->assertRedirect(route('erp.annual-budget-plans.show', $annual))->assertSessionHas('success');
        $this->assertSame('2026-0001', $annual->code);
        $this->assertSame($wid->subProject->budget_parent_id, $annual->budget_parent_id);
        $this->assertSame(300000.0, $this->remaining($wid));
        $this->assertSame(200000.0, $this->remaining($annual));
    }

    public function test_annual_plan_is_refused_when_the_wid_does_not_have_enough(): void
    {
        $wid = $this->widWithBudget(500000);

        $this->from(route('erp.annual-budget-plans.index'))
            ->post(route('erp.annual-budget-plans.store'), $this->annualData($wid, 500001))
            ->assertRedirect(route('erp.annual-budget-plans.index'))
            ->assertSessionHas('error', "Remaining WID {$wid->wid_code} tidak cukup: sisa Rp 500.000, diminta Rp 500.001.");

        $this->assertSame(0, AnnualBudgetPlan::count());
        $this->assertSame(500000.0, $this->remaining($wid));
    }

    public function test_annual_plan_amount_must_be_positive(): void
    {
        $wid = $this->widWithBudget(500000);

        $this->post(route('erp.annual-budget-plans.store'), $this->annualData($wid, 0))
            ->assertSessionHasErrors('budget_amount');

        $this->assertSame(0, AnnualBudgetPlan::count());
    }

    public function test_annual_plan_is_unique_per_wid_year_area_work_type_and_subtype(): void
    {
        $wid = $this->widWithBudget(500000);
        $this->createAnnual($wid, 100000, ['work_subtype' => 'Tower']);

        $this->post(route('erp.annual-budget-plans.store'), $this->annualData($wid, 100000, ['work_subtype' => 'Tower']))
            ->assertSessionHas('error', 'Kombinasi WID + Year + Area + Work Type + Work Subtype ini sudah ada.');
        $this->assertSame(1, AnnualBudgetPlan::count());
        $this->assertSame(400000.0, $this->remaining($wid));

        // A different subtype (or year) is a different plan.
        $this->createAnnual($wid, 100000, ['work_subtype' => 'Fiber']);
        $this->createAnnual($wid, 100000, ['work_subtype' => 'Tower', 'year' => 2027]);
        $this->assertSame(200000.0, $this->remaining($wid));
    }

    public function test_updating_the_amount_takes_or_refunds_only_the_difference(): void
    {
        $wid = $this->widWithBudget(500000);
        $annual = $this->createAnnual($wid, 200000);

        $this->put(route('erp.annual-budget-plans.update', $annual), $this->annualData($wid, 350000))
            ->assertRedirect(route('erp.annual-budget-plans.show', $annual))->assertSessionHas('success');
        $this->assertSame(150000.0, $this->remaining($wid));
        $this->assertSame(350000.0, $this->remaining($annual));

        $this->put(route('erp.annual-budget-plans.update', $annual), $this->annualData($wid, 100000))
            ->assertSessionHas('success');
        $this->assertSame(400000.0, $this->remaining($wid));
    }

    public function test_increasing_the_amount_beyond_the_wid_is_refused(): void
    {
        $wid = $this->widWithBudget(500000);
        $annual = $this->createAnnual($wid, 200000);

        // Only 300k left on the WID, so +300,001 is too much.
        $this->put(route('erp.annual-budget-plans.update', $annual), $this->annualData($wid, 500001))
            ->assertSessionHas('error', "Remaining WID {$wid->wid_code} tidak cukup: sisa Rp 300.000, diminta Rp 300.001.");

        $this->assertSame(200000.0, (float) $annual->fresh()->budget_amount);
        $this->assertSame(300000.0, $this->remaining($wid));
    }

    public function test_moving_to_another_wid_refunds_the_old_and_takes_the_full_amount_from_the_new(): void
    {
        $parent = $this->createParent(1000000);
        $sub = $this->createSubProject($parent);
        $old = $this->createWid($sub, 400000);
        $new = $this->createWid($sub, 300000);
        $annual = $this->createAnnual($old, 250000);

        $this->put(route('erp.annual-budget-plans.update', $annual), $this->annualData($new, 250000))
            ->assertSessionHas('success');

        $this->assertSame($new->id, $annual->fresh()->work_item_id);
        $this->assertSame(400000.0, $this->remaining($old));
        $this->assertSame(50000.0, $this->remaining($new));
    }

    public function test_moving_to_a_wid_without_enough_remaining_is_refused(): void
    {
        $parent = $this->createParent(1000000);
        $sub = $this->createSubProject($parent);
        $old = $this->createWid($sub, 400000);
        $new = $this->createWid($sub, 100000);
        $annual = $this->createAnnual($old, 250000);

        $this->put(route('erp.annual-budget-plans.update', $annual), $this->annualData($new, 250000))
            ->assertSessionHas('error');

        $this->assertSame($old->id, $annual->fresh()->work_item_id);
        $this->assertSame(150000.0, $this->remaining($old));
        $this->assertSame(100000.0, $this->remaining($new));
    }

    public function test_annual_cannot_be_reduced_below_what_its_monthly_plans_hold(): void
    {
        $wid = $this->widWithBudget(500000);
        $annual = $this->createAnnual($wid, 200000);
        $this->createMonthly($annual, 120000);

        $this->put(route('erp.annual-budget-plans.update', $annual), $this->annualData($wid, 119999))
            ->assertSessionHas('error', 'Budget Amount tidak boleh lebih kecil dari yang sudah dibagi ke Monthly Budget Plan (Rp 120.000).');
        $this->assertSame(200000.0, (float) $annual->fresh()->budget_amount);

        // Down to exactly what the months hold is fine.
        $this->put(route('erp.annual-budget-plans.update', $annual), $this->annualData($wid, 120000))
            ->assertSessionHas('success');
        $this->assertSame(0.0, $this->remaining($annual));
        $this->assertSame(380000.0, $this->remaining($wid));
    }
}
