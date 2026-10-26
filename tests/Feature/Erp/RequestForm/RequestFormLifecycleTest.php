<?php

namespace Tests\Feature\Erp\RequestForm;

use App\Models\Erp\ErpApproval;
use App\Models\Erp\PurchaseRequest;
use App\Models\Erp\PurchaseRequestItem;
use App\Models\Erp\RequestForm;
use App\Models\Erp\RequestFormItem;
use Tests\ErpTestCase;

class RequestFormLifecycleTest extends ErpTestCase
{
    use RequestFormFixtures;

    public function test_superadmin_unlocks_a_rejected_rf_back_to_draft_and_it_can_be_resubmitted(): void
    {
        $approver = $this->makeUser('manager');
        $this->approvalLevel(1, $approver);
        $requester = $this->makeUser('staff');
        $superadmin = $this->makeUser('superadmin');
        $rf = $this->createRfViaHttp($requester);
        $this->submitRf($requester, $rf);
        $this->reject($approver, $this->approvalAt($rf, 1), 'fix qty')->assertSessionHas('success');

        $this->actingInProject($superadmin)->from(route('erp.request-form.show', $rf))
            ->post(route('erp.request-form.unlock', $rf))
            ->assertSessionHas('success');

        $this->assertSame('Draft', $rf->fresh()->status);
        $this->assertSame(0, $rf->approvals()->count());

        // Back in Draft: editable and submittable again with a fresh chain.
        $this->actingInProject($requester)
            ->put(route('erp.request-form.update', $rf), $this->rfPayload('non_project', [
                $this->itemPayload(['id' => $rf->items()->first()->id, 'qty' => 2]),
            ]))->assertSessionHasNoErrors();
        $this->assertSame(20000, $rf->fresh()->total_amount);
        $this->submitRf($requester, $rf)->assertSessionHas('success');
        $this->assertSame('Pending', $this->approvalAt($rf, 1)->status);
        $this->assertSame('Submitted', $rf->fresh()->status);
    }

    public function test_unlock_is_superadmin_only_and_noop_on_draft(): void
    {
        $this->approvalLevel(1, $this->makeUser('manager'));
        $requester = $this->makeUser('staff');
        $admin = $this->makeUser('admin');
        $rf = $this->createRfViaHttp($requester);

        $this->actingInProject($this->makeUser('superadmin'))
            ->post(route('erp.request-form.unlock', $rf))
            ->assertSessionHas('error', 'Request Form sudah berstatus Draft.');

        $this->submitRf($requester, $rf);
        $this->actingInProject($admin)->post(route('erp.request-form.unlock', $rf))->assertForbidden();
        $this->actingInProject($requester)->post(route('erp.request-form.unlock', $rf))->assertForbidden();

        $this->assertSame('Submitted', $rf->fresh()->status);
        $this->assertSame(1, $rf->approvals()->count());
    }

    public function test_superadmin_deletes_an_rf_with_its_items_approvals_and_prs(): void
    {
        $requester = $this->makeUser('staff');
        $superadmin = $this->makeUser('superadmin');
        $rf = $this->approvedRf($requester);
        $this->actingInProject($this->makeUser('logistik'))
            ->post(route('erp.purchase-requests.store', $rf), ['items' => [[
                'request_form_item_id' => $rf->items->first()->id, 'pr_requested_qty' => 1, 'required_qty' => 5,
            ]]])->assertSessionHas('success');
        $pr = PurchaseRequest::sole();
        $other = $this->createRfViaHttp($requester);

        $this->actingInProject($superadmin)
            ->delete(route('erp.request-form.destroy', $rf))
            ->assertRedirect(route('erp.request-form.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($rf);
        $this->assertSoftDeleted($pr);
        $this->assertSame(0, RequestFormItem::where('request_form_id', $rf->id)->count());
        $this->assertSame(0, ErpApproval::where('request_form_id', $rf->id)->count());
        $this->assertSame(0, PurchaseRequestItem::count());
        // Unrelated RF is untouched.
        $this->assertSame(1, $other->items()->count());
        $this->get(route('erp.request-form.show', $rf))->assertNotFound();

        // RF numbers are not reused after a delete.
        $next = $this->createRfViaHttp($requester);
        $this->assertStringEndsWith('-00003', $next->rf_no);
    }

    public function test_only_superadmin_may_delete_an_rf(): void
    {
        $requester = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($requester);

        $this->actingInProject($requester)->delete(route('erp.request-form.destroy', $rf))->assertForbidden();
        $this->actingInProject($this->makeUser('admin'))->delete(route('erp.request-form.destroy', $rf))->assertForbidden();

        $this->assertNotSoftDeleted($rf);
        $this->assertSame(1, RequestForm::count());
    }
}
