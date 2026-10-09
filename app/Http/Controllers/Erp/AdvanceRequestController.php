<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\AdvanceRequest;
use App\Models\Erp\BudgetPlanDetail;
use App\Models\Erp\ErpApproval;
use App\Models\Erp\ErpApprovalConfig;
use App\Models\Erp\ErpWorkItem;
use App\Helpers\NotificationHelper;
use App\Services\BudgetRollupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdvanceRequestController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('advance_requests.view'), 403);

        $advanceRequests = AdvanceRequest::with([
            'budgetPlanDetail.monthlyBudgetPlan.annualBudgetPlan.budgetParent',
            'work', 'requestBy',
        ])->latest()->paginate(20);

        return view('erp.budget.advance_requests.index', compact('advanceRequests'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('advance_requests.create'), 403);

        $details = BudgetPlanDetail::with('monthlyBudgetPlan.annualBudgetPlan.budgetParent')->get();
        $workItems = ErpWorkItem::orderBy('wid_code')->get();

        return view('erp.budget.advance_requests.create', compact('details', 'workItems'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('advance_requests.create'), 403);

        $data = $request->validate([
            'budget_plan_detail_id' => ['required', 'exists:erp_budget_plan_details,id'],
            'work_id' => ['required', 'exists:erp_work_items,id'],
            'budget_period' => ['required', 'date'],
            'advance_request_date' => ['required', 'date'],
            'number_of_site' => ['nullable', 'integer', 'min:1'],
            'amount_per_unit' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $totalAmount = !empty($data['number_of_site'])
            ? $data['amount_per_unit'] * $data['number_of_site']
            : $data['amount_per_unit'];

        $advanceRequest = $this->createWithGeneratedCode(array_merge($data, [
            'request_by_id' => auth()->id(),
            'total_amount_advance' => $totalAmount,
            'status' => 'Draft',
        ]));

        NotificationHelper::pushLive('advance_request', $advanceRequest->id, $advanceRequest->status);

        return redirect()
            ->route('erp.advance-requests.show', $advanceRequest)
            ->with('success', 'Advance Request berhasil dibuat.');
    }

    public function show(AdvanceRequest $advanceRequest)
    {
        abort_unless(auth()->user()->hasPermission('advance_requests.view'), 403);

        $advanceRequest->load([
            'budgetPlanDetail.monthlyBudgetPlan.annualBudgetPlan.budgetParent',
            'work', 'requestBy', 'paidBy',
            'approvals.assignedUser', 'approvals.actualApprover',
        ]);

        return view('erp.budget.advance_requests.show', compact('advanceRequest'));
    }

    public function submit(AdvanceRequest $advanceRequest)
    {
        abort_unless(auth()->user()->hasPermission('advance_requests.create'), 403);

        return DB::transaction(function () use ($advanceRequest) {
            $advanceRequest = AdvanceRequest::where('id', $advanceRequest->id)->lockForUpdate()->firstOrFail();

            if ($advanceRequest->status !== 'Draft') {
                return redirect()->back()->with('error', 'Hanya Advance Request berstatus Draft yang bisa disubmit.');
            }

            if ($advanceRequest->approvals()->count() > 0) {
                return redirect()->back()->with('error', 'Advance Request sudah pernah disubmit.');
            }

            $configs = ErpApprovalConfig::where('record_type', 'advance_request')
                ->orderBy('level')
                ->get();

            if ($configs->isEmpty()) {
                return redirect()->back()->with('error', 'Approval flow is not configured for this record type.');
            }

            $isFirst = true;
            $firstApproverId = null;
            foreach ($configs as $config) {
                ErpApproval::create([
                    'advance_request_id' => $advanceRequest->id,
                    'level' => $config->level,
                    'assigned_to_role_id' => $config->role_id,
                    'assigned_to_user_id' => $config->user_id,
                    'status' => $isFirst ? 'Pending' : 'Waiting',
                ]);
                if ($isFirst) {
                    $firstApproverId = $config->user_id;
                }
                $isFirst = false;
            }

            $advanceRequest->update(['status' => 'Submitted']);

            NotificationHelper::pushLive('advance_request', $advanceRequest->id, $advanceRequest->status);

            if ($firstApproverId) {
                NotificationHelper::send(
                    $firstApproverId,
                    'advance_request_submitted',
                    'Kasbon Menunggu Persetujuan',
                    "Advance Request {$advanceRequest->code} menunggu persetujuan Anda.",
                    route('erp.advance-requests.show', $advanceRequest),
                    'advance_request',
                    $advanceRequest->id
                );
            }

            return redirect()->back()->with('success', 'Advance Request submitted for approval.');
        });
    }

    public function approve(Request $request, ErpApproval $approval)
    {
        abort_unless(auth()->user()->hasPermission('advance_requests.approve'), 403);

        $request->validate(['comments' => 'nullable|string']);

        return DB::transaction(function () use ($request, $approval) {
            $approval = ErpApproval::where('id', $approval->id)->lockForUpdate()->firstOrFail();

            if ($approval->status !== 'Pending') {
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
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menyetujui tahap ini.');
            }

            $advanceRequest = $approval->advance_request_id
                ? AdvanceRequest::where('id', $approval->advance_request_id)->lockForUpdate()->first()
                : null;

            // Segregation of duties: the requester cannot also approve their own kasbon.
            if (!$isSuperadmin && $advanceRequest && $advanceRequest->request_by_id == $user->id) {
                return redirect()->back()->with('error', 'Anda tidak dapat menyetujui Advance Request yang Anda ajukan sendiri.');
            }

            $approval->update([
                'status' => 'Approved',
                'comments' => $request->input('comments'),
                'actual_approver_id' => $user->id,
                'approved_at' => now(),
                'is_override' => $isSuperadmin && !$isDesignatedApprover,
            ]);

            if ($advanceRequest) {
                $nextApproval = $advanceRequest->approvals()->where('status', 'Waiting')->orderBy('level')->lockForUpdate()->first();

                if ($nextApproval) {
                    $nextApproval->update(['status' => 'Pending']);

                    NotificationHelper::pushLive('advance_request', $advanceRequest->id, $advanceRequest->status);

                    if ($nextApproval->assigned_to_user_id) {
                        NotificationHelper::send(
                            $nextApproval->assigned_to_user_id,
                            'advance_request_submitted',
                            'Kasbon Menunggu Persetujuan',
                            "Advance Request {$advanceRequest->code} menunggu persetujuan Anda.",
                            route('erp.advance-requests.show', $advanceRequest),
                            'advance_request',
                            $advanceRequest->id
                        );
                    }
                } else {
                    // Fully approved — per spec, actual_amount defaults to what was
                    // requested; Finance can still adjust it before marking Paid.
                    $advanceRequest->update([
                        'status' => 'Approved',
                        'actual_amount' => $advanceRequest->total_amount_advance,
                    ]);

                    BudgetRollupService::recalculateDetail($advanceRequest->budget_plan_detail_id);

                    NotificationHelper::pushLive('advance_request', $advanceRequest->id, $advanceRequest->status);

                    if ($advanceRequest->request_by_id) {
                        NotificationHelper::send(
                            $advanceRequest->request_by_id,
                            'advance_request_approved',
                            'Kasbon Disetujui',
                            "Advance Request {$advanceRequest->code} telah disetujui sepenuhnya.",
                            route('erp.advance-requests.show', $advanceRequest),
                            'advance_request',
                            $advanceRequest->id
                        );
                    }
                    NotificationHelper::notifyFinance(
                        'advance_request_ready_to_pay',
                        'Kasbon Siap Dibayar',
                        "Advance Request {$advanceRequest->code} sudah disetujui, siap diproses pembayarannya.",
                        route('erp.advance-requests.show', $advanceRequest),
                        'advance_request',
                        $advanceRequest->id
                    );
                }
            }

            return redirect()->back()->with('success', 'Advance Request berhasil disetujui.');
        });
    }

    public function reject(Request $request, ErpApproval $approval)
    {
        abort_unless(auth()->user()->hasPermission('advance_requests.approve'), 403);

        $request->validate(['reason' => 'required|string|max:500']);

        return DB::transaction(function () use ($request, $approval) {
            $approval = ErpApproval::where('id', $approval->id)->lockForUpdate()->firstOrFail();

            if ($approval->status !== 'Pending') {
                return redirect()->back()->with('error', 'Tahap approval ini sudah diproses sebelumnya.');
            }

            $user = auth()->user();
            $isAuthorized = $user->hasRole('superadmin');

            if (!$isAuthorized && $approval->assigned_to_user_id) {
                $isAuthorized = ($user->id == $approval->assigned_to_user_id);
            } elseif (!$isAuthorized && $approval->assigned_to_role_id) {
                $isAuthorized = DB::connection('master')
                    ->table('role_user')
                    ->where('user_id', $user->id)
                    ->where('role_id', $approval->assigned_to_role_id)
                    ->exists();
            }

            if (!$isAuthorized) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menolak tahap ini.');
            }

            $approval->update([
                'status' => 'Rejected',
                'comments' => $request->input('reason'),
                'actual_approver_id' => $user->id,
                'approved_at' => now(),
            ]);

            $advanceRequest = $approval->advance_request_id
                ? AdvanceRequest::where('id', $approval->advance_request_id)->lockForUpdate()->first()
                : null;

            if ($advanceRequest) {
                $advanceRequest->approvals()->where('status', 'Waiting')->update(['status' => 'Cancelled']);
                $advanceRequest->update(['status' => 'Rejected']);

                NotificationHelper::pushLive('advance_request', $advanceRequest->id, $advanceRequest->status);

                if ($advanceRequest->request_by_id) {
                    NotificationHelper::send(
                        $advanceRequest->request_by_id,
                        'advance_request_rejected',
                        'Kasbon Ditolak',
                        "Advance Request {$advanceRequest->code} ditolak. Alasan: {$request->input('reason')}",
                        route('erp.advance-requests.show', $advanceRequest),
                        'advance_request',
                        $advanceRequest->id
                    );
                }
            }

            return redirect()->back()->with('success', 'Advance Request berhasil ditolak.');
        });
    }

    public function pay(AdvanceRequest $advanceRequest)
    {
        $user = auth()->user();

        if (!$user->hasRole(['finance', 'superadmin'])) {
            return redirect()->back()->with('error', 'Hanya Finance atau Superadmin yang berhak memproses pembayaran kasbon.');
        }

        return DB::transaction(function () use ($advanceRequest, $user) {
            $advanceRequest = AdvanceRequest::where('id', $advanceRequest->id)->lockForUpdate()->firstOrFail();

            if ($advanceRequest->status !== 'Approved') {
                return redirect()->back()->with('error', 'Hanya Advance Request berstatus Approved yang bisa dibayar.');
            }

            if ($advanceRequest->payment_status === 'Paid') {
                return redirect()->back()->with('error', 'Advance Request ini sudah dibayar.');
            }

            $advanceRequest->update([
                'payment_status' => 'Paid',
                'payment_date' => now(),
                'paid_by_id' => $user->id,
            ]);

            NotificationHelper::pushLive('advance_request', $advanceRequest->id, $advanceRequest->status);

            if ($advanceRequest->request_by_id) {
                NotificationHelper::send(
                    $advanceRequest->request_by_id,
                    'advance_request_paid',
                    'Kasbon Sudah Dibayar',
                    "Advance Request {$advanceRequest->code} telah dibayar.",
                    route('erp.advance-requests.show', $advanceRequest),
                    'advance_request',
                    $advanceRequest->id
                );
            }

            return redirect()->back()->with('success', 'Advance Request ditandai sudah dibayar.');
        });
    }

    /**
     * Locks the matching rows before reading the current max code, then creates
     * inside the same transaction — unlike RequestFormController's generator,
     * two concurrent submits can't both land on the same ADVREQ-NNNNN number.
     * The retry loop only matters for the very first row (nothing to lock yet).
     */
    private function createWithGeneratedCode(array $data): AdvanceRequest
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $prefix = 'ADVREQ-';
                    $latest = AdvanceRequest::withTrashed()
                        ->where('code', 'like', $prefix.'%')
                        ->lockForUpdate()
                        ->orderByRaw('CAST(SUBSTRING(code, '.(strlen($prefix) + 1).') AS UNSIGNED) DESC')
                        ->value('code');

                    $num = 0;
                    if ($latest && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $latest, $m)) {
                        $num = (int) $m[1];
                    }

                    $data['code'] = $prefix.str_pad($num + 1, 5, '0', STR_PAD_LEFT);

                    return AdvanceRequest::create($data);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempt === 2) {
                    throw $e;
                }
            }
        }
    }
}
