<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\ErpSubProject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ErpWorkItemController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('budgets.view'), 403);
        
        $subProjects = ErpSubProject::with('budgetParent')->orderBy('name')->get()->map(function($sp) {
            $budgetParent = $sp->budgetParent;
            if ($budgetParent) {
                $totalAllocated = ErpWorkItem::whereHas('subProject', function($q) use ($budgetParent) {
                    $q->where('budget_parent_id', $budgetParent->id);
                })->sum('allocated_budget');
                $sp->available_budget = max(0, $budgetParent->total_budget - $totalAllocated);
            } else {
                $sp->available_budget = 0;
            }
            return $sp;
        });

        return view('erp.work_items.index', compact('subProjects'));
    }

    public function datatable(Request $r)
    {
        abort_unless(auth()->user()->hasPermission('budgets.view'), 403);
        $columns = ['id', 'sub_project_id', 'wid_code', 'name', 'allocated_budget', 'remaining_budget', 'updated_at'];

        $draw        = (int) $r->input('draw', 1);
        $start       = (int) $r->input('start', 0);
        $length      = (int) $r->input('length', 10);
        $orderColIdx = (int) $r->input('order.0.column', 0);
        $orderDir    = $r->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderCol    = $columns[$orderColIdx] ?? 'id';
        $search      = trim((string) $r->input('search.value', ''));

        $base = ErpWorkItem::with('subProject.budgetParent');

        $recordsTotal = (clone $base)->count();

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('wid_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('subProject', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderBy($orderCol, $orderDir)
            ->skip($start)->take($length)->get()
            ->map(function ($c, $i) use ($start) {
                $editBtn = auth()->user()->hasPermission('budgets.update')
                    ? '<button class="btn btn-sm btn-warning text-white me-1" onclick="openEdit('.$c->id.',\''.$c->sub_project_id.'\',\''.addslashes(e($c->wid_code)).'\',\''.addslashes(e($c->name)).'\',\''.$c->allocated_budget.'\')"><i class="bx bx-edit-alt"></i></button>'
                    : '';

                $deleteBtn = auth()->user()->hasPermission('budgets.delete')
                    ? '<button class="btn btn-sm btn-danger" onclick="deleteItem('.$c->id.')"><i class="bx bx-trash"></i></button>'
                    : '';

                return [
                    'rownum'           => $start + $i + 1,
                    'sub_project'      => e($c->subProject->name ?? '-'),
                    'wid_code'         => e($c->wid_code),
                    'name'             => e($c->name),
                    'allocated_budget' => number_format($c->allocated_budget, 2),
                    'remaining_budget' => number_format($c->remaining_budget, 2),
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

    public function getNextWidCode(Request $r)
    {
        $subProjectId = $r->input('sub_project_id');
        $subProject = ErpSubProject::find($subProjectId);
        if (!$subProject) {
            return response()->json(['wid_code' => '']);
        }

        $nextCode = $this->generateNextWidCode($subProject);
        return response()->json(['wid_code' => $nextCode]);
    }

    private function generateNextWidCode(ErpSubProject $subProject): string
    {
        $spCode = trim($subProject->sub_project_code);
        // Strip 'SP-' prefix if exists
        $cleanCode = preg_replace('/^SP[-_]?/i', '', $spCode);
        $cleanCode = strtoupper($cleanCode ?: 'PRJ');
        $prefix = 'WID-' . $cleanCode . '-';

        $latestWid = ErpWorkItem::withTrashed()
            ->where('wid_code', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(wid_code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->value('wid_code');

        $num = 0;
        if ($latestWid && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $latestWid, $match)) {
            $num = (int) $match[1];
        }

        return $prefix . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }

    public function store(Request $r)
    {
        abort_unless(auth()->user()->hasPermission('budgets.create'), 403);

        $subProject = ErpSubProject::with('budgetParent')->findOrFail($r->input('sub_project_id'));
        if (empty($r->input('wid_code'))) {
            $r->merge(['wid_code' => $this->generateNextWidCode($subProject)]);
        }

        $data = $r->validate([
            'sub_project_id'   => ['required', 'exists:tenant.erp_sub_projects,id'],
            'wid_code'         => ['required', 'max:50', Rule::unique('tenant.erp_work_items', 'wid_code')],
            'name'             => ['required', 'max:255'],
            'allocated_budget' => ['required', 'numeric', 'min:0', 'max:999999999999'],
        ]);

        DB::connection('tenant')->beginTransaction();
        try {
            // Cek apakah alokasi melebihi budget parent
            $budgetParent = $subProject->budgetParent;
            
            // Total alokasi semua WID di bawah Budget Parent ini
            $totalAllocated = ErpWorkItem::whereHas('subProject', function($q) use ($budgetParent) {
                $q->where('budget_parent_id', $budgetParent->id);
            })->sum('allocated_budget');

            $availableBudget = max(0, $budgetParent->total_budget - $totalAllocated);

            if (($totalAllocated + $data['allocated_budget']) > $budgetParent->total_budget) {
                $formattedTotal = number_format($budgetParent->total_budget, 0, ',', '.');
                $formattedAllocated = number_format($totalAllocated, 0, ',', '.');
                $formattedAvailable = number_format($availableBudget, 0, ',', '.');
                $formattedInput = number_format($data['allocated_budget'], 0, ',', '.');

                throw new \Exception("Alokasi Anggaran Melebihi Batas Pagu!\n\nInduk: {$budgetParent->name} ({$budgetParent->budget_code})\n• Total Pagu Induk: Rp {$formattedTotal}\n• Sudah Teralokasi: Rp {$formattedAllocated}\n• Sisa Pagu Tersedia: Rp {$formattedAvailable}\n\nAnda menginput Rp {$formattedInput}. Mohon masukkan nominal maksimal Rp {$formattedAvailable}.");
            }

            $data['remaining_budget'] = $data['allocated_budget'];
            ErpWorkItem::create($data);

            DB::connection('tenant')->commit();
            return response()->json(['success' => 'Work Item created successfully']);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $r, $id)
    {
        abort_unless(auth()->user()->hasPermission('budgets.update'), 403);
        $workItem = ErpWorkItem::findOrFail($id);

        $data = $r->validate([
            'sub_project_id'   => ['required', 'exists:tenant.erp_sub_projects,id'],
            'wid_code'         => ['required', 'max:50', Rule::unique('tenant.erp_work_items', 'wid_code')->ignore($workItem->id)],
            'name'             => ['required', 'max:255'],
            'allocated_budget' => ['required', 'numeric', 'min:0', 'max:999999999999'],
        ]);

        DB::connection('tenant')->beginTransaction();
        try {
            $subProject = ErpSubProject::with('budgetParent')->findOrFail($data['sub_project_id']);
            $budgetParent = $subProject->budgetParent;
            
            $totalAllocated = ErpWorkItem::where('id', '!=', $workItem->id)
                ->whereHas('subProject', function($q) use ($budgetParent) {
                    $q->where('budget_parent_id', $budgetParent->id);
                })->sum('allocated_budget');

            $availableBudget = max(0, $budgetParent->total_budget - $totalAllocated);

            if (($totalAllocated + $data['allocated_budget']) > $budgetParent->total_budget) {
                $formattedTotal = number_format($budgetParent->total_budget, 0, ',', '.');
                $formattedAllocated = number_format($totalAllocated, 0, ',', '.');
                $formattedAvailable = number_format($availableBudget, 0, ',', '.');
                $formattedInput = number_format($data['allocated_budget'], 0, ',', '.');

                throw new \Exception("Alokasi Anggaran Melebihi Batas Pagu!\n\nInduk: {$budgetParent->name} ({$budgetParent->budget_code})\n• Total Pagu Induk: Rp {$formattedTotal}\n• Sudah Teralokasi: Rp {$formattedAllocated}\n• Sisa Pagu Tersedia: Rp {$formattedAvailable}\n\nAnda menginput Rp {$formattedInput}. Mohon masukkan nominal maksimal Rp {$formattedAvailable}.");
            }

            $diff = $data['allocated_budget'] - $workItem->allocated_budget;
            $data['remaining_budget'] = $workItem->remaining_budget + $diff;

            if ($data['remaining_budget'] < 0) {
                throw new \Exception('Allocated budget cannot be less than already used budget');
            }

            $workItem->update($data);

            DB::connection('tenant')->commit();
            return response()->json(['success' => 'Work Item updated successfully']);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->hasPermission('budgets.delete'), 403);
        ErpWorkItem::findOrFail($id)->delete();

        return response()->json(['success' => 'Work Item deleted successfully']);
    }

    // Ajax helper for Request Form to load WIDs
    public function getWorkItemsByProject(Request $r)
    {
        $wids = ErpWorkItem::with('subProject.budgetParent')
            ->where('remaining_budget', '>', 0)
            ->get()
            ->map(function($w) {
                return [
                    'id' => $w->id,
                    'text' => $w->wid_code . ' - ' . $w->name . ' (Sisa: ' . number_format($w->remaining_budget, 2) . ')'
                ];
            });
        
        return response()->json($wids);
    }
}
