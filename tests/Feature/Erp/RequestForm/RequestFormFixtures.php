<?php

namespace Tests\Feature\Erp\RequestForm;

use App\Models\Erp\ErpApprovalConfig;
use App\Models\Erp\ErpBudgetParent;
use App\Models\Erp\ErpSubProject;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\RequestForm;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Shared fixtures for the Request Form / Purchase Request / RF approval tests.
 * Prerequisite data is created directly; the flows under test go through HTTP.
 */
trait RequestFormFixtures
{
    /** A Budget Parent -> Sub Project -> Work Item (WID) chain. */
    protected function makeWorkItem(string $widCode = null, float $budget = 100_000_000): ErpWorkItem
    {
        $suffix = Str::upper(Str::random(5));
        $parent = ErpBudgetParent::create([
            'budget_code' => 'BP-'.$suffix,
            'name' => 'Budget Parent '.$suffix,
            'total_budget' => $budget,
            'remaining_budget' => $budget,
        ]);
        $sub = ErpSubProject::create([
            'budget_parent_id' => $parent->id,
            'sub_project_code' => 'SP-'.$suffix,
            'name' => 'Sub Project '.$suffix,
        ]);

        return ErpWorkItem::create([
            'sub_project_id' => $sub->id,
            'wid_code' => $widCode ?? 'WID-'.$suffix,
            'name' => 'Work Item '.$suffix,
            'allocated_budget' => $budget,
            'remaining_budget' => $budget,
        ]);
    }

    protected function itemPayload(array $overrides = []): array
    {
        return array_merge([
            'product_name' => 'Kabel FO 24 Core',
            'product_id_text' => 'PRD-0001',
            'wid' => 'NON-PROJECT',
            'currency' => 'IDR',
            'unit_cost' => 10000,
            'qty' => 5,
            'date_required' => now()->addWeek()->format('Y-m-d'),
            'pic' => 'Budi',
            'within_budget' => 1,
            'status' => 'Requested',
            'remark' => 'line remark',
        ], $overrides);
    }

    protected function rfPayload(string $recordType = 'non_project', array $items = null, array $overrides = []): array
    {
        return array_merge([
            'record_type' => $recordType,
            'project_code' => $recordType === 'project' ? 'SP-TEST' : null,
            'priority' => 'Normal',
            'remark' => 'Need materials',
            'rf_date' => now()->format('Y-m-d'),
            'expense_material_equipment' => 1,
            'items' => $items ?? [$this->itemPayload()],
        ], $overrides);
    }

    /** Create an RF through the real store route as `$user` and return it. */
    protected function createRfViaHttp(User $user, string $recordType = 'non_project', array $items = null, array $overrides = []): RequestForm
    {
        $this->actingInProject($user)
            ->post(route('erp.request-form.store'), $this->rfPayload($recordType, $items, $overrides))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        return RequestForm::latest('id')->firstOrFail();
    }

    /** One request_form approval level assigned to a user or a role. */
    protected function approvalLevel(int $level, User $user = null, Role $role = null, array $extra = []): ErpApprovalConfig
    {
        return ErpApprovalConfig::create(array_merge([
            'record_type' => 'request_form',
            'level' => $level,
            'name' => 'Level '.$level,
            'user_id' => $user?->id,
            'role_id' => $role?->id,
            'min_amount' => null,
            'max_amount' => null,
            'is_project' => null,
        ], $extra));
    }

    protected function submitRf(User $user, RequestForm $rf)
    {
        return $this->actingInProject($user)
            ->from(route('erp.request-form.show', $rf))
            ->post(route('erp.approvals.submit', $rf));
    }

    /** The approval row of `$rf` at `$level`. */
    protected function approvalAt(RequestForm $rf, int $level)
    {
        return $rf->approvals()->where('level', $level)->firstOrFail();
    }

    protected function approve(User $user, $approval, string $comments = 'OK')
    {
        return $this->actingInProject($user)
            ->from(route('erp.request-form.index'))
            ->post(route('erp.approvals.approve', $approval), ['comments' => $comments]);
    }

    protected function reject(User $user, $approval, ?string $reason = 'Not needed')
    {
        return $this->actingInProject($user)
            ->from(route('erp.request-form.index'))
            ->post(route('erp.approvals.reject', $approval), ['reason' => $reason]);
    }

    /**
     * An RF already approved through the real submit/approve routes, using a
     * single-level config assigned to a dedicated approver.
     */
    protected function approvedRf(User $requester, array $items = null): RequestForm
    {
        $approver = $this->makeUser('manager');
        $this->approvalLevel(1, $approver);
        $rf = $this->createRfViaHttp($requester, 'non_project', $items);
        $this->submitRf($requester, $rf)->assertSessionHas('success');
        $this->approve($approver, $this->approvalAt($rf, 1))->assertSessionHas('success');
        $this->assertSame('Approved', $rf->fresh()->status);

        return $rf->fresh('items');
    }
}
