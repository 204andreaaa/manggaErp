<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Erp\ErpApprovalConfig;
use App\Models\User;
use App\Models\Role;

class ApprovalConfigController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);

        $configs = ErpApprovalConfig::with(['role', 'user'])->orderBy('level')->get();
        $projectConfigs = $configs->where('record_type', 'project');
        $nonProjectConfigs = $configs->where('record_type', 'non_project');
        $poLowConfigs = $configs->where('record_type', 'purchase_order_low');
        $poHighConfigs = $configs->where('record_type', 'purchase_order_high');

        $projectId = session('current_project');
        $usersQuery = User::orderBy('name');
        if ($projectId) {
            $usersQuery->whereHas('projects', function ($q) use ($projectId) {
                $q->where('projects.id', $projectId);
            });
        }
        $users = $usersQuery->get();
        if ($users->isEmpty()) {
            $users = User::orderBy('name')->get();
        }

        $roles = Role::orderBy('name')->get();

        return view('erp.approval_configs.index', compact(
            'projectConfigs', 'nonProjectConfigs', 'poLowConfigs', 'poHighConfigs', 'users', 'roles'
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);

        $data = $request->validate([
            'record_type' => 'required|in:project,non_project,purchase_order_low,purchase_order_high',
            'level' => 'required|integer|min:1',
            'name' => 'required|string|max:100',
            'role_id' => 'nullable|exists:roles,id',
            'user_id' => 'nullable|exists:master.users,id',
        ]);

        if (empty($data['role_id']) && empty($data['user_id'])) {
            return back()->with('error', 'Silakan pilih Role atau User yang di-assign untuk Approval ini.')->withInput();
        }

        // Cek apakah level ini sudah ada untuk record_type ini
        $exists = ErpApprovalConfig::where('record_type', $data['record_type'])
            ->where('level', $data['level'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Level ' . $data['level'] . ' untuk ' . ucfirst($data['record_type']) . ' sudah digunakan. Silakan gunakan level lain.')->withInput();
        }

        ErpApprovalConfig::create($data);

        return back()->with('success', 'Konfigurasi Approval berhasil ditambahkan.');
    }

    public function destroy(ErpApprovalConfig $approval_config)
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);

        $approval_config->delete();

        return back()->with('success', 'Konfigurasi Approval berhasil dihapus.');
    }
}
