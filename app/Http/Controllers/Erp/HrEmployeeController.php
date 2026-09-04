<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;

class HrEmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::on('master')->with('user')->orderBy('id')->get();
        $totalEmployees = $employees->count();
        $activeEmployees = $employees->where('status', 'active')->count();
        $linkedUsers = $employees->whereNotNull('user_id')->count();

        return view('erp.hr.index', compact('employees', 'totalEmployees', 'activeEmployees', 'linkedUsers'));
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
