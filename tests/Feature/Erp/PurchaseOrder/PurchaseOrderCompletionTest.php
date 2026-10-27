<?php

namespace Tests\Feature\Erp\PurchaseOrder;

use App\Models\Erp\ErpGoodsReceipt;
use App\Models\Erp\ErpPaymentAdvice;
use App\Models\Erp\ErpPaymentAdviceDetail;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\User;

/** A PO is Completed once it is fully received AND fully paid, in either order. */
class PurchaseOrderCompletionTest extends PurchaseOrderTestCase
{
    private ErpPurchaseOrder $po;

    private ErpPaymentAdviceDetail $termin;

    private User $logistik;

    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();
        $rf = $this->makeApprovedRf([['name' => 'Besi Beton', 'qty' => 4, 'unit_cost' => 250_000]]);
        $this->po = $this->makeApprovedPo($rf);
        $this->logistik = $this->makeUser('logistik');
        $this->finance = $this->makeUser('finance');

        // One approved, still unpaid termin covering the whole invoice.
        $pa = ErpPaymentAdvice::create([
            'supplier_invoice_no' => 'SI-CMP-'.$this->po->id,
            'erp_purchase_order_id' => $this->po->id,
            'supplier_id' => $this->po->supplier_id,
            'invoice_no' => 'INV-CMP',
            'total_invoice_amount' => 1_000_000,
            'total_invoice_amount_with_tax' => 1_000_000,
            'sum_payment_amount' => 1_000_000,
            'sum_payment_amount_with_tax' => 1_000_000,
            'outstanding' => 0,
            'status' => 'Draft',
            'approval_status' => 'Draft',
            'payment_closed' => false,
        ]);
        $this->termin = ErpPaymentAdviceDetail::create([
            'supplier_detail_no' => 'SID-CMP-'.$this->po->id,
            'erp_payment_advice_id' => $pa->id,
            'erp_purchase_order_id' => $this->po->id,
            'payment_amount' => 1_000_000,
            'payment_amount_with_tax' => 1_000_000,
            'payment_method' => 'Bank Transfer',
            'payment_type' => '100%',
            'approval_status' => 'Approved',
            'approved_date' => now(),
        ]);
    }

    private function receiveEverything(): void
    {
        $this->actingInProject($this->logistik)
            ->post(route('erp.goods-receipts.store', $this->po), [
                'date' => now()->toDateString(),
                'supplier_do_no' => 'SJ-CMP',
                'items' => [[
                    'po_item_id' => $this->po->items[0]->id,
                    'delivered_qty' => 4,
                    'received_qty' => 4,
                ]],
            ])
            ->assertSessionHasNoErrors();
        $gr = ErpGoodsReceipt::latest('id')->firstOrFail();
        $this->post(route('erp.goods-receipts.receive', $gr))->assertSessionHas('success');
    }

    private function payTheTermin(): void
    {
        $this->actingInProject($this->finance)
            ->post(route('erp.payment-advice-details.mark-paid', $this->termin))
            ->assertSessionHas('success');
    }

    public function test_po_completes_when_goods_arrive_after_payment(): void
    {
        $this->payTheTermin();
        $this->po->refresh();
        $this->assertTrue($this->po->payment_closed);
        $this->assertSame('Approved', $this->po->status, 'paid but nothing received yet');

        $this->receiveEverything();

        $this->assertSame('Completed', $this->po->fresh()->status);
    }

    public function test_po_completes_when_payment_finishes_after_goods(): void
    {
        $this->receiveEverything();
        $this->po->refresh();
        $this->assertTrue($this->po->gr);
        $this->assertSame('Approved', $this->po->status, 'received but not paid yet');

        $this->payTheTermin();

        $this->assertSame('Completed', $this->po->fresh()->status);
    }

    public function test_approval_alone_does_not_complete_a_received_po(): void
    {
        $this->termin->update(['approval_status' => 'Draft', 'approved_date' => null]);
        $this->receiveEverything();

        $this->actingInProject($this->makeUser('superadmin'))
            ->post(route('erp.payment-advice-details.approve', $this->termin))
            ->assertSessionHas('success');

        $this->po->refresh();
        $this->assertFalse($this->po->payment_closed);
        $this->assertSame('Approved', $this->po->status);
    }
}
