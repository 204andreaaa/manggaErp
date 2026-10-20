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

    // Same rows (and order) as the Salesforce "New Advance Request" form.
    public const EXPENSE_TYPES = [
        'Personnel', 'Materials', 'Transportation & Telecommunication',
        'Office', 'Utilities', 'Other Expense',
    ];

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('advance_requests.create'), 403);

        return view('erp.budget.advance_requests.create', array_merge(
            self::budgetFormOptions(),
            ['expenseTypes' => self::EXPENSE_TYPES]
        ));
    }

    /**
     * Dropdown data shared by the kasbon and declaration forms: Budget Parents,
     * the Area/Work Type/Subtype combos that actually have an Annual Budget Plan,
     * WIDs tagged with their Budget Parent, and users for "Request By".
     */
    public static function budgetFormOptions(): array
    {
        return [
            'budgetParents' => \App\Models\Erp\ErpBudgetParent::orderBy('name')->get(['id', 'budget_code', 'name']),
            'annualPlans' => \App\Models\Erp\AnnualBudgetPlan::orderBy('year')
                ->get(['id', 'budget_parent_id', 'year', 'area', 'work_type', 'work_subtype']),
            'workItems' => ErpWorkItem::with('subProject:id,budget_parent_id')->orderBy('wid_code')->get(),
            'users' => \App\Models\User::orderBy('name')->get(['id', 'name']),
        ];
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('advance_requests.create'), 403);

        $data = $request->validate([
            'advance_request_date' => ['required', 'date'],
            'budget_parent_id' => ['required', 'exists:erp_budget_parents,id'],
            'area' => ['required', 'string', 'max:150'],
            'work_type' => ['required', 'string', 'max:80'],
            'work_subtype' => ['nullable', 'string', 'max:150'],
            'work_id' => ['required', 'exists:erp_work_items,id'],
            'request_by_id' => ['required', 'integer', 'exists:master.users,id'],
            'number_of_site' => ['nullable', 'integer', 'min:1'],
            'expense_index' => ['required', 'integer', 'min:0', 'max:'.(count(self::EXPENSE_TYPES) - 1)],
            'lines' => ['required', 'array'],
            'lines.*.amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string'],
        ], [
            'expense_index.required' => 'Pilih satu Expense Type untuk kasbon ini.',
        ]);

        // One kasbon = one expense type (as in Salesforce); spreading the money
        // across several types happens later, in its Expense Declarations.
        $index = (int) $data['expense_index'];
        foreach ($data['lines'] as $i => $other) {
            if ((int) $i !== $index && (float) ($other['amount'] ?? 0) > 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'expense_index' => 'Satu kasbon hanya untuk 1 Expense Type. Kosongkan Amount di baris lain.',
                ]);
            }
        }
        $amount = (float) ($data['lines'][$index]['amount'] ?? 0);
        if ($amount <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                "lines.{$index}.amount" => 'Amount untuk '.self::EXPENSE_TYPES[$index].' wajib diisi lebih dari 0.',
            ]);
        }
        $expenseType = self::EXPENSE_TYPES[$index];

        $work = ErpWorkItem::with('subProject')->findOrFail($data['work_id']);
        if ((int) $work->subProject?->budget_parent_id !== (int) $data['budget_parent_id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'work_id' => "WID {$work->wid_code} bukan milik Account (Budget Parent) yang dipilih.",
            ]);
        }

        $monthly = \App\Services\BudgetDetailResolver::monthlyFor(
            $data['budget_parent_id'], $data['area'], $data['work_type'], $data['work_subtype'] ?? null, $data['advance_request_date']
        );
        $detail = \App\Services\BudgetDetailResolver::detailFor($monthly, $expenseType);

        $advanceRequest = $this->createWithGeneratedCode([
            'budget_plan_detail_id' => $detail->id,
            'work_id' => $data['work_id'],
            'budget_period' => $data['advance_request_date'],
            'advance_request_date' => $data['advance_request_date'],
            'request_by_id' => $data['request_by_id'],
            'number_of_site' => $data['number_of_site'] ?? null,
            'amount_per_unit' => $amount,
            'total_amount_advance' => !empty($data['number_of_site']) ? $amount * $data['number_of_site'] : $amount,
            'description' => $data['lines'][$index]['description'] ?? null,
            'status' => 'Draft',
        ]);

        NotificationHelper::pushLive('advance_request', $advanceRequest->id, $advanceRequest->status);

        return redirect()
            ->route('erp.advance-requests.show', $advanceRequest)
            ->with('success', 'Advance Request berhasil dibuat.');
    }

    /**
     * Keeps only the expense-type rows the user ticked (or filled an amount for),
     * keyed by expense type. Rejects the form if nothing usable was entered.
     */
    public static function checkedLines(array $lines, array $allowedTypes): array
    {
        $picked = [];
        foreach ($allowedTypes as $i => $type) {
            $line = $lines[$i] ?? [];
            $amount = (float) ($line['amount'] ?? 0);
            if (!empty($line['checked']) || $amount > 0) {
                if ($amount <= 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "lines.{$i}.amount" => "Amount untuk {$type} wajib diisi lebih dari 0.",
                    ]);
                }
                $picked[$type] = ['amount' => $amount, 'description' => $line['description'] ?? null];
            }
        }

        if (!$picked) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lines' => 'Isi minimal satu Expense Type (centang dan isi Amount-nya).',
            ]);
        }

        return $picked;
    }

    public function show(AdvanceRequest $advanceRequest)
    {
        abort_unless(auth()->user()->hasPermission('advance_requests.view'), 403);

        $advanceRequest->load([
            'budgetPlanDetail.monthlyBudgetPlan.annualBudgetPlan.budgetParent',
            'work', 'requestBy', 'paidBy',
            'approvals.assignedUser', 'approvals.actualApprover',
            'expenseDeclarations.budgetPlanDetail', 'expenseDeclarations.work',
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
