<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);
        return view('erp.currencies.index');
    }

    public function datatable(Request $r)
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);
        $columns = ['id', 'code', 'name', 'symbol', 'updated_at'];

        $draw        = (int) $r->input('draw', 1);
        $start       = (int) $r->input('start', 0);
        $length      = (int) $r->input('length', 10);
        $orderColIdx = (int) $r->input('order.0.column', 0);
        $orderDir    = $r->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderCol    = $columns[$orderColIdx] ?? 'id';
        $search      = trim((string) $r->input('search.value', ''));

        $base = Currency::query();

        $recordsTotal = (clone $base)->count();

        if ($search !== '') {
            $base->where(function($q) use ($search){
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderBy($orderCol, $orderDir)
            ->skip($start)->take($length)->get()
            ->map(function ($c, $i) use ($start) {
                $editBtn = auth()->user()->hasPermission('products.update')
                    ? '<button class="btn btn-sm btn-warning text-white me-1" onclick="openEdit('.$c->id.',\''.addslashes(e($c->code)).'\',\''.addslashes(e($c->name)).'\',\''.addslashes(e($c->symbol ?? '')).'\')"><i class="bx bx-edit-alt"></i></button>'
                    : '';

                $deleteBtn = auth()->user()->hasPermission('products.delete')
                    ? '<button class="btn btn-sm btn-danger" onclick="deleteItem('.$c->id.')"><i class="bx bx-trash"></i></button>'
                    : '';

                return [
                    'rownum'  => $start + $i + 1,
                    'code'    => e($c->code),
                    'name'    => e($c->name),
                    'symbol'  => e($c->symbol ?? '-'),
                    'actions' => '<div class="text-center">'.$editBtn.$deleteBtn.'</div>',
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
            'code'   => ['required', 'max:10', Rule::unique('currencies', 'code')],
            'name'   => ['required', 'max:100'],
            'symbol' => ['nullable', 'max:10'],
        ]);

        Currency::create($data);

        return response()->json(['success' => 'Currency created successfully']);
    }

    public function update(Request $r, $id)
    {
        abort_unless(auth()->user()->hasPermission('products.update'), 403);
        $currency = Currency::findOrFail($id);

        $data = $r->validate([
            'code'   => ['required', 'max:10', Rule::unique('currencies', 'code')->ignore($currency->id)],
            'name'   => ['required', 'max:100'],
            'symbol' => ['nullable', 'max:10'],
        ]);

        $currency->update($data);

        return response()->json(['success' => 'Currency updated successfully']);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->hasPermission('products.delete'), 403);
        Currency::findOrFail($id)->delete();

        return response()->json(['success' => 'Currency deleted successfully']);
    }
}
