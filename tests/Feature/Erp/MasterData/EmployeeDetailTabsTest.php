<?php

namespace Tests\Feature\Erp\MasterData;

use App\Models\Employee;
use App\Models\Erp\AdvanceRequest;
use App\Models\Erp\EmployeeAdvance;
use App\Models\Erp\RequestForm;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\ErpTestCase;
use Tests\Feature\Erp\Budget\BudgetFixtures;

/**
 * The employee page has a tab per module showing only what that employee made. A tab
 * appears when the viewer may see the module; the employee, HRD, CEO and admins see all.
 */
class EmployeeDetailTabsTest extends ErpTestCase
{
    use BudgetFixtures;

    private User $owner;
    private Employee $employee;
    private RequestForm $ownRf;
    private RequestForm $otherRf;
    private string $kasbonCode;

    protected function setUp(): void
    {
        parent::setUp();

        // budget chain for a project kasbon, built as superadmin
        $this->loginAsBudgetAdmin();
        $wid = $this->createWid($this->createSubProject($this->createParent(1000000)), 400000);
        $detail = $this->createDetail($this->createMonthly($this->createAnnual($wid, 200000), 100000), 'Office', 50000);
        $kasbonId = $this->approvedKasbon($detail, 10000);

        $this->owner = $this->makeUser('staff', [], ['name' => 'Rina Staff']);
        $this->employee = Employee::create(['user_id' => $this->owner->id, 'nik' => 'EMP-T001', 'name' => 'Rina Staff', 'department' => 'General Affair']);
        $other = $this->makeUser('staff');

        $rf = fn (User $u, string $no) => RequestForm::create(['rf_no' => $no, 'record_type' => 'non_project', 'created_by_id' => $u->id, 'rf_date' => now(), 'status' => 'Draft', 'remark' => 'Kertas '.$no]);
        $this->ownRf = $rf($this->owner, 'RF-OWN-001');
        $this->otherRf = $rf($other, 'RF-OTHER-001');

        DB::connection('tenant')->table('erp_advance_requests')->where('id', $kasbonId)->update(['request_by_id' => $this->owner->id]);
        $this->kasbonCode = AdvanceRequest::find($kasbonId)->code;
        $declaration = ['record_type' => 'with_advance', 'budget_plan_detail_id' => $detail->id, 'work_id' => $wid->id, 'request_by_id' => $this->owner->id,
            'request_date' => '2026-01-20', 'status' => 'Draft', 'created_at' => now(), 'updated_at' => now()];
        DB::connection('tenant')->table('erp_expense_declarations')->insert($declaration + ['code' => 'ED-CHILD-1', 'advance_request_id' => $kasbonId]);
        DB::connection('tenant')->table('erp_expense_declarations')->insert(['record_type' => 'without_advance', 'code' => 'ED-ALONE-1', 'advance_request_id' => null] + $declaration);

        EmployeeAdvance::create(['code' => 'ADVEXNPJ-00001', 'employee_id' => $this->employee->id, 'status' => 'Draft', 'currency' => 'IDR', 'advance_amount' => 500000]);
    }

    public function test_the_employee_sees_all_of_their_own_tabs_and_only_their_documents(): void
    {
        $this->actingInProject($this->owner)
            ->get(route('erp.hr.employees.show', $this->employee))
            ->assertOk()
            ->assertSee('id="tab-rf"', false)->assertSee('id="tab-kasbon"', false)->assertSee('id="tab-advexnpj"', false)->assertSee('id="tab-leave"', false)
            ->assertSee('RF-OWN-001')->assertDontSee('RF-OTHER-001')
            ->assertSee($this->kasbonCode)->assertSee('ED-CHILD-1')->assertSee('ED-ALONE-1')->assertSee('tanpa kasbon')
            ->assertSee('ADVEXNPJ-00001');

        $this->get(route('erp.hr.employee-advances.show', EmployeeAdvance::first()))->assertOk();
    }

    public function test_hrd_ceo_and_admin_see_every_tab(): void
    {
        foreach (['hrd', 'ceo', 'admin'] as $role) {
            $this->actingInProject($this->makeUser($role))
                ->get(route('erp.hr.employees.show', $this->employee))
                ->assertOk()
                ->assertSee('id="tab-rf"', false)->assertSee('id="tab-kasbon"', false)->assertSee('id="tab-advexnpj"', false)
                ->assertSee('RF-OWN-001');
        }
    }

    public function test_others_only_get_the_tabs_they_have_permission_for(): void
    {
        $this->actingInProject($this->makeUser('hr_viewer', ['employees.view']))
            ->get(route('erp.hr.employees.show', $this->employee))
            ->assertOk()
            ->assertSee('id="tab-advexnpj"', false)->assertSee('ADVEXNPJ-00001')
            ->assertDontSee('id="tab-rf"', false)->assertDontSee('RF-OWN-001')
            ->assertDontSee('id="tab-kasbon"', false)->assertDontSee($this->kasbonCode);

        $this->actingInProject($this->makeUser('rf_viewer', ['employees.view', 'request_forms.view']))
            ->get(route('erp.hr.employees.show', $this->employee))
            ->assertOk()->assertSee('id="tab-rf"', false)->assertSee('RF-OWN-001')->assertDontSee('id="tab-kasbon"', false);
    }

    public function test_someone_else_without_permission_cannot_open_the_page(): void
    {
        $this->actingInProject($this->makeUser('staff'));

        $this->get(route('erp.hr.employees.show', $this->employee))->assertForbidden();
        $this->get(route('erp.hr.employee-advances.show', EmployeeAdvance::first()))->assertForbidden();
    }

    public function test_navbar_links_to_your_own_profile(): void
    {
        $this->actingInProject($this->owner)
            ->get(route('erp.hr.employees.show', $this->employee))
            ->assertSee('Profil Saya');
    }
}
