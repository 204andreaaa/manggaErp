<?php

namespace Tests\Feature\Erp\Kasbon;

use Tests\ErpTestCase;

/**
 * Settling closes a paid kasbon: the part that was not declared is recorded as
 * returned by the employee, the outstanding drops to 0 and the budget is released.
 */
class KasbonSettlementTest extends ErpTestCase
{
    use KasbonFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpKasbonWorld();
    }

    public function test_finance_settles_the_unused_part_and_the_budget_is_released(): void
    {
        $kasbon = $this->paidKasbon('Office', 5000000);
        $this->declarationWithAdvance($kasbon, ['Office' => 4000000])->assertSessionHasNoErrors();
        $this->assertEquals(1000000, (float) $kasbon->fresh()->advance_outstanding);
        $this->assertEquals(45000000, $this->remaining('Office'));

        $this->actingInProject($this->finance)
            ->get(route('erp.advance-requests.show', $kasbon))->assertOk()->assertSee('Settle / Tutup Kasbon');
        $this->post(route('erp.advance-requests.settle', $kasbon), ['settlement_date' => '2026-01-20', 'settlement_note' => 'tunai ke kasir'])
            ->assertSessionHas('success');

        $kasbon->refresh();
        $this->assertTrue($kasbon->isSettled());
        $this->assertEquals(1000000, (float) $kasbon->settlement_amount);
        $this->assertSame('2026-01-20', $kasbon->settlement_date->toDateString());
        $this->assertSame($this->finance->id, (int) $kasbon->settled_by_id);
        $this->assertEquals(0, (float) $kasbon->advance_outstanding);
        // only what was really spent stays out of the budget
        $this->assertEquals(46000000, $this->remaining('Office'));

        $this->get(route('erp.advance-requests.show', $kasbon))->assertOk()->assertSee('Closed')->assertDontSee('Settle / Tutup Kasbon');
    }

    public function test_a_settled_kasbon_takes_no_more_declarations_and_cannot_be_settled_twice(): void
    {
        $kasbon = $this->paidKasbon('Office', 5000000);
        $this->actingInProject($this->finance)
            ->post(route('erp.advance-requests.settle', $kasbon), ['settlement_date' => '2026-01-20'])->assertSessionHas('success');

        $this->actingInProject($this->requester)
            ->get(route('erp.expense-declarations.create'))->assertOk()->assertDontSee($kasbon->code);
        $this->declarationWithAdvance($kasbon, ['Office' => 1000])->assertSessionHasErrors('advance_request_id');

        $this->actingInProject($this->finance)
            ->post(route('erp.advance-requests.settle', $kasbon), ['settlement_date' => '2026-01-21'])->assertSessionHas('error');
        $this->assertSame('2026-01-20', $kasbon->fresh()->settlement_date->toDateString());
    }

    public function test_only_a_paid_kasbon_can_be_settled_and_only_by_finance(): void
    {
        $unpaid = $this->approvedKasbon('Office', 1000000);
        $this->actingInProject($this->finance)
            ->post(route('erp.advance-requests.settle', $unpaid), ['settlement_date' => '2026-01-20'])->assertSessionHas('error');
        $this->assertFalse($unpaid->fresh()->isSettled());

        $paid = $this->paidKasbon('Office', 1000000);
        $this->actingInProject($this->requester)
            ->post(route('erp.advance-requests.settle', $paid), ['settlement_date' => '2026-01-20'])->assertForbidden();
        $this->assertFalse($paid->fresh()->isSettled());
    }
}
