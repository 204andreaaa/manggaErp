<?php

namespace Tests\Feature\Erp\RequestForm;

use App\Models\Role;
use App\Models\User;
use Tests\ErpTestCase;

/**
 * Anyone whose role has request_forms.create (every role by default) may raise an RF;
 * only the person who made it, or an admin, may edit or submit it.
 */
class RequestFormAccessTest extends ErpTestCase
{
    use RequestFormFixtures;

    public function test_only_the_maker_or_an_admin_edits_and_submits_an_rf(): void
    {
        $maker = $this->makeUser('staff');
        $colleague = $this->makeUser('staff');
        $this->approvalLevel(1, $this->makeUser('manager'));
        $rf = $this->createRfViaHttp($maker);

        $this->actingInProject($colleague)
            ->get(route('erp.request-form.show', $rf))->assertOk()
            ->assertDontSee('Edit RF')->assertDontSee(route('erp.approvals.submit', $rf));
        $this->get(route('erp.request-form.edit', $rf))->assertForbidden();
        $this->put(route('erp.request-form.update', $rf), $this->rfPayload('non_project', null, ['department_id' => $rf->department_id]))->assertForbidden();
        $this->submitRf($colleague, $rf)->assertForbidden();
        $this->assertSame('Draft', $rf->fresh()->status);

        $this->actingInProject($maker)
            ->get(route('erp.request-form.show', $rf))->assertOk()->assertSee('Edit RF')->assertSee(route('erp.approvals.submit', $rf));
        $this->get(route('erp.request-form.edit', $rf))->assertOk();

        $this->submitRf($this->makeUser('admin'), $rf)->assertSessionHas('success');
        $this->assertSame('Submitted', $rf->fresh()->status);
    }

    public function test_a_role_without_request_forms_create_cannot_raise_an_rf(): void
    {
        $user = $this->makeUser('auditor');
        Role::where('slug', 'auditor')->update(['permissions' => json_encode(['request_forms.view'])]);
        $user = User::find($user->id);

        $this->actingInProject($user)->get(route('erp.request-form.create', ['type' => 'non_project']))->assertForbidden();
        $this->post(route('erp.request-form.store'), $this->rfPayload())->assertForbidden();
        $this->get(route('erp.request-form.index'))->assertOk()->assertDontSee('+ Create Request Form');
    }
}
