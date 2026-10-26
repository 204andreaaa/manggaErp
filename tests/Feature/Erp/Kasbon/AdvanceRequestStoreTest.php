<?php

namespace Tests\Feature\Erp\Kasbon;

use App\Models\Erp\AdvanceRequest;
use Tests\ErpTestCase;

/** Kasbon form: budget resolution, one record per expense type, early budget check, pages. */
class AdvanceRequestStoreTest extends ErpTestCase
{
    use KasbonFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpKasbonWorld();
    }

    public function test_index_create_and_show_pages_render(): void
    {
        $kasbon = $this->createKasbon('Office', 1000000);
        $this->actingInProject($this->requester);

        $this->get(route('erp.advance-requests.index'))->assertOk()->assertSee($kasbon->code);
        $this->get(route('erp.advance-requests.create'))->assertOk()->assertSee('WID-TEST-001');
        $this->get(route('erp.advance-requests.show', $kasbon))->assertOk()->assertSee($kasbon->code);
    }

    public function test_show_page_renders_an_approved_kasbon_with_approvals_and_declarations(): void
    {
        $kasbon = $this->paidKasbon('Office', 2000000);
        $this->declarationWithAdvance($kasbon, ['Office' => 500000])->assertSessionHasNoErrors();

        $this->actingInProject($this->requester);
        $this->get(route('erp.advance-requests.show', $kasbon))->assertOk()->assertSee($kasbon->code);
    }

    public function test_user_without_permission_cannot_open_or_post_the_form(): void
    {
        $this->actingInProject($this->makeUser('outsider'));

        $this->get(route('erp.advance-requests.index'))->assertForbidden();
        $this->get(route('erp.advance-requests.create'))->assertForbidden();
        $this->post(route('erp.advance-requests.store'), $this->kasbonPayload(['Office' => 1000]))->assertForbidden();
        $this->assertSame(0, AdvanceRequest::count());
    }

    public function test_one_ticked_type_creates_one_draft_kasbon_on_the_matching_detail(): void
    {
        $response = $this->postKasbon(['Office' => 1500000]);

        $kasbon = AdvanceRequest::sole();
        $response->assertRedirect(route('erp.advance-requests.show', $kasbon));
        $this->assertMatchesRegularExpression('/^ADVREQ-\d{5}$/', $kasbon->code);
        $this->assertSame('Draft', $kasbon->status);
        $this->assertSame($this->detail('Office')->id, $kasbon->budget_plan_detail_id);
        $this->assertSame($this->wid->id, (int) $kasbon->work_id);
        $this->assertEquals(1500000, (float) $kasbon->total_amount_advance);
        $this->assertEquals(1500000, (float) $kasbon->amount_per_unit);
        // Draft does not cut anything.
        $this->assertEquals(50000000, $this->remaining('Office'));
    }

    public function test_each_ticked_type_creates_a_separate_kasbon(): void
    {
        $this->postKasbon(['Office' => 1000000, 'Other Expense' => 2000000])
            ->assertRedirect(route('erp.advance-requests.index'));

        $kasbons = AdvanceRequest::orderBy('id')->get();
        $this->assertCount(2, $kasbons);
        $this->assertSame(['ADVREQ-00001', 'ADVREQ-00002'], $kasbons->pluck('code')->all());
        $this->assertSame(['Office', 'Other Expense'], $kasbons->map(fn ($k) => $k->budgetPlanDetail->expense_type)->all());
        $this->assertEquals([1000000, 2000000], $kasbons->map(fn ($k) => (float) $k->total_amount_advance)->all());
    }

    public function test_codes_keep_counting_after_existing_kasbon(): void
    {
        $this->createKasbon('Office', 1000);
        $second = $this->createKasbon('Office', 1000);

        $this->assertSame('ADVREQ-00002', $second->code);
    }

    public function test_number_of_site_multiplies_the_amount(): void
    {
        $this->postKasbon(['Office' => 1000000], ['number_of_site' => 3])->assertSessionHasNoErrors();

        $kasbon = AdvanceRequest::sole();
        $this->assertEquals(1000000, (float) $kasbon->amount_per_unit);
        $this->assertEquals(3000000, (float) $kasbon->total_amount_advance);
        $this->assertSame(3, (int) $kasbon->number_of_site);
    }

    public function test_number_of_site_counts_toward_the_budget_check(): void
    {
        // 4 jt x 3 site = 12 jt > Other Expense 10 jt.
        $this->postKasbon(['Other Expense' => 4000000], ['number_of_site' => 3])->assertSessionHasErrors('lines');
        $this->assertSame(0, AdvanceRequest::count());
    }

    public function test_missing_annual_plan_is_a_validation_error(): void
    {
        $this->postKasbon(['Office' => 1000], ['area' => 'Bandung'])->assertSessionHasErrors('area');
        $this->postKasbon(['Office' => 1000], ['advance_request_date' => '2025-01-15'])->assertSessionHasErrors('area');
        $this->assertSame(0, AdvanceRequest::count());
    }

    public function test_missing_monthly_plan_is_a_validation_error(): void
    {
        $this->postKasbon(['Office' => 1000], ['advance_request_date' => '2026-02-10'])->assertSessionHasErrors('area');
        $this->assertSame(0, AdvanceRequest::count());
    }

    public function test_missing_detail_for_a_ticked_type_aborts_the_whole_form(): void
    {
        $this->postKasbon(['Office' => 1000, 'Personnel' => 1000])->assertSessionHasErrors('lines');
        $this->assertSame(0, AdvanceRequest::count());
    }

    public function test_work_subtype_must_match_the_annual_plan(): void
    {
        $annual = $this->makeAnnual($this->wid, 2026, self::AREA, self::WORK_TYPE, 'Drive Test', 10000000);
        $monthly = $this->makeMonthly($annual, 1, 5000000);
        $this->makeDetail($monthly, 'Office', 5000000);

        $this->postKasbon(['Office' => 1000], ['work_subtype' => 'Other Subtype'])->assertSessionHasErrors('area');
        $this->postKasbon(['Office' => 1000], ['work_subtype' => 'Drive Test'])->assertSessionHasNoErrors();
        $this->postKasbon(['Office' => 1000])->assertSessionHasNoErrors();

        $kasbons = AdvanceRequest::orderBy('id')->get();
        $this->assertSame($this->detail('Office', $monthly)->id, $kasbons[0]->budget_plan_detail_id);
        $this->assertSame($this->detail('Office')->id, $kasbons[1]->budget_plan_detail_id);
    }

    public function test_amount_above_the_detail_remaining_is_refused(): void
    {
        $this->postKasbon(['Other Expense' => 10000001])->assertSessionHasErrors('lines');
        $this->assertSame(0, AdvanceRequest::count());

        $this->postKasbon(['Other Expense' => 10000000])->assertSessionHasNoErrors();
        $this->assertSame(1, AdvanceRequest::count());
    }

    public function test_budget_check_uses_what_approved_kasbon_already_cut(): void
    {
        $this->approvedKasbon('Other Expense', 7000000);

        $this->postKasbon(['Other Expense' => 3000001])->assertSessionHasErrors('lines');
        $this->postKasbon(['Other Expense' => 3000000])->assertSessionHasNoErrors();
    }

    public function test_nothing_ticked_or_ticked_without_amount_is_refused(): void
    {
        $this->postKasbon([], ['lines' => [0 => ['checked' => 0, 'amount' => null]]])->assertSessionHasErrors('lines');
        $this->postKasbon([], ['lines' => [4 => ['checked' => 1, 'amount' => 0]]])->assertSessionHasErrors('lines.4.amount');
        $this->assertSame(0, AdvanceRequest::count());
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingInProject($this->requester);
        $this->post(route('erp.advance-requests.store'), [])
            ->assertSessionHasErrors(['advance_request_date', 'area', 'work_type', 'work_id', 'request_by_id', 'lines']);
    }
}
