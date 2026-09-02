<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ErpSubProject;
use App\Models\Erp\ErpBudgetParent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ErpSubProjectController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('budgets.view'), 403);
        $budgetParents = ErpBudgetParent::orderBy('name')->get();
        return view('erp.sub_projects.index', compact('budgetParents'));
    }

    public function datatable(Request $r)
    {
        abort_unless(auth()->user()->hasPermission('budgets.view'), 403);
        $columns = ['id', 'budget_parent_id', 'sub_project_code', 'name', 'updated_at'];

        $draw        = (int) $r->input('draw', 1);
        $start       = (int) $r->input('start', 0);
        $length      = (int) $r->input('length', 10);
        $orderColIdx = (int) $r->input('order.0.column', 0);
        $orderDir    = $r->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderCol    = $columns[$orderColIdx] ?? 'id';
        $search      = trim((string) $r->input('search.value', ''));

        $base = ErpSubProject::with('budgetParent');

        $recordsTotal = (clone $base)->count();

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('sub_project_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('budgetParent', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderBy($orderCol, $orderDir)
            ->skip($start)->take($length)->get()
            ->map(function ($c, $i) use ($start) {
                $editBtn = auth()->user()->hasPermission('budgets.update')
                    ? '<button class="btn btn-sm btn-warning text-white me-1" onclick="openEdit('.$c->id.',\''.$c->budget_parent_id.'\',\''.addslashes(e($c->sub_project_code)).'\',\''.addslashes(e($c->name)).'\')"><i class="bx bx-edit-alt"></i></button>'
                    : '';

                $deleteBtn = auth()->user()->hasPermission('budgets.delete')
                    ? '<button class="btn btn-sm btn-danger" onclick="deleteItem('.$c->id.')"><i class="bx bx-trash"></i></button>'
                    : '';

                return [
                    'rownum'           => $start + $i + 1,
                    'budget_parent'    => e($c->budgetParent->name ?? '-'),
                    'sub_project_code' => e($c->sub_project_code),
                    'name'             => e($c->name),
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
            'budget_parent_id'  => ['required', 'exists:tenant.erp_budget_parents,id'],
            'sub_project_code'  => ['required', 'max:50', Rule::unique('tenant.erp_sub_projects', 'sub_project_code')],
            'name'              => ['required', 'max:255'],
        ]);

        ErpSubProject::create($data);

        return response()->json(['success' => 'Sub Project created successfully']);
    }

    public function update(Request $r, $id)
    {
        abort_unless(auth()->user()->hasPermission('budgets.update'), 403);
        $project = ErpSubProject::findOrFail($id);

        $data = $r->validate([
            'budget_parent_id'  => ['required', 'exists:tenant.erp_budget_parents,id'],
            'sub_project_code'  => ['required', 'max:50', Rule::unique('tenant.erp_sub_projects', 'sub_project_code')->ignore($project->id)],
            'name'              => ['required', 'max:255'],
        ]);

        $project->update($data);

        return response()->json(['success' => 'Sub Project updated successfully']);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->hasPermission('budgets.delete'), 403);
        ErpSubProject::findOrFail($id)->delete();

        return response()->json(['success' => 'Sub Project deleted successfully']);
    }
}
