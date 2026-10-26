<?php

namespace Tests\Feature\Erp\PurchaseOrder;

use App\Models\Erp\ErpApprovalConfig;
use App\Models\Erp\ErpGoodsReceipt;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\ErpStock;
use App\Models\Erp\RequestForm;
use App\Models\User;

class GoodsReceiptTest extends PurchaseOrderTestCase
{
    private RequestForm $rf;

    private ErpPurchaseOrder $po;

    private User $logistik;

    protected function setUp(): void
    {
        parent::setUp();
        $this->makeProduct('PRD-SEMEN', 'Semen 50kg');
        $this->rf = $this->makeApprovedRf([
            ['name' => 'Semen 50kg', 'code' => 'PRD-SEMEN', 'qty' => 10, 'unit_cost' => 60_000],
            ['name' => 'Jasa Angkut', 'qty' => 1, 'unit_cost' => 500_000],
        ]);
        $this->po = $this->makeApprovedPo($this->rf);
        $this->logistik = $this->makeUser('logistik');
    }

    private function poItem(int $index)
    {
        return $this->po->items[$index];
    }

    /** @param array<int, array{0:int,1:float}> $lines [poItemIndex, qty] */
    private function grPayload(array $lines, array $overrides = []): array
    {
        return array_merge([
            'date' => now()->toDateString(),
            'supplier_do_no' => 'SJ-001',
            'remarks' => 'diterima',
            'items' => array_map(fn ($line) => [
                'po_item_id' => $this->poItem($line[0])->id,
                'delivered_qty' => $line[1],
                'received_qty' => $line[1],
            ], $lines),
        ], $overrides);
    }

    private function recordGr(array $lines): ErpGoodsReceipt
    {
        $this->actingInProject($this->logistik)
            ->post(route('erp.goods-receipts.store', $this->po), $this->grPayload($lines))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        return ErpGoodsReceipt::latest('id')->firstOrFail();
    }

    public function test_full_receipt_completes_the_po_items_and_adds_stock(): void
    {
        $this->actingInProject($this->logistik)->get(route('erp.goods-receipts.create', $this->po))->assertOk()->assertSee('Semen 50kg');

        $gr = $this->recordGr([[0, 10], [1, 1]]);

        $this->assertSame('Recorded', $gr->status);
        $this->assertSame('GR-'.now()->format('Y-m').'-35150', $gr->do_no);
        $this->assertSame($this->po->id, $gr->erp_purchase_order_id);
        $this->assertSame($this->supplier->id, $gr->supplier_id);
        $this->assertSame($this->warehouse->id, $gr->warehouse_id);
        $this->assertSame($this->logistik->id, $gr->owner_id);
        $this->assertEquals(11, $gr->total_received_qty);
        $this->assertCount(2, $gr->items);
        $this->assertEquals(0, $this->poItem(0)->fresh()->remaining_qty);

        $this->get(route('erp.goods-receipts.show', $gr))->assertOk()->assertSee($gr->do_no);

        $this->post(route('erp.goods-receipts.receive', $gr))->assertSessionHas('success');

        $gr->refresh();
        $this->assertSame('Received', $gr->status);
        $this->assertSame($this->logistik->id, $gr->verified_by_id);
        $this->assertNotNull($gr->status_receive_date);

        $po = $this->po->fresh();
        $this->assertTrue($po->gr);
        $this->assertSame('Approved', $po->status, 'stays Approved until payment is closed');
        foreach ($this->rf->items as $rfItem) {
            $this->assertSame('Completed', $rfItem->fresh()->status);
        }

        // only the catalogue product gets stock, per warehouse + supplier
        $stock = ErpStock::sole();
        $this->assertEquals(10, $stock->qty_on_hand);
        $this->assertSame($this->warehouse->id, $stock->erp_warehouse_id);
        $this->assertSame($this->supplier->id, $stock->erp_supplier_id);

        // a fully received PO takes no more GRs
        $this->post(route('erp.goods-receipts.store', $this->po), $this->grPayload([[0, 1]]))->assertSessionHas('error');
        $this->assertSame(1, ErpGoodsReceipt::count());

        // receiving twice is refused and does not double the stock
        $this->post(route('erp.goods-receipts.receive', $gr))->assertSessionHas('error');
        $this->assertEquals(10, ErpStock::sole()->qty_on_hand);

        $this->get(route('erp.goods-receipts.print', $gr))->assertOk();
    }

    public function test_partial_receipts_keep_the_po_open_until_everything_arrived(): void
    {
        $first = $this->recordGr([[0, 4], [1, 1]]);
        $this->post(route('erp.goods-receipts.receive', $first));

        $po = $this->po->fresh();
        $this->assertFalse($po->gr);
        $this->assertSame('Approved', $po->status);
        $this->assertEquals(6, $this->poItem(0)->fresh()->remaining_qty);
        $this->assertSame('Ordered', $this->rf->items[0]->fresh()->status);
        $this->assertSame('Completed', $this->rf->items[1]->fresh()->status);
        $this->assertEquals(4, ErpStock::sole()->qty_on_hand);

        $second = $this->recordGr([[0, 6]]);
        $this->assertSame('GR-'.now()->format('Y-m').'-35151', $second->do_no);
        $this->post(route('erp.goods-receipts.receive', $second));

        $this->assertTrue($this->po->fresh()->gr);
        $this->assertSame('Completed', $this->rf->items[0]->fresh()->status);
        $this->assertEquals(10, ErpStock::sole()->qty_on_hand);
    }

    public function test_over_receipt_is_refused(): void
    {
        $this->actingInProject($this->logistik)
            ->post(route('erp.goods-receipts.store', $this->po), $this->grPayload([[0, 11]]))
            ->assertSessionHas('error');
        $this->assertSame(0, ErpGoodsReceipt::count());

        // a recorded (not yet received) GR already uses up the remaining qty
        $this->recordGr([[0, 7]]);
        $this->post(route('erp.goods-receipts.store', $this->po), $this->grPayload([[0, 4]]))->assertSessionHas('error');
        $this->assertSame(1, ErpGoodsReceipt::count());
        $this->assertEquals(3, $this->poItem(0)->fresh()->remaining_qty);
    }

    public function test_gr_validation_and_po_status_checks(): void
    {
        $this->actingInProject($this->logistik)
            ->post(route('erp.goods-receipts.store', $this->po), ['items' => [['po_item_id' => 999999, 'delivered_qty' => -1, 'received_qty' => null]]])
            ->assertSessionHasErrors(['date', 'items.0.po_item_id', 'items.0.delivered_qty', 'items.0.received_qty']);

        foreach (['Draft', 'Submitted', 'Rejected', 'Cancelled'] as $status) {
            $this->po->update(['status' => $status]);
            $this->get(route('erp.goods-receipts.create', $this->po))->assertRedirect();
            $this->post(route('erp.goods-receipts.store', $this->po), $this->grPayload([[0, 1]]))->assertSessionHas('error');
        }
        $this->assertSame(0, ErpGoodsReceipt::count());
    }

    public function test_full_receipt_of_a_payment_closed_po_marks_it_completed(): void
    {
        $this->po->update(['payment_closed' => true]);

        $gr = $this->recordGr([[0, 10], [1, 1]]);
        $this->post(route('erp.goods-receipts.receive', $gr));

        $this->assertSame('Completed', $this->po->fresh()->status);
    }

    public function test_users_without_gr_rights_are_refused(): void
    {
        $staff = $this->makeUser('staff');
        $this->actingInProject($staff);
        $this->get(route('erp.goods-receipts.create', $this->po))->assertForbidden();
        $this->post(route('erp.goods-receipts.store', $this->po), $this->grPayload([[0, 1]]))->assertForbidden();
        $this->assertSame(0, ErpGoodsReceipt::count());

        $gr = $this->recordGr([[0, 2]]);
        $this->actingInProject($staff)->post(route('erp.goods-receipts.receive', $gr))->assertForbidden();
        $this->assertSame('Recorded', $gr->fresh()->status);

        // goods_receipts.create permission is enough to record a GR
        $clerk = $this->makeUser('clerk', ['goods_receipts.create']);
        $this->actingInProject($clerk)->post(route('erp.goods-receipts.store', $this->po), $this->grPayload([[0, 1]]))->assertSessionHas('success');
        $this->assertSame(2, ErpGoodsReceipt::count());
    }

    public function test_gr_verification_config_limits_who_can_receive(): void
    {
        $verifier = $this->makeUser('warehouse_head');
        ErpApprovalConfig::create(['record_type' => 'gr_verification', 'level' => 1, 'name' => 'Verifikasi GR', 'user_id' => $verifier->id]);
        $gr = $this->recordGr([[0, 10]]);

        // logistik may record but is not the configured verifier
        $this->post(route('erp.goods-receipts.receive', $gr))->assertForbidden();
        $this->assertSame('Recorded', $gr->fresh()->status);
        $this->assertSame(0, ErpStock::count());

        $this->actingInProject($verifier)->post(route('erp.goods-receipts.receive', $gr))->assertSessionHas('success');
        $this->assertSame('Received', $gr->fresh()->status);
        $this->assertSame($verifier->id, $gr->fresh()->verified_by_id);
        $this->assertEquals(10, ErpStock::sole()->qty_on_hand);
    }

    public function test_role_based_gr_verifier_can_receive(): void
    {

        $verifier = $this->makeUser('warehouse_head'); // role name "Warehouse Head", slug "warehouse_head"
        ErpApprovalConfig::create(['record_type' => 'gr_verification', 'level' => 1, 'name' => 'Verifikasi GR', 'role_id' => $this->role('warehouse_head')->id]);
        $gr = $this->recordGr([[0, 10]]);

        $this->actingInProject($verifier)->post(route('erp.goods-receipts.receive', $gr))->assertSessionHas('success');
        $this->assertSame('Received', $gr->fresh()->status);
    }

    public function test_receive_ignores_verified_by_id_from_the_request(): void
    {
        $gr = $this->recordGr([[0, 10]]);
        $someoneElse = $this->makeUser('warehouse_head');

        $this->actingInProject($this->logistik)
            ->post(route('erp.goods-receipts.receive', $gr), ['verified_by_id' => $someoneElse->id])
            ->assertSessionHas('success');

        $this->assertSame($this->logistik->id, $gr->fresh()->verified_by_id);
    }

    public function test_superadmin_deletes_a_received_gr_and_stock_is_reversed(): void
    {
        $gr = $this->recordGr([[0, 10], [1, 1]]);
        $this->post(route('erp.goods-receipts.receive', $gr));
        $this->assertTrue($this->po->fresh()->gr);

        $this->delete(route('erp.goods-receipts.destroy', $gr))->assertSessionHas('error');
        $this->assertNotNull($gr->fresh());

        $this->actingInProject($this->makeUser('superadmin'))
            ->delete(route('erp.goods-receipts.destroy', $gr))
            ->assertRedirect(route('erp.purchase-orders.show', $this->po));

        $this->assertNull(ErpGoodsReceipt::find($gr->id));
        $this->assertEquals(0, ErpStock::sole()->qty_on_hand);
        $po = $this->po->fresh();
        $this->assertFalse($po->gr);
        $this->assertSame('Approved', $po->status);
        $this->assertEquals(10, $this->poItem(0)->fresh()->remaining_qty);
    }
}
