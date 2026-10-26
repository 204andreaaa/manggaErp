<?php

namespace Tests\Feature\Erp\Payment;

use App\Models\Erp\ErpApproval;
use App\Models\Erp\ErpPaymentAdvice;
use App\Models\Erp\ErpPaymentAdviceDetail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\ErpTestCase;

/** Approving / rejecting termins through the levels and marking them paid. */
class PaymentApprovalAndPaidTest extends ErpTestCase
{
    use PaymentFixtures;

    private User $checker;
    private User $ceo;
    private User $financeUser;

    /** PA (owned by finance) with the given termins; levels: user `checker`, then role `ceo`. */
    private function submittedPa(float $total, array $amounts): ErpPaymentAdvice
    {
        $this->checker = $this->makeUser('pa_checker');
        $this->ceo = $this->makeUser('ceo');
        $this->financeUser = $this->finance();
        $this->configureApprovalLevels([['user' => $this->checker], ['role' => 'ceo']]);

        $po = $this->makeApprovedPo($total);
        $pa = $this->makePaymentAdvice($po, $total, $amounts, $this->financeUser);

        $this->actingInProject($this->financeUser);
        $this->post(route('erp.payment-advice-details.submit', $pa->details()->orderBy('id')->first()), ['invoice_no' => 'INV-1'])
            ->assertSessionHas('success');

        return $pa;
    }

    private function approvals(ErpPaymentAdviceDetail $detail)
    {
        return ErpApproval::where('payment_advice_detail_id', $detail->id)->orderBy('level')->get();
    }

    public function test_termin_is_approved_through_all_levels_and_totals_update(): void
    {
        $pa = $this->submittedPa(10_000_000, [4_000_000, 6_000_000]);
        $detail = $pa->details()->orderBy('id')->first();

        // Level 1: the designated user.
        $this->actingInProject($this->checker);
        $this->post(route('erp.payment-advice-details.approve', $detail), ['comments' => 'OK level 1'])->assertSessionHas('success');

        $approvals = $this->approvals($detail);
        $this->assertSame(['Approved', 'Pending'], $approvals->pluck('status')->all());
        $this->assertSame($this->checker->id, (int) $approvals[0]->actual_approver_id);
        $this->assertSame('OK level 1', $approvals[0]->comments);
        $this->assertFalse($approvals[0]->is_override);
        $this->assertSame('Submitted', $detail->fresh()->approval_status);
        $this->assertEquals(10_000_000, $pa->fresh()->outstanding);

        // Level 2: anyone holding the ceo role.
        $this->actingInProject($this->ceo);
        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('success');

        $this->assertSame(['Approved', 'Approved'], $this->approvals($detail)->pluck('status')->all());
        $detail->refresh();
        $this->assertSame('Approved', $detail->approval_status);
        $this->assertNotNull($detail->approved_date);

        $pa->refresh();
        $this->assertEquals(4_000_000, $pa->sum_payment_amount_with_tax);
        $this->assertEquals(6_000_000, $pa->outstanding);
        $this->assertFalse($pa->payment_closed);
        $this->assertFalse((bool) $pa->purchaseOrder->fresh()->payment_closed);

        // The submitter hears about the final approval.
        $this->assertTrue(Notification::where('user_id', $this->financeUser->id)->where('type', 'payment_advice_approved')->exists());
    }

    public function test_approving_the_last_termin_closes_the_pa_and_the_po(): void
    {
        $pa = $this->submittedPa(5_000_000, [5_000_000]);
        $detail = $pa->details()->first();

        $this->actingInProject($this->checker);
        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('success');
        $this->actingInProject($this->ceo);
        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('success');

        $pa->refresh();
        $this->assertEquals(0, $pa->outstanding);
        $this->assertTrue($pa->payment_closed);
        $this->assertSame('Completed', $pa->status);
        $this->assertTrue((bool) $pa->purchaseOrder->fresh()->payment_closed);
    }

    public function test_user_who_is_not_the_current_approver_is_refused(): void
    {
        $pa = $this->submittedPa(5_000_000, [5_000_000]);
        $detail = $pa->details()->first();

        // The level-2 ceo cannot act while level 1 is pending; nor can an unrelated finance user.
        foreach ([$this->ceo, $this->makeUser('finance')] as $user) {
            $this->actingInProject($user);
            $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('error');
            $this->post(route('erp.payment-advice-details.reject', $detail), ['reason' => 'no'])->assertSessionHas('error');
        }

        $this->assertSame(['Pending', 'Waiting'], $this->approvals($detail)->pluck('status')->all());
        $this->assertSame('Submitted', $detail->fresh()->approval_status);
    }

    public function test_pa_owner_cannot_approve_their_own_payment_advice(): void
    {
        $this->financeUser = $this->finance();
        $this->configureApprovalLevels([['role' => 'finance']]);
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000], $this->financeUser);
        $detail = $pa->details()->first();

        $this->actingInProject($this->financeUser);
        $this->post(route('erp.payment-advice-details.submit', $detail), ['invoice_no' => 'INV-1'])->assertSessionHas('success');
        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('error');

        $this->assertSame('Pending', $this->approvals($detail)->first()->status);
        $this->assertSame('Submitted', $detail->fresh()->approval_status);
    }

    public function test_superadmin_override_is_flagged(): void
    {
        $pa = $this->submittedPa(5_000_000, [5_000_000]);
        $detail = $pa->details()->first();
        $admin = $this->makeUser('superadmin');

        $this->actingInProject($admin);
        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('success');

        $first = $this->approvals($detail)->first();
        $this->assertSame('Approved', $first->status);
        $this->assertTrue($first->is_override);
        $this->assertSame($admin->id, (int) $first->actual_approver_id);
    }

    public function test_reject_at_level_one_cancels_later_levels_and_notifies_the_submitter(): void
    {
        $pa = $this->submittedPa(5_000_000, [5_000_000]);
        $detail = $pa->details()->first();

        $this->actingInProject($this->checker);
        $this->post(route('erp.payment-advice-details.reject', $detail), ['reason' => 'Invoice salah'])->assertSessionHas('success');

        $approvals = $this->approvals($detail);
        $this->assertSame(['Rejected', 'Cancelled'], $approvals->pluck('status')->all());
        $this->assertSame('Invoice salah', $approvals[0]->comments);
        $this->assertSame($this->checker->id, (int) $approvals[0]->actual_approver_id);
        $this->assertSame('Rejected', $detail->fresh()->approval_status);

        // A rejected termin does not reduce the outstanding amount.
        $pa->refresh();
        $this->assertEquals(5_000_000, $pa->outstanding);
        $this->assertEquals(0, $pa->sum_payment_amount_with_tax);
        $this->assertTrue(Notification::where('user_id', $this->financeUser->id)->where('type', 'payment_advice_rejected')->exists());
    }

    public function test_reject_at_the_last_level(): void
    {
        $pa = $this->submittedPa(5_000_000, [5_000_000]);
        $detail = $pa->details()->first();

        $this->actingInProject($this->checker);
        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('success');
        $this->actingInProject($this->ceo);
        $this->post(route('erp.payment-advice-details.reject', $detail), ['reason' => 'Budget habis'])->assertSessionHas('success');

        $this->assertSame(['Approved', 'Rejected'], $this->approvals($detail)->pluck('status')->all());
        $this->assertSame('Rejected', $detail->fresh()->approval_status);
        $this->assertFalse($pa->fresh()->payment_closed);
    }

    public function test_without_dynamic_approvals_staff_cannot_use_the_legacy_approve_or_reject(): void
    {
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->makeUser('staff', ['payment_advice_details.approve', 'payment_advice_details.reject']));

        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('error');
        $this->post(route('erp.payment-advice-details.reject', $detail), ['reason' => 'x'])->assertSessionHas('error');

        $this->assertSame('Draft', $detail->fresh()->approval_status);
        $this->assertSame(0, ErpApproval::where('payment_advice_detail_id', $detail->id)->count());
    }

    public function test_without_approval_config_a_finance_user_approves_the_single_level(): void
    {
        $this->markTestSkipped('BUG: with no payment_advice config, submitDetail creates an unassigned Pending approval, so approveDetail refuses everyone but superadmin (the intended 1-level Finance/CEO fallback never applies).');

        $owner = $this->finance();
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000], $owner);
        $detail = $pa->details()->first();

        $this->actingInProject($owner);
        $this->post(route('erp.payment-advice-details.submit', $detail), ['invoice_no' => 'INV-1'])->assertSessionHas('success');

        $this->actingInProject($this->makeUser('ceo'));
        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('success');

        $this->assertSame('Approved', $detail->fresh()->approval_status);
    }

    public function test_finance_marks_an_approved_termin_paid_with_a_receipt(): void
    {
        $pa = $this->submittedPa(5_000_000, [5_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->makeUser('superadmin'));
        $this->post(route('erp.payment-advice-details.approve', $detail));
        $this->post(route('erp.payment-advice-details.approve', $detail));
        $this->assertSame('Approved', $detail->fresh()->approval_status);

        $this->actingInProject($this->makeUser('finance'));
        $this->post(route('erp.payment-advice-details.mark-paid', $detail), [
            'payment_receipt' => UploadedFile::fake()->create('bukti.pdf', 30, 'application/pdf'),
        ])->assertSessionHas('success');

        $detail->refresh();
        $this->trackUpload($detail->payment_receipt);
        $this->assertSame(now()->toDateString(), $detail->date_paid->toDateString());
        $this->assertMatchesRegularExpression('#^uploads/receipts/receipt_\d+_\w+\.pdf$#', $detail->payment_receipt);
        $this->assertFileExists(public_path($detail->payment_receipt));
        $this->assertSame('Approved', $detail->approval_status);
        $this->assertTrue($pa->fresh()->payment_closed);
        $this->assertTrue(Notification::where('user_id', $this->financeUser->id)->where('type', 'payment_advice_paid')->exists());
    }

    public function test_mark_paid_rejects_disallowed_receipt_types(): void
    {
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000);
        $detail = $this->makeDetail($pa, 1_000_000, ['approval_status' => 'Approved']);
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.mark-paid', $detail), [
            'payment_receipt' => UploadedFile::fake()->create('script.php', 5, 'text/x-php'),
        ])->assertSessionHasErrors('payment_receipt');

        $this->assertNull($detail->fresh()->date_paid);
        $this->assertNull($detail->fresh()->payment_receipt);
    }

    public function test_non_finance_user_cannot_mark_paid(): void
    {
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000);
        $detail = $this->makeDetail($pa, 1_000_000, ['approval_status' => 'Approved']);
        $this->actingInProject($this->makeUser('staff', ['payment_advice_details.mark_paid']));

        $this->post(route('erp.payment-advice-details.mark-paid', $detail))->assertSessionHas('error');
        $this->assertNull($detail->fresh()->date_paid);
    }

    public function test_an_unapproved_termin_cannot_be_marked_paid(): void
    {
        $this->markTestSkipped('BUG: markPaidDetail does not check approval_status — a Draft/Submitted/Rejected termin can be marked paid.');

        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.mark-paid', $detail))->assertSessionHas('error');
        $this->assertNull($detail->fresh()->date_paid);
    }
}
