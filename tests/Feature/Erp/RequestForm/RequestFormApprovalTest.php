<?php

namespace Tests\Feature\Erp\RequestForm;

use App\Models\Notification;
use App\Models\Role;
use Tests\ErpTestCase;

class RequestFormApprovalTest extends ErpTestCase
{
    use RequestFormFixtures;

    public function test_rf_without_line_items_cannot_be_submitted(): void
    {
        $this->approvalLevel(1, $this->makeUser('manager'));
        $user = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($user, 'non_project', []);

        $this->submitRf($user, $rf)->assertSessionHas('error');

        $this->assertSame('Draft', $rf->fresh()->status);
        $this->assertSame(0, $rf->approvals()->count());
    }

    public function test_submit_is_refused_when_no_approval_flow_is_configured(): void
    {
        $user = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($user);

        $this->submitRf($user, $rf)->assertSessionHas('error', 'Approval flow is not configured for this record type.');

        $this->assertSame('Draft', $rf->fresh()->status);
        $this->assertSame(0, $rf->approvals()->count());
    }

    public function test_submit_builds_the_approval_chain_and_notifies_the_first_approver(): void
    {
        $first = $this->makeUser('manager');
        $second = $this->makeUser('director');
        $this->approvalLevel(2, $second);
        $this->approvalLevel(1, $first);
        $user = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($user);

        $this->submitRf($user, $rf)
            ->assertRedirect(route('erp.request-form.show', $rf))
            ->assertSessionHas('success');

        $this->assertSame('Submitted', $rf->fresh()->status);
        $this->assertSame(
            [[1, $first->id, 'Pending'], [2, $second->id, 'Waiting']],
            $rf->approvals()->orderBy('level')->get()
                ->map(fn ($a) => [(int) $a->level, (int) $a->assigned_to_user_id, $a->status])->all()
        );
        $this->assertTrue(Notification::where('user_id', $first->id)->where('type', 'rf_submitted')->where('reference_id', $rf->id)->exists());
        $this->assertFalse(Notification::where('user_id', $second->id)->exists());

        // Submitting twice does not duplicate the chain; a submitted RF is locked for edits.
        $this->submitRf($user, $rf)->assertSessionHas('error', 'Request Form is already submitted for approval.');
        $this->assertSame(2, $rf->approvals()->count());
        $this->get(route('erp.request-form.edit', $rf))->assertForbidden();
        $this->get(route('erp.request-form.show', $rf))->assertOk();
    }

    public function test_only_configs_matching_record_type_and_amount_band_are_used(): void
    {
        $always = $this->makeUser('manager');
        $projectOnly = $this->makeUser('pm');
        $bigOnly = $this->makeUser('director');
        $this->approvalLevel(1, $always);
        $this->approvalLevel(2, $projectOnly, null, ['is_project' => 1]);
        $this->approvalLevel(3, $bigOnly, null, ['min_amount' => 1_000_000]);

        $user = $this->makeUser('staff');
        // non-project, total 50,000
        $rf = $this->createRfViaHttp($user);
        $this->submitRf($user, $rf)->assertSessionHas('success');
        $this->assertSame([$always->id], $rf->approvals()->pluck('assigned_to_user_id')->map(fn ($v) => (int) $v)->all());

        // project, total 2,000,000 → all three levels
        $wid = $this->makeWorkItem();
        $big = $this->createRfViaHttp($user, 'project', [$this->itemPayload(['wid' => $wid->wid_code, 'unit_cost' => 200000, 'qty' => 10])]);
        $this->submitRf($user, $big)->assertSessionHas('success');
        $this->assertSame([1, 2, 3], $big->approvals()->orderBy('level')->pluck('level')->map(fn ($v) => (int) $v)->all());
    }

    public function test_multi_level_approval_reaches_approved_with_user_and_role_approvers(): void
    {
        $financeRole = Role::firstOrCreate(['slug' => 'finance'], ['name' => 'Finance']);
        $level1 = $this->makeUser('manager');
        $financeMember = $this->makeUser('finance');
        $level3 = $this->makeUser('director');
        $this->approvalLevel(1, $level1);
        $this->approvalLevel(2, null, $financeRole);
        $this->approvalLevel(3, $level3);

        $requester = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($requester);
        $this->submitRf($requester, $rf)->assertSessionHas('success');

        // Level 1
        $this->approve($level1, $this->approvalAt($rf, 1), 'looks fine')->assertSessionHas('success');
        $a1 = $this->approvalAt($rf, 1);
        $this->assertSame('Approved', $a1->status);
        $this->assertSame('looks fine', $a1->comments);
        $this->assertSame($level1->id, (int) $a1->actual_approver_id);
        $this->assertNotNull($a1->approved_at);
        $this->assertFalse($a1->is_override);
        $this->assertSame('Pending', $this->approvalAt($rf, 2)->status);
        $this->assertSame('Waiting', $this->approvalAt($rf, 3)->status);
        $this->assertSame('Submitted', $rf->fresh()->status);

        // Level 2 is assigned to a role: any member of that role may approve.
        $this->approve($financeMember, $this->approvalAt($rf, 2))->assertSessionHas('success');
        $this->assertSame('Approved', $this->approvalAt($rf, 2)->status);
        $this->assertSame('Pending', $this->approvalAt($rf, 3)->status);
        $this->assertTrue(Notification::where('user_id', $level3->id)->where('type', 'rf_approval_update')->exists());

        // Level 3 (last)
        $this->approve($level3, $this->approvalAt($rf, 3))->assertSessionHas('success');
        $this->assertSame('Approved', $rf->fresh()->status);
        $this->assertSame(0, $rf->approvals()->where('status', '!=', 'Approved')->count());
        $this->assertTrue(Notification::where('user_id', $requester->id)->where('type', 'rf_approval_update')
            ->where('title', 'Request Form Disetujui')->exists());

        // An already-approved step cannot be approved again.
        $this->approve($level3, $this->approvalAt($rf, 3))->assertSessionHas('error', 'Tahap approval ini sudah diproses sebelumnya.');
    }

    public function test_a_waiting_level_cannot_be_approved_before_its_turn(): void
    {
        $level1 = $this->makeUser('manager');
        $level2 = $this->makeUser('director');
        $this->approvalLevel(1, $level1);
        $this->approvalLevel(2, $level2);
        $requester = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($requester);
        $this->submitRf($requester, $rf);

        $this->approve($level2, $this->approvalAt($rf, 2))->assertSessionHas('error');

        $this->assertSame('Waiting', $this->approvalAt($rf, 2)->status);
        $this->assertSame('Pending', $this->approvalAt($rf, 1)->status);
    }

    public function test_user_who_is_not_the_assigned_approver_is_refused(): void
    {
        $financeRole = Role::firstOrCreate(['slug' => 'finance'], ['name' => 'Finance']);
        $this->approvalLevel(1, $this->makeUser('manager'));
        $this->approvalLevel(2, null, $financeRole);
        $requester = $this->makeUser('staff');
        // Even a role with every request_forms permission is not an approver unless assigned.
        $outsider = $this->makeUser('auditor', ['request_forms.approve', 'request_forms.reject']);
        $rf = $this->createRfViaHttp($requester);
        $this->submitRf($requester, $rf);

        $this->approve($outsider, $this->approvalAt($rf, 1))
            ->assertSessionHas('error', 'Anda tidak memiliki akses untuk menyetujui tahap ini.');
        $this->reject($outsider, $this->approvalAt($rf, 1))
            ->assertSessionHas('error', 'Anda tidak memiliki akses untuk menolak tahap ini.');

        $a1 = $this->approvalAt($rf, 1);
        $this->assertSame('Pending', $a1->status);
        $this->assertNull($a1->actual_approver_id);
        $this->assertSame('Submitted', $rf->fresh()->status);
    }

    public function test_requester_cannot_approve_their_own_rf_even_when_assigned(): void
    {
        $requester = $this->makeUser('manager');
        $this->approvalLevel(1, $requester);
        $rf = $this->createRfViaHttp($requester);
        $this->submitRf($requester, $rf);

        $this->approve($requester, $this->approvalAt($rf, 1))
            ->assertSessionHas('error', 'Anda tidak dapat menyetujui Request Form yang Anda ajukan sendiri.');

        $this->assertSame('Pending', $this->approvalAt($rf, 1)->status);
        $this->assertSame('Submitted', $rf->fresh()->status);
    }

    public function test_superadmin_can_override_an_approval_step(): void
    {
        $this->approvalLevel(1, $this->makeUser('manager'));
        $requester = $this->makeUser('staff');
        $superadmin = $this->makeUser('superadmin');
        $rf = $this->createRfViaHttp($requester);
        $this->submitRf($requester, $rf);

        $this->approve($superadmin, $this->approvalAt($rf, 1))->assertSessionHas('success');

        $a1 = $this->approvalAt($rf, 1);
        $this->assertSame('Approved', $a1->status);
        $this->assertTrue($a1->is_override);
        $this->assertSame($superadmin->id, (int) $a1->actual_approver_id);
        $this->assertSame('Approved', $rf->fresh()->status);
    }

    public function test_rejection_at_a_middle_level_cancels_later_levels_and_rejects_the_rf(): void
    {
        $level1 = $this->makeUser('manager');
        $level2 = $this->makeUser('director');
        $level3 = $this->makeUser('ceo');
        $this->approvalLevel(1, $level1);
        $this->approvalLevel(2, $level2);
        $this->approvalLevel(3, $level3);
        $requester = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($requester);
        $this->submitRf($requester, $rf);
        $this->approve($level1, $this->approvalAt($rf, 1))->assertSessionHas('success');

        $this->reject($level2, $this->approvalAt($rf, 2), 'Budget too high')->assertSessionHas('success');

        $this->assertSame('Rejected', $rf->fresh()->status);
        $this->assertSame('Approved', $this->approvalAt($rf, 1)->status);
        $a2 = $this->approvalAt($rf, 2);
        $this->assertSame('Rejected', $a2->status);
        $this->assertSame('Budget too high', $a2->comments);
        $this->assertSame($level2->id, (int) $a2->actual_approver_id);
        $this->assertSame('Cancelled', $this->approvalAt($rf, 3)->status);
        $this->assertTrue(Notification::where('user_id', $requester->id)->where('type', 'rf_rejected')->where('reference_id', $rf->id)->exists());

        // The cancelled level cannot be approved any more.
        $this->approve($level3, $this->approvalAt($rf, 3))->assertSessionHas('error');
        $this->assertSame('Rejected', $rf->fresh()->status);

        // The reason is kept on the RF, since the approval rows reset on resubmit.
        $this->assertTrue($rf->notesAttachments()->where('type', 'note')->where('content', 'like', '%Budget too high%')->exists());

        // Rejected goes straight back to the owner (no superadmin unlock needed):
        // they can edit it and resubmit, which starts a fresh approval chain.
        $this->actingInProject($requester);
        $this->get(route('erp.request-form.show', $rf))->assertOk()->assertSee('Ajukan Ulang');
        $this->get(route('erp.request-form.edit', $rf))->assertOk();
        $this->submitRf($requester, $rf)->assertSessionHas('success');

        $rf->refresh();
        $this->assertSame('Submitted', $rf->status);
        $this->assertSame(3, $rf->approvals()->count());
        $this->assertSame('Pending', $this->approvalAt($rf, 1)->status);
        $this->assertSame('Waiting', $this->approvalAt($rf, 2)->status);
    }

    public function test_reject_requires_a_reason(): void
    {
        $level1 = $this->makeUser('manager');
        $this->approvalLevel(1, $level1);
        $requester = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($requester);
        $this->submitRf($requester, $rf);

        $this->reject($level1, $this->approvalAt($rf, 1), null)->assertSessionHasErrors('reason');

        $this->assertSame('Pending', $this->approvalAt($rf, 1)->status);
        $this->assertSame('Submitted', $rf->fresh()->status);
    }
}
