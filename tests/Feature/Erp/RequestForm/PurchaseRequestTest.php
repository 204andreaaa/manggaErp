<?php

namespace Tests\Feature\Erp\RequestForm;

use App\Models\Erp\PurchaseRequest;
use App\Models\Erp\PurchaseRequestItem;
use App\Models\Erp\RequestForm;
use App\Models\Erp\RequestFormItem;
use Tests\ErpTestCase;

class PurchaseRequestTest extends ErpTestCase
{
    use RequestFormFixtures;

    private function postPr($user, RequestForm $rf, array $lines, array $extra = [])
    {
        return $this->actingInProject($user)
            ->from(route('erp.request-form.show', $rf))
            ->post(route('erp.purchase-requests.store', $rf), array_merge(['items' => $lines], $extra));
    }

    private function line(RequestFormItem $item, float $qty): array
    {
        return ['request_form_item_id' => $item->id, 'pr_requested_qty' => $qty, 'required_qty' => (float) $item->qty];
    }

    public function test_logistik_creates_partial_then_full_pr_and_rf_item_statuses_follow(): void
    {
        $requester = $this->makeUser('staff');
        $logistik = $this->makeUser('logistik');
        $rf = $this->approvedRf($requester, [
            $this->itemPayload(['product_name' => 'A', 'qty' => 10]),
            $this->itemPayload(['product_name' => 'B', 'qty' => 4]),
        ]);
        $a = $rf->items->firstWhere('product_name', 'A');
        $b = $rf->items->firstWhere('product_name', 'B');

        $this->postPr($logistik, $rf, [$this->line($a, 4), $this->line($b, 4)], ['expense_transportation' => 1])
            ->assertRedirect(route('erp.request-form.show', $rf))
            ->assertSessionHas('success');

        $pr = PurchaseRequest::with('items')->sole();
        $this->assertSame($rf->id, (int) $pr->request_form_id);
        $this->assertSame('Submitted', $pr->status);
        $this->assertSame($logistik->name, $pr->requestor);
        $this->assertTrue($pr->expense_transportation);
        $this->assertMatchesRegularExpression('/^PR-\d{4}-\d{2}-00001$/', $pr->pr_no);
        $this->assertCount(2, $pr->items);

        $this->assertSame(['4.00', 'Ordered'], [$a->fresh()->qty_fulfilled, $a->fresh()->status]);
        $this->assertSame(['4.00', 'Completed'], [$b->fresh()->qty_fulfilled, $b->fresh()->status]);

        // Second PR for the rest of A completes it.
        $this->postPr($logistik, $rf, [$this->line($a, 6)])->assertSessionHas('success');
        $this->assertSame(['10.00', 'Completed'], [$a->fresh()->qty_fulfilled, $a->fresh()->status]);
        $this->assertStringEndsWith('-00002', PurchaseRequest::latest('id')->first()->pr_no);

        $this->get(route('erp.purchase-requests.show', $pr))->assertOk()->assertSee($pr->pr_no);
        $this->get(route('erp.purchase-request-items.show', $pr->items->first()))->assertOk();
        $this->get(route('erp.request-form.show', $rf))->assertOk()->assertSee($pr->pr_no);
    }

    public function test_user_without_create_pr_permission_is_refused(): void
    {
        $requester = $this->makeUser('staff');
        $rf = $this->approvedRf($requester);
        $item = $rf->items->first();

        $this->postPr($requester, $rf, [$this->line($item, 1)])->assertForbidden();

        $this->assertSame(0, PurchaseRequest::count());
        $this->assertSame(['0.00', 'Requested'], [$item->fresh()->qty_fulfilled, $item->fresh()->status]);
    }

    public function test_role_with_create_pr_permission_can_create_pr(): void
    {
        $requester = $this->makeUser('staff');
        $buyer = $this->makeUser('procurement', ['request_forms.create_pr']);
        $rf = $this->approvedRf($requester);

        $this->postPr($buyer, $rf, [$this->line($rf->items->first(), 5)])->assertSessionHas('success');

        $this->assertSame(1, PurchaseRequest::count());
        $this->assertSame('Completed', $rf->items->first()->fresh()->status);
    }

    public function test_pr_validation(): void
    {
        $logistik = $this->makeUser('logistik');
        $rf = $this->approvedRf($this->makeUser('staff'));
        $item = $rf->items->first();

        $this->postPr($logistik, $rf, [])->assertSessionHasErrors('items');
        $this->postPr($logistik, $rf, [['request_form_item_id' => 999999, 'pr_requested_qty' => 0, 'required_qty' => null]])
            ->assertSessionHasErrors(['items.0.request_form_item_id', 'items.0.pr_requested_qty', 'items.0.required_qty']);

        $this->assertSame(0, PurchaseRequest::count());
        $this->assertSame('Requested', $item->fresh()->status);
    }

    public function test_pr_cannot_be_created_from_an_rf_that_is_not_approved(): void
    {

        $logistik = $this->makeUser('logistik');
        $rf = $this->createRfViaHttp($this->makeUser('staff'));

        $this->postPr($logistik, $rf, [$this->line($rf->items()->first(), 1)])->assertForbidden();
        $this->assertSame(0, PurchaseRequest::count());
    }

    public function test_pr_cannot_include_items_from_another_rf(): void
    {

        $logistik = $this->makeUser('logistik');
        $requester = $this->makeUser('staff');
        $rf = $this->approvedRf($requester);
        $otherItem = $this->createRfViaHttp($requester)->items()->first();

        $this->postPr($logistik, $rf, [$this->line($otherItem, 1)])->assertSessionHasErrors('items.0.request_form_item_id');
        $this->assertSame('Requested', $otherItem->fresh()->status);
    }

    public function test_deleting_a_pr_rolls_back_rf_item_fulfilment(): void
    {
        $logistik = $this->makeUser('logistik');
        $rf = $this->approvedRf($this->makeUser('staff'), [$this->itemPayload(['qty' => 10])]);
        $item = $rf->items->first();

        $this->postPr($logistik, $rf, [$this->line($item, 3)]);
        $this->postPr($logistik, $rf, [$this->line($item, 7)]);
        [$first, $second] = PurchaseRequest::orderBy('id')->get();
        $this->assertSame('Completed', $item->fresh()->status);

        $this->actingInProject($logistik)->from(route('erp.request-form.show', $rf))
            ->delete(route('erp.purchase-requests.destroy', $second))
            ->assertRedirect(route('erp.request-form.show', $rf))
            ->assertSessionHas('success');
        $this->assertSoftDeleted($second);
        $this->assertSame(['3.00', 'Ordered'], [$item->fresh()->qty_fulfilled, $item->fresh()->status]);

        $this->delete(route('erp.purchase-requests.destroy', $first))->assertSessionHas('success');
        $this->assertSame(['0.00', 'Requested'], [$item->fresh()->qty_fulfilled, $item->fresh()->status]);
        $this->assertSame(0, PurchaseRequest::count());
    }

    public function test_user_without_delete_pr_permission_cannot_delete_a_pr(): void
    {
        $logistik = $this->makeUser('logistik');
        $requester = $this->makeUser('staff');
        $rf = $this->approvedRf($requester);
        $item = $rf->items->first();
        $this->postPr($logistik, $rf, [$this->line($item, 2)]);
        $pr = PurchaseRequest::sole();

        $this->actingInProject($requester)->delete(route('erp.purchase-requests.destroy', $pr))->assertForbidden();
        $this->assertNotSoftDeleted($pr);
        $this->assertSame('2.00', $item->fresh()->qty_fulfilled);

        $remover = $this->makeUser('procurement', ['request_forms.delete_pr']);
        $this->actingInProject($remover)->delete(route('erp.purchase-requests.destroy', $pr))->assertSessionHas('success');
        $this->assertSoftDeleted($pr);
        $this->assertSame(1, PurchaseRequestItem::count(), 'PR line rows are kept with the soft-deleted PR');
    }
}
