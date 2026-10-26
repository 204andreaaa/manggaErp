<?php

namespace Tests\Feature\Erp\Budget;

use App\Models\Erp\BudgetPlanDetail;
use App\Models\Erp\MonthlyBudgetPlan;
use Tests\ErpTestCase;

class MonthlyAndDetailPlanTest extends ErpTestCase
{
    use BudgetFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsBudgetAdmin();
    }

    private function annual(float $amount = 1000000)
    {
        return $this->createAnnual($this->widWithBudget(5000000, 10000000), $amount);
    }

    public function test_monthly_plan_comes_out_of_the_annual_unallocated_amount(): void
    {
        $annual = $this->annual(1000000);

        $monthly = $this->createMonthly($annual, 600000, 2, ['revenue_plan' => 1000]);

        $this->assertStringStartsWith('BP-'.now()->format('Y-m').'-', $monthly->code);
        $this->assertSame(2026, (int) $monthly->year);
        $this->assertSame(600000.0, $this->remaining($monthly));
        $this->assertSame(2000.0, (float) $monthly->total_revenue_plan);
        $this->assertSame(400000.0, $this->remaining($annual));
    }

    public function test_monthly_plan_bigger_than_what_the_annual_has_left_is_refused(): void
    {
        $annual = $this->annual(1000000);
        $this->createMonthly($annual, 600000);

        $this->post(route('erp.annual-budget-plans.monthly-plans.store', $annual), $this->monthlyData(400001, 1, ['month' => 2]))
            ->assertSessionHas('error', "Sisa Annual Budget Plan {$annual->code} tidak cukup: sisa Rp 400.000, diminta Rp 400.001.");

        $this->assertSame(1, MonthlyBudgetPlan::count());
        $this->assertSame(400000.0, $this->remaining($annual));
    }

    public function test_same_month_twice_is_refused(): void
    {
        $annual = $this->annual();
        $this->createMonthly($annual, 100000);

        $this->post(route('erp.annual-budget-plans.monthly-plans.store', $annual), $this->monthlyData(100000))
            ->assertSessionHas('error', 'Bulan ini sudah ada Monthly Budget Plan-nya.');
        $this->assertSame(1, MonthlyBudgetPlan::count());
    }

    public function test_detail_plan_is_amount_times_qty_and_comes_out_of_the_monthly(): void
    {
        $annual = $this->annual();
        $monthly = $this->createMonthly($annual, 500000, 3);

        $detail = $this->createDetail($monthly, 'Office', 100000);
        $this->createDetail($monthly, 'Personnel', 20000, true);

        $this->assertStringStartsWith('BPD-', $detail->code);
        $this->assertSame(300000.0, (float) $detail->total_expense_plan);
        $this->assertSame(300000.0, $this->remaining($detail));
        $monthly->refresh();
        $this->assertSame(100000.0, (float) $monthly->total_direct_expense_plan);
        $this->assertSame(20000.0, (float) $monthly->total_indirect_expense_plan);
        $this->assertSame(360000.0, (float) $monthly->total_expense_plan);
        $this->assertSame(140000.0, (float) $monthly->remaining_budget);
        $this->assertSame(360000.0, (float) $annual->fresh()->total_expense_plan);
    }

    public function test_detail_that_does_not_fit_in_the_monthly_is_refused(): void
    {
        $monthly = $this->createMonthly($this->annual(), 500000, 3);
        $this->createDetail($monthly, 'Office', 100000);

        // 66,667 x 3 = 200,001 > 200,000 left.
        $this->post(route('erp.monthly-budget-plans.budget-plan-details.store', $monthly), ['expense_type' => 'Utilities', 'amount' => 66667])
            ->assertSessionHas('error', "Sisa Monthly Budget Plan {$monthly->code} tidak cukup: sisa Rp 200.000, diminta Rp 200.001 (Amount x Qty 3).");

        $this->assertSame(1, BudgetPlanDetail::count());
    }

    public function test_detail_expense_type_is_unique_per_month_and_direct_indirect(): void
    {
        $monthly = $this->createMonthly($this->annual(), 500000);
        $this->createDetail($monthly, 'Office', 1000);

        $this->post(route('erp.monthly-budget-plans.budget-plan-details.store', $monthly), ['expense_type' => 'Office', 'amount' => 1000])
            ->assertSessionHas('error', 'Expense type (dengan direct/indirect yang sama) sudah ada di bulan ini.');
        $this->post(route('erp.monthly-budget-plans.budget-plan-details.store', $monthly), ['expense_type' => 'Not A Type', 'amount' => 1000])
            ->assertSessionHasErrors('expense_type');

        // Same type but indirect is allowed.
        $this->createDetail($monthly, 'Office', 1000, true);
        $this->assertSame(2, BudgetPlanDetail::count());
    }

    public function test_detail_update_must_fit_and_not_drop_below_what_kasbon_used(): void
    {
        $monthly = $this->createMonthly($this->annual(), 500000);
        $office = $this->createDetail($monthly, 'Office', 100000);
        $this->createDetail($monthly, 'Utilities', 300000);
        $this->approvedKasbon($office, 60000);
        $this->assertSame(40000.0, $this->remaining($office));

        $update = fn (float $amount) => $this->put(route('erp.budget-plan-details.update', $office), ['expense_type' => 'Office', 'amount' => $amount]);

        $update(200001)->assertSessionHas('error'); // only 200k left besides Utilities
        $update(59999)->assertSessionHas('error', 'Plan Office tidak boleh lebih kecil dari yang sudah terpakai kasbon/deklarasi (Rp 60.000).');
        $this->assertSame(100000.0, (float) $office->fresh()->amount);

        $update(150000)->assertRedirect(route('erp.monthly-budget-plans.show', $monthly))->assertSessionHas('success');
        $this->assertSame(150000.0, (float) $office->fresh()->total_expense_plan);
        $this->assertSame(90000.0, $this->remaining($office));
        $this->assertSame(50000.0, $this->remaining($monthly));
    }

    public function test_changing_monthly_qty_resizes_its_details(): void
    {
        $annual = $this->annual(1000000);
        $monthly = $this->createMonthly($annual, 500000, 1);
        $detail = $this->createDetail($monthly, 'Office', 100000);

        $this->put(route('erp.monthly-budget-plans.update', $monthly), $this->monthlyData(500000, 4))
            ->assertRedirect(route('erp.monthly-budget-plans.show', $monthly))->assertSessionHas('success');

        $this->assertSame(400000.0, (float) $detail->fresh()->total_expense_plan);
        $this->assertSame(100000.0, $this->remaining($monthly));
        $this->assertSame(500000.0, $this->remaining($annual));
    }

    public function test_monthly_update_refused_when_details_would_exceed_its_budget(): void
    {
        $monthly = $this->createMonthly($this->annual(1000000), 500000, 1);
        $this->createDetail($monthly, 'Office', 100000);

        $this->put(route('erp.monthly-budget-plans.update', $monthly), $this->monthlyData(500000, 6))
            ->assertSessionHas('error', 'Budget Plan Detail bulan ini (Rp 600.000) melebihi Budget Amount yang diisi (Rp 500.000).');

        $this->assertSame(1, (int) $monthly->fresh()->qty);
    }

    public function test_monthly_update_refused_when_a_detail_would_drop_below_what_is_used(): void
    {
        $monthly = $this->createMonthly($this->annual(1000000), 500000, 2);
        $detail = $this->createDetail($monthly, 'Office', 100000); // plan 200k
        $this->approvedKasbon($detail, 150000);

        $this->put(route('erp.monthly-budget-plans.update', $monthly), $this->monthlyData(500000, 1))
            ->assertSessionHas('error', "Qty ini membuat plan Office ({$detail->code}) lebih kecil dari yang sudah terpakai (Rp 150.000).");

        $this->assertSame(2, (int) $monthly->fresh()->qty);
        $this->assertSame(200000.0, (float) $detail->fresh()->total_expense_plan);
    }

    public function test_monthly_update_refused_beyond_the_annual_unallocated_amount(): void
    {
        $annual = $this->annual(1000000);
        $monthly = $this->createMonthly($annual, 500000);
        $this->createMonthly($annual, 300000, 1, ['month' => 2]);

        $this->put(route('erp.monthly-budget-plans.update', $monthly), $this->monthlyData(700001))
            ->assertSessionHas('error', "Sisa Annual Budget Plan {$annual->code} tidak cukup: sisa Rp 700.000, diminta Rp 700.001.");
        $this->put(route('erp.monthly-budget-plans.update', $monthly), $this->monthlyData(700000))
            ->assertSessionHas('success');

        $this->assertSame(0.0, $this->remaining($annual));
    }
}
