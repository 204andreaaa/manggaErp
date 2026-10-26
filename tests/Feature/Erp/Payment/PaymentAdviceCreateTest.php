<?php

namespace Tests\Feature\Erp\Payment;

use App\Models\Erp\ErpPaymentAdvice;
use App\Models\Erp\ErpPaymentAdviceDetail;
use Tests\ErpTestCase;

class PaymentAdviceCreateTest extends ErpTestCase
{
    use PaymentFixtures;

    public function test_finance_opens_the_index_and_create_pages_for_a_po(): void
    {
        $po = $this->makeApprovedPo();
        $this->actingInProject($this->finance());

        $this->get(route('erp.payment-advices.index'))->assertOk();
        $this->get(route('erp.payment-advices.create', ['po_id' => $po->id]))
            ->assertOk()
            ->assertSee($po->po_no);
    }

    public function test_creating_a_pa_for_an_approved_po_makes_the_header_and_first_termin(): void
    {
        $po = $this->makeApprovedPo(10_000_000);
        $finance = $this->finance();
        $this->actingInProject($finance);

        $response = $this->post(route('erp.payment-advices.store'), $this->paPayload($po));

        $pa = ErpPaymentAdvice::where('erp_purchase_order_id', $po->id)->sole();
        $response->assertRedirect(route('erp.payment-advices.show', $pa))->assertSessionHas('success');

        $this->assertMatchesRegularExpression('/^SI\d{4}-\d{2}-\d{5}$/', $pa->supplier_invoice_no);
        $this->assertSame($po->supplier_id, $pa->supplier_id);
        $this->assertSame('INV-VENDOR-001', $pa->invoice_no);
        $this->assertSame('Siti Finance', $pa->contact_person);
        $this->assertSame('Draft', $pa->status);
        $this->assertSame('Draft', $pa->approval_status);
        $this->assertFalse($pa->payment_closed);
        $this->assertSame($finance->id, (int) $pa->owner_id);
        $this->assertEquals(10_000_000, $pa->total_invoice_amount_with_tax);
        // Draft termins don't count as paid yet.
        $this->assertEquals(0, $pa->sum_payment_amount_with_tax);
        $this->assertEquals(10_000_000, $pa->outstanding);

        $detail = $pa->details()->sole();
        $this->assertMatchesRegularExpression('/^SID-\d{4}-\d{2}-\d{5}$/', $detail->supplier_detail_no);
        $this->assertSame($po->id, (int) $detail->erp_purchase_order_id);
        $this->assertEquals(3_000_000, $detail->payment_amount_with_tax);
        $this->assertSame('DP 1', $detail->payment_type);
        $this->assertSame('Bank Transfer', $detail->payment_method);
        $this->assertSame('Draft', $detail->approval_status);
    }

    public function test_contact_person_and_due_date_default_from_the_po(): void
    {
        $po = $this->makeApprovedPo();
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advices.store'), $this->paPayload($po, ['contact_person' => null, 'due_date' => null]))
            ->assertSessionHas('success');

        $pa = ErpPaymentAdvice::where('erp_purchase_order_id', $po->id)->sole();
        $this->assertSame('Budi Supplier', $pa->contact_person);
        $this->assertSame(now()->addDays(30)->toDateString(), $pa->due_date->toDateString());
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advices.store'), [])
            ->assertSessionHasErrors(['erp_purchase_order_id', 'invoice_no', 'total_invoice_amount', 'initial_payment_amount', 'payment_method', 'payment_type']);

        $this->post(route('erp.payment-advices.store'), $this->paPayload($this->makeApprovedPo(), ['erp_purchase_order_id' => 999999, 'total_invoice_amount' => -5]))
            ->assertSessionHasErrors(['erp_purchase_order_id', 'total_invoice_amount']);

        $this->assertSame(0, ErpPaymentAdvice::count());
        $this->assertSame(0, ErpPaymentAdviceDetail::count());
    }

    public function test_guest_cannot_create_a_payment_advice(): void
    {
        $po = $this->makeApprovedPo();

        $this->post(route('erp.payment-advices.store'), $this->paPayload($po))->assertRedirect(route('login'));
        $this->assertSame(0, ErpPaymentAdvice::count());
    }

    public function test_show_page_displays_totals_sum_and_outstanding(): void
    {
        $po = $this->makeApprovedPo(10_000_000);
        $pa = $this->makePaymentAdvice($po, 10_000_000);
        $this->makeDetail($pa, 4_000_000, ['approval_status' => 'Approved']);
        $this->makeDetail($pa, 6_000_000);
        $pa->update(['sum_payment_amount' => 4_000_000, 'sum_payment_amount_with_tax' => 4_000_000, 'outstanding' => 6_000_000]);

        $this->actingInProject($this->finance());

        $this->get(route('erp.payment-advices.show', $pa))
            ->assertOk()
            ->assertSee($pa->supplier_invoice_no)
            ->assertSee('IDR 10.000.000')
            ->assertSee('IDR 4.000.000')
            ->assertSee('IDR 6.000.000');

        $this->get(route('erp.payment-advice-details.show', $pa->details()->first()))
            ->assertOk()
            ->assertSee($pa->details()->first()->supplier_detail_no);
    }

    public function test_datatable_lists_payment_advices_with_outstanding(): void
    {
        $po = $this->makeApprovedPo(5_000_000);
        $pa = $this->makePaymentAdvice($po, 5_000_000, [5_000_000]);
        $this->actingInProject($this->finance());

        $json = $this->post(route('erp.payment-advices.datatable'), ['draw' => 1, 'start' => 0, 'length' => 10])
            ->assertOk()
            ->json();

        $this->assertSame(1, $json['recordsTotal']);
        $this->assertSame($po->po_no, $json['data'][0]['po_no']);
        $this->assertSame('IDR 5.000.000', $json['data'][0]['outstanding']);
        $this->assertStringContainsString($pa->supplier_invoice_no, $json['data'][0]['supplier_invoice_no']);
    }

    public function test_only_superadmin_can_delete_a_payment_advice(): void
    {
        $po = $this->makeApprovedPo();
        $pa = $this->makePaymentAdvice($po, 1_000_000, [1_000_000]);

        $this->actingInProject($this->finance());
        $this->delete(route('erp.payment-advices.destroy', $pa))->assertSessionHas('error');
        $this->assertDatabaseHas('erp_payment_advices', ['id' => $pa->id], 'tenant');

        $this->actingInProject($this->makeUser('superadmin'));
        $this->delete(route('erp.payment-advices.destroy', $pa))->assertRedirect(route('erp.payment-advices.index'));
        $this->assertDatabaseMissing('erp_payment_advices', ['id' => $pa->id], 'tenant');
        $this->assertSame(0, ErpPaymentAdviceDetail::where('erp_payment_advice_id', $pa->id)->count());
    }

    public function test_user_without_payment_permission_cannot_create_a_payment_advice(): void
    {

        $po = $this->makeApprovedPo();
        $this->actingInProject($this->makeUser('staff'));

        $response = $this->post(route('erp.payment-advices.store'), $this->paPayload($po));

        $this->assertContains($response->status(), [302, 403]);
        $this->assertSame(0, ErpPaymentAdvice::count());
    }

    public function test_initial_termin_cannot_exceed_the_invoice_total(): void
    {

        $po = $this->makeApprovedPo();
        $this->actingInProject($this->finance());

        $this->post(route('erp.payment-advices.store'), $this->paPayload($po, ['total_invoice_amount' => 1_000_000, 'initial_payment_amount' => 5_000_000]))
            ->assertSessionHasErrors('initial_payment_amount');

        $this->assertSame(0, ErpPaymentAdvice::count());
    }
}
