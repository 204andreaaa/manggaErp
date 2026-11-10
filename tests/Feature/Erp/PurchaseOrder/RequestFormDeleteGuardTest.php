<?php

namespace Tests\Feature\Erp\PurchaseOrder;

use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\RequestForm;

/**
 * An RF that already has a PO is part of the purchase record and cannot be deleted;
 * the PO has to be cancelled first (which returns the WID budget).
 */
class RequestFormDeleteGuardTest extends PurchaseOrderTestCase
{
    public function test_an_rf_with_a_live_po_cannot_be_deleted(): void
    {
        $superadmin = $this->makeUser('superadmin');
        $wid = $this->makeWorkItem(10_000_000, 9_000_000);
        $rf = $this->makeApprovedRf([['qty' => 2, 'unit_cost' => 500_000, 'wid' => $wid->wid_code]]);

        foreach (['Draft', 'Approved', 'Completed'] as $status) {
            $po = $this->makeApprovedPo($rf, ['status' => $status]);

            $this->actingInProject($superadmin)
                ->get(route('erp.request-form.show', $rf))
                ->assertOk()->assertSee('RF tidak dapat dihapus karena sudah ada PO ('.$po->po_no.')');

            $this->from(route('erp.request-form.show', $rf))
                ->delete(route('erp.request-form.destroy', $rf))
                ->assertRedirect(route('erp.request-form.show', $rf))
                ->assertSessionHas('error');

            $this->assertNotSoftDeleted($rf);
            $this->assertModelExists($po);
            $po->items()->delete();
            $po->delete();
        }

        $this->assertSame(9_000_000.0, (float) $wid->fresh()->remaining_budget);
    }

    public function test_after_the_po_is_cancelled_the_rf_can_be_deleted_and_the_budget_is_back(): void
    {
        $superadmin = $this->makeUser('superadmin');
        // the approved PO below already took 1.000.000 from the WID
        $wid = $this->makeWorkItem(10_000_000, 9_000_000);
        $rf = $this->makeApprovedRf([['qty' => 2, 'unit_cost' => 500_000, 'wid' => $wid->wid_code]]);
        $po = $this->makeApprovedPo($rf);

        $this->actingInProject($superadmin)->post(route('erp.purchase-orders.cancel', $po))->assertSessionHas('success');
        $this->assertSame(10_000_000.0, (float) $wid->fresh()->remaining_budget);

        $this->delete(route('erp.request-form.destroy', $rf))
            ->assertRedirect(route('erp.request-form.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($rf);
        $this->assertSame(0, ErpPurchaseOrder::count());
        // deleting does not refund a second time
        $this->assertSame(10_000_000.0, (float) $wid->fresh()->remaining_budget);
        $this->assertSame(0, RequestForm::count());
    }
}
