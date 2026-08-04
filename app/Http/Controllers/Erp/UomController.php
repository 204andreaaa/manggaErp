<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\Uom;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UomController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);
        return view('erp.uoms.index');
    }

    public function datatable(Request $r)
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);
        $columns = ['id', 'uom_name', 'description', 'updated_at'];

        $draw        = (int) $r->input('draw', 1);
        $start       = (int) $r->input('start', 0);
        $length      = (int) $r->input('length', 10);
        $orderColIdx = (int) $r->input('order.0.column', 0);
        $orderDir    = $r->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderCol    = $columns[$orderColIdx] ?? 'id';
        $search      = trim((string) $r->input('search.value', ''));

        $base = Uom::query();

        $recordsTotal = (clone $base)->count();

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('uom_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderBy($orderCol, $orderDir)
            ->skip($start)->take($length)->get()
            ->map(function ($c, $i) use ($start) {
                $editBtn = auth()->user()->hasPermission('products.update')
                    ? '<button class="btn btn-sm btn-warning text-white me-1" onclick="openEdit('.$c->id.',\''.addslashes(e($c->uom_name)).'\',\''.addslashes(e($c->description ?? '')).'\')"><i class="bx bx-edit-alt"></i></button>'
                    : '';

                $deleteBtn = auth()->user()->hasPermission('products.delete')
                    ? '<button class="btn btn-sm btn-danger" onclick="deleteItem('.$c->id.')"><i class="bx bx-trash"></i></button>'
                    : '';

                return [
                    'rownum'      => $start + $i + 1,
                    'uom_name'    => e($c->uom_name),
                    'description' => e($c->description ?? ''),
                    'actions'     => '<div class="text-center">'.$editBtn.$deleteBtn.'</div>',
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
        abort_unless(auth()->user()->hasPermission('products.create'), 403);

        $data = $r->validate([
            'uom_name'    => ['required', 'max:150', Rule::unique('uoms', 'uom_name')],
            'description' => ['nullable', 'max:255'],
        ]);

        Uom::create($data);

        return response()->json(['success' => 'UOM created successfully']);
    }

    public function update(Request $r, $id)
    {
        abort_unless(auth()->user()->hasPermission('products.update'), 403);
        $uom = Uom::findOrFail($id);

        $data = $r->validate([
            'uom_name'    => ['required', 'max:150', Rule::unique('uoms', 'uom_name')->ignore($uom->id)],
            'description' => ['nullable', 'max:255'],
        ]);

        $uom->update($data);

        return response()->json(['success' => 'UOM updated successfully']);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->hasPermission('products.delete'), 403);
        Uom::findOrFail($id)->delete();

        return response()->json(['success' => 'UOM deleted successfully']);
    }
}
