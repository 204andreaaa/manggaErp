<?php

namespace App\Http\Controllers\Erp;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Erp\EmployeeAdvance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Advance-Expense Declaration Non Project, opened from an employee's detail page. No approval. */
class EmployeeAdvanceController extends Controller
{
    public function create(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('employees.update'), 403);
        $employee = Employee::findOrFail($request->query('employee_id'));

        return view('erp.hr.advances.form', [
            'advance' => new EmployeeAdvance(['status' => 'Draft', 'currency' => 'IDR']),
            'employee' => $employee,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('employees.update'), 403);
        $data = $this->validated($request, true);
        $employee = Employee::findOrFail($data['employee_id']);
        $department = Department::where('name', $employee->department)->first();

        $advance = EmployeeAdvance::create($data + [
            'code' => EmployeeAdvance::nextCode(),
            'department_id' => $department?->id,
            'department_name' => $employee->department,
            'created_by_id' => auth()->id(),
            'updated_by_id' => auth()->id(),
        ]);

        NotificationHelper::pushLive('employee_advance', $advance->id, 'created');

        if ($request->boolean('save_new')) {
            return redirect()->route('erp.hr.employee-advances.create', ['employee_id' => $employee->id])
                ->with('success', "{$advance->code} berhasil disimpan.");
        }

        return redirect()->route('erp.hr.employee-advances.show', $advance)->with('success', "{$advance->code} berhasil disimpan.");
    }

    public function show(EmployeeAdvance $employeeAdvance)
    {
        $employeeAdvance->load(['employee', 'updatedBy']);
        abort_unless($employeeAdvance->employee?->viewableBy(auth()->user()) ?? auth()->user()->hasPermission('employees.view'), 403);

        return view('erp.hr.advances.show', ['advance' => $employeeAdvance]);
    }

    public function edit(EmployeeAdvance $employeeAdvance)
    {
        abort_unless(auth()->user()->hasPermission('employees.update'), 403);

        return view('erp.hr.advances.form', [
            'advance' => $employeeAdvance,
            'employee' => $employeeAdvance->employee()->firstOrFail(),
        ]);
    }

    public function update(Request $request, EmployeeAdvance $employeeAdvance)
    {
        abort_unless(auth()->user()->hasPermission('employees.update'), 403);
        $employeeAdvance->update($this->validated($request, false) + ['updated_by_id' => auth()->id()]);

        NotificationHelper::pushLive('employee_advance', $employeeAdvance->id, 'updated');

        return redirect()->route('erp.hr.employee-advances.show', $employeeAdvance)->with('success', "{$employeeAdvance->code} berhasil diperbarui.");
    }

    public function destroy(EmployeeAdvance $employeeAdvance)
    {
        abort_unless(auth()->user()->hasPermission('employees.update'), 403);
        $employeeId = $employeeAdvance->employee_id;
        $employeeAdvance->delete();

        NotificationHelper::pushLive('employee_advance', $employeeAdvance->id, 'deleted');

        return redirect()->route('erp.hr.employees.show', $employeeId)->with('success', "{$employeeAdvance->code} berhasil dihapus.");
    }

    private function validated(Request $request, bool $creating): array
    {
        return $request->validate(array_filter([
            'employee_id' => $creating ? ['required', 'integer', Rule::exists('master.employees', 'id')] : null,
            'status' => ['required', Rule::in(EmployeeAdvance::STATUSES)],
            'currency' => ['required', 'string', 'max:10'],
            'advance_request_date' => ['nullable', 'date'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'expense_type' => ['nullable', Rule::in(EmployeeAdvance::EXPENSE_TYPES)],
            'payment_status' => ['nullable', Rule::in(EmployeeAdvance::PAYMENT_STATUSES)],
            'payment_date' => ['nullable', 'date'],
            'declaration_date' => ['nullable', 'date'],
            'declaration_amount' => ['nullable', 'numeric', 'min:0'],
            'declaration_description' => ['nullable', 'string'],
        ]));
    }
}
