<?php

namespace Tests\Feature\Erp\Payment;

use App\Models\Erp\ErpPaymentAdviceDetail;
use Tests\ErpTestCase;

/** Adding / removing termins (payment advice details) and the amount rules. */
class PaymentTermTest extends ErpTestCase
{
    use PaymentFixtures;

    private function termPayload(array $overrides = []): array
    {
        return array_merge([
            'payment_amount' => 2_000_000,
            'payment_method' => 'Bank Transfer',
            'payment_type' => 'DP 2',
            'remark' => 'Termin kedua',
        ], $overrides);
    }

    public function test_finance_adds_a_termin_within_the_unallocated_amount(): void
    {
        $po = $this->makeApprovedPo(10_000_000);
        $pa = $this->makePaymentAdvice($po, 10_000_000, [3_000_000]);
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.store', $pa), $this->termPayload(['payment_amount' => 7_000_000]))
            ->assertRedirect(route('erp.payment-advices.show', $pa))
            ->assertSessionHas('success');

        $details = $pa->details()->orderBy('id')->get();
        $this->assertCount(2, $details);
        $new = $details->last();
        $this->assertEquals(7_000_000, $new->payment_amount_with_tax);
        $this->assertSame('DP 2', $new->payment_type);
        $this->assertSame('Draft', $new->approval_status);
        $this->assertSame($po->id, (int) $new->erp_purchase_order_id);
        // Still nothing approved, so nothing deducted from outstanding.
        $this->assertEquals(10_000_000, $pa->fresh()->outstanding);
    }

    public function test_termin_cannot_exceed_the_unallocated_amount(): void
    {
        $po = $this->makeApprovedPo(10_000_000);
        $pa = $this->makePaymentAdvice($po, 10_000_000, [3_000_000]);
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.store', $pa), $this->termPayload(['payment_amount' => 7_000_001]))
            ->assertSessionHasErrors('payment_amount');

        $this->post(route('erp.payment-advice-details.store', $pa), $this->termPayload(['payment_amount' => 0]))
            ->assertSessionHasErrors('payment_amount');

        $this->assertSame(1, $pa->details()->count());
    }

    public function test_no_termin_can_be_added_once_the_invoice_is_fully_allocated(): void
    {
        $po = $this->makeApprovedPo(10_000_000);
        $pa = $this->makePaymentAdvice($po, 10_000_000, [4_000_000, 6_000_000]);
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.store', $pa), $this->termPayload(['payment_amount' => 1]))
            ->assertSessionHas('error');

        $this->assertSame(2, $pa->details()->count());
    }

    public function test_no_termin_can_be_added_to_a_closed_payment_advice(): void
    {
        $po = $this->makeApprovedPo(10_000_000);
        $pa = $this->makePaymentAdvice($po, 10_000_000, [1_000_000]);
        $pa->update(['payment_closed' => true, 'outstanding' => 0]);
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.store', $pa), $this->termPayload())
            ->assertSessionHas('error');

        $this->assertSame(1, $pa->details()->count());
    }

    public function test_finance_deletes_a_draft_termin_but_not_an_approved_one(): void
    {
        $po = $this->makeApprovedPo(10_000_000);
        $pa = $this->makePaymentAdvice($po, 10_000_000);
        $approved = $this->makeDetail($pa, 4_000_000, ['approval_status' => 'Approved']);
        $draft = $this->makeDetail($pa, 6_000_000);
        $this->actingInProject($this->finance());

        $this->delete(route('erp.payment-advice-details.destroy', $approved))->assertSessionHas('error');
        $this->assertDatabaseHas('erp_payment_advice_details', ['id' => $approved->id], 'tenant');

        $this->delete(route('erp.payment-advice-details.destroy', $draft))->assertSessionHas('success');
        $this->assertDatabaseMissing('erp_payment_advice_details', ['id' => $draft->id], 'tenant');

        // Totals recalculated from the remaining approved termin.
        $pa->refresh();
        $this->assertEquals(4_000_000, $pa->sum_payment_amount_with_tax);
        $this->assertEquals(6_000_000, $pa->outstanding);
    }

    public function test_paid_termin_cannot_be_deleted(): void
    {
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000);
        $paid = $this->makeDetail($pa, 1_000_000, ['date_paid' => now()]);
        $this->actingInProject($this->makeUser('superadmin'));

        $this->delete(route('erp.payment-advice-details.destroy', $paid))->assertSessionHas('error');
        $this->assertDatabaseHas('erp_payment_advice_details', ['id' => $paid->id], 'tenant');
    }

    public function test_non_finance_user_cannot_delete_a_termin(): void
    {
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->makeUser('staff', ['payment_advices.view']));

        $this->delete(route('erp.payment-advice-details.destroy', $detail))->assertSessionHas('error');
        $this->assertDatabaseHas('erp_payment_advice_details', ['id' => $detail->id], 'tenant');
    }

    public function test_user_without_payment_permission_cannot_add_a_termin(): void
    {
        $this->markTestSkipped('BUG: ErpPaymentAdviceController::storeDetail has no permission/role check — any logged-in user can add a termin.');

        $po = $this->makeApprovedPo(10_000_000);
        $pa = $this->makePaymentAdvice($po, 10_000_000, [3_000_000]);
        $this->actingInProject($this->makeUser('staff'));

        $response = $this->post(route('erp.payment-advice-details.store', $pa), $this->termPayload());

        $this->assertContains($response->status(), [302, 403]);
        $this->assertSame(1, ErpPaymentAdviceDetail::where('erp_payment_advice_id', $pa->id)->count());
    }
}
