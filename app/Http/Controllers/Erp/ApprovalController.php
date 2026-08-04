<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Erp\RequestForm;
use App\Models\Erp\ErpApproval;
use App\Models\Erp\ErpApprovalConfig;

class ApprovalController extends Controller
{
    public function submit(RequestForm $requestForm)
    {
        if ($requestForm->approvals()->count() > 0) {
            return redirect()->back()->with('error', 'Request Form is already submitted for approval.');
        }

        if ($requestForm->items()->count() === 0) {
            return redirect()->back()->with('error', 'Request Form tidak bisa disubmit karena belum ada item (RF Line) yang ditambahkan.');
        }

        $configs = ErpApprovalConfig::where('record_type', $requestForm->record_type)
            ->orderBy('level')
            ->get();

        if ($configs->isEmpty()) {
            return redirect()->back()->with('error', 'Approval flow is not configured for this record type.');
        }

        $isFirst = true;
        foreach ($configs as $config) {
            ErpApproval::create([
                'request_form_id' => $requestForm->id,
                'level' => $config->level,
                'assigned_to_role_id' => $config->role_id,
                'assigned_to_user_id' => $config->user_id,
                'status' => $isFirst ? 'Pending' : 'Waiting',
            ]);
            $isFirst = false;
        }

        $requestForm->update(['status' => 'Submitted']);

        return redirect()->back()->with('success', 'Request Form submitted for approval successfully.');
    }

    public function approve(Request $request, ErpApproval $approval)
    {
        // Validation for authorization
        $isAuthorized = false;
        
        if (auth()->user()->hasRole('superadmin')) {
            $isAuthorized = true;
        } elseif ($approval->assigned_to_user_id) {
            if (auth()->id() == $approval->assigned_to_user_id) {
                $isAuthorized = true;
            }
        } elseif ($approval->assigned_to_role_id) {
            // Check if user has this role in the current tenant DB
            // Using DB facade to avoid cross-connection relation issues just in case
            $hasRole = \Illuminate\Support\Facades\DB::connection('tenant')
                ->table('role_user')
                ->where('user_id', auth()->id())
                ->where('role_id', $approval->assigned_to_role_id)
                ->exists();
            if ($hasRole) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menyetujui tahap ini.');
        }

        $request->validate([
            'comments' => 'nullable|string',
        ]);

        $approval->update([
            'status' => 'Approved',
            'comments' => $request->input('comments'),
            'actual_approver_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        $requestForm = $approval->requestForm;

        // Trigger next approval step if any
        $nextApproval = $requestForm->approvals()->where('status', 'Waiting')->orderBy('level')->first();
        
        if ($nextApproval) {
            $nextApproval->update(['status' => 'Pending']);
        } else {
            // All approved
            $requestForm->update(['status' => 'Approved']);
        }

        return redirect()->back()->with('success', 'Approval berhasil disubmit.');
    }
}
