<?php

namespace Tests\Feature\Erp\Payment;

use App\Models\Erp\ErpApproval;
use App\Models\Notification;
use Illuminate\Http\UploadedFile;
use Tests\ErpTestCase;

/** Vendor invoice upload and submitting a termin into the approval flow. */
class PaymentInvoiceAndSubmitTest extends ErpTestCase
{
    use PaymentFixtures;

    public function test_finance_saves_the_vendor_invoice_with_an_attachment(): void
    {
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.update-invoice', $detail), [
            'invoice_no' => 'INV-777',
            'invoice_attachment' => UploadedFile::fake()->create('invoice.pdf', 50, 'application/pdf'),
        ])->assertSessionHas('success');

        $detail->refresh();
        $this->trackUpload($detail->invoice_attachment);
        $this->assertSame('INV-777', $detail->invoice_no);
        $this->assertMatchesRegularExpression('#^uploads/invoices/inv_\d+_\w+\.pdf$#', $detail->invoice_attachment);
        $this->assertFileExists(public_path($detail->invoice_attachment));
        $this->assertSame('Draft', $detail->approval_status);
    }

    public function test_invoice_upload_rejects_disallowed_file_types(): void
    {
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.update-invoice', $detail), [
            'invoice_no' => 'INV-777',
            'invoice_attachment' => UploadedFile::fake()->create('evil.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors('invoice_attachment');

        $this->assertNull($detail->fresh()->invoice_attachment);
        $this->assertNull($detail->fresh()->invoice_no);
    }

    public function test_non_finance_user_cannot_edit_the_invoice(): void
    {
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->makeUser('staff', ['payment_advice_details.update_invoice']));

        $this->post(route('erp.payment-advice-details.update-invoice', $detail), ['invoice_no' => 'INV-1'])->assertForbidden();
        $this->assertNull($detail->fresh()->invoice_no);
    }

    public function test_submit_creates_one_approval_per_configured_level_and_notifies_the_first_approver(): void
    {
        $approver1 = $this->makeUser('pa_checker');
        $approver2 = $this->makeUser('ceo');
        $this->configureApprovalLevels([['user' => $approver1], ['role' => 'ceo']]);

        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 2_000_000, [2_000_000]);
        $detail = $pa->details()->first();
        $finance = $this->finance();
        $this->actingInProject($finance);

        $this->post(route('erp.payment-advice-details.submit', $detail), [
            'invoice_no' => 'INV-900',
            'invoice_attachment' => UploadedFile::fake()->create('invoice.jpg', 20, 'image/jpeg'),
        ])->assertSessionHas('success');

        $detail->refresh();
        $this->trackUpload($detail->invoice_attachment);
        $this->assertSame('Submitted', $detail->approval_status);
        $this->assertSame('INV-900', $detail->invoice_no);
        $this->assertSame($finance->id, (int) $detail->submitted_by_id);
        $this->assertFileExists(public_path($detail->invoice_attachment));

        $approvals = ErpApproval::where('payment_advice_detail_id', $detail->id)->orderBy('level')->get();
        $this->assertSame([1, 2], $approvals->pluck('level')->map(fn ($l) => (int) $l)->all());
        $this->assertSame(['Pending', 'Waiting'], $approvals->pluck('status')->all());
        $this->assertSame($approver1->id, (int) $approvals[0]->assigned_to_user_id);
        $this->assertSame($approver2->roles()->first()->id, (int) $approvals[1]->assigned_to_role_id);

        $this->assertTrue(Notification::where('user_id', $approver1->id)->where('type', 'payment_advice_submitted')->exists());
    }

    public function test_submit_only_uses_levels_whose_amount_range_matches(): void
    {
        $small = $this->makeUser('small_approver');
        $big = $this->makeUser('big_approver');
        $this->configureApprovalLevels([
            ['user' => $small, 'max' => 5_000_000],
            ['user' => $big, 'min' => 5_000_001],
        ]);

        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.submit', $detail), ['invoice_no' => 'INV-1'])->assertSessionHas('success');

        $approvals = ErpApproval::where('payment_advice_detail_id', $detail->id)->get();
        $this->assertCount(1, $approvals);
        $this->assertSame($small->id, (int) $approvals[0]->assigned_to_user_id);
        $this->assertSame('Pending', $approvals[0]->status);
    }

    public function test_submit_requires_an_invoice_number(): void
    {
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.submit', $detail), [])->assertSessionHas('error');

        $this->assertSame('Draft', $detail->fresh()->approval_status);
        $this->assertSame(0, ErpApproval::where('payment_advice_detail_id', $detail->id)->count());
    }

    public function test_termins_must_be_submitted_in_order(): void
    {
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 2_000_000, [1_000_000, 1_000_000]);
        [$first, $second] = $pa->details()->orderBy('id')->get()->all();
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.submit', $second), ['invoice_no' => 'INV-2'])->assertSessionHas('error');
        $this->assertSame('Draft', $second->fresh()->approval_status);

        $first->update(['approval_status' => 'Approved']);
        $this->post(route('erp.payment-advice-details.submit', $second), ['invoice_no' => 'INV-2'])->assertSessionHas('success');
        $this->assertSame('Submitted', $second->fresh()->approval_status);
    }

    public function test_a_termin_cannot_be_submitted_twice(): void
    {
        $this->configureApprovalLevels([['user' => $this->makeUser('pa_checker')]]);
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advice-details.submit', $detail), ['invoice_no' => 'INV-1'])->assertSessionHas('success');
        $this->post(route('erp.payment-advice-details.submit', $detail), ['invoice_no' => 'INV-1'])->assertSessionHas('error');

        $this->assertSame(1, ErpApproval::where('payment_advice_detail_id', $detail->id)->count());
    }

    public function test_non_finance_user_cannot_submit(): void
    {
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000]);
        $detail = $pa->details()->first();
        $this->actingInProject($this->makeUser('staff', ['payment_advice_details.submit']));

        $this->post(route('erp.payment-advice-details.submit', $detail), ['invoice_no' => 'INV-1'])->assertForbidden();

        $this->assertSame('Draft', $detail->fresh()->approval_status);
        $this->assertSame(0, ErpApproval::where('payment_advice_detail_id', $detail->id)->count());
    }
}
