<?php

namespace Tests\Feature\Erp\MasterData;

use App\Models\Erp\ErpApprovalConfig;
use App\Models\Role;
use Tests\ErpTestCase;

class ApprovalConfigTest extends ErpTestCase
{
    use MasterDataHelpers;

    private function config(array $attributes = []): ErpApprovalConfig
    {
        return ErpApprovalConfig::create(array_merge([
            'record_type' => 'request_form',
            'level' => 1,
            'name' => 'Manager',
            'min_amount' => 0,
            'max_amount' => 10000000,
            'is_project' => null,
        ], $attributes));
    }

    public function test_list_page_shows_configs(): void
    {
        $approver = $this->makeUser('staff', [], ['name' => 'Pak Approver']);
        $this->config(['name' => 'Site Manager Step', 'user_id' => $approver->id]);
        $this->actingInProject($this->makeUser('superadmin'));

        $this->get(route('erp.approval-configs.index'))->assertOk()->assertSee('Site Manager Step');
    }

    public function test_create_user_and_role_steps(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $approver = $this->makeUser('staff');
        $finance = Role::firstOrCreate(['slug' => 'finance'], ['name' => 'Finance']);

        $this->from(route('erp.approval-configs.index'))->post(route('erp.approval-configs.store'), [
            'record_type' => 'purchase_order', 'level' => 1, 'name' => 'PM',
            'assign_type' => 'user', 'user_id' => $approver->id, 'role_id' => $finance->id,
            'min_amount' => '0', 'max_amount' => 5000000, 'is_project' => '1',
        ])->assertRedirect(route('erp.approval-configs.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('erp_approval_configs', [
            'record_type' => 'purchase_order', 'level' => 1, 'user_id' => $approver->id,
            'role_id' => null, 'min_amount' => 0, 'max_amount' => 5000000, 'is_project' => 1,
        ], 'tenant');

        // Single-step verification types are forced to level 1 with no amount/project filter.
        $this->post(route('erp.approval-configs.store'), [
            'record_type' => 'gr_verification', 'level' => 3, 'name' => 'GR check',
            'assign_type' => 'role', 'role_id' => $finance->id, 'user_id' => $approver->id,
            'min_amount' => 100, 'max_amount' => 200, 'is_project' => '0',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('erp_approval_configs', [
            'record_type' => 'gr_verification', 'level' => 1, 'role_id' => $finance->id, 'user_id' => null,
            'min_amount' => null, 'max_amount' => null, 'is_project' => null,
        ], 'tenant');
    }

    public function test_create_rejects_invalid_input_and_conflicting_steps(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $approver = $this->makeUser('staff');
        $this->config(['min_amount' => 0, 'max_amount' => 10000000]);
        $this->config(['record_type' => 'po_verification', 'max_amount' => null, 'min_amount' => null]);

        $this->post(route('erp.approval-configs.store'), [
            'record_type' => 'nonsense', 'level' => 0, 'assign_type' => 'user', 'min_amount' => 500, 'max_amount' => 100,
        ])->assertSessionHasErrors(['record_type', 'level', 'name', 'user_id', 'max_amount']);

        // Overlaps the existing level-1 RF range.
        $this->post(route('erp.approval-configs.store'), [
            'record_type' => 'request_form', 'level' => 1, 'name' => 'Overlap',
            'assign_type' => 'user', 'user_id' => $approver->id, 'min_amount' => 5000000, 'max_amount' => 20000000,
        ])->assertSessionHasErrors('min_amount');

        // A second verifier for a single-step type.
        $this->post(route('erp.approval-configs.store'), [
            'record_type' => 'po_verification', 'level' => 1, 'name' => 'Second',
            'assign_type' => 'user', 'user_id' => $approver->id,
        ])->assertSessionHasErrors('record_type');

        // Adjacent, non-overlapping range is fine.
        $this->post(route('erp.approval-configs.store'), [
            'record_type' => 'request_form', 'level' => 1, 'name' => 'Director',
            'assign_type' => 'user', 'user_id' => $approver->id, 'min_amount' => 10000001, 'max_amount' => '', 'is_project' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, ErpApprovalConfig::count());
    }

    public function test_update_and_delete(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $approver = $this->makeUser('staff');
        $cfg = $this->config(['user_id' => $approver->id]);
        $this->config(['level' => 2, 'name' => 'Level two', 'user_id' => $approver->id]);

        // Updating itself does not count as a conflict with itself.
        $this->put(route('erp.approval-configs.update', $cfg), [
            'record_type' => 'request_form', 'level' => 1, 'name' => 'Manager (edited)',
            'assign_type' => 'user', 'user_id' => $approver->id, 'min_amount' => 0, 'max_amount' => 20000000,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('erp_approval_configs', ['id' => $cfg->id, 'name' => 'Manager (edited)', 'max_amount' => 20000000], 'tenant');

        // Moving it onto level 2 collides with the existing level-2 step.
        $this->put(route('erp.approval-configs.update', $cfg), [
            'record_type' => 'request_form', 'level' => 2, 'name' => 'Clash',
            'assign_type' => 'user', 'user_id' => $approver->id, 'min_amount' => '', 'max_amount' => '', 'is_project' => '',
        ])->assertSessionHasErrors('min_amount');
        $this->assertSame(1, $cfg->fresh()->level);

        $this->delete(route('erp.approval-configs.destroy', $cfg))->assertRedirect();
        $this->assertDatabaseMissing('erp_approval_configs', ['id' => $cfg->id], 'tenant');
    }

    public function test_a_step_with_only_a_maximum_amount_can_be_saved(): void
    {

        $this->actingInProject($this->makeUser('superadmin'));
        $approver = $this->makeUser('staff');

        $this->post(route('erp.approval-configs.store'), [
            'record_type' => 'request_form', 'level' => 1, 'name' => 'Up to 5M',
            'assign_type' => 'user', 'user_id' => $approver->id, 'min_amount' => '', 'max_amount' => 5000000, 'is_project' => '',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, ErpApprovalConfig::count());
    }

    public function test_omitted_amount_fields_do_not_crash(): void
    {

        $this->actingInProject($this->makeUser('superadmin'));
        $approver = $this->makeUser('staff');

        $this->post(route('erp.approval-configs.store'), [
            'record_type' => 'request_form', 'level' => 1, 'name' => 'No amounts',
            'assign_type' => 'user', 'user_id' => $approver->id,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, ErpApprovalConfig::count());
    }

    public function test_only_superadmin_may_manage_approval_configs(): void
    {
        $cfg = $this->config(['name' => 'Keep']);
        $approver = $this->makeUser('staff');
        $form = ['record_type' => 'request_form', 'level' => 3, 'name' => 'Nope', 'assign_type' => 'user', 'user_id' => $approver->id];

        // Even the "admin" role (which passes every hasPermission check) is refused here.
        foreach ([$this->makeUser('admin'), $this->makeUser('staff', ['approval_configs.view', 'approval_configs.create'])] as $user) {
            $this->actingInProject($user);
            $this->get(route('erp.approval-configs.index'))->assertForbidden();
            $this->post(route('erp.approval-configs.store'), $form)->assertForbidden();
            $this->put(route('erp.approval-configs.update', $cfg), $form)->assertForbidden();
            $this->delete(route('erp.approval-configs.destroy', $cfg))->assertForbidden();
        }

        $this->assertSame('Keep', $cfg->fresh()->name);
        $this->assertSame(1, ErpApprovalConfig::count());
    }
}
