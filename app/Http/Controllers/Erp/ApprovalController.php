<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Erp\RequestForm;
use App\Models\Erp\ErpApproval;
use App\Models\Erp\ErpApprovalConfig;

class ApprovalController extends Controller
{
    public function submit(RequestForm $requestForm)
    {
        if ($requestForm->items()->count() === 0) {
            return redirect()->back()->with('error', 'Request Form tidak bisa disubmit karena belum ada item (RF Line) yang ditambahkan.');
        }

        DB::beginTransaction();
        try {
            // Lock the RF row so two concurrent submits can't both pass the "already submitted" check.
            $requestForm = RequestForm::where('id', $requestForm->id)->lockForUpdate()->firstOrFail();

            if ($requestForm->approvals()->count() > 0) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Request Form is already submitted for approval.');
            }

            $isProject = $requestForm->record_type === 'project' ? 1 : 0;
            $totalAmount = $requestForm->total_amount ?? 0;

            $configs = ErpApprovalConfig::where('record_type', 'request_form')
                ->where(function ($q) use ($isProject) {
                    $q->whereNull('is_project')->orWhere('is_project', $isProject);
                })
                ->where(function ($q) use ($totalAmount) {
                    $q->whereNull('min_amount')->orWhere('min_amount', '<=', $totalAmount);
                })
                ->where(function ($q) use ($totalAmount) {
                    $q->whereNull('max_amount')->orWhere('max_amount', '>=', $totalAmount);
                })
                ->orderBy('level')
                ->get();

            if ($configs->isEmpty()) {
                DB::rollBack();
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

            DB::commit();
            return redirect()->back()->with('success', 'Request Form submitted for approval successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal submit Request Form: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, ErpApproval $approval)
    {
        $request->validate([
            'comments' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Re-fetch with a row lock so two concurrent approve/reject clicks on the
            // same step can't both succeed before either transaction commits.
            $approval = ErpApproval::where('id', $approval->id)->lockForUpdate()->firstOrFail();

            if ($approval->status !== 'Pending') {
                DB::rollBack();
                return redirect()->back()->with('error', 'Tahap approval ini sudah diproses sebelumnya.');
            }

            $user = auth()->user();
            $isSuperadmin = $user->hasRole('superadmin');
            $isDesignatedApprover = false;

            if ($approval->assigned_to_user_id) {
                $isDesignatedApprover = ($user->id == $approval->assigned_to_user_id);
            } elseif ($approval->assigned_to_role_id) {
                $isDesignatedApprover = DB::connection('master')
                    ->table('role_user')
                    ->where('user_id', $user->id)
                    ->where('role_id', $approval->assigned_to_role_id)
                    ->exists();
            }

            if (!$isSuperadmin && !$isDesignatedApprover) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menyetujui tahap ini.');
            }

            $requestForm = $approval->request_form_id
                ? RequestForm::where('id', $approval->request_form_id)->lockForUpdate()->first()
                : null;

            // Segregation of duties: the person who submitted the RF cannot also approve it.
            // Superadmin keeps its existing emergency-override privilege.
            if (!$isSuperadmin && $requestForm && $requestForm->created_by_id && $requestForm->created_by_id == $user->id) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Anda tidak dapat menyetujui Request Form yang Anda ajukan sendiri.');
            }

            $approval->update([
                'status' => 'Approved',
                'comments' => $request->input('comments'),
                'actual_approver_id' => $user->id,
                'approved_at' => now(),
                'is_override' => $isSuperadmin && !$isDesignatedApprover,
            ]);

            if ($requestForm) {
                $nextApproval = $requestForm->approvals()->where('status', 'Waiting')->orderBy('level')->lockForUpdate()->first();

                if ($nextApproval) {
                    $nextApproval->update(['status' => 'Pending']);
                } else {
                    $requestForm->update(['status' => 'Approved']);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Approval berhasil disubmit.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses approval: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, ErpApproval $approval)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $approval = ErpApproval::where('id', $approval->id)->lockForUpdate()->firstOrFail();

            if ($approval->status !== 'Pending') {
                DB::rollBack();
                return redirect()->back()->with('error', 'Tahap approval ini sudah diproses sebelumnya.');
            }

            $user = auth()->user();
            $isAuthorized = false;

            if ($user->hasRole('superadmin')) {
                $isAuthorized = true;
            } elseif ($approval->assigned_to_user_id) {
                if ($user->id == $approval->assigned_to_user_id) {
                    $isAuthorized = true;
                }
            } elseif ($approval->assigned_to_role_id) {
                $hasRole = DB::connection('master')
                    ->table('role_user')
                    ->where('user_id', $user->id)
                    ->where('role_id', $approval->assigned_to_role_id)
                    ->exists();
                if ($hasRole) {
                    $isAuthorized = true;
                }
            }

            if (!$isAuthorized) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menolak tahap ini.');
            }

            $approval->update([
                'status' => 'Rejected',
                'comments' => $request->input('reason'),
                'actual_approver_id' => $user->id,
                'approved_at' => now(),
            ]);

            $requestForm = $approval->request_form_id
                ? RequestForm::where('id', $approval->request_form_id)->lockForUpdate()->first()
                : null;

            // Cancel any subsequent waiting steps
            if ($requestForm) {
                $requestForm->approvals()->where('status', 'Waiting')->update(['status' => 'Cancelled']);
                $requestForm->update(['status' => 'Rejected']);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Request Form berhasil ditolak (Rejected).');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menolak Request Form: ' . $e->getMessage());
        }
    }
}
