<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Erp\AdvanceRequest;
use App\Models\Erp\EmployeeAdvance;
use App\Models\Erp\ExpenseDeclaration;
use App\Models\Erp\RequestForm;
use App\Models\User;
use Illuminate\Http\Request;

class HrEmployeeController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->hasPermission('employees.view'), 403);

        $employees = Employee::on('master')->with('user')->orderBy('id')->get();
        $totalEmployees = $employees->count();
        $activeEmployees = $employees->where('status', 'active')->count();
        $linkedUsers = $employees->whereNotNull('user_id')->count();

        return view('erp.hr.index', compact('employees', 'totalEmployees', 'activeEmployees', 'linkedUsers'));
    }

    /**
     * Employee page with one tab per module. A tab shows only documents this employee
     * made, and only when the viewer may see that module (or is the employee, HRD, CEO, admin).
     */
    public function show(Employee $employee)
    {
        $viewer = auth()->user();
        abort_unless($employee->viewableBy($viewer), 403);

        $employee->load('user');
        $all = $employee->seesEverything($viewer);
        $userId = $employee->user_id;
        $tabs = [
            'rf' => $all || $viewer->hasPermission('request_forms.view'),
            'kasbon' => $all || $viewer->hasPermission('advance_requests.view') || $viewer->hasPermission('expense_declarations.view'),
            'advexnpj' => $all || $viewer->hasPermission('employees.view'),
            'leave' => true,
        ];

        // RFs and project kasbon are made by a login account; an employee without one has none
        $requestForms = $tabs['rf'] && $userId
            ? RequestForm::where('created_by_id', $userId)->latest('rf_date')->latest('id')->get()
            : collect();
        $kasbons = $tabs['kasbon'] && $userId
            ? AdvanceRequest::with('expenseDeclarations')->where('request_by_id', $userId)->latest('advance_request_date')->latest('id')->get()
            : collect();
        $standaloneDeclarations = $tabs['kasbon'] && $userId
            ? ExpenseDeclaration::where('request_by_id', $userId)->whereNull('advance_request_id')->latest('request_date')->latest('id')->get()
            : collect();
        $advances = $tabs['advexnpj']
            ? EmployeeAdvance::where('employee_id', $employee->id)->latest('id')->get()
            : collect();

        $activity = collect()
            ->merge($requestForms->map(fn ($r) => ['rf', $r->rf_date ?? $r->created_at, $r->rf_no, $r->remark, $r->total_amount, $r->status, route('erp.request-form.show', $r)]))
            ->merge($kasbons->map(fn ($k) => ['kasbon', $k->advance_request_date ?? $k->created_at, $k->code, $k->description, $k->total_amount_advance, $k->status, route('erp.advance-requests.show', $k)]))
            ->merge($kasbons->flatMap->expenseDeclarations->merge($standaloneDeclarations)
                ->map(fn ($d) => ['declaration', $d->request_date ?? $d->created_at, $d->code, $d->description, $d->amount_declaration, $d->status, route('erp.expense-declarations.show', $d)]))
            ->merge($advances->map(fn ($a) => ['advexnpj', $a->advance_request_date ?? $a->created_at, $a->code, $a->description, $a->advance_amount, $a->status, route('erp.hr.employee-advances.show', $a)]))
            ->sortByDesc(fn ($row) => $row[1]?->format('Y-m-d His'))
            ->take(8)->values();

        return view('erp.hr.show', compact('employee', 'tabs', 'requestForms', 'kasbons', 'standaloneDeclarations', 'advances', 'activity'));
    }

    public function attendances()
    {
        return view('erp.hr.coming_soon', [
            'moduleTitle' => 'Absensi & Kehadiran (Attendance)',
            'moduleIcon'  => 'bx-calendar-check',
            'moduleDesc'  => 'Fitur pencatatan kehadiran online, integrasi mesin fingerprint, shift kerja, dan rekap absensi bulanan.',
        ]);
    }

    public function payroll()
    {
        return view('erp.hr.coming_soon', [
            'moduleTitle' => 'Payroll & Penggajian Karyawan',
            'moduleIcon'  => 'bx-wallet-alt',
            'moduleDesc'  => 'Fitur generate slip gaji otomatis, perhitungan PPh 21, tunjangan, potongan kasbon, dan BPJS Ketenagakerjaan/Kesehatan.',
        ]);
    }
}
