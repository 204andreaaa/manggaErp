<?php

namespace Tests\Feature\Erp\Budget;

use App\Models\Erp\ErpBudgetParent;
use Tests\ErpTestCase;

class BudgetParentAndSubProjectTest extends ErpTestCase
{
    use BudgetFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsBudgetAdmin();
    }

    public function test_new_budget_parent_starts_with_remaining_equal_to_total(): void
    {
        $parent = $this->createParent(750000);

        $this->assertSame(750000.0, (float) $parent->total_budget);
        $this->assertSame(750000.0, (float) $parent->remaining_budget);
    }

    public function test_budget_parent_code_must_be_unique(): void
    {
        $this->createParent(100, ['budget_code' => 'BP-DUP']);

        $this->postJson(route('erp.budget-parents.store'), [
            'budget_code' => 'BP-DUP', 'name' => 'Again', 'total_budget' => 100, 'status' => 'Active',
        ])->assertStatus(422)->assertJsonValidationErrors('budget_code');
    }

    public function test_editing_total_recalculates_remaining_minus_allocated(): void
    {
        $parent = $this->createParent(1000000);
        $this->createWid($this->createSubProject($parent), 300000);

        $this->putJson(route('erp.budget-parents.update', $parent), [
            'budget_code' => $parent->budget_code, 'name' => $parent->name,
            'total_budget' => 2000000, 'status' => 'Active',
        ])->assertOk()->assertJson(['success' => 'Budget Parent updated successfully']);

        $this->assertSame(1700000.0, $this->remaining($parent));
    }

    public function test_total_cannot_drop_below_what_is_allocated_to_wids(): void
    {
        $parent = $this->createParent(1000000);
        $this->createWid($this->createSubProject($parent), 600000);

        $this->putJson(route('erp.budget-parents.update', $parent), [
            'budget_code' => $parent->budget_code, 'name' => $parent->name,
            'total_budget' => 500000, 'status' => 'Active',
        ])->assertStatus(422)->assertJsonFragment(['message' => 'Total Budget tidak boleh lebih kecil dari yang sudah dialokasikan ke WID (Rp 600.000).']);

        $parent->refresh();
        $this->assertSame(1000000.0, (float) $parent->total_budget);
        $this->assertSame(400000.0, (float) $parent->remaining_budget);
    }

    public function test_moving_a_sub_project_moves_its_allocation_between_parents(): void
    {
        $from = $this->createParent(1000000);
        $to = $this->createParent(1000000);
        $sub = $this->createSubProject($from);
        $this->createWid($sub, 250000);
        $this->assertSame(750000.0, $this->remaining($from));

        $this->putJson(route('erp.sub-projects.update', $sub), [
            'budget_parent_id' => $to->id, 'sub_project_code' => $sub->sub_project_code, 'name' => $sub->name,
        ])->assertOk();

        $this->assertSame(1000000.0, $this->remaining($from));
        $this->assertSame(750000.0, $this->remaining($to));
    }

    public function test_sub_project_store_refuses_unknown_parent_and_duplicate_code(): void
    {
        $parent = $this->createParent(1000);
        $this->createSubProject($parent, 'SP-SAME');

        $this->postJson(route('erp.sub-projects.store'), [
            'budget_parent_id' => 999999, 'sub_project_code' => 'SP-SAME', 'name' => 'x',
        ])->assertStatus(422)->assertJsonValidationErrors(['budget_parent_id', 'sub_project_code']);
    }

    public function test_deleting_a_sub_project_returns_its_allocation_to_the_parent(): void
    {
        $parent = $this->createParent(1000000);
        $sub = $this->createSubProject($parent);
        $this->createWid($sub, 400000);
        $this->assertSame(600000.0, $this->remaining($parent));

        $this->deleteJson(route('erp.sub-projects.destroy', $sub))->assertOk();

        // Soft-deleted sub project: its WIDs no longer count against the parent.
        $this->assertSame(1000000.0, $this->remaining($parent));
    }

    public function test_budget_parent_can_be_deleted(): void
    {
        $parent = $this->createParent(1000);

        $this->deleteJson(route('erp.budget-parents.destroy', $parent))->assertOk();

        $this->assertSoftDeleted($parent);
        $this->assertNull(ErpBudgetParent::find($parent->id));
    }
}
