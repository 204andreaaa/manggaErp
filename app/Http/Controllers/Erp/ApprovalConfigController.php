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
        
        $rfConfigs = $configs->where('record_type', 'request_form');
        $poConfigs = $configs->where('record_type', 'purchase_order');
        $paConfigs = $configs->where('record_type', 'payment_advice');
        $poVerifConfigs = $configs->where('record_type', 'po_verification');
        $grVerifConfigs = $configs->where('record_type', 'gr_verification');

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

        $maxLevels = [
            'request_form' => $rfConfigs->max('level') ?? 0,
            'purchase_order' => $poConfigs->max('level') ?? 0,
            'payment_advice' => $paConfigs->max('level') ?? 0,
            'po_verification' => $poVerifConfigs->max('level') ?? 0,
            'gr_verification' => $grVerifConfigs->max('level') ?? 0,
        ];

        return view('erp.approval_configs.index', compact(
            'rfConfigs', 'poConfigs', 'paConfigs', 'poVerifConfigs', 'grVerifConfigs', 'users', 'maxLevels'
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);

        $data = $request->validate([
            'record_type' => 'required|in:request_form,purchase_order,payment_advice,po_verification,gr_verification',
            'level' => 'required|integer|min:1',
            'name' => 'required|string|max:100',
            'user_id' => 'required|exists:master.users,id',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'is_project' => 'nullable|in:1,0,',
        ]);

        if (isset($data['is_project']) && $data['is_project'] === '') {
            $data['is_project'] = null;
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
