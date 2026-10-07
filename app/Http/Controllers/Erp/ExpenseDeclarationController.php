<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\AdvanceRequest;
use App\Models\Erp\BudgetPlanDetail;
use App\Models\Erp\ErpApproval;
use App\Models\Erp\ErpApprovalConfig;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\ExpenseDeclaration;
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

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('expense_declarations.create'), 403);

        $details = BudgetPlanDetail::with('monthlyBudgetPlan.annualBudgetPlan.budgetParent')->get();
        $workItems = ErpWorkItem::orderBy('wid_code')->get();
        $advanceRequests = AdvanceRequest::where('status', 'Approved')
            ->where('payment_status', 'Paid')
            ->get();

        return view('erp.budget.expense_declarations.create', compact('details', 'workItems', 'advanceRequests'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('expense_declarations.create'), 403);

        $data = $request->validate([
            'record_type' => ['required', 'in:with_advance,without_advance'],
            'budget_plan_detail_id' => ['required', 'exists:erp_budget_plan_details,id'],
            'advance_request_id' => ['nullable', 'exists:erp_advance_requests,id'],
            'work_id' => ['required', 'exists:erp_work_items,id'],
            'site_name' => ['nullable', 'string', 'max:150'],
            'is_indirect' => ['nullable', 'boolean'],
            'po_number' => ['nullable', 'string', 'max:80'],
            'vehicle_info' => ['nullable', 'string', 'max:150'],
            'request_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'amount_declaration' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'rfin_ref' => ['nullable', 'string', 'max:120'],
            'remark_rf_line' => ['nullable', 'string', 'max:150'],
            'remark' => ['nullable', 'string'],
        ]);

        $this->assertRecordTypeRules($data);

        $currency = $data['currency'] ?? 'IDR';
        $exchangeRate = $data['exchange_rate'] ?? 1;

        $declaration = $this->createWithGeneratedCode(array_merge($data, [
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'converted_amount_idr' => $data['amount_declaration'] * $exchangeRate,
            'is_indirect' => (bool) ($data['is_indirect'] ?? false),
            'request_by_id' => auth()->id(),
            'status' => 'Draft',
        ]));

        return redirect()
            ->route('erp.expense-declarations.show', $declaration)
            ->with('success', 'Expense Declaration berhasil dibuat.');
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
            foreach ($configs as $config) {
                ErpApproval::create([
                    'expense_declaration_id' => $expenseDeclaration->id,
                    'level' => $config->level,
                    'assigned_to_role_id' => $config->role_id,
                    'assigned_to_user_id' => $config->user_id,
                    'status' => $isFirst ? 'Pending' : 'Waiting',
                ]);
                $isFirst = false;
            }

            $expenseDeclaration->update(['status' => 'Submitted']);

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
                } else {
                    $declaration->update(['status' => 'Approved']);

                    BudgetRollupService::recalculateDetail($declaration->budget_plan_detail_id);

                    if ($declaration->record_type === 'with_advance' && $declaration->advance_request_id) {
                        BudgetRollupService::recalculateAdvanceRequest($declaration->advance_request_id);
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
