<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ProductFamily;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductFamilyController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);
        return view('erp.product_families.index');
    }

    public function datatable(Request $r)
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);
        $columns = ['id', 'family_name', 'description', 'updated_at'];

        $draw        = (int) $r->input('draw', 1);
        $start       = (int) $r->input('start', 0);
        $length      = (int) $r->input('length', 10);
        $orderColIdx = (int) $r->input('order.0.column', 0);
        $orderDir    = $r->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderCol    = $columns[$orderColIdx] ?? 'id';
        $search      = trim((string) $r->input('search.value', ''));

        $base = ProductFamily::query();

        $recordsTotal = (clone $base)->count();

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('family_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderBy($orderCol, $orderDir)
            ->skip($start)->take($length)->get()
            ->map(function ($c, $i) use ($start) {
                $editBtn = auth()->user()->hasPermission('products.update')
                    ? '<button class="btn btn-sm btn-warning text-white me-1" onclick="openEdit('.$c->id.',\''.addslashes(e($c->family_name)).'\',\''.addslashes(e($c->description ?? '')).'\')"><i class="bx bx-edit-alt"></i></button>'
                    : '';

                $deleteBtn = auth()->user()->hasPermission('products.delete')
                    ? '<button class="btn btn-sm btn-danger" onclick="deleteItem('.$c->id.')"><i class="bx bx-trash"></i></button>'
                    : '';

                return [
                    'rownum'      => $start + $i + 1,
                    'family_name' => e($c->family_name),
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
            'family_name' => ['required', 'max:150', Rule::unique('product_families', 'family_name')],
            'description' => ['nullable', 'max:255'],
        ]);

        ProductFamily::create($data);

        return response()->json(['success' => 'Product Family created successfully']);
    }

    public function update(Request $r, $id)
    {
        abort_unless(auth()->user()->hasPermission('products.update'), 403);
        $family = ProductFamily::findOrFail($id);

        $data = $r->validate([
            'family_name' => ['required', 'max:150', Rule::unique('product_families', 'family_name')->ignore($family->id)],
            'description' => ['nullable', 'max:255'],
        ]);

        $family->update($data);

        return response()->json(['success' => 'Product Family updated successfully']);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->hasPermission('products.delete'), 403);
        ProductFamily::findOrFail($id)->delete();

        return response()->json(['success' => 'Product Family deleted successfully']);
    }
}
