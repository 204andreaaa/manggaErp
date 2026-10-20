<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\AdvanceRequest;
use App\Models\Erp\BudgetPlanDetail;
use App\Models\Erp\ErpApproval;
use App\Models\Erp\ErpApprovalConfig;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\ExpenseDeclaration;
use App\Helpers\NotificationHelper;
use App\Services\BudgetRollupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseDeclarationController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('expense_declarations.view'), 403);

        $declarations = ExpenseDeclaration::with([
            'budgetPlanDetail.monthlyBudgetPlan.annualBudgetPlan.budgetParent',
            'advanceRequest', 'work', 'requestBy',
        ])->latest()->paginate(20);

        return view('erp.budget.expense_declarations.index', compact('declarations'));
    }

    // Same rows (and order) as the Salesforce "New Expense Declaration" form.
    public const EXPENSE_TYPES = [
        'Personnel', 'Materials', 'Materials-Subcon', 'Transportation & Telecommunication',
        'Office', 'Utilities', 'Other Expense',
    ];

    public function create(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('expense_declarations.create'), 403);

        $advanceRequests = AdvanceRequest::with(['budgetPlanDetail.monthlyBudgetPlan.annualBudgetPlan', 'work'])
            ->where('status', 'Approved')
            ->where('payment_status', 'Paid')
            ->latest()
            ->get();

        $preselectedAdvance = $request->query('advance_request_id');

        return view('erp.budget.expense_declarations.create', array_merge(
            AdvanceRequestController::budgetFormOptions(),
            [
                'expenseTypes' => self::EXPENSE_TYPES,
                'advanceRequests' => $advanceRequests,
                'preselectedAdvance' => $advanceRequests->firstWhere('id', (int) $preselectedAdvance) ? (int) $preselectedAdvance : null,
            ]
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('expense_declarations.create'), 403);

        $withoutAdvance = $request->input('record_type') === 'without_advance';

        $data = $request->validate([
            'record_type' => ['required', 'in:with_advance,without_advance'],
            'advance_request_id' => ['nullable', 'exists:erp_advance_requests,id'],
            'budget_parent_id' => [$withoutAdvance ? 'required' : 'nullable', 'exists:erp_budget_parents,id'],
            'area' => [$withoutAdvance ? 'required' : 'nullable', 'string', 'max:150'],
            'work_type' => [$withoutAdvance ? 'required' : 'nullable', 'string', 'max:80'],
            'work_subtype' => ['nullable', 'string', 'max:150'],
            'work_id' => [$withoutAdvance ? 'required' : 'nullable', 'exists:erp_work_items,id'],
            'request_by_id' => ['required', 'integer', 'exists:master.users,id'],
            'request_date' => ['required', 'date'],
            'vehicle_info' => ['nullable', 'string', 'max:150'],
            'site_name' => ['nullable', 'string', 'max:150'],
            'po_number' => ['nullable', 'string', 'max:80'],
            'lines' => ['required', 'array'],
            'lines.*.checked' => ['nullable', 'boolean'],
            'lines.*.amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string'],
        ]);

        $this->assertRecordTypeRules($data);

        $lines = AdvanceRequestController::checkedLines($data['lines'], self::EXPENSE_TYPES);

        if ($withoutAdvance) {
            $work = ErpWorkItem::with('subProject')->findOrFail($data['work_id']);
            if ((int) $work->subProject?->budget_parent_id !== (int) $data['budget_parent_id']) {
                throw ValidationException::withMessages([
                    'work_id' => "WID {$work->wid_code} bukan milik Account (Budget Parent) yang dipilih.",
                ]);
            }
            $workId = $work->id;
            $monthly = \App\Services\BudgetDetailResolver::monthlyFor(
                $data['budget_parent_id'], $data['area'], $data['work_type'], $data['work_subtype'] ?? null, $data['request_date']
            );
        } else {
            // A declaration against a kasbon always lands in the kasbon's own month,
            // Account and WID — whatever the form posted for those is ignored.
            $advanceRequest = AdvanceRequest::with('budgetPlanDetail.monthlyBudgetPlan')->findOrFail($data['advance_request_id']);
            $workId = $advanceRequest->work_id;
            $monthly = $advanceRequest->budgetPlanDetail->monthlyBudgetPlan;
        }

        foreach ($lines as $type => $line) {
            $lines[$type]['detail'] = \App\Services\BudgetDetailResolver::detailFor($monthly, $type);
        }

        $created = DB::transaction(function () use ($data, $lines, $workId, $withoutAdvance) {
            $created = [];
            foreach ($lines as $line) {
                $created[] = $this->createWithGeneratedCode([
                    'record_type' => $data['record_type'],
                    'budget_plan_detail_id' => $line['detail']->id,
                    'advance_request_id' => $withoutAdvance ? null : $data['advance_request_id'],
                    'work_id' => $workId,
                    'site_name' => $data['site_name'] ?? null,
                    'is_indirect' => (bool) $line['detail']->is_indirect,
                    'po_number' => $withoutAdvance ? ($data['po_number'] ?? null) : null,
                    'vehicle_info' => $data['vehicle_info'] ?? null,
                    'request_by_id' => $data['request_by_id'],
                    'request_date' => $data['request_date'],
                    'description' => $line['description'] ?? null,
                    'amount_declaration' => $line['amount'],
                    'currency' => 'IDR',
                    'exchange_rate' => 1,
                    'converted_amount_idr' => $line['amount'],
                    'status' => 'Draft',
                ]);
            }
            return $created;
        });

        foreach ($created as $declaration) {
            NotificationHelper::pushLive('expense_declaration', $declaration->id, $declaration->status);
        }

        $message = count($created) === 1
            ? 'Expense Declaration berhasil dibuat.'
            : count($created).' Expense Declaration berhasil dibuat (satu per Expense Type): '.collect($created)->pluck('code')->implode(', ').'.';

        if (!$withoutAdvance) {
            return redirect()->route('erp.advance-requests.show', $data['advance_request_id'])->with('success', $message);
        }

        return count($created) === 1
            ? redirect()->route('erp.expense-declarations.show', $created[0])->with('success', $message)
            : redirect()->route('erp.expense-declarations.index')->with('success', $message);
    }

    public function show(ExpenseDeclaration $expenseDeclaration)
    {
        abort_unless(auth()->user()->hasPermission('expense_declarations.view'), 403);

        $expenseDeclaration->load([
            'budgetPlanDetail.monthlyBudgetPlan.annualBudgetPlan.budgetParent',
            'advanceRequest', 'work', 'requestBy',
            'approvals.assignedUser', 'approvals.actualApprover',
        ]);

        return view('erp.budget.expense_declarations.show', compact('expenseDeclaration'));
    }

    public function submit(ExpenseDeclaration $expenseDeclaration)
    {
        abort_unless(auth()->user()->hasPermission('expense_declarations.create'), 403);

        return DB::transaction(function () use ($expenseDeclaration) {
            $expenseDeclaration = ExpenseDeclaration::where('id', $expenseDeclaration->id)->lockForUpdate()->firstOrFail();

            if ($expenseDeclaration->status !== 'Draft') {
                return redirect()->back()->with('error', 'Hanya Expense Declaration berstatus Draft yang bisa disubmit.');
            }

            if ($expenseDeclaration->approvals()->count() > 0) {
                return redirect()->back()->with('error', 'Expense Declaration sudah pernah disubmit.');
            }

            $configs = ErpApprovalConfig::where('record_type', 'expense_declaration')
                ->orderBy('level')
                ->get();

            if ($configs->isEmpty()) {
                return redirect()->back()->with('error', 'Approval flow is not configured for this record type.');
            }

            $isFirst = true;
            $firstApproverId = null;
            foreach ($configs as $config) {
                ErpApproval::create([
                    'expense_declaration_id' => $expenseDeclaration->id,
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

            $expenseDeclaration->update(['status' => 'Submitted']);

            NotificationHelper::pushLive('expense_declaration', $expenseDeclaration->id, $expenseDeclaration->status);

            if ($firstApproverId) {
                NotificationHelper::send(
                    $firstApproverId,
                    'expense_declaration_submitted',
                    'Deklarasi Menunggu Persetujuan',
                    "Expense Declaration {$expenseDeclaration->code} menunggu persetujuan Anda.",
                    route('erp.expense-declarations.show', $expenseDeclaration),
                    'expense_declaration',
                    $expenseDeclaration->id
                );
            }

            return redirect()->back()->with('success', 'Expense Declaration submitted for approval.');
        });
    }

    public function approve(Request $request, ErpApproval $approval)
    {
        abort_unless(auth()->user()->hasPermission('expense_declarations.approve'), 403);

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

            $declaration = $approval->expense_declaration_id
                ? ExpenseDeclaration::where('id', $approval->expense_declaration_id)->lockForUpdate()->first()
                : null;

            if (!$isSuperadmin && $declaration && $declaration->request_by_id == $user->id) {
                return redirect()->back()->with('error', 'Anda tidak dapat menyetujui Expense Declaration yang Anda ajukan sendiri.');
            }

            $approval->update([
                'status' => 'Approved',
                'comments' => $request->input('comments'),
                'actual_approver_id' => $user->id,
                'approved_at' => now(),
                'is_override' => $isSuperadmin && !$isDesignatedApprover,
            ]);

            if ($declaration) {
                $nextApproval = $declaration->approvals()->where('status', 'Waiting')->orderBy('level')->lockForUpdate()->first();

                if ($nextApproval) {
                    $nextApproval->update(['status' => 'Pending']);

                    NotificationHelper::pushLive('expense_declaration', $declaration->id, $declaration->status);

                    if ($nextApproval->assigned_to_user_id) {
                        NotificationHelper::send(
                            $nextApproval->assigned_to_user_id,
                            'expense_declaration_submitted',
                            'Deklarasi Menunggu Persetujuan',
                            "Expense Declaration {$declaration->code} menunggu persetujuan Anda.",
                            route('erp.expense-declarations.show', $declaration),
                            'expense_declaration',
                            $declaration->id
                        );
                    }
                } else {
                    $declaration->update(['status' => 'Approved']);

                    BudgetRollupService::recalculateDetail($declaration->budget_plan_detail_id);

                    if ($declaration->record_type === 'with_advance' && $declaration->advance_request_id) {
                        BudgetRollupService::recalculateAdvanceRequest($declaration->advance_request_id);
                    }

                    NotificationHelper::pushLive('expense_declaration', $declaration->id, $declaration->status);

                    if ($declaration->request_by_id) {
                        NotificationHelper::send(
                            $declaration->request_by_id,
                            'expense_declaration_approved',
                            'Deklarasi Disetujui',
                            "Expense Declaration {$declaration->code} telah disetujui.",
                            route('erp.expense-declarations.show', $declaration),
                            'expense_declaration',
                            $declaration->id
                        );
                    }
                }
            }

            return redirect()->back()->with('success', 'Expense Declaration berhasil disetujui.');
        });
    }

    public function reject(Request $request, ErpApproval $approval)
    {
        abort_unless(auth()->user()->hasPermission('expense_declarations.approve'), 403);

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

            $declaration = $approval->expense_declaration_id
                ? ExpenseDeclaration::where('id', $approval->expense_declaration_id)->lockForUpdate()->first()
                : null;

            if ($declaration) {
                $declaration->approvals()->where('status', 'Waiting')->update(['status' => 'Cancelled']);
                $declaration->update(['status' => 'Rejected']);

                NotificationHelper::pushLive('expense_declaration', $declaration->id, $declaration->status);

                if ($declaration->request_by_id) {
                    NotificationHelper::send(
                        $declaration->request_by_id,
                        'expense_declaration_rejected',
                        'Deklarasi Ditolak',
                        "Expense Declaration {$declaration->code} ditolak. Alasan: {$request->input('reason')}",
                        route('erp.expense-declarations.show', $declaration),
                        'expense_declaration',
                        $declaration->id
                    );
                }
            }

            return redirect()->back()->with('success', 'Expense Declaration berhasil ditolak.');
        });
    }

    private function assertRecordTypeRules(array $data): void
    {
        if ($data['record_type'] === 'with_advance') {
            if (empty($data['advance_request_id'])) {
                throw ValidationException::withMessages([
                    'advance_request_id' => 'Deklarasi with_advance wajib memilih kasbon (Advance Request).',
                ]);
            }

            $advanceRequest = AdvanceRequest::find($data['advance_request_id']);
            if (!$advanceRequest || $advanceRequest->status !== 'Approved' || $advanceRequest->payment_status !== 'Paid') {
                throw ValidationException::withMessages([
                    'advance_request_id' => 'Kasbon yang dipilih harus berstatus Approved dan sudah Paid.',
                ]);
            }
        } else {
            if (!empty($data['advance_request_id'])) {
                throw ValidationException::withMessages([
                    'advance_request_id' => 'Deklarasi without_advance tidak boleh menunjuk kasbon.',
                ]);
            }
        }
    }

    private function createWithGeneratedCode(array $data): ExpenseDeclaration
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $prefix = 'EXP'.now()->format('ym').'-';
                    $latest = ExpenseDeclaration::withTrashed()
                        ->where('code', 'like', $prefix.'%')
                        ->lockForUpdate()
                        ->orderByRaw('CAST(SUBSTRING(code, '.(strlen($prefix) + 1).') AS UNSIGNED) DESC')
                        ->value('code');

                    $num = 0;
                    if ($latest && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $latest, $m)) {
                        $num = (int) $m[1];
                    }

                    $data['code'] = $prefix.str_pad($num + 1, 7, '0', STR_PAD_LEFT);

                    return ExpenseDeclaration::create($data);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempt === 2) {
                    throw $e;
                }
            }
        }
    }
}
