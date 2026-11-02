<?php

namespace Tests\Feature\Erp\PurchaseOrder;

/** RFs and POs listed on a WID's detail page, charged the way PO approval cuts the budget. */
class WorkItemUsageTest extends PurchaseOrderTestCase
{
    public function test_wid_page_lists_rfs_and_pos_with_the_amount_charged_to_it(): void
    {
        $wid = $this->makeWorkItem(10_000_000, null, 'WID-001');
        $this->makeWorkItem(10_000_000, null, 'WID-002');
        $other = \App\Models\Erp\ErpWorkItem::where('wid_code', 'WID-002')->first();

        // one PO split over two WIDs: only the WID-001 line counts here
        $split = $this->makeApprovedRf([
            ['name' => 'Kabel', 'qty' => 2, 'unit_cost' => 100_000, 'wid' => 'WID-001'],
            ['name' => 'Tiang', 'qty' => 1, 'unit_cost' => 50_000, 'wid' => 'WID-002'],
        ]);
        $splitPo = $this->makeApprovedPo($split);

        // no WID on the lines: falls back to the RF's project code
        $byCode = $this->makeApprovedRf([['name' => 'Semen', 'qty' => 1, 'unit_cost' => 30_000]], ['project_code' => 'WID-001']);
        $byCodePo = $this->makeApprovedPo($byCode);

        // the RF's WID wins over its project code, so this one belongs to WID-002
        $elsewhere = $this->makeApprovedRf([['name' => 'Pasir', 'qty' => 1, 'unit_cost' => 70_000]], ['project_code' => 'WID-001', 'work_item_id' => $other->id]);
        $elsewherePo = $this->makeApprovedPo($elsewhere);

        $this->actingInProject($this->makeUser('superadmin'))
            ->get(route('erp.work-items.show', $wid))
            ->assertOk()
            ->assertSee($split->rf_no)
            ->assertSee($splitPo->po_no)
            ->assertSee('IDR 200.000')
            ->assertSee($byCode->rf_no)
            ->assertSee($byCodePo->po_no)
            ->assertSee('IDR 30.000')
            ->assertDontSee($elsewherePo->po_no);

        $this->get(route('erp.work-items.show', $other))
            ->assertOk()
            ->assertSee($splitPo->po_no)
            ->assertSee('IDR 50.000')
            ->assertSee($elsewherePo->po_no)
            ->assertDontSee($byCodePo->po_no);
    }

    public function test_by_project_lookup_still_answers_next_to_the_detail_route(): void
    {
        $this->makeWorkItem(1_000_000, null, 'WID-LOOKUP');

        $this->actingInProject($this->makeUser('superadmin'))
            ->getJson(route('erp.work-items.by_project'))
            ->assertOk()
            ->assertJsonFragment(['text' => 'WID-LOOKUP - Work Item WID-LOOKUP (Sisa: 1,000,000.00)']);
    }
}
