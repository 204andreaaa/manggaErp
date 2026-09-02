<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ErpBudgetParent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ErpBudgetParentController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('budgets.view'), 403);
        return view('erp.budget_parents.index');
    }

    public function datatable(Request $r)
    {
        abort_unless(auth()->user()->hasPermission('budgets.view'), 403);
        $columns = ['id', 'budget_code', 'name', 'total_budget', 'remaining_budget', 'status', 'updated_at'];

        $draw        = (int) $r->input('draw', 1);
        $start       = (int) $r->input('start', 0);
        $length      = (int) $r->input('length', 10);
        $orderColIdx = (int) $r->input('order.0.column', 0);
        $orderDir    = $r->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderCol    = $columns[$orderColIdx] ?? 'id';
        $search      = trim((string) $r->input('search.value', ''));

        $base = ErpBudgetParent::query();

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
                $editBtn = auth()->user()->hasPermission('budgets.update')
                    ? '<button class="btn btn-sm btn-warning text-white me-1" onclick="openEdit('.$c->id.',\''.addslashes(e($c->budget_code)).'\',\''.addslashes(e($c->name)).'\',\''.$c->total_budget.'\',\''.addslashes(e($c->status)).'\')"><i class="bx bx-edit-alt"></i></button>'
                    : '';

                $deleteBtn = auth()->user()->hasPermission('budgets.delete')
                    ? '<button class="btn btn-sm btn-danger" onclick="deleteItem('.$c->id.')"><i class="bx bx-trash"></i></button>'
                    : '';

                return [
                    'rownum'           => $start + $i + 1,
                    'budget_code'      => e($c->budget_code),
                    'name'             => e($c->name),
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

    public function store(Request $r)
    {
        abort_unless(auth()->user()->hasPermission('budgets.create'), 403);

        $data = $r->validate([
            'budget_code'  => ['required', 'max:50', Rule::unique('tenant.erp_budget_parents', 'budget_code')],
            'name'         => ['required', 'max:255'],
            'total_budget' => ['required', 'numeric', 'min:0'],
            'status'       => ['required', 'max:50'],
        ]);

        // When creating, remaining budget equals total budget initially
        $data['remaining_budget'] = $data['total_budget'];

        ErpBudgetParent::create($data);

        return response()->json(['success' => 'Budget Parent created successfully']);
    }

    public function update(Request $r, $id)
    {
        abort_unless(auth()->user()->hasPermission('budgets.update'), 403);
        $budget = ErpBudgetParent::findOrFail($id);

        $data = $r->validate([
            'budget_code'  => ['required', 'max:50', Rule::unique('tenant.erp_budget_parents', 'budget_code')->ignore($budget->id)],
            'name'         => ['required', 'max:255'],
            'total_budget' => ['required', 'numeric', 'min:0'],
            'status'       => ['required', 'max:50'],
        ]);

        // Recalculate remaining budget if total budget changes (simplified logic for now)
        // In a real strict system, we might need to check if total_budget < (original_total - original_remaining)
        $diff = $data['total_budget'] - $budget->total_budget;
        $data['remaining_budget'] = $budget->remaining_budget + $diff;

        if ($data['remaining_budget'] < 0) {
            return response()->json(['message' => 'Total budget cannot be less than already used budget'], 422);
        }

        $budget->update($data);

        return response()->json(['success' => 'Budget Parent updated successfully']);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->hasPermission('budgets.delete'), 403);
        ErpBudgetParent::findOrFail($id)->delete();

        return response()->json(['success' => 'Budget Parent deleted successfully']);
    }
}
