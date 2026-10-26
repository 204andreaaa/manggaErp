<?php

namespace Tests\Feature\Erp\PurchaseOrder;

use App\Models\Erp\ErpNoteAttachment;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\RequestFormItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderCreateTest extends PurchaseOrderTestCase
{
    public function test_procurement_creates_a_draft_po_from_an_approved_rf(): void
    {
        $rf = $this->makeApprovedRf([
            ['name' => 'Semen', 'qty' => 10, 'unit_cost' => 50_000, 'wid' => 'WID-001'],
            ['name' => 'Pasir', 'qty' => 2, 'unit_cost' => 300_000, 'wid' => 'WID-001'],
            ['name' => 'Bata', 'qty' => 5, 'unit_cost' => 1_000, 'wid' => 'WID-001'],
        ]);
        [$semen, $pasir, $bata] = $rf->items->all();
        $buyer = $this->makeUser('procurement');

        $response = $this->actingInProject($buyer)->post(route('erp.purchase-orders.store'), $this->storePayload($rf, [], [
            ['request_form_item_id' => $semen->id, 'qty' => 10, 'unit_cost' => 50_000, 'tax' => 55_000, 'remarks' => 'urgent'],
            ['request_form_item_id' => $pasir->id, 'qty' => 2, 'unit_cost' => 300_000, 'tax' => 0, 'remarks' => null],
            // qty 0 lines are skipped and the RF item stays open
            ['request_form_item_id' => $bata->id, 'qty' => 0, 'unit_cost' => 1_000, 'tax' => 0, 'remarks' => null],
        ]));

        $po = ErpPurchaseOrder::firstOrFail();
        $response->assertRedirect(route('erp.purchase-orders.show', $po));

        $this->assertSame('Draft', $po->status);
        $this->assertSame('PO-'.now()->format('Y').'-00001', $po->po_no);
        $this->assertSame($rf->id, $po->request_form_id);
        $this->assertSame($buyer->id, $po->owner_id);
        $this->assertSame('Gudang Utama', $po->destination);
        $this->assertSame('PRJ-TEST', $po->project); // defaults to the RF project code
        $this->assertTrue($po->expense_material_equipment);
        $this->assertFalse($po->expense_office);
        $this->assertEquals(1_100_000, $po->total_po_amount);
        $this->assertEquals(55_000, $po->tax);
        $this->assertEquals(1_155_000, $po->total_po_amount_with_tax);
        $this->assertEquals(1_155_000, $po->balance_amount);

        $this->assertCount(2, $po->items);
        $this->assertEquals(555_000, $po->items->firstWhere('request_form_item_id', $semen->id)->total_cost);
        $this->assertSame('Ordered', $semen->fresh()->status);
        $this->assertSame('Ordered', $pasir->fresh()->status);
        $this->assertSame('Requested', $bata->fresh()->status);
    }

    public function test_po_numbers_run_sequentially_within_the_year(): void
    {
        $buyer = $this->makeUser('procurement');
        $first = $this->createPoViaHttp($buyer, $this->makeApprovedRf([['qty' => 1]]));
        $second = $this->createPoViaHttp($buyer, $this->makeApprovedRf([['qty' => 1]]));

        $year = now()->format('Y');
        $this->assertSame("PO-{$year}-00001", $first->po_no);
        $this->assertSame("PO-{$year}-00002", $second->po_no);
    }

    public function test_create_page_offers_only_items_that_are_not_ordered_yet(): void
    {
        $rf = $this->makeApprovedRf([['name' => 'Kabel NYM', 'qty' => 1], ['name' => 'Saklar Lampu', 'qty' => 1]]);
        $buyer = $this->makeUser('procurement');
        $this->createPoViaHttp($buyer, $rf, [], [
            ['request_form_item_id' => $rf->items[0]->id, 'qty' => 1, 'unit_cost' => 1000, 'tax' => 0, 'remarks' => null],
        ]);

        $this->get(route('erp.purchase-orders.create', $rf))
            ->assertOk()
            ->assertSee('Saklar Lampu')
            ->assertDontSee('Kabel NYM');

        // once everything is ordered the page refuses
        $this->createPoViaHttp($buyer, $rf, [], [
            ['request_form_item_id' => $rf->items[1]->id, 'qty' => 1, 'unit_cost' => 1000, 'tax' => 0, 'remarks' => null],
        ]);
        $this->from(route('erp.purchase-orders.index'))
            ->get(route('erp.purchase-orders.create', $rf))
            ->assertRedirect(route('erp.purchase-orders.index'))
            ->assertSessionHas('error');
    }

    public function test_store_validation_rejects_bad_input_and_creates_nothing(): void
    {
        $rf = $this->makeApprovedRf([['qty' => 1]]);
        $item = $rf->items[0];
        $this->actingInProject($this->makeUser('procurement'));

        $this->post(route('erp.purchase-orders.store'), $this->storePayload($rf, ['supplier_id' => null, 'date' => null, 'items' => null]))
            ->assertSessionHasErrors(['supplier_id', 'date', 'items']);

        $this->post(route('erp.purchase-orders.store'), $this->storePayload($rf, ['request_form_id' => 999999]))
            ->assertSessionHasErrors('request_form_id');

        $this->post(route('erp.purchase-orders.store'), $this->storePayload($rf, [], [
            ['request_form_item_id' => 999999, 'qty' => -1, 'unit_cost' => -5, 'tax' => -1, 'remarks' => null],
        ]))->assertSessionHasErrors(['items.0.request_form_item_id', 'items.0.qty', 'items.0.unit_cost', 'items.0.tax']);

        $this->post(route('erp.purchase-orders.store'), $this->storePayload($rf, ['erp_warehouse_id' => 999999]))
            ->assertSessionHasErrors('erp_warehouse_id');

        $this->assertSame(0, ErpPurchaseOrder::count());
        $this->assertSame('Requested', $item->fresh()->status);
    }

    public function test_users_without_po_create_permission_are_refused(): void
    {
        $rf = $this->makeApprovedRf([['qty' => 1]]);

        $this->actingInProject($this->makeUser('staff'));
        $this->get(route('erp.purchase-orders.create', $rf))->assertForbidden();
        $this->post(route('erp.purchase-orders.store'), $this->storePayload($rf))->assertForbidden();
        $this->assertSame(0, ErpPurchaseOrder::count());
        $this->assertSame('Requested', $rf->items[0]->fresh()->status);

        // the permission alone (without the procurement role) is enough
        $this->createPoViaHttp($this->makeUser('purchaser', ['purchase_orders.create']), $rf);
        $this->assertSame(1, ErpPurchaseOrder::count());
    }

    public function test_attachments_are_stored_on_the_public_disk(): void
    {
        Storage::fake('public');
        $rf = $this->makeApprovedRf([['qty' => 1]]);

        $po = $this->createPoViaHttp($this->makeUser('procurement'), $rf, [
            'attachments' => [UploadedFile::fake()->create('quotation.pdf', 50, 'application/pdf')],
        ]);

        $attachment = ErpNoteAttachment::where('notable_id', $po->id)->firstOrFail();
        $this->assertSame('quotation.pdf', $attachment->file_name);
        Storage::disk('public')->assertExists($attachment->file_path);

        // non-pdf/image files are refused
        $this->post(route('erp.purchase-orders.store'), $this->storePayload($this->makeApprovedRf([['qty' => 1]]), [
            'attachments' => [UploadedFile::fake()->create('virus.exe', 10)],
        ]))->assertSessionHasErrors('attachments.0');
    }

    public function test_store_refuses_an_rf_item_that_is_already_on_another_po(): void
    {

        $rf = $this->makeApprovedRf([['qty' => 1]]);
        $buyer = $this->makeUser('procurement');
        $this->createPoViaHttp($buyer, $rf);

        $this->post(route('erp.purchase-orders.store'), $this->storePayload($rf));

        $this->assertSame(1, ErpPurchaseOrder::count());
        $this->assertSame(1, RequestFormItem::first()->purchaseOrderItems()->count());
    }
}
