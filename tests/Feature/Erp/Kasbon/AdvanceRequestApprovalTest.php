<?php

namespace Tests\Feature\Erp\Kasbon;

use App\Models\Erp\AdvanceRequest;
use App\Models\Erp\ErpApprovalConfig;
use Tests\ErpTestCase;

/** Kasbon submit -> multi-level approval -> budget cut on final approval -> reject -> pay. */
class AdvanceRequestApprovalTest extends ErpTestCase
{
    use KasbonFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpKasbonWorld(approvalLevels: 2);
    }

    public function test_submit_creates_approval_rows_from_the_config(): void
    {
        $kasbon = $this->createKasbon('Office', 1000000);

        $this->submitKasbon($kasbon)->assertSessionHas('success');

        $kasbon->refresh();
        $this->assertSame('Submitted', $kasbon->status);
        $approvals = $kasbon->approvals()->orderBy('level')->get();
        $this->assertSame([1, 2], $approvals->pluck('level')->map(fn ($l) => (int) $l)->all());
        $this->assertSame(['Pending', 'Waiting'], $approvals->pluck('status')->all());
        $this->assertEquals(50000000, $this->remaining('Office'), 'Submitting does not cut the budget');
    }

    public function test_submit_without_approval_config_is_refused(): void
    {
        ErpApprovalConfig::where('record_type', 'advance_request')->delete();
        $kasbon = $this->createKasbon('Office', 1000000);

        $this->submitKasbon($kasbon)->assertSessionHas('error');

        $this->assertSame('Draft', $kasbon->fresh()->status);
        $this->assertSame(0, $kasbon->approvals()->count());
    }

    public function test_only_a_draft_can_be_submitted(): void
    {
        $kasbon = $this->createKasbon('Office', 1000000);
        $this->submitKasbon($kasbon)->assertSessionHas('success');

        $this->submitKasbon($kasbon)->assertSessionHas('error');
        $this->assertSame(2, $kasbon->approvals()->count());
    }

    public function test_full_approval_cuts_the_detail_through_outstanding_but_not_the_wid(): void
    {
        $kasbon = $this->createKasbon('Other Expense', 3000000);
        $this->submitKasbon($kasbon);

        // Level 1: moves on to level 2, no cut yet.
        $this->actingInProject($this->approver);
        $this->post(route('erp.advance-requests.approvals.approve', $this->pendingApproval($kasbon)), ['comments' => 'L1'])
            ->assertSessionHas('success');
        $this->assertSame('Submitted', $kasbon->fresh()->status);
        $this->assertSame(['Approved', 'Pending'], $kasbon->approvals()->orderBy('level')->pluck('status')->all());
        $this->assertEquals(10000000, $this->remaining('Other Expense'));

        // Level 2 (final): approved and the detail is cut.
        $this->actingInProject($this->approver2);
        $this->post(route('erp.advance-requests.approvals.approve', $this->pendingApproval($kasbon)), ['comments' => 'L2'])
            ->assertSessionHas('success');

        $kasbon->refresh();
        $this->assertSame('Approved', $kasbon->status);
        $this->assertEquals(3000000, (float) $kasbon->actual_amount);
        $this->assertEquals(3000000, (float) $kasbon->advance_outstanding);
        $detail = $this->detail('Other Expense');
        $this->assertEquals(7000000, (float) $detail->remaining_budget);
        $this->assertEquals(3000000, (float) $detail->actual_advance_request);
        $this->assertEquals(50000000, $this->remaining('Office'), 'Other details are untouched');
        $this->assertEquals(self::WID_REMAINING, (float) $this->wid->fresh()->remaining_budget, 'Kasbon never cuts the WID');

        $approval = $kasbon->approvals()->where('level', 2)->first();
        $this->assertSame($this->approver2->id, (int) $approval->actual_approver_id);
        $this->assertFalse((bool) $approval->is_override);
    }

    public function test_requester_cannot_approve_their_own_kasbon(): void
    {
        // The requester also holds the level-1 approver role.
        $this->requester->roles()->attach(\App\Models\Role::where('slug', 'kasbon_approver')->value('id'));
        $kasbon = $this->createKasbon('Office', 1000000);
        $this->submitKasbon($kasbon);

        $this->actingInProject($this->requester->fresh());
        $this->post(route('erp.advance-requests.approvals.approve', $this->pendingApproval($kasbon)))
            ->assertSessionHas('error');

        $this->assertSame('Pending', $kasbon->approvals()->where('level', 1)->value('status'));
    }

    public function test_user_not_assigned_to_the_level_cannot_approve_or_reject(): void
    {
        $kasbon = $this->createKasbon('Office', 1000000);
        $this->submitKasbon($kasbon);
        $approval = $this->pendingApproval($kasbon);

        $this->actingInProject($this->approver2); // assigned to level 2, not 1
        $this->post(route('erp.advance-requests.approvals.approve', $approval))->assertSessionHas('error');
        $this->post(route('erp.advance-requests.approvals.reject', $approval), ['reason' => 'no'])->assertSessionHas('error');
        $this->assertSame('Pending', $approval->fresh()->status);

        // Without the approve permission at all: 403.
        $this->actingInProject($this->requester);
        $this->post(route('erp.advance-requests.approvals.approve', $approval))->assertForbidden();
    }

    public function test_superadmin_can_override_any_level(): void
    {
        $kasbon = $this->createKasbon('Office', 1000000);
        $this->submitKasbon($kasbon);

        $this->actingInProject($this->makeUser('superadmin'));
        while ($approval = $kasbon->approvals()->where('status', 'Pending')->first()) {
            $this->post(route('erp.advance-requests.approvals.approve', $approval))->assertSessionHas('success');
            $this->assertTrue((bool) $approval->fresh()->is_override);
        }

        $this->assertSame('Approved', $kasbon->fresh()->status);
    }

    public function test_final_approval_is_refused_when_the_detail_no_longer_covers_it(): void
    {
        $first = $this->createKasbon('Other Expense', 6000000);
        $second = $this->createKasbon('Other Expense', 6000000);
        $this->submitKasbon($first);
        $this->submitKasbon($second);

        $this->approveKasbonFully($first); // 10 jt -> 4 jt left

        $this->actingInProject($this->approver);
        $this->post(route('erp.advance-requests.approvals.approve', $this->pendingApproval($second)))->assertSessionHas('success');
        $finalStep = $this->pendingApproval($second);
        $this->actingInProject($this->approver2);
        $this->post(route('erp.advance-requests.approvals.approve', $finalStep))->assertSessionHas('error');

        $this->assertSame('Submitted', $second->fresh()->status);
        $this->assertSame('Pending', $finalStep->fresh()->status);
        $this->assertEquals(4000000, $this->remaining('Other Expense'));
    }

    public function test_an_already_processed_step_cannot_be_approved_again(): void
    {
        $kasbon = $this->createKasbon('Office', 1000000);
        $this->submitKasbon($kasbon);
        $step = $this->pendingApproval($kasbon);
        $this->actingInProject($this->approver);
        $this->post(route('erp.advance-requests.approvals.approve', $step))->assertSessionHas('success');

        $this->post(route('erp.advance-requests.approvals.approve', $step))->assertSessionHas('error');
        // Level 2 is still waiting for its own approver.
        $this->assertSame('Pending', $kasbon->approvals()->where('level', 2)->value('status'));
    }

    public function test_reject_cancels_remaining_levels_and_leaves_the_budget(): void
    {
        $kasbon = $this->createKasbon('Office', 1000000);
        $this->submitKasbon($kasbon);

        $this->actingInProject($this->approver);
        $this->post(route('erp.advance-requests.approvals.reject', $this->pendingApproval($kasbon)), [])
            ->assertSessionHasErrors('reason');
        $this->post(route('erp.advance-requests.approvals.reject', $this->pendingApproval($kasbon)), ['reason' => 'Tidak perlu'])
            ->assertSessionHas('success');

        $kasbon->refresh();
        $this->assertSame('Rejected', $kasbon->status);
        $this->assertSame(['Rejected', 'Cancelled'], $kasbon->approvals()->orderBy('level')->pluck('status')->all());
        $this->assertSame('Tidak perlu', $kasbon->approvals()->where('level', 1)->value('comments'));
        $this->assertEquals(50000000, $this->remaining('Office'));
    }

    public function test_only_finance_or_superadmin_can_mark_paid(): void
    {
        $kasbon = $this->approvedKasbon('Office', 1000000);

        $this->actingInProject($this->approver);
        $this->post(route('erp.advance-requests.pay', $kasbon))->assertSessionHas('error');
        $this->assertNotSame('Paid', $kasbon->fresh()->payment_status);

        $this->actingInProject($this->finance);
        $this->post(route('erp.advance-requests.pay', $kasbon))->assertSessionHas('success');

        $kasbon->refresh();
        $this->assertSame('Paid', $kasbon->payment_status);
        $this->assertSame($this->finance->id, (int) $kasbon->paid_by_id);
        $this->assertNotNull($kasbon->payment_date);
        $this->assertEquals(1000000, (float) $kasbon->advance_outstanding, 'Paying does not change the outstanding');
        $this->assertEquals(49000000, $this->remaining('Office'), 'Paying does not cut again');
    }

    public function test_cannot_pay_twice_or_pay_an_unapproved_kasbon(): void
    {
        $draft = $this->createKasbon('Office', 1000000);
        $this->actingInProject($this->finance);
        $this->post(route('erp.advance-requests.pay', $draft))->assertSessionHas('error');
        $this->assertNotSame('Paid', $draft->fresh()->payment_status);

        $paid = $this->paidKasbon('Office', 1000000);
        $this->actingInProject($this->makeUser('superadmin'));
        $this->post(route('erp.advance-requests.pay', $paid))->assertSessionHas('error');
        $this->assertSame($this->finance->id, (int) $paid->fresh()->paid_by_id);
    }

    public function test_a_rejected_kasbon_is_resubmitted_as_a_new_round_from_level_one(): void
    {
        // Three levels so the rejection happens in the middle of the chain.
        ErpApprovalConfig::create([
            'record_type' => 'advance_request', 'level' => 3, 'name' => 'Step 3',
            'role_id' => \App\Models\Role::where('slug', 'kasbon_approver2')->value('id'),
        ]);
        $kasbon = $this->createKasbon('Other Expense', 3000000);
        $this->submitKasbon($kasbon)->assertSessionHas('success');
        $this->actingInProject($this->approver);
        $this->post(route('erp.advance-requests.approvals.approve', $this->pendingApproval($kasbon)), ['comments' => 'L1 ok'])
            ->assertSessionHas('success');
        $this->actingInProject($this->approver2);
        $this->post(route('erp.advance-requests.approvals.reject', $this->pendingApproval($kasbon)), ['reason' => 'Lampiran kurang'])
            ->assertSessionHas('success');

        $kasbon->refresh();
        $this->assertSame('Rejected', $kasbon->status);
        $round1 = $kasbon->approvals()->orderBy('level')->get()->map->only(['id', 'level', 'round', 'status', 'comments', 'actual_approver_id'])->all();
        $this->assertSame(['Approved', 'Rejected', 'Cancelled'], array_column($round1, 'status'));
        $this->assertSame([1, 1, 1], array_column($round1, 'round'));
        $this->assertEquals(10000000, $this->remaining('Other Expense'));

        // The owner resubmits it as-is.
        $this->actingInProject($this->requester);
        $this->get(route('erp.advance-requests.show', $kasbon))->assertOk()->assertSee('Ajukan Ulang');
        $this->submitKasbon($kasbon)->assertSessionHas('success');

        $kasbon->refresh();
        $this->assertSame('Submitted', $kasbon->status);
        $this->assertSame(2, $kasbon->latestApprovalRound());
        $this->assertSame(['Pending', 'Waiting', 'Waiting'], $kasbon->currentApprovals()->orderBy('level')->pluck('status')->all());
        $this->assertSame([$this->requester->id], $kasbon->currentApprovals()->pluck('submitted_by_id')->map(fn ($v) => (int) $v)->unique()->values()->all());
        $this->assertSame($round1, $kasbon->approvals()->where('round', 1)->orderBy('level')->get()
            ->map->only(['id', 'level', 'round', 'status', 'comments', 'actual_approver_id'])->all());

        // A running round cannot be submitted again.
        $this->submitKasbon($kasbon)->assertSessionHas('error');

        // Round 2 completes the kasbon and the final approval still cuts the detail.
        $this->approveKasbonFully($kasbon);
        $kasbon->refresh();
        $this->assertSame('Approved', $kasbon->status);
        $this->assertEquals(7000000, $this->remaining('Other Expense'));
        $this->assertSame(['Approved', 'Rejected', 'Cancelled'], $kasbon->approvals()->where('round', 1)->orderBy('level')->pluck('status')->all());

        $this->actingInProject($this->requester);
        $this->get(route('erp.advance-requests.show', $kasbon))->assertOk()
            ->assertSeeInOrder(['Round 1', 'Lampiran kurang', 'Approval Request Submitted', 'Round 2']);
    }

    public function test_rejected_kasbon_does_not_count_against_the_budget(): void
    {
        $kasbon = $this->createKasbon('Other Expense', 9000000);
        $this->submitKasbon($kasbon);
        $this->actingInProject($this->approver);
        $this->post(route('erp.advance-requests.approvals.reject', $this->pendingApproval($kasbon)), ['reason' => 'x']);

        $this->postKasbon(['Other Expense' => 10000000])->assertSessionHasNoErrors();
        $this->assertSame(2, AdvanceRequest::count());
    }
}
