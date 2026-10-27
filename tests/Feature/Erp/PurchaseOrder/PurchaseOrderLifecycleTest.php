<?php

namespace Tests\Feature\Erp\PurchaseOrder;

use App\Models\Erp\ErpApproval;
use App\Models\Erp\ErpApprovalConfig;
use App\Models\Erp\ErpGoodsReceipt;
use App\Models\Erp\ErpPaymentAdvice;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\RequestForm;
use App\Models\User;

/** Edit, cancel, unlock, delete and verification of POs. */
class PurchaseOrderLifecycleTest extends PurchaseOrderTestCase
{
    private ErpWorkItem $wid;

    private User $buyer;

    private User $admin;

    private RequestForm $rf;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wid = $this->makeWorkItem(10_000_000);
        $this->buyer = $this->makeUser('procurement');
        $this->admin = $this->makeUser('superadmin');
        $this->rf = $this->makeApprovedRf([
            ['name' => 'Pipa PVC', 'qty' => 10, 'unit_cost' => 100_000, 'wid' => 'WID-001'],
            ['name' => 'Lem Pipa', 'qty' => 5, 'unit_cost' => 20_000, 'wid' => 'WID-001'],
        ]);
    }

    /** Draft PO for both RF items, total 1,100,000. */
    private function draftPo(): ErpPurchaseOrder
    {
        return $this->createPoViaHttp($this->buyer, $this->rf);
    }

    /** PO approved through the routes (single level approved by superadmin). */
    private function approvedPo(): ErpPurchaseOrder
    {
        $po = $this->draftPo();
        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po));
        $this->actingInProject($this->admin)->post(route('erp.purchase-orders.approve', $po))->assertSessionHas('success');
        $this->assertSame('Approved', $po->fresh()->status);

        return $po->fresh();
    }

    private function updatePayload(array $items, array $overrides = []): array
    {
        return array_merge([
            'supplier_id' => $this->supplier->id,
            'erp_warehouse_id' => $this->warehouse->id,
            'date' => now()->toDateString(),
            'payment_method' => 'Cash',
            'items' => $items,
        ], $overrides);
    }

    public function test_draft_po_can_be_edited_and_totals_are_recalculated(): void
    {
        $po = $this->draftPo();
        [$pipa] = $this->rf->items->all();

        $this->actingInProject($this->buyer)->get(route('erp.purchase-orders.edit', $po))->assertOk();

        $this->put(route('erp.purchase-orders.update', $po), $this->updatePayload([
            ['request_form_item_id' => $pipa->id, 'qty' => 8, 'unit_cost' => 90_000, 'tax' => 10_000, 'remarks' => 'nego'],
        ], ['description' => 'revisi harga']))->assertRedirect(route('erp.purchase-orders.show', $po));

        $po->refresh();
        $this->assertSame('Draft', $po->status);
        $this->assertSame('revisi harga', $po->description);
        $this->assertSame('Cash', $po->payment_method);
        $this->assertCount(1, $po->items);
        $this->assertEquals(720_000, $po->total_po_amount);
        $this->assertEquals(10_000, $po->tax);
        $this->assertEquals(730_000, $po->total_po_amount_with_tax);
        $this->assertEquals(730_000, $po->items[0]->total_cost);

        $this->put(route('erp.purchase-orders.update', $po), $this->updatePayload([]))->assertSessionHasErrors('items');
        $this->assertCount(1, $po->fresh()->items);
    }

    public function test_submitted_po_cannot_be_edited_and_staff_cannot_edit_at_all(): void
    {
        $po = $this->draftPo();
        $original = $po->total_po_amount_with_tax;

        $this->actingInProject($this->makeUser('staff'));
        $this->get(route('erp.purchase-orders.edit', $po))->assertForbidden();
        $this->put(route('erp.purchase-orders.update', $po), $this->updatePayload([
            ['request_form_item_id' => $this->rf->items[0]->id, 'qty' => 1, 'unit_cost' => 1, 'tax' => 0],
        ]))->assertForbidden();

        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po));
        $this->get(route('erp.purchase-orders.edit', $po))->assertRedirect(route('erp.purchase-orders.show', $po));
        $this->put(route('erp.purchase-orders.update', $po), $this->updatePayload([
            ['request_form_item_id' => $this->rf->items[0]->id, 'qty' => 1, 'unit_cost' => 1, 'tax' => 0],
        ]))->assertSessionHas('error');

        $po->refresh();
        $this->assertSame('Submitted', $po->status);
        $this->assertEquals($original, $po->total_po_amount_with_tax);
        $this->assertCount(2, $po->items);
    }

    public function test_cancelling_an_approved_po_refunds_the_wid_budget(): void
    {
        $po = $this->approvedPo();
        $this->assertEquals(8_900_000, $this->wid->fresh()->remaining_budget);

        $this->actingInProject($this->admin)->post(route('erp.purchase-orders.cancel', $po))->assertSessionHas('success');

        $po->refresh();
        $this->assertSame('Cancelled', $po->status);
        $this->assertNotNull($po->rejected_date);
        $this->assertEquals(10_000_000, $this->wid->fresh()->remaining_budget);
        foreach ($this->rf->items as $item) {
            $this->assertNotSame('Ordered', $item->fresh()->status, 'RF items are released on cancel');
        }

        // cancelling twice does not refund twice
        $this->wid->update(['remaining_budget' => 5_000_000]);
        $this->post(route('erp.purchase-orders.cancel', $po))->assertSessionHas('error');
        $this->assertEquals(5_000_000, $this->wid->fresh()->remaining_budget);
    }

    public function test_refund_never_exceeds_the_allocated_budget(): void
    {
        $po = $this->approvedPo();
        $this->wid->update(['remaining_budget' => 9_500_000]);

        $this->actingInProject($this->admin)->post(route('erp.purchase-orders.cancel', $po));

        $this->assertEquals(10_000_000, $this->wid->fresh()->remaining_budget);
    }

    public function test_cancelling_a_submitted_po_cancels_open_steps_without_touching_budget(): void
    {
        $this->poLevel(1, $this->makeUser('manager'));
        $this->poLevel(2, $this->makeUser('ceo'));
        $po = $this->draftPo();
        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po));

        $this->actingInProject($this->admin)->post(route('erp.purchase-orders.cancel', $po));

        $this->assertSame('Cancelled', $po->fresh()->status);
        $this->assertSame(0, $po->approvals()->whereIn('status', ['Pending', 'Waiting'])->count());
        $this->assertSame(2, $po->approvals()->where('status', 'Cancelled')->count());
        $this->assertEquals(10_000_000, $this->wid->fresh()->remaining_budget);
    }

    public function test_only_superadmin_can_cancel(): void
    {
        $po = $this->approvedPo();

        foreach (['procurement', 'admin', 'ceo'] as $role) {
            $this->actingInProject($this->makeUser($role))->post(route('erp.purchase-orders.cancel', $po))->assertSessionHas('error');
        }

        $this->assertSame('Approved', $po->fresh()->status);
        $this->assertEquals(8_900_000, $this->wid->fresh()->remaining_budget);
    }

    public function test_cancel_releases_rf_items_back_to_requested(): void
    {

        $po = $this->approvedPo();
        $this->actingInProject($this->admin)->post(route('erp.purchase-orders.cancel', $po));

        foreach ($this->rf->items as $item) {
            $this->assertSame('Requested', $item->fresh()->status);
        }
    }

    public function test_items_of_a_cancelled_po_can_be_ordered_again(): void
    {

        $po = $this->approvedPo();
        $this->actingInProject($this->admin)->post(route('erp.purchase-orders.cancel', $po));

        $this->actingInProject($this->buyer)
            ->get(route('erp.purchase-orders.create', $this->rf))
            ->assertOk()
            ->assertSee('Pipa PVC');
    }

    public function test_unlock_returns_an_approved_po_to_draft_and_refunds_the_budget(): void
    {
        $po = $this->approvedPo();
        $this->assertSame(1, ErpPaymentAdvice::where('erp_purchase_order_id', $po->id)->count());

        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.unlock', $po))->assertSessionHas('error');
        $this->assertSame('Approved', $po->fresh()->status);

        $this->actingInProject($this->admin)->post(route('erp.purchase-orders.unlock', $po))->assertSessionHas('success');

        $po->refresh();
        $this->assertSame('Draft', $po->status);
        $this->assertNull($po->approved_date);
        $this->assertSame(0, $po->approvals()->count());
        $this->assertSame(0, ErpPaymentAdvice::where('erp_purchase_order_id', $po->id)->count());
        $this->assertEquals(10_000_000, $this->wid->fresh()->remaining_budget);
    }

    public function test_unlock_is_refused_once_goods_were_received(): void
    {
        $po = $this->approvedPo();
        ErpGoodsReceipt::create(['do_no' => 'GR-X', 'erp_purchase_order_id' => $po->id, 'status' => 'Received']);

        $this->actingInProject($this->admin)->post(route('erp.purchase-orders.unlock', $po))->assertSessionHas('error');

        $this->assertSame('Approved', $po->fresh()->status);
        $this->assertEquals(8_900_000, $this->wid->fresh()->remaining_budget);
    }

    public function test_superadmin_deletes_a_po_and_its_rf_items_are_requested_again(): void
    {
        $po = $this->draftPo();

        $this->actingInProject($this->buyer)->delete(route('erp.purchase-orders.destroy', $po))->assertSessionHas('error');
        $this->assertNotNull($po->fresh());

        $this->actingInProject($this->admin)->delete(route('erp.purchase-orders.destroy', $po))
            ->assertRedirect(route('erp.procurement.dashboard'));

        $this->assertNull(ErpPurchaseOrder::find($po->id));
        $this->assertSame(0, ErpApproval::where('purchase_order_id', $po->id)->count());
        foreach ($this->rf->items as $item) {
            $this->assertSame('Requested', $item->fresh()->status);
        }
    }

    public function test_po_verification_config_blocks_submit_until_the_verifier_verifies(): void
    {
        $verifier = $this->makeUser('head_procurement');
        ErpApprovalConfig::create(['record_type' => 'po_verification', 'level' => 1, 'name' => 'Verifikasi PO', 'user_id' => $verifier->id]);
        $po = $this->draftPo();

        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po))->assertSessionHas('error');
        $this->assertSame('Draft', $po->fresh()->status);

        // procurement is not the configured verifier
        $this->post(route('erp.purchase-orders.verify', $po))->assertSessionHas('error');
        $this->assertNull($po->fresh()->verified_by_id);

        $this->actingInProject($verifier)->post(route('erp.purchase-orders.verify', $po))->assertSessionHas('success');
        $po->refresh();
        $this->assertSame($verifier->id, $po->verified_by_id);
        $this->assertNotNull($po->verification_timestamp);

        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po))->assertSessionHas('success');
        $this->assertSame('Submitted', $po->fresh()->status);
    }

    public function test_editing_a_verified_po_clears_the_verification(): void
    {
        $po = $this->draftPo();
        $this->actingInProject($this->admin)->post(route('erp.purchase-orders.verify', $po));
        $this->assertSame($this->admin->id, $po->fresh()->verified_by_id);

        $this->actingInProject($this->buyer)->put(route('erp.purchase-orders.update', $po), $this->updatePayload([
            ['request_form_item_id' => $this->rf->items[0]->id, 'qty' => 1, 'unit_cost' => 1000, 'tax' => 0],
        ]));

        $this->assertNull($po->fresh()->verified_by_id);
        $this->assertNull($po->fresh()->verification_timestamp);
    }

    public function test_without_a_verification_config_procurement_can_verify_but_staff_cannot(): void
    {
        $po = $this->draftPo();

        $this->actingInProject($this->makeUser('staff'))->post(route('erp.purchase-orders.verify', $po))->assertSessionHas('error');
        $this->assertNull($po->fresh()->verified_by_id);

        $checker = $this->makeUser('procurement');
        $this->actingInProject($checker)->post(route('erp.purchase-orders.verify', $po))->assertSessionHas('success');
        $this->assertSame($checker->id, $po->fresh()->verified_by_id);
    }

    public function test_role_based_po_verifier_can_verify(): void
    {

        $verifier = $this->makeUser('head_procurement'); // role name "Head Procurement", slug "head_procurement"
        ErpApprovalConfig::create(['record_type' => 'po_verification', 'level' => 1, 'name' => 'Verifikasi PO', 'role_id' => $this->role('head_procurement')->id]);
        $po = $this->draftPo();

        $this->actingInProject($verifier)->post(route('erp.purchase-orders.verify', $po))->assertSessionHas('success');
        $this->assertSame($verifier->id, $po->fresh()->verified_by_id);
    }

    public function test_cancel_is_refused_once_a_goods_receipt_exists(): void
    {
        $po = $this->approvedPo();
        $this->actingInProject($this->makeUser('logistik'))
            ->post(route('erp.goods-receipts.store', $po), [
                'date' => now()->toDateString(),
                'supplier_do_no' => 'SJ-1',
                'items' => [['po_item_id' => $po->items[0]->id, 'delivered_qty' => 1, 'received_qty' => 1]],
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, ErpGoodsReceipt::where('erp_purchase_order_id', $po->id)->count());

        $this->actingInProject($this->admin)
            ->post(route('erp.purchase-orders.cancel', $po))
            ->assertSessionHas('error', 'PO tidak dapat di-cancel karena sudah ada Goods Receipt (penerimaan barang).');

        $this->assertSame('Approved', $po->fresh()->status);
        $this->assertEquals(8_900_000, $this->wid->fresh()->remaining_budget);
        $this->get(route('erp.purchase-orders.show', $po))->assertOk()->assertSee('sudah ada Goods Receipt', false);
    }

    public function test_cancel_is_refused_once_a_termin_was_submitted(): void
    {
        $po = $this->approvedPo();
        $pa = ErpPaymentAdvice::where('erp_purchase_order_id', $po->id)->firstOrFail();
        $pa->details()->orderBy('id')->first()->update(['approval_status' => 'Submitted']);

        $this->actingInProject($this->admin)
            ->post(route('erp.purchase-orders.cancel', $po))
            ->assertSessionHas('error');

        $this->assertSame('Approved', $po->fresh()->status);
        $this->assertNotSame('Cancelled', $pa->fresh()->status);
        $this->assertEquals(8_900_000, $this->wid->fresh()->remaining_budget);
    }

    public function test_cancel_is_refused_for_a_draft_termin_that_has_approval_history(): void
    {
        $po = $this->approvedPo();
        $detail = ErpPaymentAdvice::where('erp_purchase_order_id', $po->id)->firstOrFail()->details()->first();
        ErpApproval::create(['payment_advice_detail_id' => $detail->id, 'level' => 1, 'round' => 1, 'status' => 'Rejected']);

        $this->actingInProject($this->admin)->post(route('erp.purchase-orders.cancel', $po))->assertSessionHas('error');

        $this->assertSame('Approved', $po->fresh()->status);
    }

    public function test_cancel_with_an_untouched_payment_advice_cancels_it_too(): void
    {
        $po = $this->approvedPo();
        $pa = ErpPaymentAdvice::where('erp_purchase_order_id', $po->id)->firstOrFail();
        $this->assertGreaterThan(0, $pa->details()->count());

        $this->actingInProject($this->admin)->post(route('erp.purchase-orders.cancel', $po))->assertSessionHas('success');

        $this->assertSame('Cancelled', $po->fresh()->status);
        $this->assertSame('Cancelled', $pa->fresh()->status);
        $this->assertSame(0, $pa->details()->where('approval_status', '!=', 'Cancelled')->count());
        $this->assertEquals(10_000_000, $this->wid->fresh()->remaining_budget);
        foreach ($this->rf->items as $item) {
            $this->assertSame('Requested', $item->fresh()->status);
        }
    }

    public function test_print_shows_the_real_uom_of_the_product(): void
    {
        $uom = \App\Models\Erp\Uom::create(['uom_name' => 'BATANG']);
        $this->makeProduct('PRD-PIPA', 'Pipa PVC')->update(['uom_id' => $uom->id]);
        $this->rf->items()->where('product_name', 'Pipa PVC')->update(['product_id_text' => 'PRD-PIPA']);
        $po = $this->approvedPo();

        $this->actingInProject($this->buyer)
            ->get(route('erp.purchase-orders.print', $po))
            ->assertOk()
            ->assertSee('BATANG');
    }

    public function test_index_show_and_print_pages_render(): void
    {
        $po = $this->approvedPo();

        $this->actingInProject($this->buyer);
        $this->get(route('erp.purchase-orders.index'))->assertOk()->assertSee($po->po_no);
        $this->get(route('erp.purchase-orders.show', $po))->assertOk()->assertSee($po->po_no)->assertSee('Pipa PVC');
        $this->get(route('erp.purchase-orders.print', $po))->assertOk()->assertSee('Pipa PVC');
    }
}
