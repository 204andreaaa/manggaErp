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

        $isProject = $requestForm->record_type === 'project' ? 1 : 0;
        $totalAmount = $requestForm->total_amount ?? 0;

        $configs = ErpApprovalConfig::where('record_type', 'request_form')
            ->where(function($q) use ($isProject) {
                $q->whereNull('is_project')->orWhere('is_project', $isProject);
            })
            ->where(function($q) use ($totalAmount) {
                $q->whereNull('min_amount')->orWhere('min_amount', '<=', $totalAmount);
            })
            ->where(function($q) use ($totalAmount) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $totalAmount);
            })
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
            // Check if user has this role in the current master DB
            // Using DB facade to avoid cross-connection relation issues just in case
            $hasRole = \Illuminate\Support\Facades\DB::connection('master')
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

    public function reject(Request $request, ErpApproval $approval)
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
            $hasRole = \Illuminate\Support\Facades\DB::connection('master')
                ->table('role_user')
                ->where('user_id', auth()->id())
                ->where('role_id', $approval->assigned_to_role_id)
                ->exists();
            if ($hasRole) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menolak tahap ini.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $approval->update([
                'status' => 'Rejected',
                'comments' => $request->input('reason'),
                'actual_approver_id' => auth()->id(),
                'approved_at' => now(),
            ]);

            $requestForm = $approval->requestForm;

            // Cancel any subsequent waiting steps
            if ($requestForm) {
                $requestForm->approvals()->where('status', 'Waiting')->update(['status' => 'Cancelled']);
                $requestForm->update(['status' => 'Rejected']);
            }

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->back()->with('success', 'Request Form berhasil ditolak (Rejected).');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menolak Request Form: ' . $e->getMessage());
        }
    }
}
