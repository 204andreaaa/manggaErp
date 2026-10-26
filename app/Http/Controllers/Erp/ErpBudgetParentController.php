<?php

namespace App\Http\Controllers\Erp;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\Erp\AdvanceRequest;
use App\Models\Erp\ErpBudgetParent;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\ExpenseDeclaration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ErpBudgetParentController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('budget_parents.view') || auth()->user()->hasPermission('budgets.view'), 403);
        $users = User::orderBy('name')->get();
        return view('erp.budget_parents.index', compact('users'));
    }

    public function datatable(Request $r)
    {
        abort_unless(auth()->user()->hasPermission('budget_parents.view') || auth()->user()->hasPermission('budgets.view'), 403);
        $columns = ['id', 'budget_code', 'name', 'total_budget', 'remaining_budget', 'status', 'updated_at'];

        $draw        = (int) $r->input('draw', 1);
        $start       = (int) $r->input('start', 0);
        $length      = (int) $r->input('length', 10);
        $orderColIdx = (int) $r->input('order.0.column', 0);
        $orderDir    = $r->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderCol    = $columns[$orderColIdx] ?? 'id';
        $search      = trim((string) $r->input('search.value', ''));

        $base = ErpBudgetParent::with('kam');

        $recordsTotal = (clone $base)->count();

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('budget_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderBy($orderCol, $orderDir)
            ->skip($start)->take($length)->get()
            ->map(function ($c, $i) use ($start) {
                $editBtn = (auth()->user()->hasPermission('budget_parents.update') || auth()->user()->hasPermission('budgets.update'))
                    ? '<button class="btn btn-sm btn-warning text-white me-1" onclick="openEdit('.$c->id.','.js_attr($c->budget_code).','.js_attr($c->name).','.($c->kam_user_id ?: 'null').',\''.$c->total_budget.'\','.js_attr($c->status).')"><i class="bx bx-edit-alt"></i></button>'
                    : '';

                $deleteBtn = (auth()->user()->hasPermission('budget_parents.delete') || auth()->user()->hasPermission('budgets.delete'))
                    ? '<button class="btn btn-sm btn-danger" onclick="deleteItem('.$c->id.')"><i class="bx bx-trash"></i></button>'
                    : '';

                return [
                    'rownum'           => $start + $i + 1,
                    'budget_code'      => '<a href="'.e(route('erp.budget-parents.show', $c)).'">'.e($c->budget_code).'</a>',
                    'name'             => e($c->name),
                    'kam'              => e($c->kam->name ?? '-'),
                    'total_budget'     => number_format($c->total_budget, 2),
                    'remaining_budget' => number_format($c->remaining_budget, 2),
                    'status'           => e($c->status),
                    'actions'          => '<div class="text-center">'.$editBtn.$deleteBtn.'</div>',
                ];
            });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $rows,
        ]);
    }

    /**
     * Salesforce "Account" style page: the Budget Parent's fields plus related
     * lists for everything hanging off it (plans, sub projects, WIDs, kasbon,
     * declarations). Display only — no budget rule lives here.
     */
    public function show(ErpBudgetParent $budgetParent)
    {
        abort_unless(auth()->user()->hasPermission('budget_parents.view') || auth()->user()->hasPermission('budgets.view'), 403);

        $budgetParent->load([
            'kam',
            'subProjects' => fn ($q) => $q->withCount('workItems')->orderBy('sub_project_code'),
            'annualBudgetPlans' => fn ($q) => $q->with('workItem')->orderByDesc('year')->orderBy('area'),
        ]);

        $workItems = ErpWorkItem::with('subProject')
            ->whereHas('subProject', fn ($q) => $q->where('budget_parent_id', $budgetParent->id))
            ->orderBy('wid_code')
            ->get();
        $workIds = $workItems->pluck('id');

        $advanceRequests = AdvanceRequest::with(['budgetPlanDetail', 'work'])
            ->whereIn('work_id', $workIds)->latest()->get();
        $declarations = ExpenseDeclaration::with(['advanceRequest', 'work'])
            ->whereIn('work_id', $workIds)->latest()->get();

        return view('erp.budget_parents.show', compact('budgetParent', 'workItems', 'advanceRequests', 'declarations'));
    }

    public function store(Request $r)
    {
        abort_unless(auth()->user()->hasPermission('budget_parents.create') || auth()->user()->hasPermission('budgets.create'), 403);

        $data = $r->validate([
            'budget_code'  => ['required', 'max:50', Rule::unique('tenant.erp_budget_parents', 'budget_code')],
            'name'         => ['required', 'max:255'],
            'kam_user_id'  => ['nullable', 'integer'],
            'total_budget' => ['required', 'numeric', 'min:0'],
            'status'       => ['required', 'max:50'],
        ]);

        // When creating, remaining budget equals total budget initially
        $data['remaining_budget'] = $data['total_budget'];

        $budget = ErpBudgetParent::create($data);

        NotificationHelper::pushLive('budget_parent', $budget->id, 'created');

        return response()->json(['success' => 'Budget Parent created successfully']);
    }

    public function update(Request $r, $id)
    {
        abort_unless(auth()->user()->hasPermission('budget_parents.update') || auth()->user()->hasPermission('budgets.update'), 403);
        $budget = ErpBudgetParent::findOrFail($id);

        $data = $r->validate([
            'budget_code'  => ['required', 'max:50', Rule::unique('tenant.erp_budget_parents', 'budget_code')->ignore($budget->id)],
            'name'         => ['required', 'max:255'],
            'kam_user_id'  => ['nullable', 'integer'],
            'total_budget' => ['required', 'numeric', 'min:0'],
            'status'       => ['required', 'max:50'],
        ]);

        $allocated = $budget->allocatedToWorkItems();
        $data['remaining_budget'] = $data['total_budget'] - $allocated;

        if ($data['remaining_budget'] < 0) {
            return response()->json(['message' => 'Total Budget tidak boleh lebih kecil dari yang sudah dialokasikan ke WID (Rp '.number_format($allocated, 0, ',', '.').').'], 422);
        }

        $budget->update($data);

        NotificationHelper::pushLive('budget_parent', $budget->id, 'updated');

        return response()->json(['success' => 'Budget Parent updated successfully']);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->hasPermission('budget_parents.delete') || auth()->user()->hasPermission('budgets.delete'), 403);
        $budget = ErpBudgetParent::findOrFail($id);
        $budget->delete();

        NotificationHelper::pushLive('budget_parent', $budget->id, 'deleted');

        return response()->json(['success' => 'Budget Parent deleted successfully']);
    }
}
