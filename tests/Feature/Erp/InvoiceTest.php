<?php

namespace Tests\Feature\Erp;

use App\Models\Company;
use App\Models\Erp\ErpBudgetParent;
use App\Models\Erp\ErpSubProject;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\Invoice;
use App\Models\User;
use Tests\ErpTestCase;

/**
 * Customer invoices: the amount is the sum of the lines per WID, the status follows
 * the payments received (New -> Partial Paid -> Paid).
 */
class InvoiceTest extends ErpTestCase
{
    private User $finance;
    private ErpWorkItem $wid;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finance = $this->makeUser('finance', ['invoices.view', 'invoices.create', 'invoices.update', 'invoices.delete']);
        $this->company = Company::firstOrCreate(['code' => 'MANDAU'], ['name' => 'Mandau', 'short_name' => 'MANDAU', 'is_active' => true]);
        $parent = ErpBudgetParent::create(['budget_code' => 'BP-INV', 'name' => 'Account', 'total_budget' => 1_000_000, 'remaining_budget' => 0, 'status' => 'Active']);
        $sub = ErpSubProject::create(['budget_parent_id' => $parent->id, 'sub_project_code' => 'SP-INV', 'name' => 'Sub']);
        $this->wid = ErpWorkItem::create(['sub_project_id' => $sub->id, 'wid_code' => 'WID-INV-001', 'name' => 'Tower A', 'allocated_budget' => 1_000_000, 'remaining_budget' => 1_000_000]);
    }

    private function createInvoice(array $overrides = []): Invoice
    {
        $this->actingInProject($this->finance)
            ->post(route('erp.invoices.store'), array_merge([
                'invoice_number' => 'MDU04871/IJE-MANDAU/XI/2026', 'invoice_date' => '2026-11-10', 'due_date' => '2026-12-11',
                'company_id' => $this->company->id, 'customer_name' => 'Indosat', 'top' => 'FP 100%',
                'ppn_rate' => 0, 'pph_rate' => 0,
            ], $overrides))->assertSessionHasNoErrors();

        return Invoice::latest('id')->firstOrFail();
    }

    private function addDetail(Invoice $invoice, float $amount, array $overrides = [])
    {
        return $this->post(route('erp.invoices.details.store', $invoice), array_merge([
            'work_item_id' => $this->wid->id, 'amount' => $amount, 'terms' => 'DP I', 'currency' => 'IDR',
        ], $overrides));
    }

    public function test_a_line_needs_its_work_id_terms_and_currency_and_takes_account_and_project_from_the_wid(): void
    {
        $invoice = $this->createInvoice();

        $this->addDetail($invoice, 1_000, ['work_item_id' => null, 'terms' => '', 'currency' => 'EUR'])
            ->assertSessionHasErrors(['work_item_id', 'terms', 'currency']);

        // an Account that is not the WID's own is refused
        $otherParent = ErpBudgetParent::create(['budget_code' => 'BP-OTHER', 'name' => 'Other', 'total_budget' => 1, 'remaining_budget' => 0, 'status' => 'Active']);
        $this->addDetail($invoice, 1_000, ['budget_parent_id' => $otherParent->id])->assertSessionHasErrors('work_item_id');

        $this->addDetail($invoice, 1_000, ['update_wid' => 1, 'save_new' => 1])
            ->assertSessionHas('success')->assertSessionHas('reopen_detail', true);
        $detail = $invoice->details()->sole();
        $this->assertSame($this->wid->subProject->budget_parent_id, (int) $detail->budget_parent_id);
        $this->assertSame($this->wid->sub_project_id, (int) $detail->sub_project_id);
        $this->assertSame('SP-INV', $detail->project_code);
        $this->assertTrue($detail->update_wid);
    }

    public function test_usd_lines_get_their_own_total_and_do_not_count_towards_the_idr_amount(): void
    {
        $invoice = $this->createInvoice();
        $this->addDetail($invoice, 1_000_000);
        $this->addDetail($invoice, 2_500, ['currency' => 'USD']);

        $invoice->refresh();
        $this->assertEquals(1_000_000, (float) $invoice->invoice_amount);
        $this->assertEquals(2_500, (float) $invoice->invoice_amount_usd);
        $this->get(route('erp.invoices.show', $invoice))->assertOk()->assertSee('Total USD')->assertSee('USD 2.500,00');
    }

    private function pay(Invoice $invoice, float $amount, string $date = '2026-11-20')
    {
        return $this->post(route('erp.invoices.payments.store', $invoice), ['payment_date' => $date, 'amount' => $amount, 'method' => 'Bank Transfer']);
    }

    public function test_invoice_amount_comes_from_its_lines_and_status_from_the_payments(): void
    {
        $invoice = $this->createInvoice();
        $this->assertSame('INV-00001', $invoice->code);
        $this->assertSame('MANDAU', $invoice->company_name);
        $this->assertSame('New', $invoice->status);

        $this->addDetail($invoice, 6_306_000)->assertSessionHas('success');
        $this->addDetail($invoice, 6_306_000)->assertSessionHas('success');
        $invoice->refresh();
        $this->assertEquals(12_612_000, (float) $invoice->invoice_amount);
        $this->assertSame(['INVDET-00001', 'INVDET-00002'], $invoice->details()->orderBy('id')->pluck('code')->all());

        $this->pay($invoice, 5_000_000, '2026-11-20')->assertSessionHas('success');
        $invoice->refresh();
        $this->assertSame('Partial Paid', $invoice->status);
        $this->assertEquals(7_612_000, (float) $invoice->outstanding);
        $this->assertSame('2026-11-20', $invoice->partial_payment_date->toDateString());
        $this->assertNull($invoice->final_payment_date);

        $this->pay($invoice, 7_612_000, '2026-12-01')->assertSessionHas('success');
        $invoice->refresh();
        $this->assertSame('Paid', $invoice->status);
        $this->assertEquals(0, (float) $invoice->outstanding);
        $this->assertSame('2026-12-01', $invoice->final_payment_date->toDateString());

        // deleting a payment reopens it
        $this->delete(route('erp.invoice-payments.destroy', $invoice->payments()->latest('id')->first()))->assertSessionHas('success');
        $this->assertSame('Partial Paid', $invoice->fresh()->status);
    }

    public function test_payments_cannot_exceed_what_is_billed(): void
    {
        $invoice = $this->createInvoice();
        $this->pay($invoice, 1_000)->assertSessionHasErrors('amount'); // nothing billed yet

        $this->addDetail($invoice, 1_000_000);
        $this->pay($invoice, 1_000_001)->assertSessionHasErrors('amount');
        $this->pay($invoice, 1_000_000)->assertSessionHas('success');
        $this->assertSame('Paid', $invoice->fresh()->status);
    }

    public function test_lines_and_invoices_already_paid_for_are_protected(): void
    {
        $invoice = $this->createInvoice();
        $this->addDetail($invoice, 600_000);
        $this->addDetail($invoice, 400_000);
        $this->pay($invoice, 500_000);
        [$first, $second] = $invoice->details()->orderBy('id')->get()->all();

        // dropping the 600k line would leave 400k billed against 500k paid
        $this->delete(route('erp.invoice-details.destroy', $first))->assertSessionHas('error');
        $this->delete(route('erp.invoices.destroy', $invoice))->assertSessionHas('error');
        $this->assertNotNull($invoice->fresh());

        // dropping the 400k line still covers what was paid
        $this->delete(route('erp.invoice-details.destroy', $second))->assertSessionHas('success');
        $this->assertEquals(600_000, (float) $invoice->fresh()->invoice_amount);
    }

    public function test_invoice_number_is_unique_and_due_date_not_before_invoice_date(): void
    {
        $this->createInvoice();
        $this->post(route('erp.invoices.store'), [
            'invoice_number' => 'MDU04871/IJE-MANDAU/XI/2026', 'invoice_date' => '2026-11-10', 'due_date' => '2026-11-01',
        ])->assertSessionHasErrors(['invoice_number', 'due_date']);
    }

    public function test_pages_render_and_list_filters_by_status(): void
    {
        $paid = $this->createInvoice(['invoice_number' => 'INV-PAID']);
        $this->addDetail($paid, 100);
        $this->pay($paid, 100);
        $this->createInvoice(['invoice_number' => 'INV-OPEN']);

        $this->get(route('erp.invoices.index'))->assertOk()->assertSee('INV-PAID')->assertSee('INV-OPEN');
        $this->get(route('erp.invoices.index', ['status' => 'Paid']))->assertOk()->assertSee('INV-PAID')->assertDontSee('INV-OPEN');
        $this->get(route('erp.invoices.create'))->assertOk();
        $this->get(route('erp.invoices.show', $paid))->assertOk()->assertSee('WID-INV-001')->assertSee('Tower A');
        $this->get(route('erp.invoices.edit', $paid))->assertOk();

        $this->put(route('erp.invoices.update', $paid), [
            'invoice_number' => 'INV-PAID', 'invoice_date' => '2026-11-10', 'remarks' => 'diubah', 'ppn_rate' => 0, 'pph_rate' => 0,
        ])->assertRedirect(route('erp.invoices.show', $paid));
        $this->assertSame('diubah', $paid->fresh()->remarks);
    }

    public function test_ppn_is_added_and_pph_on_the_services_part_is_withheld_by_the_customer(): void
    {
        // 100jt, PPN 11%, PPh 2%: billed 111jt, customer transfers 109jt
        $invoice = $this->createInvoice(['ppn_rate' => 11, 'pph_rate' => 2]);
        $this->addDetail($invoice, 100_000_000);
        $invoice->refresh();
        $this->assertEquals(11_000_000, (float) $invoice->ppn_amount);
        $this->assertEquals(111_000_000, (float) $invoice->total_amount);
        $this->assertEquals(2_000_000, (float) $invoice->pph_amount);
        $this->assertEquals(109_000_000, (float) $invoice->amount_due);
        $this->assertEquals(109_000_000, (float) $invoice->outstanding);

        $this->pay($invoice, 109_000_001)->assertSessionHasErrors('amount');
        $this->pay($invoice, 109_000_000)->assertSessionHas('success');
        $this->assertSame('Paid', $invoice->fresh()->status, 'the withheld PPh counts as paid');

        $this->get(route('erp.invoices.show', $invoice))->assertOk()
            ->assertSee('Nilai Invoice + PPN')->assertSee('Rp 111.000.000')->assertSee('Harus Dibayar Customer')->assertSee('Rp 109.000.000');

        // a rate change that would leave the customer owing less than paid is refused
        $this->put(route('erp.invoices.update', $invoice), [
            'invoice_number' => $invoice->invoice_number, 'invoice_date' => '2026-11-10', 'ppn_rate' => 0, 'pph_rate' => 2,
        ])->assertSessionHasErrors('ppn_rate');
        $this->assertEquals(11, (float) $invoice->fresh()->ppn_rate);
    }

    public function test_no_pph_at_zero_percent_and_defaults_come_from_the_admin_setting(): void
    {
        // materials only: PPh 0%, the customer pays DPP + PPN
        $invoice = $this->createInvoice(['ppn_rate' => 11, 'pph_rate' => 0]);
        $this->addDetail($invoice, 100_000_000);
        $this->assertEquals(0, (float) $invoice->fresh()->pph_amount);
        $this->assertEquals(111_000_000, (float) $invoice->fresh()->amount_due);

        // only an admin sets the defaults; a new invoice starts from them
        $this->get(route('erp.invoices.tax-settings'))->assertForbidden();
        $admin = $this->makeUser('superadmin');
        $this->actingInProject($admin)->put(route('erp.invoices.tax-settings.update'), ['ppn_rate' => 12, 'pph_rate' => 2.5])->assertSessionHas('success');
        $this->assertSame('12', \App\Models\AppSetting::get(\App\Models\AppSetting::INVOICE_PPN_RATE));
        $this->actingInProject($this->finance)->get(route('erp.invoices.create'))->assertOk()
            ->assertSee('name="ppn_rate" class="form-control" min="0" max="100" step="0.01" required value="12"', false);
    }

    public function test_access_follows_the_invoice_permissions(): void
    {
        $invoice = $this->createInvoice();

        $this->actingInProject($this->makeUser('staff'));
        $this->get(route('erp.invoices.index'))->assertForbidden();
        $this->get(route('erp.invoices.show', $invoice))->assertForbidden();

        $this->actingInProject($this->makeUser('ceo', ['invoices.view']));
        $this->get(route('erp.invoices.show', $invoice))->assertOk()->assertDontSee('Catat Pembayaran')->assertDontSee('Tambah Rincian');
        $this->post(route('erp.invoices.details.store', $invoice), ['amount' => 100])->assertForbidden();
        $this->post(route('erp.invoices.store'), ['invoice_number' => 'X', 'invoice_date' => '2026-11-10'])->assertForbidden();
    }
}
