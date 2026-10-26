<?php

namespace Tests\Feature\Erp\MasterData;

use App\Models\Department;
use App\Models\Employee;
use Tests\ErpTestCase;

class DepartmentAndEmployeeTest extends ErpTestCase
{
    use MasterDataHelpers;

    private function employee(string $department, string $name = 'Sari'): Employee
    {
        return Employee::create(['name' => $name, 'department' => $department, 'status' => 'active']);
    }

    public function test_list_page_shows_and_filters_departments(): void
    {
        Department::create(['code' => 'FIN', 'name' => 'Finance Dept', 'status' => 'active']);
        Department::create(['code' => 'OLD', 'name' => 'Old Dept', 'status' => 'inactive']);
        $this->actingInProject($this->makeUser('superadmin'));

        $this->get(route('erp.departments.index'))->assertOk()->assertSee('Finance Dept')->assertSee('Old Dept');
        $this->get(route('erp.departments.index', ['status' => 'inactive']))->assertOk()
            ->assertSee('Old Dept')->assertDontSee('Finance Dept');
        $this->get(route('erp.departments.index', ['search' => 'fin']))->assertOk()
            ->assertSee('Finance Dept')->assertDontSee('Old Dept');
    }

    public function test_create_upper_cases_code_and_rejects_duplicates(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));

        $this->from(route('erp.departments.index'))
            ->post(route('erp.departments.store'), ['code' => 'proc', 'name' => 'Procurement', 'status' => 'active'])
            ->assertRedirect(route('erp.departments.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('departments', ['code' => 'PROC', 'name' => 'Procurement', 'status' => 'active'], 'master');

        $this->post(route('erp.departments.store'), ['code' => 'PROC', 'name' => 'Again', 'status' => 'active'])
            ->assertSessionHasErrors('code');
        $this->post(route('erp.departments.store'), ['code' => 'X1', 'status' => 'closed'])
            ->assertSessionHasErrors(['name', 'status']);
        $this->assertSame(1, Department::count());
    }

    public function test_renaming_a_department_moves_its_employees_and_status_toggles(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $dept = Department::create(['code' => 'FIN', 'name' => 'Finance', 'status' => 'active']);
        $sari = $this->employee('Finance');

        $this->put(route('erp.departments.update', $dept), ['code' => 'fin', 'name' => 'Finance & Tax', 'status' => 'active'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Finance & Tax', $dept->fresh()->name);
        $this->assertSame('Finance & Tax', $sari->fresh()->department);

        $this->patchJson(route('erp.departments.toggle_status', $dept))->assertOk()->assertJson(['status' => 'inactive']);
        $this->patchJson(route('erp.departments.toggle_status', $dept))->assertOk()->assertJson(['status' => 'active']);
    }

    public function test_delete_is_blocked_while_employees_use_the_department(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $used = Department::create(['code' => 'FIN', 'name' => 'Finance', 'status' => 'active']);
        $free = Department::create(['code' => 'EMP', 'name' => 'Empty', 'status' => 'active']);
        $this->employee('Finance');

        $this->deleteJson(route('erp.departments.destroy', $used))->assertStatus(422)->assertJsonStructure(['error']);
        $this->assertNotNull(Department::find($used->id));

        $this->deleteJson(route('erp.departments.destroy', $free))->assertOk();
        $this->assertNull(Department::find($free->id));
    }

    public function test_user_without_departments_permission_is_refused(): void
    {
        $dept = Department::create(['code' => 'FIN', 'name' => 'Keep', 'status' => 'active']);
        $this->actingInProject($this->makeUser('staff'));

        $this->get(route('erp.departments.index'))->assertForbidden();
        $this->post(route('erp.departments.store'), ['code' => 'NO', 'name' => 'Nope', 'status' => 'active'])->assertForbidden();
        $this->put(route('erp.departments.update', $dept), ['code' => 'FIN', 'name' => 'Nope', 'status' => 'active'])->assertForbidden();
        $this->patchJson(route('erp.departments.toggle_status', $dept))->assertForbidden();
        $this->deleteJson(route('erp.departments.destroy', $dept))->assertForbidden();

        $this->assertDatabaseHas('departments', ['id' => $dept->id, 'name' => 'Keep', 'status' => 'active'], 'master');
        $this->assertSame(1, Department::count());
    }

    public function test_hr_employee_list_shows_employees(): void
    {
        $this->employee('Finance', 'Sari Wulandari');
        $this->actingInProject($this->makeUser('superadmin'));

        $this->get(route('erp.hr.employees.index'))->assertOk()->assertSee('Sari Wulandari');
    }

    public function test_hr_employee_list_requires_employees_permission(): void
    {

        $this->employee('Finance', 'Sari Wulandari');
        $this->actingInProject($this->makeUser('staff'));

        $this->get(route('erp.hr.employees.index'))->assertForbidden();
    }
}
