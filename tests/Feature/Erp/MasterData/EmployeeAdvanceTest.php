<?php

namespace Tests\Feature\Erp\MasterData;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Erp\EmployeeAdvance;
use Tests\ErpTestCase;

/** Advance-Expense Declaration Non Project, listed on the employee's detail page. No approval. */
class EmployeeAdvanceTest extends ErpTestCase
{
    private function employee(): Employee
    {
        Department::firstOrCreate(['code' => 'FIN'], ['name' => 'Finance & Accounting', 'status' => 'active']);

        return Employee::create(['nik' => 'EMP-0730', 'name' => 'Lilu Finance', 'department' => 'Finance & Accounting', 'join_date' => '2020-01-15']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'status' => 'Draft',
            'currency' => 'IDR',
            'advance_request_date' => '2026-11-01',
            'advance_amount' => 3000000,
            'description' => 'Pinjaman karyawan untuk biaya berobat, cicilan potong gaji 5 bulan',
            'expense_type' => 'Pinjaman Karyawan',
            'payment_status' => 'Paid',
            'payment_date' => '2026-11-02',
        ], $overrides);
    }

    public function test_employee_name_opens_a_detail_page_with_its_advances(): void
    {
        $employee = $this->employee();
        $this->actingInProject($this->makeUser('hr', ['employees.view', 'employees.update']));

        $this->get(route('erp.hr.employees.index'))->assertOk()->assertSee(route('erp.hr.employees.show', $employee), false);
        $this->get(route('erp.hr.employees.show', $employee))
            ->assertOk()->assertSee('Lilu Finance')->assertSee('EMP-0730')
            ->assertSee('Advance-Expense Declaration Non Project')->assertSee('No records to display');
    }

    public function test_create_edit_and_delete_an_advance_without_approval(): void
    {
        $employee = $this->employee();
        $this->actingInProject($this->makeUser('hr', ['employees.view', 'employees.update']));

        $this->get(route('erp.hr.employee-advances.create', ['employee_id' => $employee->id]))->assertOk()->assertSee('Lilu Finance');
        $this->post(route('erp.hr.employee-advances.store'), $this->payload(['employee_id' => $employee->id]))->assertSessionHasNoErrors();
        $this->post(route('erp.hr.employee-advances.store'), $this->payload(['employee_id' => $employee->id, 'save_new' => 1]))
            ->assertRedirect(route('erp.hr.employee-advances.create', ['employee_id' => $employee->id]));

        [$first, $second] = EmployeeAdvance::orderBy('id')->get()->all();
        $this->assertSame(['ADVEXNPJ-00001', 'ADVEXNPJ-00002'], [$first->code, $second->code]);
        $this->assertSame('Finance & Accounting', $first->department_name, 'department copied from the employee');
        $this->assertNotNull($first->department_id);

        $this->get(route('erp.hr.employees.show', $employee))->assertOk()->assertSee('ADVEXNPJ-00001')->assertSee('IDR 3.000.000');

        $this->put(route('erp.hr.employee-advances.update', $first), $this->payload([
            'status' => 'Completed', 'declaration_date' => '2026-11-05', 'declaration_amount' => 2750000, 'declaration_description' => 'Kuitansi rumah sakit',
        ]))->assertRedirect(route('erp.hr.employee-advances.show', $first));
        $this->get(route('erp.hr.employee-advances.show', $first))->assertOk()->assertSee('Completed')->assertSee('IDR 2.750.000')->assertSee('Kuitansi rumah sakit');

        $this->delete(route('erp.hr.employee-advances.destroy', $second))->assertRedirect(route('erp.hr.employees.show', $employee));
        $this->assertSoftDeleted($second);
        $this->post(route('erp.hr.employee-advances.store'), $this->payload(['employee_id' => $employee->id]))->assertSessionHasNoErrors();
        $this->assertSame('ADVEXNPJ-00003', EmployeeAdvance::latest('id')->value('code'), 'a deleted number is not reused');
    }

    public function test_bad_input_is_refused(): void
    {
        $employee = $this->employee();
        $this->actingInProject($this->makeUser('hr', ['employees.view', 'employees.update']));

        $this->post(route('erp.hr.employee-advances.store'), $this->payload(['employee_id' => 999999, 'status' => 'Approved', 'expense_type' => 'Liburan', 'advance_amount' => -5]))
            ->assertSessionHasErrors(['employee_id', 'status', 'expense_type', 'advance_amount']);
        $this->assertSame(0, EmployeeAdvance::count());
    }

    public function test_view_only_users_cannot_change_and_others_cannot_see(): void
    {
        $employee = $this->employee();
        $advance = EmployeeAdvance::create($this->payload(['code' => 'ADVEXNPJ-00001', 'employee_id' => $employee->id]));

        $this->actingInProject($this->makeUser('viewer', ['employees.view']));
        $this->get(route('erp.hr.employees.show', $employee))->assertOk()->assertSee('ADVEXNPJ-00001')
            ->assertDontSee(route('erp.hr.employee-advances.create', ['employee_id' => $employee->id]), false)
            ->assertDontSee(route('erp.hr.employee-advances.edit', $advance), false);
        $this->get(route('erp.hr.employee-advances.show', $advance))->assertOk();
        $this->get(route('erp.hr.employee-advances.create', ['employee_id' => $employee->id]))->assertForbidden();
        $this->put(route('erp.hr.employee-advances.update', $advance), $this->payload())->assertForbidden();
        $this->delete(route('erp.hr.employee-advances.destroy', $advance))->assertForbidden();

        $this->actingInProject($this->makeUser('staff'));
        $this->get(route('erp.hr.employees.show', $employee))->assertForbidden();
        $this->get(route('erp.hr.employee-advances.show', $advance))->assertForbidden();
        $this->assertNull($advance->fresh()->deleted_at);
    }
}
