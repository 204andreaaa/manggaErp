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

    public function test_approving_the_last_termin_does_not_close_the_pa_until_it_is_paid(): void
    {
        $pa = $this->submittedPa(5_000_000, [5_000_000]);
        $detail = $pa->details()->first();

        $this->actingInProject($this->checker);
        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('success');
        $this->actingInProject($this->ceo);
        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('success');

        // Approved is not "lunas": the PA and PO stay open until the money is paid.
        $pa->refresh();
        $this->assertEquals(0, $pa->outstanding);
        $this->assertFalse($pa->payment_closed);
        $this->assertNotSame('Completed', $pa->status);
        $this->assertFalse((bool) $pa->purchaseOrder->fresh()->payment_closed);

        $this->actingInProject($this->financeUser);
        $this->post(route('erp.payment-advice-details.mark-paid', $detail))->assertSessionHas('success');

        $pa->refresh();
        $this->assertTrue($pa->payment_closed);
        $this->assertSame('Completed', $pa->status);
        $this->assertTrue((bool) $pa->purchaseOrder->fresh()->payment_closed);
    }

    public function test_pa_with_two_termins_closes_only_when_both_are_paid(): void
    {
        $pa = $this->submittedPa(5_000_000, [2_000_000, 3_000_000]);
        [$first, $second] = $pa->details()->orderBy('id')->get()->all();
        $admin = $this->makeUser('superadmin');

        $this->actingInProject($admin);
        $this->post(route('erp.payment-advice-details.approve', $first));
        $this->post(route('erp.payment-advice-details.approve', $first));
        $this->post(route('erp.payment-advice-details.mark-paid', $first))->assertSessionHas('success');
        $this->assertFalse($pa->fresh()->payment_closed);

        $this->actingInProject($this->financeUser);
        $this->post(route('erp.payment-advice-details.submit', $second), ['invoice_no' => 'INV-2'])->assertSessionHas('success');
        $this->actingInProject($admin);
        $this->post(route('erp.payment-advice-details.approve', $second));
        $this->post(route('erp.payment-advice-details.approve', $second));
        $this->assertFalse($pa->fresh()->payment_closed, 'all termins approved but one unpaid');

        $this->post(route('erp.payment-advice-details.mark-paid', $second))->assertSessionHas('success');
        $this->assertTrue($pa->fresh()->payment_closed);
        $this->assertSame('Completed', $pa->fresh()->status);
    }

    public function test_pa_list_and_detail_show_the_same_status(): void
    {
        $pa = $this->submittedPa(5_000_000, [5_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->makeUser('superadmin'));
        $this->post(route('erp.payment-advice-details.approve', $detail));
        $this->post(route('erp.payment-advice-details.approve', $detail));
        $this->post(route('erp.payment-advice-details.mark-paid', $detail))->assertSessionHas('success');
        $this->assertSame('Completed', $pa->fresh()->status);

        $this->get(route('erp.payment-advices.show', $pa))->assertOk()->assertSee('Completed');

        $row = collect($this->post(route('erp.payment-advices.datatable'), ['draw' => 1, 'start' => 0, 'length' => 50])
            ->assertOk()->json('data'))
            ->first(fn ($r) => str_contains($r['supplier_invoice_no'], $pa->supplier_invoice_no));
        $this->assertNotNull($row);
        $this->assertStringContainsString('Completed', $row['approval_status']);
        $this->assertStringNotContainsString('Draft', $row['approval_status']);
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

    public function test_a_rejected_termin_is_resubmitted_as_a_new_round_from_level_one(): void
    {
        $this->checker = $this->makeUser('pa_checker');
        $this->ceo = $this->makeUser('ceo');
        $director = $this->makeUser('director');
        $this->financeUser = $this->finance();
        $this->configureApprovalLevels([['user' => $this->checker], ['role' => 'ceo'], ['user' => $director]]);
        $po = $this->makeApprovedPo(5_000_000);
        $pa = $this->makePaymentAdvice($po, 5_000_000, [5_000_000], $this->financeUser);
        $detail = $pa->details()->first();

        $this->actingInProject($this->financeUser);
        $this->post(route('erp.payment-advice-details.submit', $detail), ['invoice_no' => 'INV-1'])->assertSessionHas('success');
        $this->actingInProject($this->checker);
        $this->post(route('erp.payment-advice-details.approve', $detail), ['comments' => 'ok 1'])->assertSessionHas('success');
        $this->actingInProject($this->ceo);
        $this->post(route('erp.payment-advice-details.reject', $detail), ['reason' => 'Invoice salah'])->assertSessionHas('success');

        $round1 = $this->approvals($detail)->map->only(['id', 'level', 'round', 'status', 'comments', 'actual_approver_id'])->all();
        $this->assertSame(['Approved', 'Rejected', 'Cancelled'], array_column($round1, 'status'));
        $this->assertSame('Rejected', $detail->fresh()->approval_status);

        // The PA page offers to resubmit the rejected termin.
        $this->actingInProject($this->financeUser);
        $this->get(route('erp.payment-advices.show', $pa))->assertOk()->assertSee('Ajukan Ulang');
        $this->get(route('erp.payment-advice-details.show', $detail))->assertOk()->assertSee('Ajukan Ulang');

        $this->post(route('erp.payment-advice-details.submit', $detail), ['invoice_no' => 'INV-1b'])->assertSessionHas('success');
        $detail->refresh();
        $this->assertSame('Submitted', $detail->approval_status);
        $this->assertSame('INV-1b', $detail->invoice_no);
        $this->assertSame(2, $detail->latestApprovalRound());
        $this->assertSame(['Pending', 'Waiting', 'Waiting'], $detail->currentApprovals()->orderBy('level')->pluck('status')->all());
        $this->assertSame($round1, ErpApproval::where('payment_advice_detail_id', $detail->id)->where('round', 1)->orderBy('level')->get()
            ->map->only(['id', 'level', 'round', 'status', 'comments', 'actual_approver_id'])->all());

        // Submitting again while round 2 runs is refused.
        $this->post(route('erp.payment-advice-details.submit', $detail), ['invoice_no' => 'INV-1b'])->assertSessionHas('error');

        // Round 2 goes through all three levels.
        $this->actingInProject($this->checker);
        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('success');
        $this->actingInProject($this->ceo);
        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('success');
        $this->assertSame('Submitted', $detail->fresh()->approval_status);
        $this->actingInProject($director);
        $this->post(route('erp.payment-advice-details.approve', $detail))->assertSessionHas('success');
        $this->assertSame('Approved', $detail->fresh()->approval_status);
        $this->assertSame(['Approved', 'Rejected', 'Cancelled'], ErpApproval::where('payment_advice_detail_id', $detail->id)
            ->where('round', 1)->orderBy('level')->pluck('status')->all());

        // Both rounds are listed on the termin and on the PA, oldest first, in the order they happened.
        $this->actingInProject($this->financeUser);
        $this->get(route('erp.payment-advice-details.show', $detail))->assertOk()
            ->assertSeeInOrder(['Round 1', 'Invoice salah', 'Approval Request Submitted', 'Round 2']);
        $this->get(route('erp.payment-advices.show', $pa))->assertOk()
            ->assertSeeInOrder(['Round 1', 'Invoice salah', 'Round 2']);
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

        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.mark-paid', $detail))->assertSessionHas('error');
        $this->assertNull($detail->fresh()->date_paid);
    }
}
