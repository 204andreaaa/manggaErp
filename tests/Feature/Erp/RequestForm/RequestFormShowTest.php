<?php

namespace Tests\Feature\Erp\RequestForm;

use App\Models\Erp\ErpPurchaseOrder;
use Tests\ErpTestCase;

/** Actions offered on the RF detail page. */
class RequestFormShowTest extends ErpTestCase
{
    use RequestFormFixtures;

    public function test_approved_rf_with_orderable_items_shows_the_create_po_link(): void
    {
        $rf = $this->approvedRf($this->makeUser('staff'));
        $createPoUrl = route('erp.purchase-orders.create', $rf);

        $this->actingInProject($this->makeUser('procurement'))
            ->get(route('erp.request-form.show', $rf))
            ->assertOk()
            ->assertSee($createPoUrl, false)
            ->assertSee('Create PO');

        // Staff without PO rights does not get the button.
        $this->actingInProject($this->makeUser('staff'))
            ->get(route('erp.request-form.show', $rf))
            ->assertOk()
            ->assertDontSee($createPoUrl, false);
    }

    public function test_create_po_link_disappears_once_every_item_is_on_an_active_po(): void
    {
        $rf = $this->approvedRf($this->makeUser('staff'));
        $po = ErpPurchaseOrder::create(['po_no' => 'PO-RFSHOW-'.$rf->id, 'request_form_id' => $rf->id, 'status' => 'Draft']);
        foreach ($rf->items as $item) {
            $po->items()->create(['request_form_item_id' => $item->id, 'qty' => $item->qty, 'unit_cost' => 1, 'total_cost' => 1]);
        }

        $this->actingInProject($this->makeUser('procurement'))
            ->get(route('erp.request-form.show', $rf))
            ->assertOk()
            ->assertDontSee(route('erp.purchase-orders.create', $rf), false);

        // A cancelled PO frees the items again.
        $po->update(['status' => 'Cancelled']);
        $this->get(route('erp.request-form.show', $rf))
            ->assertSee(route('erp.purchase-orders.create', $rf), false);
    }
}
