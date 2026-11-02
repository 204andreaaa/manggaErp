<?php

namespace Tests\Feature\Erp\PurchaseOrder;

use App\Models\Department;
use App\Models\Erp\RequestForm;
use App\Models\User;

/** Non-project RFs are charged to a department; their POs show up as that department's spending. */
class DepartmentSpendingTest extends PurchaseOrderTestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'record_type' => 'non_project',
            'priority' => 'Normal',
            'rf_date' => now()->format('Y-m-d'),
            'items' => [[
                'product_name' => 'Kertas A4', 'currency' => 'IDR', 'unit_cost' => 50_000, 'qty' => 2, 'status' => 'Requested',
            ]],
        ], $overrides);
    }

    private function store(User $user, array $overrides = [])
    {
        return $this->actingInProject($user)->from(route('erp.request-form.create', ['type' => 'non_project']))
            ->post(route('erp.request-form.store'), $this->payload($overrides));
    }

    public function test_user_without_a_department_cannot_create_a_non_project_rf(): void
    {
        $user = $this->makeUser('staff');

        $this->actingInProject($user)->get(route('erp.request-form.create', ['type' => 'non_project']))
            ->assertOk()->assertSee('Data departemen kamu belum lengkap');
        $this->store($user)->assertSessionHasErrors('department_id');
        $this->assertSame(0, RequestForm::count());

        // project RFs don't need one
        $this->store($user, ['record_type' => 'project', 'project_code' => 'SP-X', 'items' => [[
            'product_name' => 'Kabel', 'wid' => 'WID-X', 'currency' => 'IDR', 'unit_cost' => 1, 'qty' => 1, 'status' => 'Requested',
        ]]])->assertSessionHasNoErrors();
        $this->assertNull(RequestForm::first()->department_id);
    }

    public function test_regular_user_is_locked_to_their_own_department(): void
    {
        $user = $this->makeUser('staff');
        $ga = $this->giveDepartment($user, 'General Affair');
        $it = Department::create(['code' => 'IT', 'name' => 'Information Technology', 'status' => 'active']);

        $this->actingInProject($user)->get(route('erp.request-form.create', ['type' => 'non_project']))
            ->assertOk()->assertSee('General Affair')->assertDontSee('name="department_id"', false);
        $this->store($user, ['department_id' => $it->id])->assertSessionHasNoErrors();

        $rf = RequestForm::firstOrFail();
        $this->assertSame([$ga->id, 'General Affair'], [(int) $rf->department_id, $rf->department_name]);

        // moving the user later doesn't rewrite the RF, even when they edit it
        $this->giveDepartment($user, 'Information Technology');
        $this->actingInProject($user)->put(route('erp.request-form.update', $rf), $this->payload(['department_id' => $it->id]))
            ->assertSessionHasNoErrors();
        $this->assertSame($ga->id, (int) $rf->fresh()->department_id);
    }

    public function test_admin_picks_the_department_and_must_pick_one(): void
    {
        $admin = $this->makeUser('admin');
        $this->giveDepartment($admin, 'General Affair');
        $it = Department::create(['code' => 'IT', 'name' => 'Information Technology', 'status' => 'active']);
        $old = Department::create(['code' => 'OLD', 'name' => 'Old Dept', 'status' => 'inactive']);

        $this->actingInProject($admin)->get(route('erp.request-form.create', ['type' => 'non_project']))
            ->assertOk()->assertSee('name="department_id"', false)->assertSee('Information Technology')->assertDontSee('Old Dept');

        $this->store($admin, ['department_id' => ''])->assertSessionHasErrors('department_id');
        $this->store($admin, ['department_id' => $old->id])->assertSessionHasErrors('department_id');
        $this->store($admin, ['department_id' => $it->id])->assertSessionHasNoErrors();

        $rf = RequestForm::firstOrFail();
        $this->assertSame([$it->id, 'Information Technology'], [(int) $rf->department_id, $rf->department_name]);
        $this->actingInProject($this->makeUser('superadmin'))->get(route('erp.request-form.show', $rf))
            ->assertOk()->assertSee(route('erp.department-spending.show', $it->id), false);
    }

    public function test_spending_pages_count_approved_rfs_and_approved_or_completed_pos(): void
    {
        $ga = Department::create(['code' => 'GA', 'name' => 'General Affair', 'status' => 'active']);
        $it = Department::create(['code' => 'IT', 'name' => 'Information Technology', 'status' => 'active']);
        $gaFields = ['record_type' => 'non_project', 'is_project' => false, 'project_code' => null, 'department_id' => $ga->id, 'department_name' => $ga->name];

        $rf1 = $this->makeApprovedRf([['name' => 'Kursi', 'qty' => 2, 'unit_cost' => 500_000]], $gaFields + ['total_amount' => 1_000_000]);
        $po1 = $this->makeApprovedPo($rf1);
        $rf2 = $this->makeApprovedRf([['name' => 'Meja', 'qty' => 1, 'unit_cost' => 300_000]], $gaFields + ['total_amount' => 300_000]);
        $po2 = $this->makeApprovedPo($rf2, ['status' => 'Completed']);
        $rf3 = $this->makeApprovedRf([['name' => 'Lemari', 'qty' => 1, 'unit_cost' => 900_000]], $gaFields + ['total_amount' => 900_000]);
        $cancelled = $this->makeApprovedPo($rf3, ['status' => 'Cancelled']);
        $draft = $this->makeApprovedRf([['name' => 'Rak', 'qty' => 1, 'unit_cost' => 70_000]], $gaFields + ['total_amount' => 70_000, 'status' => 'Draft']);
        $lastYear = $this->makeApprovedRf([['name' => 'Printer', 'qty' => 1, 'unit_cost' => 4_000_000]], $gaFields + ['total_amount' => 4_000_000, 'rf_date' => now()->subYear()->toDateString()]);
        $projectRf = $this->makeApprovedRf([['name' => 'Kabel', 'qty' => 1, 'unit_cost' => 8_000_000]]);
        $projectPo = $this->makeApprovedPo($projectRf);

        $admin = $this->makeUser('superadmin');
        $this->actingInProject($admin)->get(route('erp.department-spending.index'))
            ->assertOk()
            ->assertSee('General Affair')->assertSee('Information Technology')
            ->assertSee('IDR 2.200.000')  // approved RFs this year: 1,000,000 + 300,000 + 900,000
            ->assertSee('IDR 1.300.000')  // POs approved/completed: 1,000,000 + 300,000
            ->assertDontSee('IDR 8.000.000');

        $this->get(route('erp.department-spending.show', $ga))
            ->assertOk()
            ->assertSee($rf1->rf_no)->assertSee($draft->rf_no)->assertDontSee($lastYear->rf_no)
            ->assertSee($po1->po_no)->assertSee($po2->po_no)->assertSee($cancelled->po_no)
            ->assertDontSee($projectPo->po_no)
            ->assertSee('IDR 1.300.000');

        $this->get(route('erp.department-spending.show', ['department' => $ga, 'year' => 'all']))
            ->assertOk()->assertSee($lastYear->rf_no)->assertSee('IDR 6.200.000');

        $this->get(route('erp.department-spending.show', $it))->assertOk()->assertDontSee($rf1->rf_no);

        $this->actingInProject($this->makeUser('staff'));
        $this->get(route('erp.department-spending.index'))->assertForbidden();
        $this->get(route('erp.department-spending.show', $ga))->assertForbidden();
    }
}
