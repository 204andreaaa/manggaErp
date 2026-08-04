<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ErpWarehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ErpWarehouseController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('warehouse.view'), 403);

        $nextCode = $this->generateNextCode();

        return view('erp.warehouses.index', compact('nextCode'));
    }

    public function datatable(Request $request)
    {
        $orderable = [
            'id',
            'warehouse_code',
            'name',
            'address',
            'phone',
            'is_active',
            'updated_at'
        ];

        $draw        = (int) $request->input('draw', 1);
        $start       = (int) $request->input('start', 0);
        $length      = (int) $request->input('length', 10);
        $orderColIdx = (int) $request->input('order.0.column', 0);
        $orderDir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $search      = trim((string) $request->input('search.value', ''));

        $orderCol = $orderable[$orderColIdx] ?? 'id';

        $recordsTotal = ErpWarehouse::count();

        $base = ErpWarehouse::query();

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('warehouse_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderBy($orderCol, $orderDir)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($w, $idx) use ($start) {

                $editBtn = auth()->user()->hasPermission('warehouse.update')
                    ? '<button class="btn btn-sm btn-icon btn-outline-secondary js-edit"
                        data-id="'.$w->id.'"
                        data-warehouse_code="'.e($w->warehouse_code).'"
                        data-name="'.e($w->name).'"
                        data-type="'.e($w->type ?? '').'"
                        data-address="'.e($w->address ?? '').'"
                        data-phone="'.e($w->phone ?? '').'"
                        data-fax="'.e($w->fax ?? '').'"
                        data-last_stock_take_date="'.($w->last_stock_take_date?->format('Y-m-d') ?? '').'"
                        data-work="'.e($w->work ?? '').'"
                        data-is_active="'.($w->is_active ? '1' : '0').'"
                        data-latitude="'.e($w->latitude ?? '').'"
                        data-longitude="'.e($w->longitude ?? '').'"
                        data-capacity="'.e($w->capacity ?? '').'"
                        data-total_value="'.e($w->total_value ?? '0').'"
                        data-remark="'.e($w->remark ?? '').'">
                        <i class="bx bx-edit-alt"></i>
                    </button>'
                    : '';

                $deleteBtn = auth()->user()->hasPermission('warehouse.delete')
                    ? '<button class="btn btn-sm btn-icon btn-outline-danger js-del"
                        data-id="'.$w->id.'">
                        <i class="bx bx-trash"></i>
                    </button>'
                    : '';

                $showUrl = route('erp.warehouses.show', $w);

                return [
                    'rownum'         => $start + $idx + 1,
                    'warehouse_code' => e($w->warehouse_code),
                    'name'           => '<a href="'.$showUrl.'" class="fw-bold text-primary">'.e($w->name).'</a>',
                    'address'        => e($w->address ?? '-'),
                    'phone'          => e($w->phone ?? '-'),
                    'is_active'      => $w->is_active 
                                        ? '<span class="badge bg-label-success">Active</span>' 
                                        : '<span class="badge bg-label-danger">Inactive</span>',
                    'actions'        => '<div class="d-flex gap-1">'.$editBtn.$deleteBtn.'</div>',
                ];
            });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $rows,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('warehouse.create'), 403);

        $code = strtoupper(trim((string) $request->input('warehouse_code', '')));
        if ($code === '') $code = $this->generateNextCode();

        $request->merge([
            'warehouse_code' => $code,
            'is_active' => $request->has('is_active')
        ]);

        $request->validate([
            'warehouse_code' => ['required','max:50','unique:erp_warehouses,warehouse_code'],
            'name'           => ['required','max:150'],
            'type'           => ['nullable','max:100'],
            'address'        => ['nullable'],
            'phone'          => ['nullable','max:50'],
            'fax'            => ['nullable','max:50'],
            'last_stock_take_date' => ['nullable','date'],
            'work'           => ['nullable','max:150'],
            'latitude'       => ['nullable','max:50'],
            'longitude'      => ['nullable','max:50'],
            'capacity'       => ['nullable','integer'],
            'total_value'    => ['nullable','numeric'],
            'remark'         => ['nullable'],
        ]);

        ErpWarehouse::create($request->all());

        return response()->json([
            'success' => 'Warehouse created successfully.'
        ]);
    }

    public function show(ErpWarehouse $warehouse)
    {
        abort_unless(auth()->user()->hasPermission('warehouse.view'), 403);

        return view('erp.warehouses.show', compact('warehouse'));
    }

    public function update(Request $request, ErpWarehouse $warehouse)
    {
        abort_unless(auth()->user()->hasPermission('warehouse.update'), 403);

        $code = strtoupper(trim((string) $request->input('warehouse_code', '')));
        if ($code === '') $code = $warehouse->warehouse_code;

        $request->merge([
            'warehouse_code' => $code,
            'is_active' => $request->has('is_active')
        ]);

        $request->validate([
            'warehouse_code' => [
                'required',
                'max:50',
                Rule::unique('erp_warehouses','warehouse_code')->ignore($warehouse->id)
            ],
            'name'           => ['required','max:150'],
            'type'           => ['nullable','max:100'],
            'address'        => ['nullable'],
            'phone'          => ['nullable','max:50'],
            'fax'            => ['nullable','max:50'],
            'last_stock_take_date' => ['nullable','date'],
            'work'           => ['nullable','max:150'],
            'latitude'       => ['nullable','max:50'],
            'longitude'      => ['nullable','max:50'],
            'capacity'       => ['nullable','integer'],
            'total_value'    => ['nullable','numeric'],
            'remark'         => ['nullable'],
        ]);

        $warehouse->update($request->all());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => 'Warehouse updated successfully.'
            ]);
        }

        return redirect()->route('erp.warehouses.show', $warehouse)->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(ErpWarehouse $warehouse)
    {
        abort_unless(auth()->user()->hasPermission('warehouse.delete'), 403);

        $warehouse->delete();

        return response()->json([
            'success' => 'Warehouse deleted successfully.'
        ]);
    }

    public function nextCode()
    {
        return response()->json([
            'next_code' => $this->generateNextCode()
        ]);
    }

    private function generateNextCode(): string
    {
        $latest = ErpWarehouse::where('warehouse_code', 'like', 'WH%')
            ->orderByRaw('CAST(SUBSTRING(warehouse_code, 3) AS UNSIGNED) DESC')
            ->value('warehouse_code');

        $num = 0;

        if ($latest && preg_match('/^WH(\d+)$/i', $latest, $m)) {
            $num = (int) $m[1];
        }

        return 'WH' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
}
