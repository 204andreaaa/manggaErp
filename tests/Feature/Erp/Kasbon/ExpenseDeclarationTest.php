<?php

namespace Tests\Feature\Erp\Kasbon;

use App\Models\Erp\ExpenseDeclaration;
use App\Services\BudgetRollupService;
use Tests\ErpTestCase;

/** Expense declarations with advance (settling a kasbon) and without advance (own approval + cut). */
class ExpenseDeclarationTest extends ErpTestCase
{
    use KasbonFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpKasbonWorld();
    }

    public function test_index_create_and_show_pages_render(): void
    {
        $kasbon = $this->paidKasbon('Office', 2000000);
        $this->declarationWithAdvance($kasbon, ['Office' => 500000])->assertSessionHasNoErrors();
        $this->declarationWithoutAdvance(['Office' => 300000])->assertSessionHasNoErrors();
        $declaration = ExpenseDeclaration::where('record_type', 'without_advance')->sole();
        $this->actingInProject($this->requester);
        $this->post(route('erp.expense-declarations.submit', $declaration));

        $this->get(route('erp.expense-declarations.index'))->assertOk()->assertSee($declaration->code);
        $this->get(route('erp.expense-declarations.create'))->assertOk()->assertSee($kasbon->code);
        $this->get(route('erp.expense-declarations.create', ['advance_request_id' => $kasbon->id]))->assertOk();
        $this->get(route('erp.expense-declarations.show', $declaration))->assertOk()->assertSee($declaration->code);
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->actingInProject($this->makeUser('outsider'));

        $this->get(route('erp.expense-declarations.index'))->assertForbidden();
        $this->get(route('erp.expense-declarations.create'))->assertForbidden();
        $this->post(route('erp.expense-declarations.store'), ['record_type' => 'without_advance'])->assertForbidden();
    }

    // ---- with advance ----

    public function test_with_advance_needs_an_approved_and_paid_kasbon(): void
    {
        $approvedUnpaid = $this->approvedKasbon('Office', 1000000);
        $this->declarationWithAdvance($approvedUnpaid, ['Office' => 1000])->assertSessionHasErrors('advance_request_id');

        $draft = $this->createKasbon('Office', 1000000);
        $this->declarationWithAdvance($draft, ['Office' => 1000])->assertSessionHasErrors('advance_request_id');

        $this->actingInProject($this->requester);
        $this->post(route('erp.expense-declarations.store'), [
            'record_type' => 'with_advance', 'request_by_id' => $this->requester->id,
            'request_date' => '2026-01-16', 'lines' => $this->lines(['Office' => 1000]),
        ])->assertSessionHasErrors('advance_request_id');

        $this->assertSame(0, ExpenseDeclaration::count());
    }

    public function test_specific_type_kasbon_is_declared_only_under_its_own_type(): void
    {
        $kasbon = $this->paidKasbon('Office', 2000000);

        $this->declarationWithAdvance($kasbon, ['Office' => 500000, 'Personnel' => 100000])->assertSessionHasErrors('lines');
        $this->declarationWithAdvance($kasbon, ['Materials-Subcon' => 100000])->assertSessionHasErrors('lines');
        $this->assertSame(0, ExpenseDeclaration::count());
    }

    public function test_declaration_with_advance_is_approved_at_once_and_settles_the_outstanding(): void
    {
        $kasbon = $this->paidKasbon('Office', 2000000);
        $this->assertEquals(48000000, $this->remaining('Office'));

        $this->declarationWithAdvance($kasbon, ['Office' => 1500000])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('erp.advance-requests.show', $kasbon));

        $declaration = ExpenseDeclaration::sole();
        $this->assertSame('Approved', $declaration->status);
        $this->assertSame('with_advance', $declaration->record_type);
        $this->assertSame('Office', $declaration->expense_type);
        $this->assertSame($kasbon->budget_plan_detail_id, $declaration->budget_plan_detail_id);
        $this->assertSame(0, $declaration->approvals()->count(), 'No approval rows for a with-advance declaration');
        $this->assertMatchesRegularExpression('/^EXP\d{4}-\d{7}$/', $declaration->code);

        $this->assertEquals(500000, (float) $kasbon->fresh()->advance_outstanding);
        $detail = $this->detail('Office');
        $this->assertEquals(48000000, (float) $detail->remaining_budget, 'No double cut: outstanding just turned into expense');
        $this->assertEquals(1500000, (float) $detail->actual_expense_with_advance);
        $this->assertEquals(1500000, (float) $detail->actual_expense_amount);
    }

    public function test_other_expense_kasbon_can_be_split_but_charges_the_kasbon_detail(): void
    {
        $kasbon = $this->paidKasbon('Other Expense', 3000000);

        $this->declarationWithAdvance($kasbon, ['Office' => 1000000, 'Personnel' => 500000])
            ->assertSessionHasNoErrors();

        $declarations = ExpenseDeclaration::orderBy('expense_type')->get();
        $this->assertCount(2, $declarations);
        $this->assertSame(['Office', 'Personnel'], $declarations->pluck('expense_type')->all());
        $this->assertTrue($declarations->every(fn ($d) => $d->status === 'Approved'));
        $this->assertTrue($declarations->every(fn ($d) => $d->budget_plan_detail_id === $kasbon->budget_plan_detail_id));

        $this->assertEquals(1500000, (float) $kasbon->fresh()->advance_outstanding);
        $this->assertEquals(7000000, $this->remaining('Other Expense'));
        $this->assertEquals(50000000, $this->remaining('Office'), 'The Office detail is not charged');
    }

    public function test_declarations_may_not_exceed_what_is_left_of_the_kasbon(): void
    {
        $kasbon = $this->paidKasbon('Other Expense', 3000000);

        $this->declarationWithAdvance($kasbon, ['Office' => 2000000, 'Personnel' => 1000001])->assertSessionHasErrors('lines');
        $this->assertSame(0, ExpenseDeclaration::count());

        $this->declarationWithAdvance($kasbon, ['Office' => 1500000])->assertSessionHasNoErrors();
        $this->declarationWithAdvance($kasbon, ['Other Expense' => 1500001])->assertSessionHasErrors('lines');
        $this->declarationWithAdvance($kasbon, ['Other Expense' => 1500000])->assertSessionHasNoErrors();

        $this->assertEquals(0, (float) $kasbon->fresh()->advance_outstanding);
        $this->assertEquals(7000000, $this->remaining('Other Expense'));
        $this->declarationWithAdvance($kasbon, ['Other Expense' => 1])->assertSessionHasErrors('lines');
    }

    public function test_with_advance_ignores_posted_wid_and_uses_the_kasbon_wid(): void
    {
        $kasbon = $this->paidKasbon('Office', 1000000);

        $this->declarationWithAdvance($kasbon, ['Office' => 1000], ['area' => 'Elsewhere', 'work_type' => 'Other'])
            ->assertSessionHasNoErrors();

        $this->assertSame((int) $kasbon->work_id, (int) ExpenseDeclaration::sole()->work_id);
    }

    // ---- without advance ----

    public function test_without_advance_may_not_point_to_a_kasbon(): void
    {
        $kasbon = $this->paidKasbon('Office', 1000000);

        $this->declarationWithoutAdvance(['Office' => 1000], ['advance_request_id' => $kasbon->id])
            ->assertSessionHasErrors('advance_request_id');
        $this->assertSame(0, ExpenseDeclaration::count());
    }

    public function test_without_advance_resolves_the_detail_and_waits_as_draft(): void
    {
        $this->declarationWithoutAdvance(['Office' => 2000000])->assertSessionHasNoErrors();

        $declaration = ExpenseDeclaration::sole();
        $this->assertSame('Draft', $declaration->status);
        $this->assertSame($this->detail('Office')->id, $declaration->budget_plan_detail_id);
        $this->assertNull($declaration->advance_request_id);
        $this->assertEquals(50000000, $this->remaining('Office'), 'Draft does not cut');
    }

    public function test_without_advance_creates_one_declaration_per_type(): void
    {
        $this->declarationWithoutAdvance(['Office' => 1000, 'Other Expense' => 2000])
            ->assertRedirect(route('erp.expense-declarations.index'));

        $this->assertSame(['Office', 'Other Expense'], ExpenseDeclaration::orderBy('id')->pluck('expense_type')->all());
    }

    public function test_without_advance_requires_a_plan_and_budget_at_save(): void
    {
        $this->declarationWithoutAdvance(['Office' => 1000], ['area' => 'Bandung'])->assertSessionHasErrors('area');
        $this->declarationWithoutAdvance(['Office' => 1000], ['request_date' => '2026-03-01'])->assertSessionHasErrors('area');
        $this->declarationWithoutAdvance(['Materials-Subcon' => 1000])->assertSessionHasErrors('lines');
        $this->declarationWithoutAdvance(['Office' => 50000001])->assertSessionHasErrors('lines');
        $this->declarationWithoutAdvance([], ['work_id' => null, 'area' => null, 'work_type' => null, 'lines' => $this->lines(['Office' => 1])])
            ->assertSessionHasErrors(['work_id', 'area', 'work_type']);
        $this->assertSame(0, ExpenseDeclaration::count());
    }

    public function test_without_advance_final_approval_cuts_the_detail(): void
    {
        $this->declarationWithoutAdvance(['Office' => 2000000]);
        $declaration = ExpenseDeclaration::sole();

        $this->actingInProject($this->requester);
        $this->post(route('erp.expense-declarations.submit', $declaration))->assertSessionHas('success');
        $this->post(route('erp.expense-declarations.submit', $declaration))->assertSessionHas('error');
        $declaration->refresh();
        $this->assertSame('Submitted', $declaration->status);
        $this->assertSame(['Pending'], $declaration->approvals()->pluck('status')->all());

        $this->actingInProject($this->approver);
        $this->post(route('erp.expense-declarations.approvals.approve', $this->pendingApproval($declaration)), ['comments' => 'ok'])
            ->assertSessionHas('success');

        $this->assertSame('Approved', $declaration->fresh()->status);
        $detail = $this->detail('Office');
        $this->assertEquals(48000000, (float) $detail->remaining_budget);
        $this->assertEquals(2000000, (float) $detail->actual_expense_without_advance);
        $this->assertEquals(self::WID_REMAINING, (float) $this->wid->fresh()->remaining_budget);
    }

    public function test_without_advance_requester_cannot_approve_own_declaration(): void
    {
        $this->requester->roles()->attach(\App\Models\Role::where('slug', 'kasbon_approver')->value('id'));
        $this->declarationWithoutAdvance(['Office' => 1000]);
        $declaration = ExpenseDeclaration::sole();
        $this->actingInProject($this->requester);
        $this->post(route('erp.expense-declarations.submit', $declaration));

        $this->actingInProject($this->requester->fresh());
        $this->post(route('erp.expense-declarations.approvals.approve', $this->pendingApproval($declaration)))
            ->assertSessionHas('error');
        $this->assertSame('Submitted', $declaration->fresh()->status);
    }

    public function test_without_advance_final_approval_is_refused_when_budget_was_used_meanwhile(): void
    {
        $this->declarationWithoutAdvance(['Other Expense' => 6000000]);
        $declaration = ExpenseDeclaration::sole();
        $this->actingInProject($this->requester);
        $this->post(route('erp.expense-declarations.submit', $declaration));

        $this->approvedKasbon('Other Expense', 5000000); // 10 jt -> 5 jt left

        $step = $this->pendingApproval($declaration);
        $this->actingInProject($this->approver);
        $this->post(route('erp.expense-declarations.approvals.approve', $step))->assertSessionHas('error');

        $this->assertSame('Submitted', $declaration->fresh()->status);
        $this->assertSame('Pending', $step->fresh()->status);
        $this->assertEquals(5000000, $this->remaining('Other Expense'));
    }

    public function test_without_advance_reject(): void
    {
        $this->declarationWithoutAdvance(['Office' => 1000]);
        $declaration = ExpenseDeclaration::sole();
        $this->actingInProject($this->requester);
        $this->post(route('erp.expense-declarations.submit', $declaration));

        $this->actingInProject($this->approver);
        $this->post(route('erp.expense-declarations.approvals.reject', $this->pendingApproval($declaration)), ['reason' => 'Nota hilang'])
            ->assertSessionHas('success');

        $this->assertSame('Rejected', $declaration->fresh()->status);
        $this->assertEquals(50000000, $this->remaining('Office'));
    }

    public function test_submit_without_config_is_refused(): void
    {
        \App\Models\Erp\ErpApprovalConfig::where('record_type', 'expense_declaration')->delete();
        $this->declarationWithoutAdvance(['Office' => 1000]);
        $declaration = ExpenseDeclaration::sole();

        $this->actingInProject($this->requester);
        $this->post(route('erp.expense-declarations.submit', $declaration))->assertSessionHas('error');
        $this->assertSame('Draft', $declaration->fresh()->status);
    }

    // ---- rollup ----

    public function test_rollup_remaining_is_plan_minus_expense_minus_outstanding(): void
    {
        // Monthly qty 2: plan = amount x qty.
        $monthly = $this->makeMonthly($this->annual, 4, 30000000, qty: 2);
        $detail = $this->makeDetail($monthly, 'Office', 10000000);
        $this->assertEquals(20000000, (float) $detail->total_expense_plan);
        $this->assertEquals(20000000, (float) $detail->remaining_budget);
        $this->assertEquals(10000000, (float) $monthly->fresh()->remaining_budget, 'Monthly remaining = budget - detail plans');

        $date = ['advance_request_date' => '2026-04-10'];
        $this->postKasbon(['Office' => 4000000], $date)->assertSessionHasNoErrors();
        $kasbon = \App\Models\Erp\AdvanceRequest::latest('id')->first();
        $this->submitKasbon($kasbon);
        $this->approveKasbonFully($kasbon);
        $this->actingInProject($this->finance);
        $this->post(route('erp.advance-requests.pay', $kasbon));

        $this->declarationWithAdvance($kasbon->fresh(), ['Office' => 1000000])->assertSessionHasNoErrors();
        $this->declarationWithoutAdvance(['Office' => 3000000], ['request_date' => '2026-04-11'])->assertSessionHasNoErrors();
        $without = ExpenseDeclaration::where('record_type', 'without_advance')->sole();
        $this->actingInProject($this->requester);
        $this->post(route('erp.expense-declarations.submit', $without));
        $this->actingInProject($this->approver);
        $this->post(route('erp.expense-declarations.approvals.approve', $this->pendingApproval($without)));

        // 20 jt - (1 jt + 3 jt expense) - 3 jt outstanding = 13 jt
        $detail->refresh();
        $this->assertEquals(13000000, (float) $detail->remaining_budget);
        $this->assertEquals(13000000, BudgetRollupService::detailRemaining($detail));
        $this->assertEquals(4000000, (float) $detail->actual_expense_amount);
        $this->assertEquals(4000000, (float) $monthly->fresh()->total_actual_expense);
    }
}
