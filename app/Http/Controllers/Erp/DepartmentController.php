<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    protected function ensureCanManageDepartments(string $permission = 'departments.view')
    {
        $me = auth()->user();

        if (!$me || !$me->hasPermission($permission)) {
            abort(403, 'Unauthorized access to Departments module.');
        }

        return $me;
    }

    public function index(Request $request)
    {
        $this->ensureCanManageDepartments('departments.view');

        $query = Department::on('master')->withCount('employees')->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }

        $departments = $query->get();

        return view('erp.departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $this->ensureCanManageDepartments('departments.create');

        $data = $request->validate([
            'code'        => ['required', 'string', 'max:50', 'unique:master.departments,code'],
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data['code'] = strtoupper(trim($data['code']));

        Department::create($data);

        return back()->with('success', 'Department berhasil ditambahkan.');
    }

    public function update(Request $request, Department $department)
    {
        $this->ensureCanManageDepartments('departments.update');

        $data = $request->validate([
            'code'        => ['required', 'string', 'max:50', Rule::unique('master.departments', 'code')->ignore($department->id)],
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $oldName = $department->name;
        $data['code'] = strtoupper(trim($data['code']));

        $department->update($data);

        // Jika nama departemen berubah, sinkronkan ke data employees yang memakai nama lama
        if ($oldName !== $data['name']) {
            Employee::on('master')->where('department', $oldName)->update(['department' => $data['name']]);
        }

        return back()->with('edit_success', 'Department berhasil diperbarui.');
    }

    public function destroy(Department $department)
    {
        $this->ensureCanManageDepartments('departments.delete');

        $employeeCount = Employee::on('master')->where('department', $department->name)->count();
        if ($employeeCount > 0) {
            return response()->json([
                'error' => "Tidak dapat menghapus departemen ini karena masih digunakan oleh {$employeeCount} karyawan."
            ], 422);
        }

        $department->delete();

        return response()->json(['success' => 'Department berhasil dihapus.']);
    }

    public function toggleStatus(Department $department)
    {
        $this->ensureCanManageDepartments('departments.update');

        $department->status = ($department->status === 'active') ? 'inactive' : 'active';
        $department->save();

        return response()->json([
            'success' => 'Status departemen berhasil diubah.',
            'status'  => $department->status,
        ]);
    }
}
