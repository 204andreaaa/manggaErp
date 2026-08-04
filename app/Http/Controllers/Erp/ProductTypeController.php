<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ProductType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductTypeController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);
        return view('erp.product_types.index');
    }

    public function datatable(Request $r)
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);
        $columns = ['id', 'type_name', 'description', 'updated_at'];

        $draw        = (int) $r->input('draw', 1);
        $start       = (int) $r->input('start', 0);
        $length      = (int) $r->input('length', 10);
        $orderColIdx = (int) $r->input('order.0.column', 0);
        $orderDir    = $r->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderCol    = $columns[$orderColIdx] ?? 'id';
        $search      = trim((string) $r->input('search.value', ''));

        $base = ProductType::query();

        $recordsTotal = (clone $base)->count();

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('type_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderBy($orderCol, $orderDir)
            ->skip($start)->take($length)->get()
            ->map(function ($c, $i) use ($start) {
                $editBtn = auth()->user()->hasPermission('products.update')
                    ? '<button class="btn btn-sm btn-warning text-white me-1" onclick="openEdit('.$c->id.',\''.addslashes(e($c->type_name)).'\',\''.addslashes(e($c->description ?? '')).'\')"><i class="bx bx-edit-alt"></i></button>'
                    : '';

                $deleteBtn = auth()->user()->hasPermission('products.delete')
                    ? '<button class="btn btn-sm btn-danger" onclick="deleteItem('.$c->id.')"><i class="bx bx-trash"></i></button>'
                    : '';

                return [
                    'rownum'      => $start + $i + 1,
                    'type_name'   => e($c->type_name),
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
            'type_name'   => ['required', 'max:150', Rule::unique('product_types', 'type_name')],
            'description' => ['nullable', 'max:255'],
        ]);

        ProductType::create($data);

        return response()->json(['success' => 'Product Type created successfully']);
    }

    public function update(Request $r, $id)
    {
        abort_unless(auth()->user()->hasPermission('products.update'), 403);
        $type = ProductType::findOrFail($id);

        $data = $r->validate([
            'type_name'   => ['required', 'max:150', Rule::unique('product_types', 'type_name')->ignore($type->id)],
            'description' => ['nullable', 'max:255'],
        ]);

        $type->update($data);

        return response()->json(['success' => 'Product Type updated successfully']);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->hasPermission('products.delete'), 403);
        ProductType::findOrFail($id)->delete();

        return response()->json(['success' => 'Product Type deleted successfully']);
    }
}
