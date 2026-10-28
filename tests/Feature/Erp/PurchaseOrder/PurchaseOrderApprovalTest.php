<?php

namespace Tests\Feature\Erp\PurchaseOrder;

use App\Models\Erp\ErpApproval;
use App\Models\Erp\ErpPaymentAdvice;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\ErpWorkItem;
use App\Models\Notification;
use App\Models\User;

class PurchaseOrderApprovalTest extends PurchaseOrderTestCase
{
    private ErpWorkItem $wid;

    private User $buyer;

    private User $managerA;

    private User $ceo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wid = $this->makeWorkItem(10_000_000);
        $this->buyer = $this->makeUser('procurement');
        $this->managerA = $this->makeUser('manager');
        $this->ceo = $this->makeUser('ceo');
    }

    /** Draft PO worth 2,000,000 + 200,000 tax against WID-001. */
    private function draftPo(): ErpPurchaseOrder
    {
        $rf = $this->makeApprovedRf([['qty' => 4, 'unit_cost' => 500_000, 'wid' => 'WID-001']]);

        return $this->createPoViaHttp($this->buyer, $rf, [], [
            ['request_form_item_id' => $rf->items[0]->id, 'qty' => 4, 'unit_cost' => 500_000, 'tax' => 200_000, 'remarks' => null],
        ]);
    }

    private function twoLevels(): void
    {
        $this->poLevel(1, $this->managerA);
        $this->poLevel(2, null, $this->role('ceo'));
    }

    private function approvalAt(ErpPurchaseOrder $po, int $level): ErpApproval
    {
        return $po->approvals()->where('level', $level)->latest('id')->firstOrFail();
    }

    public function test_submit_creates_the_approval_chain_from_the_configs(): void
    {
        $this->twoLevels();
        $po = $this->draftPo();

        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po))->assertSessionHas('success');

        $po->refresh();
        $this->assertSame('Submitted', $po->status);
        $this->assertNotNull($po->submitted_date);
        $this->assertSame('Approved', $this->approvalAt($po, 0)->status); // "submitted" history row
        $this->assertSame('Pending', $this->approvalAt($po, 1)->status);
        $this->assertSame($this->managerA->id, $this->approvalAt($po, 1)->assigned_to_user_id);
        $this->assertSame('Waiting', $this->approvalAt($po, 2)->status);
        $this->assertSame($this->role('ceo')->id, $this->approvalAt($po, 2)->assigned_to_role_id);

        // only Draft POs can be submitted
        $this->post(route('erp.purchase-orders.submit', $po))->assertSessionHas('error');
        $this->assertSame(3, $po->approvals()->count());
    }

    public function test_configs_outside_the_amount_range_or_project_type_are_skipped(): void
    {
        $this->poLevel(1, $this->managerA, null, ['max_amount' => 1_000_000]);   // PO is 2.2M: skipped
        $this->poLevel(2, $this->ceo, null, ['is_project' => false]);             // project RF: skipped
        $this->poLevel(3, null, $this->role('ceo'), ['min_amount' => 2_000_000, 'is_project' => true]);
        $po = $this->draftPo();

        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po));

        $this->assertSame([0, 3], $po->approvals()->orderBy('level')->pluck('level')->all());
        $this->assertSame('Pending', $this->approvalAt($po, 3)->status);
    }

    public function test_multi_level_approval_reaches_approved_and_cuts_the_wid_budget(): void
    {
        $this->twoLevels();
        $po = $this->draftPo();
        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po));

        // level 1 (user-assigned)
        $this->actingInProject($this->managerA)
            ->post(route('erp.purchase-orders.approve', $po), ['comments' => 'ok level 1'])
            ->assertSessionHas('success');

        $po->refresh();
        $this->assertSame('Submitted', $po->status);
        $level1 = $this->approvalAt($po, 1);
        $this->assertSame('Approved', $level1->status);
        $this->assertSame($this->managerA->id, $level1->actual_approver_id);
        $this->assertSame('ok level 1', $level1->comments);
        $this->assertFalse((bool) $level1->is_override);
        $this->assertSame('Pending', $this->approvalAt($po, 2)->status);
        $this->assertEquals(10_000_000, $this->wid->fresh()->remaining_budget, 'budget is only cut on final approval');
        $this->assertSame(0, ErpPaymentAdvice::count());

        // level 2 (role-assigned) => final
        $this->actingInProject($this->ceo)
            ->post(route('erp.purchase-orders.approve', $po))
            ->assertSessionHas('success');

        $po->refresh();
        $this->assertSame('Approved', $po->status);
        $this->assertNotNull($po->approved_date);
        $this->assertSame('Approved', $this->approvalAt($po, 2)->status);
        $this->assertEquals(10_000_000 - 2_200_000, $this->wid->fresh()->remaining_budget);
        $this->assertEquals(10_000_000, $this->wid->fresh()->allocated_budget);

        // a 100% payment advice is generated for the PO
        $advice = ErpPaymentAdvice::where('erp_purchase_order_id', $po->id)->sole();
        $this->assertEquals(2_200_000, $advice->outstanding);
        $this->assertSame('Draft', $advice->status);
        $this->assertSame(1, $advice->details()->count());

        $this->assertTrue(Notification::where('user_id', $this->buyer->id)->where('type', 'po_approved')->where('reference_id', $po->id)->exists());
    }

    public function test_users_who_are_not_the_current_approver_are_refused(): void
    {
        $this->twoLevels();
        $po = $this->draftPo();
        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po));

        // the level-2 CEO cannot jump the queue, an unrelated user cannot approve or reject
        foreach ([$this->ceo, $this->makeUser('staff')] as $outsider) {
            $this->actingInProject($outsider)->post(route('erp.purchase-orders.approve', $po))->assertSessionHas('error');
        }
        $this->actingInProject($this->makeUser('staff'))
            ->post(route('erp.purchase-orders.reject', $po), ['comments' => 'no'])
            ->assertSessionHas('error');

        $po->refresh();
        $this->assertSame('Submitted', $po->status);
        $this->assertSame('Pending', $this->approvalAt($po, 1)->status);
        $this->assertNull($this->approvalAt($po, 1)->actual_approver_id);
    }

    public function test_the_po_owner_cannot_approve_their_own_po(): void
    {
        $this->poLevel(1, $this->buyer);
        $po = $this->draftPo();
        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po));

        $this->post(route('erp.purchase-orders.approve', $po))->assertSessionHas('error');

        $this->assertSame('Submitted', $po->fresh()->status);
        $this->assertSame('Pending', $this->approvalAt($po, 1)->status);
    }

    public function test_superadmin_can_override_a_step_and_it_is_flagged(): void
    {
        $this->twoLevels();
        $po = $this->draftPo();
        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po));

        $admin = $this->makeUser('superadmin');
        $this->actingInProject($admin)->post(route('erp.purchase-orders.approve', $po));

        $level1 = $this->approvalAt($po, 1);
        $this->assertSame('Approved', $level1->status);
        $this->assertSame($admin->id, $level1->actual_approver_id);
        $this->assertTrue((bool) $level1->is_override);
        $this->assertSame('Pending', $this->approvalAt($po, 2)->status);
    }

    public function test_reject_needs_a_reason_and_sends_the_po_back(): void
    {
        $this->twoLevels();
        $po = $this->draftPo();
        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po));

        $this->actingInProject($this->managerA)
            ->post(route('erp.purchase-orders.reject', $po), ['comments' => ''])
            ->assertSessionHasErrors('comments');
        $this->assertSame('Submitted', $po->fresh()->status);

        $this->post(route('erp.purchase-orders.reject', $po), ['comments' => 'harga terlalu mahal'])
            ->assertSessionHas('success');

        $po->refresh();
        $this->assertSame('Rejected', $po->status);
        $this->assertNotNull($po->rejected_date);
        $this->assertNull($po->verified_by_id);
        $level1 = $this->approvalAt($po, 1);
        $this->assertSame('Rejected', $level1->status);
        $this->assertSame('harga terlalu mahal', $level1->comments);
        $this->assertSame('Cancelled', $this->approvalAt($po, 2)->status);
        $this->assertEquals(10_000_000, $this->wid->fresh()->remaining_budget);
        $this->assertTrue(Notification::where('user_id', $this->buyer->id)->where('type', 'po_rejected')->exists());
    }

    public function test_rejected_or_draft_pos_cannot_be_approved(): void
    {

        $this->twoLevels();
        $rejected = $this->draftPo();
        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $rejected));
        $this->actingInProject($this->managerA)->post(route('erp.purchase-orders.reject', $rejected), ['comments' => 'no']);
        $draft = $this->draftPo();

        $this->actingInProject($this->ceo);
        $this->post(route('erp.purchase-orders.approve', $rejected));
        $this->post(route('erp.purchase-orders.approve', $draft));

        $this->assertSame('Rejected', $rejected->fresh()->status);
        $this->assertSame('Draft', $draft->fresh()->status);
        $this->assertEquals(10_000_000, $this->wid->fresh()->remaining_budget);
    }

    public function test_only_submitted_pos_can_be_rejected(): void
    {
        $this->twoLevels();
        $draft = $this->draftPo();

        $this->actingInProject($this->ceo)
            ->post(route('erp.purchase-orders.reject', $draft), ['comments' => 'no'])
            ->assertSessionHas('error');

        $this->assertSame('Draft', $draft->fresh()->status);
    }

    public function test_resubmitting_after_rejection_starts_a_new_round_from_level_one(): void
    {
        $this->twoLevels();
        $po = $this->draftPo();
        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po));
        $this->actingInProject($this->managerA)->post(route('erp.purchase-orders.approve', $po), ['comments' => 'ok A']);
        $this->actingInProject($this->ceo)->post(route('erp.purchase-orders.reject', $po), ['comments' => 'revisi']);

        // The rejected round keeps its whole history.
        $round1 = $po->approvals()->orderBy('level')->get()->map->only(['id', 'level', 'round', 'status', 'comments', 'actual_approver_id'])->all();
        $this->assertSame([[0, 'Approved'], [1, 'Approved'], [2, 'Rejected']], array_map(fn ($a) => [(int) $a['level'], $a['status']], $round1));
        $this->assertSame([1, 1, 1], array_column($round1, 'round'));

        // owner edits the rejected PO (back to Draft) and submits again
        $rfItem = $po->items()->first()->request_form_item_id;
        $this->actingInProject($this->buyer)->put(route('erp.purchase-orders.update', $po), [
            'supplier_id' => $this->supplier->id,
            'date' => now()->toDateString(),
            'payment_method' => 'Transfer',
            'items' => [['request_form_item_id' => $rfItem, 'qty' => 4, 'unit_cost' => 450_000, 'tax' => 0]],
        ])->assertSessionHasNoErrors();
        $this->assertSame('Draft', $po->fresh()->status);
        $this->assertNull($po->fresh()->rejected_date);

        $this->post(route('erp.purchase-orders.submit', $po))->assertSessionHas('success');

        // Round 2 starts again from level 1; round 1 rows are not touched.
        $po->refresh();
        $this->assertSame('Submitted', $po->status);
        $this->assertSame(2, $po->latestApprovalRound());
        $this->assertSame(
            [[0, 'Approved'], [1, 'Pending'], [2, 'Waiting']],
            $po->currentApprovals()->orderBy('level')->get()->map(fn ($a) => [(int) $a->level, $a->status])->all()
        );
        $this->assertSame($round1, $po->approvals()->where('round', 1)->orderBy('level')->get()
            ->map->only(['id', 'level', 'round', 'status', 'comments', 'actual_approver_id'])->all());

        // Level 2 has to wait for level 1 again.
        $this->actingInProject($this->ceo)->post(route('erp.purchase-orders.approve', $po))->assertSessionHas('error');
        $this->actingInProject($this->managerA)->post(route('erp.purchase-orders.approve', $po))->assertSessionHas('success');
        $this->assertSame('Submitted', $po->fresh()->status);
        $this->actingInProject($this->ceo)->post(route('erp.purchase-orders.approve', $po))->assertSessionHas('success');
        $this->assertSame('Approved', $po->fresh()->status);
        $this->assertEquals(10_000_000 - 1_800_000, $this->wid->fresh()->remaining_budget);
        $this->assertSame(['Approved', 'Approved', 'Rejected'], $po->approvals()->where('round', 1)->orderBy('level')->pluck('status')->all());

        // The approval history shows both rounds, oldest first, in the order they happened.
        $this->actingInProject($this->buyer)->get(route('erp.purchase-orders.show', $po))->assertOk()
            ->assertSeeInOrder(['Round 1', 'revisi', 'Approval Request Submitted', 'Round 2']);
    }

    public function test_a_rejected_po_can_be_resubmitted_without_editing(): void
    {
        $this->twoLevels();
        $po = $this->draftPo();
        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po));
        $this->actingInProject($this->managerA)->post(route('erp.purchase-orders.reject', $po), ['comments' => 'cek lagi']);
        $this->assertSame('Rejected', $po->fresh()->status);

        $this->actingInProject($this->buyer)->get(route('erp.purchase-orders.show', $po))->assertOk()->assertSee('Ajukan Ulang');
        $this->post(route('erp.purchase-orders.submit', $po))->assertSessionHas('success');

        $po->refresh();
        $this->assertSame('Submitted', $po->status);
        $this->assertNull($po->rejected_date);
        $this->assertSame(['Approved', 'Rejected', 'Cancelled'], $po->approvals()->where('round', 1)->orderBy('level')->pluck('status')->all());
        $this->assertSame(['Approved', 'Pending', 'Waiting'], $po->currentApprovals()->orderBy('level')->pluck('status')->all());
    }

    public function test_without_configs_small_pos_need_procurement_and_large_pos_need_the_ceo(): void
    {
        // small PO (<= 1,000,000): procurement approves, CEO-only users are refused
        $rf = $this->makeApprovedRf([['qty' => 1, 'unit_cost' => 800_000, 'wid' => 'WID-001']]);
        $small = $this->createPoViaHttp($this->buyer, $rf);
        $this->post(route('erp.purchase-orders.submit', $small));
        $this->assertSame('Submitted', $small->fresh()->status);

        $this->actingInProject($this->makeUser('staff'))->post(route('erp.purchase-orders.approve', $small))->assertSessionHas('error');
        $this->actingInProject($this->ceo)->post(route('erp.purchase-orders.approve', $small))->assertSessionHas('error');
        $this->assertSame('Submitted', $small->fresh()->status);

        $this->actingInProject($this->makeUser('procurement'))->post(route('erp.purchase-orders.approve', $small))->assertSessionHas('success');
        $this->assertSame('Approved', $small->fresh()->status);
        $this->assertEquals(9_200_000, $this->wid->fresh()->remaining_budget);

        // large PO (> 1,000,000): procurement is refused, CEO approves
        $large = $this->draftPo();
        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $large));
        $this->actingInProject($this->makeUser('procurement'))->post(route('erp.purchase-orders.approve', $large))->assertSessionHas('error');
        $this->assertSame('Submitted', $large->fresh()->status);

        $this->actingInProject($this->ceo)->post(route('erp.purchase-orders.approve', $large))->assertSessionHas('success');
        $this->assertSame('Approved', $large->fresh()->status);
        $this->assertEquals(9_200_000 - 2_200_000, $this->wid->fresh()->remaining_budget);
    }

    public function test_over_budget_po_is_approved_and_the_wid_floors_at_zero(): void
    {
        // Current behaviour (known): over-budget POs are not refused.
        $this->wid->update(['remaining_budget' => 1_000_000]);
        $this->poLevel(1, $this->managerA);
        $po = $this->draftPo(); // 2,200,000

        $this->actingInProject($this->buyer)->post(route('erp.purchase-orders.submit', $po));
        $this->actingInProject($this->managerA)->post(route('erp.purchase-orders.approve', $po));

        $this->assertSame('Approved', $po->fresh()->status);
        $this->assertEquals(0, $this->wid->fresh()->remaining_budget);
    }

    public function test_budget_falls_back_to_the_rf_work_item_when_items_carry_no_wid(): void
    {
        $other = $this->makeWorkItem(5_000_000, null, 'WID-RF');
        $rf = $this->makeApprovedRf([['qty' => 2, 'unit_cost' => 100_000]], ['work_item_id' => $other->id]);
        $po = $this->createPoViaHttp($this->buyer, $rf);
        $this->post(route('erp.purchase-orders.submit', $po));

        $this->actingInProject($this->makeUser('superadmin'))->post(route('erp.purchase-orders.approve', $po));

        $this->assertSame('Approved', $po->fresh()->status);
        $this->assertEquals(4_800_000, $other->fresh()->remaining_budget);
        $this->assertEquals(10_000_000, $this->wid->fresh()->remaining_budget);
    }
}
