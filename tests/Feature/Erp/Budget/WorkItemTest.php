<?php

namespace Tests\Feature\Erp\Budget;

use App\Models\Erp\ErpWorkItem;
use Illuminate\Support\Facades\DB;
use Tests\ErpTestCase;

class WorkItemTest extends ErpTestCase
{
    use BudgetFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsBudgetAdmin();
    }

    public function test_new_wid_starts_with_remaining_equal_to_allocated_and_cuts_the_parent(): void
    {
        $parent = $this->createParent(1000000);
        $wid = $this->createWid($this->createSubProject($parent), 300000);

        $this->assertSame(300000.0, (float) $wid->allocated_budget);
        $this->assertSame(300000.0, (float) $wid->remaining_budget);
        $this->assertSame(700000.0, $this->remaining($parent));
    }

    public function test_wid_code_is_generated_from_the_sub_project_when_left_empty(): void
    {
        $sub = $this->createSubProject($this->createParent(1000000), 'SP-ABC');

        $this->getJson(route('erp.work-items.next-code', ['sub_project_id' => $sub->id]))
            ->assertOk()->assertJson(['wid_code' => 'WID-ABC-001']);

        $this->postJson(route('erp.work-items.store'), [
            'sub_project_id' => $sub->id, 'wid_code' => '', 'name' => 'Auto', 'allocated_budget' => 10,
        ])->assertOk();

        $this->assertTrue(ErpWorkItem::where('wid_code', 'WID-ABC-001')->exists());
    }

    public function test_wid_allocation_must_be_more_than_zero(): void
    {
        $sub = $this->createSubProject($this->createParent(1000000));

        $this->postJson(route('erp.work-items.store'), [
            'sub_project_id' => $sub->id, 'wid_code' => 'WID-ZERO', 'name' => 'Zero', 'allocated_budget' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors('allocated_budget');

        $this->assertFalse(ErpWorkItem::where('wid_code', 'WID-ZERO')->exists());
    }

    public function test_wid_allocation_must_fit_in_the_parents_unallocated_amount(): void
    {
        $parent = $this->createParent(1000000);
        $sub = $this->createSubProject($parent);
        $this->createWid($sub, 800000);

        $response = $this->postJson(route('erp.work-items.store'), [
            'sub_project_id' => $sub->id, 'wid_code' => 'WID-OVER', 'name' => 'Over', 'allocated_budget' => 200001,
        ])->assertStatus(422);

        $this->assertStringContainsString('Alokasi Anggaran Melebihi Batas Pagu', $response->json('message'));
        $this->assertStringContainsString('Sisa Pagu Tersedia: Rp 200.000', $response->json('message'));
        $this->assertFalse(ErpWorkItem::where('wid_code', 'WID-OVER')->exists());
        $this->assertSame(200000.0, $this->remaining($parent));

        // Exactly the remainder fits.
        $this->createWid($sub, 200000);
        $this->assertSame(0.0, $this->remaining($parent));
    }

    public function test_updating_wid_allocation_adjusts_wid_remaining_and_parent(): void
    {
        $parent = $this->createParent(1000000);
        $sub = $this->createSubProject($parent);
        $wid = $this->createWid($sub, 300000);
        $this->createAnnual($wid, 100000); // WID remaining 200k

        $this->putJson(route('erp.work-items.update', $wid), [
            'sub_project_id' => $sub->id, 'wid_code' => $wid->wid_code, 'name' => $wid->name, 'allocated_budget' => 500000,
        ])->assertOk();

        $wid->refresh();
        $this->assertSame(500000.0, (float) $wid->allocated_budget);
        $this->assertSame(400000.0, (float) $wid->remaining_budget);
        $this->assertSame(500000.0, $this->remaining($parent));
    }

    public function test_updating_wid_allocation_is_refused_beyond_parent_or_below_what_is_used(): void
    {
        $parent = $this->createParent(1000000);
        $sub = $this->createSubProject($parent);
        $wid = $this->createWid($sub, 300000);
        $this->createWid($sub, 500000);
        $this->createAnnual($wid, 250000);
        $payload = ['sub_project_id' => $sub->id, 'wid_code' => $wid->wid_code, 'name' => $wid->name];

        // Other WID holds 500k: this one can go up to 500k, not 500,001.
        $this->putJson(route('erp.work-items.update', $wid), $payload + ['allocated_budget' => 500001])
            ->assertStatus(422);
        // 250k already given to an Annual plan.
        $this->putJson(route('erp.work-items.update', $wid), $payload + ['allocated_budget' => 200000])
            ->assertStatus(422)->assertJson(['message' => 'Allocated budget cannot be less than already used budget']);

        $wid->refresh();
        $this->assertSame(300000.0, (float) $wid->allocated_budget);
        $this->assertSame(50000.0, (float) $wid->remaining_budget);
        $this->assertSame(200000.0, $this->remaining($parent));
    }

    public function test_deleting_an_unused_wid_returns_its_allocation_to_the_parent(): void
    {
        $parent = $this->createParent(1000000);
        $wid = $this->createWid($this->createSubProject($parent), 350000);
        $this->assertSame(650000.0, $this->remaining($parent));

        $this->deleteJson(route('erp.work-items.destroy', $wid))
            ->assertOk()
            ->assertJson(['success' => 'Work Item deleted. Rp 350.000 kembali ke Budget Parent.']);

        $this->assertSoftDeleted($wid);
        $this->assertSame(1000000.0, $this->remaining($parent));
    }

    public function test_wid_used_by_annual_plan_and_kasbon_cannot_be_deleted(): void
    {
        $parent = $this->createParent(1000000);
        $wid = $this->createWid($this->createSubProject($parent), 300000);
        $annual = $this->createAnnual($wid, 100000);
        $detail = $this->createDetail($this->createMonthly($annual, 100000), 'Office', 50000);
        $this->approvedKasbon($detail, 1000);

        $response = $this->deleteJson(route('erp.work-items.destroy', $wid))->assertStatus(422);

        $message = $response->json('message');
        $this->assertStringContainsString("WID {$wid->wid_code} tidak bisa dihapus", $message);
        $this->assertStringContainsString('1 Annual Budget Plan', $message);
        $this->assertStringContainsString('1 Advance Request (kasbon)', $message);
        $this->assertNotNull($wid->fresh());
        $this->assertSame(700000.0, $this->remaining($parent));
    }

    public function test_wid_used_by_expense_declaration_or_request_form_cannot_be_deleted(): void
    {
        $wid = $this->widWithBudget(300000);
        $annual = $this->createAnnual($wid, 100000);
        $detail = $this->createDetail($this->createMonthly($annual, 100000), 'Office', 50000);
        $db = DB::connection('tenant');
        $db->table('erp_expense_declarations')->insert([
            'code' => 'ED-TEST-1', 'record_type' => 'without_advance', 'budget_plan_detail_id' => $detail->id,
            'work_id' => $wid->id, 'request_by_id' => 1, 'request_date' => '2026-01-01', 'status' => 'Draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $db->table('request_forms')->insert([
            'rf_no' => 'RF-TEST-1', 'record_type' => 'project', 'is_project' => 1, 'work_item_id' => $wid->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $message = $this->deleteJson(route('erp.work-items.destroy', $wid))->assertStatus(422)->json('message');

        $this->assertStringContainsString('1 Expense Declaration', $message);
        $this->assertStringContainsString('1 Request Form', $message);
        $this->assertNotNull($wid->fresh());
    }
}
